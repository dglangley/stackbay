<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/calcTime.php';

	if (! isset($WORKDAY_START)) { $WORKDAY_START = 0; }
	if (! isset($WORKDAY_END)) { $WORKDAY_END = 23; }

	if ($WORKDAY_START==0) { $WORKDAY_END = 23; }
	else { $WORKDAY_END = $WORKDAY_START-1; }

	$LABOR_COST = 1.13;//change this to include certain amount of payroll taxes or other associated labor costs

	function getTimesheet($techid) {
		global $WORKDAY_START,$WORKDAY_END,$LABOR_COST,$expenses,
			$debugSecsReg,$debugSecsOT,$debugSecsPd,$debugRegPayTotal,$debugOTPayTotal,$debugPdPayTotal;

		$WORKDAY_START = str_pad($WORKDAY_START,2,0,STR_PAD_LEFT);
		$WORKDAY_END = str_pad($WORKDAY_END,2,0,STR_PAD_LEFT);

		$cumLabor = 0;//cumulative labor amount
		$cumSecs = 0;//cumulative seconds worked

		// total seconds worked in regular shift hours
		$regSecs = 0;
		
		// gathers total seconds worked overtime
		$OTSecs = 0;

		$query = "SELECT *, DATE_SUB(LEFT(clockin,10), INTERVAL DAYOFWEEK(clockin)-1 DAY) start_day, ";
		//$query .= "DATE_SUB(LEFT(datetime_in,10), INTERVAL DAYOFWEEK(datetime_in)-7 DAY) end_day ";
		$query .= "DATE_SUB(LEFT(clockin,10), INTERVAL DAYOFWEEK(clockin)-8 DAY) end_day ";
		$query .= "FROM timesheets WHERE userid = '".$techid."' ";
		$query .= "; ";
		$result = qdb($query) OR die(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$weekStart = format_date($r['clockin'],'Y-m-d').' '.$WORKDAY_START.':00:00';
			$weekEnd = format_date($r['clockout'],'Y-m-d').' '.$WORKDAY_END.':59:59';
			$shiftid = $r['id'];

			$secsDiff = calcTimeDiff($r['clockin'],$r['clockout']);

			// OT seconds of this shift within the scope of a week's work
			$OTSecs = calcOT($techid,$weekStart,$weekEnd,$r['id']);
			$debugSecsReg += $secsDiff-$OTSecs;
			$debugSecsOT += $OTSecs;

			$cumSecs += $secsDiff;

			$tech_rate = getUserRate($techid);
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
		}

		return (array($cumLabor,$cumSecs));
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
?>
