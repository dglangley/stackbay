<?php
	if (! isset($WORKDAY_START)) { $WORKDAY_START = 19; }
	if (! isset($WORKDAY_END)) { $WORKDAY_END = 23; }

	if ($WORKDAY_START==0) { $WORKDAY_END = 23; }
	else { $WORKDAY_END = $WORKDAY_START-1; }

	include_once $_SERVER["ROOT_DIR"].'/inc/calcOT.php';

	$LABOR_COST = 1.13;//change this to include certain amount of payroll taxes or other associated labor costs

	function getTimesheet($techid,$taskid=0,$task_label='',$return_type='array') {//,$start='',$end='') {
		global $WORKDAY_START,$WORKDAY_END,$LABOR_COST,
			$debugSecsReg,$debugSecsOT,$debugSecsPd,$debugRegPayTotal,$debugOTPayTotal,$debugPdPayTotal;

		$WORKDAY_START = str_pad($WORKDAY_START,2,0,STR_PAD_LEFT);
		$WORKDAY_END = str_pad($WORKDAY_END,2,0,STR_PAD_LEFT);

		$cumLabor = 0;//cumulative labor amount
		$cumSecs = 0;//cumulative seconds worked
		$cumTodaySecs = 0;//cumulative seconds worked in each day
//		$cumCarrySecs = 0;//carry over seconds

		$secsDiff = 0;

		$clockin_date = '';
		$clockout_date = '';

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
//		if ($start) { $query .= "AND clockin >= '".res($start)."' "; }
//		if ($end) { $query .= "AND clockout <= '".res($end)."' "; }
		$query .= "ORDER BY clockin ASC; ";
//		$query .= "; ";

		$result = qdb($query) OR die(qe().' '.$query);

		// Generate an array to handle each timesheet calculation
		$timesheetid_data = array();

		$shifts = array();
		while ($r = mysqli_fetch_assoc($result)) {
			$clockin_date = substr($r['clockin'],0,10);
			$clockout_date = substr($r['clockout'],0,10);

			while ($clockout_date AND $clockin_date<>$clockout_date) {
				// first shift ends at 23:59:59 on the clockin date
				$first = $r;
				$first['clockout'] = $clockin_date.' 23:59:59';
				$shifts[] = $first;

				// advance to next day on calendar
				$clockin_date = format_date($r['clockin'],'Y-m-d',array('d'=>1));

				// next shift starts at midnight on the clockout date
				$r['clockin'] = $clockin_date.' 00:00:00';
			}

			$shifts[] = $r;
		}

		$clockin_date = '';
		foreach ($shifts as $r) {
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
			if ($clockin_date<>substr($r['clockin'],0,10)) {
				$clockin_date = substr($r['clockin'],0,10);

				$cumTodaySecs = 0;
			}

			$secsDiff = calcTimeDiff($r['clockin'],$r['clockout']);

			// OT seconds of this shift within the scope of a week's work
			if (isset($_REQUEST['old'])) {
				$calc = calcOT($techid,$weekStart,$weekEnd,$r['id']);
			} else {
				$calc = calcOT($techid,$weekStart,$weekEnd,$r['id'],$clockin_date);
			}
			// echo format_date($r['clockin']).' '.format_date($r['clockout']).' = '.$secsDiff.' (OT '.$calc[0].', DT '.$calc[1].')<BR>';

			$OTSecs = $calc[0];
			$DTSecs = $calc[1];

			$debugSecsReg += $secsDiff-$OTSecs;
			$debugSecsOT += $OTSecs;

			$cumSecs += $secsDiff;
			$cumTodaySecs += $secsDiff;

			$tech_rate = $r['rate'];
			$rate_in_secs = $tech_rate/3600;

			$regSecs = ($secsDiff-($OTSecs+$DTSecs));

//			echo $regSecs.' ('.$secsDiff.'-('.$OTSecs.' OT + '.$DTSecs.' DT))<BR>';
			$stdPay = ($rate_in_secs*$regSecs);
			$debugRegPayTotal += $stdPay;
			$otPay = ($rate_in_secs*$OTSecs)*1.5;
			$dtPay = ($rate_in_secs*$DTSecs)*2.0;
			$debugOTPayTotal += $otPay;
			$laborCost = ($stdPay+$otPay+$dtPay)*$LABOR_COST;
			$cumLabor += $laborCost;

			if (! isset($r['no_perdiem']) OR ! $r['no_perdiem']) {
				$debugSecsPd += $regSecs;
				// no perdiem for overtime
				$pdPay = 0;//(($r['tech_perdiem']/3600)*$regSecs);
				if (isset($r['tech_perdiem']) AND $r['tech_perdiem']) { $pdPay = (($r['tech_perdiem']/3600)*$regSecs); }

				$debugPdPayTotal += $pdPay;
				$cumLabor += $pdPay;
			}

			if (! isset($timesheetid_data[$r['id']])) {
				$timesheetid_data[$r['id']]['secsDiff'] = 0;
				$timesheetid_data[$r['id']]['CUM_secs'] = 0;
				$timesheetid_data[$r['id']]['REG_secs'] = 0;
				$timesheetid_data[$r['id']]['REG_pay'] = 0;
				$timesheetid_data[$r['id']]['OT_secs'] = 0;
				$timesheetid_data[$r['id']]['OT_pay'] = 0;
				$timesheetid_data[$r['id']]['DT_secs'] = 0;
				$timesheetid_data[$r['id']]['DT_pay'] = 0;
				$timesheetid_data[$r['id']]['totalPay'] = 0;
				$timesheetid_data[$r['id']]['laborCost'] = 0;
			}
			$timesheetid_data[$r['id']]['secsDiff'] += $secsDiff;
			$timesheetid_data[$r['id']]['CUM_secs'] += $cumTodaySecs;
			$timesheetid_data[$r['id']]['REG_secs'] += $regSecs;
			$timesheetid_data[$r['id']]['REG_pay'] += $stdPay;
			$timesheetid_data[$r['id']]['OT_secs'] += $OTSecs;
			$timesheetid_data[$r['id']]['OT_pay'] += $otPay;
			$timesheetid_data[$r['id']]['DT_secs'] += $DTSecs;
			$timesheetid_data[$r['id']]['DT_pay'] += $dtPay;
			$timesheetid_data[$r['id']]['totalPay'] += $stdPay + $otPay + $dtPay;
			$timesheetid_data[$r['id']]['laborCost'] += $laborCost;

			// stamp date-specific
			$timesheetid_data[$r['id']][$clockin_date]['secsDiff'] = $secsDiff;
			$timesheetid_data[$r['id']][$clockin_date]['CUM_secs'] = $cumTodaySecs;
			$timesheetid_data[$r['id']][$clockin_date]['REG_secs'] = $regSecs;
			$timesheetid_data[$r['id']][$clockin_date]['REG_pay'] = $stdPay;
			$timesheetid_data[$r['id']][$clockin_date]['OT_secs'] = $OTSecs;
			$timesheetid_data[$r['id']][$clockin_date]['OT_pay'] = $otPay;
			$timesheetid_data[$r['id']][$clockin_date]['DT_secs'] = $DTSecs;
			$timesheetid_data[$r['id']][$clockin_date]['DT_pay'] = $dtPay;
			$timesheetid_data[$r['id']][$clockin_date]['totalPay'] = $stdPay + $otPay + $dtPay;
			$timesheetid_data[$r['id']][$clockin_date]['laborCost'] = $laborCost;
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
