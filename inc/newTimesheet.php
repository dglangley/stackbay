<?php
	if (! isset($WORKDAY_START)) { $WORKDAY_START = 19; }
	if (! isset($WORKDAY_END)) { $WORKDAY_END = 23; }

	if ($WORKDAY_START==0) { $WORKDAY_END = 23; }
	else { $WORKDAY_END = $WORKDAY_START-1; }

	include_once $_SERVER["ROOT_DIR"].'/inc/calcTime.php';

	$LABOR_COST = 1.13;//change this to include certain amount of payroll taxes or other associated labor costs

	function getTimesheet($techid,$taskid=0,$task_label='',$return_type='array') {
		global $WORKDAY_START,$WORKDAY_END,$LABOR_COST,$expenses,
			$debugSecsReg,$debugSecsOT,$debugSecsPd,$debugRegPayTotal,$debugOTPayTotal,$debugPdPayTotal;

		$WORKDAY_START = str_pad($WORKDAY_START,2,0,STR_PAD_LEFT);
		$WORKDAY_END = str_pad($WORKDAY_END,2,0,STR_PAD_LEFT);

		$cumLabor = 0;//cumulative labor amount
		$cumSecs = 0;//cumulative seconds worked
		$cumTodaySecs = array();//cumulative seconds worked in each day

		// total seconds worked in regular shift hours
		$regSecs = 0;
		
		// gathers total seconds worked overtime
		$OTSecs = 0;

		$query = "SELECT *, DATE_SUB(LEFT(clockin,10), INTERVAL DAYOFWEEK(clockin)-1 DAY) start_day, ";
		$query .= "DATE_SUB(LEFT(clockin,10), INTERVAL DAYOFWEEK(clockin)-8 DAY) end_day ";
		$query .= "FROM timesheets WHERE userid = '".$techid."' ";
		if ($taskid) { $query .= "AND taskid = '".res($taskid)."' AND task_label = '".res($task_label)."' "; }
		else { $query .="AND clockin >= '2016-01-01 00:00:00' "; }
//$query .= "AND clockin >= '2018-02-23 19:00:00' ";
		$query .= "; ";

		$result = qdb($query) OR die(qe().' '.$query);

		// Generate an array to handle each timesheet calculation
		$timesheetid_data = array();

		while ($r = mysqli_fetch_assoc($result)) {
			// found a bug in query above where a shift on the date of the end of the week / start of the week in cases
			// where a mid-day hour is the start of the next week (i.e., 7pm), the shift would get assigned to the next
			// week and be missed on calculations; this sets the week start back 7 days to address that bug, because
			// such a shift on the day of the next week, but clocked in prior to the end of the week belongs to that prev week
			if ($r['clockin']<$r['start_day'].' '.$WORKDAY_START.':00:00') {
				$weekStart = format_date($r['clockin'],'Y-m-d',array('d'=>-7));//.' '.$WORKDAY_START.':00:00';
			} else {
				$weekStart = format_date($r['start_day'],'Y-m-d').' '.$WORKDAY_START.':00:00';
			}
			$weekEnd = format_date($r['end_day'],'Y-m-d').' '.$WORKDAY_END.':59:59';
			$shiftid = $r['id'];

			$secsDiff = calcTimeDiff($r['clockin'],$r['clockout']);

			// OT seconds of this shift within the scope of a week's work
			$calc = calcOT($techid,$weekStart,$weekEnd,$r['id']);
//			echo $r['clockin'].' '.$r['clockout'].' = '.$secsDiff.' (OT '.$calc[0].', DT '.$calc[1].')<BR>';

			$OTSecs = $calc[0];
			$DTSecs = $calc[1];

			$debugSecsReg += $secsDiff-$OTSecs;
			$debugSecsOT += $OTSecs;

			$cumSecs += $secsDiff;
			$cumTodaySecs[$r['start_day']] += $secsDiff;

			$tech_rate = $r['rate'];
			$rate_in_secs = $tech_rate/3600;

			$regSecs = ($secsDiff-($OTSecs+$DTSecs));
//			echo $regSecs.' ('.$secsDiff.'-'.$OTSecs.')<BR>';
			$stdPay = ($rate_in_secs*$regSecs);
			$debugRegPayTotal += $stdPay;
			$otPay = ($rate_in_secs*$OTSecs)*1.5;
			$dtPay = ($rate_in_secs*$DTSecs)*2.0;
			$debugOTPayTotal += $otPay;
			$laborCost = ($stdPay+$otPay+$dtPay)*$LABOR_COST;
			$cumLabor += $laborCost;

			if (! $r['no_perdiem']) {
				$debugSecsPd += $regSecs;
				// no perdiem for overtime
				$pdPay = (($r['tech_perdiem']/3600)*$regSecs);
				$debugPdPayTotal += $pdPay;
				$cumLabor += $pdPay;
			}

			$timesheetid_data[$r['id']]['secsDiff'] = $secsDiff;
			$timesheetid_data[$r['id']]['CUM_secs'] = $cumTodaySecs[$r['start_day']];
			$timesheetid_data[$r['id']]['REG_secs'] = $regSecs;
			$timesheetid_data[$r['id']]['REG_pay'] = $stdPay;
			$timesheetid_data[$r['id']]['OT_secs'] = $OTSecs;
			$timesheetid_data[$r['id']]['OT_pay'] = $otPay;
			$timesheetid_data[$r['id']]['DT_secs'] = $DTSecs;
			$timesheetid_data[$r['id']]['DT_pay'] = $dtPay;
			$timesheetid_data[$r['id']]['totalPay'] = $stdPay + $otPay + $dtPay;
			$timesheetid_data[$r['id']]['laborCost'] = $laborCost;
		}

		if ($return_type=='array') {
			return ($timesheetid_data);
		} else {
			return (array($cumLabor,$cumSecs));
		}
	}

	function calcTimeDiff($datetime_start,$datetime_end) {
		$timeStart = new DateTime($datetime_start);
		$timeEnd = new DateTime($datetime_end);
		$diff = $timeStart->diff($timeEnd);

		$days = $diff->format("%D");
		$hours = $diff->format("%H");
		$mins = $diff->format("%I");
		$secs = $diff->format("%S");
		$diff_in_secs = ($days*86400)+($hours*3600)+($mins*60)+$secs;

		return ($diff_in_secs);
	}

	function getUserRate($userid) {
		$rate = 0;

		$query = "SELECT hourly_rate FROM users WHERE id = ".res($userid).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$rate = $r['hourly_rate'];
		}

		return $rate;
	}

	function toTime($secs,$include_secs=true) {
		// given $secs seconds, what is the time g:i:s format?
		$hours = floor($secs/3600);

		// what are the remainder of seconds after taking out hours above?
		$secs -= ($hours*3600);

		$mins = floor($secs/60);

		$secs -= ($mins*60);

		if ($include_secs) {
			return (str_pad($hours,2,0,STR_PAD_LEFT).':'.str_pad($mins,2,0,STR_PAD_LEFT).':'.str_pad($secs,2,0,STR_PAD_LEFT));
		} else {
			return (str_pad($hours,2,0,STR_PAD_LEFT).':'.str_pad($mins,2,0,STR_PAD_LEFT));
		}
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
?>
