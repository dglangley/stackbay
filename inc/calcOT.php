<?php
	$WEEK_SECS = 60*60*40;
	$DAY_SECS = 60*60*8;
	$DT_SECS = 60*60*12;
	$OT = array();
	function calcOT($techid,$weekStart,$weekEnd,$shiftid=0) {
		global $OT,$WEEK_SECS,$DAY_SECS,$DT_SECS,$WORKDAY_START,$WORKDAY_END;

		if (! isset($OT[$techid])) { $OT[$techid] = array(); }
		if (! isset($OT[$techid][$weekStart])) {
			$OT[$techid][$weekStart] = array('shifts'=>array(),'total'=>0);

			$cumDaySecs = array();//keeps cumulative day-keyed seconds worked
			$cumReg = 0;//cumulative regular pay work seconds
			$cumOT = 0;//cumulative OT pay work seconds, whether over 8hr in a day or over 40hr (40 non-OT hours) in a week
			$cumDT = 0;//cumulative double-time pay work seconds (over 12hrs in a day)

			$shifts = array();
			// Query timesheet and get credited workdate based on the start time - currently, Brian is counting 7pm as the start time for the following work date.
			// Aaron FTW on the CASE statement! 1/5/17
			$query2 = "SELECT *, CASE WHEN SUBSTRING(datetime_in,12,2) >= '".$WORKDAY_START."' ";
			$query2 .= "THEN DATE_SUB(LEFT(datetime_in,10), INTERVAL -1 DAY) ELSE LEFT(datetime_in,10) END workdate ";
			$query2 .= "FROM services_techtimesheet WHERE tech_id = '".$techid."' ";
			// get any shift that either started BEFORE this week's start and ended within this week (ie, sat night to sun morning),
			// OR started within the week (and subsequently ended within the week)
			$query2 .= "AND ( (datetime_in < CAST('".$weekStart."' AS DATETIME) AND datetime_out > CAST('".$weekStart."' AS DATETIME)) ";
			$query2 .= "OR (datetime_in >= CAST('".$weekStart."' AS DATETIME) AND datetime_out <= CAST('".$weekEnd."' AS DATETIME)) ); ";
			$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				// if date start precedes week start, or date end runs over week end, set to those parameters
				if ($r2['datetime_in']<$weekStart) { $r2['datetime_in'] = $weekStart; }
				if ($r2['datetime_out']>$weekEnd) { $r2['datetime_out'] = $weekEnd; }

				$workdate_end = format_date($r2['workdate'],'Y-m-d',array('d'=>1));
				// if the shift ends after the 24-hour period beginning at the workdate, credit the shift
				// to workdate until the end of that window, then split the remaining shift into the following date
				if ($r2['datetime_out']>$workdate_end) {
					$datetime_out = $r2['datetime_out'];//save for use on the split shift
					$r2['datetime_out'] = $workdate_end.' '.$WORKDAY_END.':59:59';
					$shifts[] = $r2;

					// now prep for adding to $shifts[] below
					$r2['workdate'] = $workdate_end;
					$r2['datetime_in'] = $workdate_end.' '.$WORKDAY_START.':00:00';
					$r2['datetime_out'] = $datetime_out;
				}

				$shifts[] = $r2;
			}

			foreach ($shifts as $r2) {
				$datetime_in = $r2['datetime_in'];
				$datetime_out = $r2['datetime_out'];
				if (! isset($OT[$techid][$weekStart]['shifts'][$r2['id']])) { $OT[$techid][$weekStart]['shifts'][$r2['id']] = 0; }
				if (! isset($cumDaySecs[$r2['workdate']])) { $cumDaySecs[$r2['workdate']] = 0; }
//				echo $r2['datetime_in'].' to '.$r2['datetime_out'].' (workdate: '.$r2['workdate'].')<BR>';

				$shiftSecs = calcTimeDiff($datetime_in,$datetime_out);

				$regSecs = $shiftSecs;//by default, the entire shift is regular pay
				$otSecs = 0;
				$dtSecs = 0;

				// accumulate time toward's 40hr/wk max, but only up to a standard 8hr day per day
				if ($shiftSecs>$DAY_SECS) {
					$regSecs = $DAY_SECS;//add up to the 8hrs as regular pay
					if ($shiftSecs>$DT_SECS) {//if over 12hrs, start counting as double time
						$otSecs = $DT_SECS-$DAY_SECS;
						$dtSecs = $shiftSecs-$DT_SECS;
					} else {
						$otSecs = $shiftSecs-$DAY_SECS;//add the overage as OT time
					}
				}

				// have we already surpassed a regular week's pay into overtime? if so, count the entire shift as OT
				if (($regSecs+$cumReg)>$WEEK_SECS) {
					$otSecs += ($regSecs+$cumReg)-$WEEK_SECS;
					$regSecs = $WEEK_SECS-$cumReg;
				}

				if (($regSecs+$cumDaySecs[$r2['workdate']])>$DAY_SECS) {
					$thisOT = ($regSecs+$cumDaySecs[$r2['workdate']])-$DAY_SECS;
					$regSecs -= $thisOT;
					$otSecs += $thisOT;
					$cumDaySecs[$r2['workdate']] = $DAY_SECS;//if spilling over, set to max for a single day, then allot remaining balance as OT
				} else {
					$cumDaySecs[$r2['workdate']] += $regSecs;
				}

				$cumReg += $regSecs;
				$cumOT += $otSecs;
				$cumDT += $dtSecs;

				// capture all OT data into array for easy-lookup and eliminating re-querying later
				$OT[$techid][$weekStart]['shifts'][$r2['id']] += $otSecs;
				$OT[$techid][$weekStart]['total'] += $otSecs;
			}
		}

		if ($shiftid) {
//			echo $weekStart.' to '.$weekEnd.': Work Secs '.toTime($regSecs).' (Shift OT: '.toTime($OT[$techid][$weekStart]['shifts'][$shiftid]).')<BR>';
			if (isset($OT[$techid][$weekStart]['shifts'][$shiftid])) {
				return ($OT[$techid][$weekStart]['shifts'][$shiftid]);
			} else {
				return 0;
			}
		} else {
//			echo $weekStart.' to '.$weekEnd.': Work Secs '.toTime($regSecs).' (Week OT: '.toTime($OT[$techid][$weekStart]['total']).')<BR>';
			return ($OT[$techid][$weekStart]['total']);
		}
	}
?>
