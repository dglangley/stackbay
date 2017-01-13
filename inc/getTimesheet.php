<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/calcOT.php';

	if (! isset($WORKDAY_START)) { $WORKDAY_START = 0; }
	if (! isset($WORKDAY_END)) { $WORKDAY_END = 23; }

	if ($WORKDAY_START==0) { $WORKDAY_END = 23; }
	else { $WORKDAY_END = $WORKDAY_START-1; }

	$LABOR_COST = 1.13;//change this to include certain amount of payroll taxes or other associated labor costs

	function getTimesheet($techid,$job='') {
		global $WORKDAY_START,$WORKDAY_END,$LABOR_COST,$expenses,
			$debugSecsReg,$debugSecsOT,$debugSecsPd,$debugRegPayTotal,$debugOTPayTotal,$debugPdPayTotal;

		$WORKDAY_START = str_pad($WORKDAY_START,2,0,STR_PAD_LEFT);
		$WORKDAY_END = str_pad($WORKDAY_END,2,0,STR_PAD_LEFT);

		$jobid = 0;
		$mileage_rate = .575;
		if ($job) {
			$jobid = $job['id'];
			$mileage_rate = $job['mileage_rate'];
		}

		$cumLabor = 0;//cumulative labor amount
		$cumSecs = 0;//cumulative seconds worked
		// total seconds worked in regular shift hours
		$regSecs = 0;
		// gathers total seconds worked overtime
		$OTSecs = 0;

		$query = "SELECT *, DATE_SUB(LEFT(datetime_in,10), INTERVAL DAYOFWEEK(datetime_in)-1 DAY) start_day, ";
		//$query .= "DATE_SUB(LEFT(datetime_in,10), INTERVAL DAYOFWEEK(datetime_in)-7 DAY) end_day ";
		$query .= "DATE_SUB(LEFT(datetime_in,10), INTERVAL DAYOFWEEK(datetime_in)-8 DAY) end_day ";
		$query .= "FROM services_techtimesheet WHERE tech_id = '".$techid."' ";
		if ($jobid) { $query .= "AND job_id = '".$jobid."' "; }
		$query .= "; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$weekStart = $r['start_day'].' '.$WORKDAY_START.':00:00';
			$weekEnd = $r['end_day'].' '.$WORKDAY_END.':59:59';
			$shiftid = $r['id'];

			$secsDiff = calcTimeDiff($r['datetime_in'],$r['datetime_out']);

			// OT seconds of this shift within the scope of a week's work
			$OTSecs = calcOT($techid,$weekStart,$weekEnd,$r['id']);
			$debugSecsReg += $secsDiff-$OTSecs;
			$debugSecsOT += $OTSecs;

			$cumSecs += $secsDiff;

			$tech_rate = $r['tech_rate'];
			$rate_in_secs = $tech_rate/3600;

			$regSecs = ($secsDiff-$OTSecs);
			$stdPay = ($rate_in_secs*$regSecs);
			$debugRegPayTotal += $stdPay;
			$otPay = ($rate_in_secs*$OTSecs)*1.5;
			$debugOTPayTotal += $otPay;
			$cumLabor += ($stdPay+$otPay)*$LABOR_COST;
			if (! $r['no_perdiem']) {
				$debugSecsPd += $regSecs;
				// no perdiem for overtime
				$pdPay = (($r['tech_perdiem']/3600)*$regSecs);
				$debugPdPayTotal += $pdPay;
				$cumLabor += $pdPay;
			}

			// add mileage to expenses (Brian had it under Tasks, which I now call Labor)
			if ($r['mileage']>0) {
				$expenses[] = array(
					'timestamp'=>$r['datetime_in'],
					'entered_by_id'=>$techid,
					'job_id'=>$jobid,
					'tech_id'=>$techid,
					'label'=>'Mileage: '.$r['mileage'].'<BR/>'.$r['notes'],
					'date'=>substr($r['datetime_in'],0,10),
					'amount'=>($r['mileage']*$mileage_rate),
					'file'=>'',
					'vendor'=>'',
					'type'=>'<i class="fa fa-car"></i>',
				);
			}
		}

		return (array($cumLabor,$cumSecs));
	}

	function toTime($secs) {
		// given $secs seconds, what is the time g:i:s format?
		$hours = floor($secs/3600);

		// what are the remainder of seconds after taking out hours above?
		$secs -= ($hours*3600);

		$mins = floor($secs/60);

		$secs -= ($mins*60);

		return (str_pad($hours,2,0,STR_PAD_LEFT).':'.str_pad($mins,2,0,STR_PAD_LEFT).':'.str_pad($secs,2,0,STR_PAD_LEFT));
	}

	function timeToStr($time) {
		$t = explode(':',$time);
		$hours = $t[0];
		$mins = $t[1];
		if (! $mins) { $mins = 0; }
		$secs = $t[2];
		if (! $secs) { $secs = 0; }

		$days = floor($hours/24);
		$hours -= ($days*24);

		$str = '';
		if ($days>0) { $str .= $days.'d, '; }
		if ($hours>0 OR $str) { $str .= (int)$hours.'h, '; }
		if ($mins>0 OR $str) { $str .= (int)$mins.'m, '; }
		if ($secs>0 OR $str) { $str .= (int)$secs.'s'; }

		return ($str);
	}

	function calcTimeDiff($datetime_start,$datetime_end) {
		$timeStart = new DateTime($datetime_start);
		$timeEnd = new DateTime($datetime_end);
		$diff = $timeStart->diff($timeEnd);

		$hours = $diff->format("%H");
		$mins = $diff->format("%I");
		$secs = $diff->format("%S");
		$diff_in_secs = ($hours*3600)+($mins*60)+$secs;

		return ($diff_in_secs);
	}
?>
