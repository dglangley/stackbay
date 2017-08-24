<?php
/***** format_date - Format a given date or date/time to a different date or date/time, with optional date/time modification using $modify

	string format_date(string $datestring [, string $format = "n/j/y", array $modify])

	Returns a string formatted according to the given (or default) format string ($format) using the given string $date. Optionally,
	$modify can be used to modify the given string $date to a past/future date/time.

	$datestring can be a date or date/time in any format ("mm/dd/yy" or "mm/dd/yyyy" or "yy-mm-dd" or "yyyy-mm-dd" or even "dd-mmm-yyyy").

	$modify can be an array of date/time parameters to modify the given string $date to a past/future date/time. Parameters follow
	PHP's date() recognized format parameter strings, with only hours, minutes, months, days and years allowed:
		'h' hours
		'i' minutes
		'm' months
		'd' days
		'y' years
	If modifying to a past/earlier date, use the negative operand in front of the appropriate value (ie, array('d'=>-3) for 3 days ago).
*****/


	// function networkdays($s, $e, $holidays = array()) {
	//     // If the start and end dates are given in the wrong order, flip them.    
	//     if ($s > $e)
	//         return networkdays($e, $s, $holidays);
	
	//     // Find the ISO-8601 day of the week for the two dates.
	//     $sd = date("N", $s);
	//     $ed = date("N", $e);
	
	//     // Find the number of weeks between the dates.
	//     $w = floor(($e - $s)/(86400*7));    # Divide the difference in the two times by seven days to get the number of weeks.
	//     if ($ed >= $sd) { $w--; }        # If the end date falls on the same day of the week or a later day of the week than the start date, subtract a week.
	
	//     // Calculate net working days.
	//     $nwd = max(6 - $sd, 0);    # If the start day is Saturday or Sunday, add zero, otherewise add six minus the weekday number.
	//     $nwd += min($ed, 5);    # If the end day is Saturday or Sunday, add five, otherwise add the weekday number.
	//     $nwd += $w * 5;        # Add five days for each week in between.
	
	//     // Iterate through the array of holidays. For each holiday between the start and end dates that isn't a Saturday or a Sunday, remove one day.
	//     foreach ($holidays as $h) {
	//         $h = strtotime($h);
	//         if ($h > $s && $h < $e && date("N", $h) < 6)
	//             $nwd--;
	//     }
	
	//     return $nwd;
	// }

	$DATE_TIMES = array();
	function format_date($datetime,$date_format='n/j/y',$A='') {
		global $DATE_TIMES;

		$datetime = trim($datetime);
		if (! $datetime) { return false; }

		// how to add time or days or months or years
		if (! $A) { $A = array('h'=>0,'i'=>0,'d'=>0,'y'=>0,'m'=>0); }
		if (! isset($A['h'])) { $A['h'] = 0; }
		if (! isset($A['i'])) { $A['i'] = 0; }
		if (! isset($A['d'])) { $A['d'] = 0; }
		if (! isset($A['y'])) { $A['y'] = 0; }
		if (! isset($A['m'])) { $A['m'] = 0; }
		if (! isset($A['b'])) { $A['b'] = 0; }

	
		// split by space first, in case time is passed in
		$dt = explode(' ',$datetime);
		$date = $dt[0];
		if (preg_match('/[0-9]{4}[-\/][0-9]{1,2}[-\/][0-9]{1,2}/',$date)) {
			$D = preg_split('/[-\/]/',$date);
			$y = $D[0];
			$m = $D[1];
			$d = $D[2];
		} else if (preg_match('/[0-9]{1,2}[-\/][0-9]{1,2}[-\/][0-9]{2,4}/',$date)) {
			$D = preg_split('/[-\/]/',$date);
			$m = $D[0];
			$d = $D[1];
			$y = $D[2];
		} else if (preg_match('/[0-9]{2}-[A-Z]{3}-[0-9]{2,4}/i',$date)) {//07-May-2016 06:00:00
			$months = array('Jan'=>'01','Feb'=>'02','Mar'=>'03','Apr'=>'04','May'=>'05','Jun'=>'06','Jul'=>'07','Aug'=>'08','Sep'=>'09','Oct'=>'10','Nov'=>'11','Dec'=>'12');
			$D = preg_split('/[-\/]/',$date);
			$m = $months[ucfirst(strtolower($D[1]))];
			$d = $D[0];
			$y = $D[2];
		} else {
			return false;
		}

		$t = array(0,0,0);
		if (count($dt)>1 AND strlen($dt[1])>0) {
			$t = explode(':',$dt[1]);
		}

		$hr = $t[0]+$A['h'];
		$mn = $t[1]+$A['i'];
		$sc = '00';
		if (isset($t[2])) { $sc = $t[2]; }
		$mo = $m+$A['m'];
		$dy = $d+$A['d'];
		$yr = $y+$A['y'];
		
		if(isset($A['b'])){
			"NOTHING";
		}
		
		if (isset($DATE_TIMES[$hr.$mn.$sc.$mo.$dy.$yr.$date_format])) {
			return ($DATE_TIMES[$hr.$mn.$sc.$mo.$dy.$yr.$date_format]);
		}

		$new_date = date($date_format,mktime(($t[0]+$A['h']),($t[1]+$A['i']),$sc,($m+$A['m']),($d+$A['d']),($y+$A['y'])));
		$DATE_TIMES[$hr.$mn.$sc.$mo.$dy.$yr.$date_format] = $new_date;
		return ($new_date);
	}
    
    function addBusinessDays($startDate, $businessDays, $holidays = '') {
    	$holidays = array();
    	
	    $date = strtotime($startDate);
	    $i = 0;
	
	    while($i < $businessDays)
	    {
	        //get number of week day (1-7)
	        $day = date('N',$date);
	        //get just Y-m-d date
	        $dateYmd = date("Y-m-d",$date);
	
	        if($day < 6 && !in_array($dateYmd, $holidays)){
	            $i++;
	        }       
	        $date = strtotime($dateYmd . ' +1 day');
	    }       
	
	    return date('m/d/Y',$date);
	
	}


	function format_time($s,$hr_format='g') {
		global $U;

		// if 'dec' then return as the decimal numeric value of the time, rather than vice versa
		if ($hr_format=='dec') {
			$h = (int)substr($s,0,strpos($s,':'));
			$m = (int)substr($s,(strpos($s,':')+1),2);
			$n = $h+($m/60);

			return ($n);
		}

		$h = floor($s);
		$a = 'a';
		if ($h>=12) { $a = 'p'; }
		if (strstr($hr_format,'G') OR strstr($hr_format,'H')) {
			$a = '';
			if (strstr($hr_format,'H') AND strlen($h)==1) { $h = '0'.$h; }
		} else if ($h>12) {
			$h = floor($s%12);
		}

		// multiply by 10 to get remainder (I couldn't get decimals to work with %)
/*
		$rem = (10*$s)%(10*floor($s));
		if ($rem==5) { $m = 30; }
		else { $m = '00'; }
*/

		// new method to implement frequencies of :15 and :30, 10-26-13
		$rem = ((100*$s)%(100*floor($s)))/100;
		$m = 60*$rem;
		if ($m==0) { $m = '00'; }

		return ($h.':'.$m.$a);
	}

	if (! isset($today)) { $today = date("Y-m-d"); }
	$summary_yesterday = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-1));
	$summary_lastweek = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-7));
	$summary_lastyear = format_date(date("Y-m-d"),'Y-m-01',array('m'=>-11));
	$summary_tomorrow = format_date(date("Y-m-d"),'Y-m-d',array('d'=>1));
	$summary_nextweek = format_date(date("Y-m-d"),'Y-m-d',array('d'=>7));
	$this_month = substr($today,0,7).'-01';
	$summary_past = format_date($today,'Y-m-01',array('m'=>-1));

	function summarize_date($date,$truncate_day=true) {
		global $today,$summary_yesterday,$summary_lastweek,$summary_lastyear,$this_month,$summary_past,$summary_tomorrow,$summary_nextweek;
		$date = substr($date,0,10);

		if (strlen($date)==7) { $date .= '-01'; }

		if ($date==$today) { $date = 'Today'; }
		else if ($date==$summary_yesterday) { $date = 'Yesterday'; }
		else if ($date==$summary_tomorrow) { $date = 'Tomorrow' ; }
		else if ($date>$summary_lastweek && $date < $summary_yesterday) { $date = format_date($date,'D'); }
		else if ($date > $summary_tomorrow && $date < $summary_nextweek) { $date = format_date($date,'D, M j'); }
		else if ($date > $summary_nextweek){$date = format_date($date, "n/j/y");}
		else if ($date>=$summary_past OR $truncate_day===false) {
//			$date = format_date($date,'M j');
			// because $summary_past can change and is a floating point in time based on its particular usage,
			// we still want to format according to what point in time it's currently set at; if older than
			// $summary_lastyear, add the year for clarification
			if ($date<$summary_lastyear) {
				$date = format_date($date,"M j 'y");
			} else {
				$date = format_date($date,'M j');
			}
		} else if ($date>=$summary_lastyear) {
			// because $summary_past can change and is a floating point in time based on its particular usage,
			// we still want to format according to what point in time it's currently set at; if older than
			// $summary_lastyear, add the year for clarification
			if ($summary_past<$summary_lastyear) {
				$date = format_date($date,"M 'y");
			} else {
				$date = format_date($date,'M');
			}
		}
		else { $date = format_date($date,"M 'y"); }

		return ($date);
	}

	function format_dateTitle($order_date,$dated_qty) {
		global $today,$yesterday;

/*
		if ($order_date==$today) { $date = 'Today'; }
		else if ($order_date==$yesterday) { $date = 'Yesterday'; }
		else if ($order_date>$lastWeek) { $date = format_date($order_date,'D'); }
		else if ($order_date>=$lastYear) { $date = format_date($order_date,'M j'); }
		else { $date = format_date($order_date,'M j, y'); }
*/
		$date = summarize_date($order_date);
		// highlight any records in the past week
		$dateSty = '';
		if ($order_date>$GLOBALS['lastWeek']) { $dateSty = ' style="font-weight:bold"'; }

		// $dtitle = '<div class="date-group"><a href="javascript:void(0);" class="modal-results" data-target="marketModal"'.$dateSty.'>'.$date.': '.
		// 	'qty '.$dated_qty.' <i class="fa fa-list-alt"></i></a></div>';

		// Changed to remove unused modal currently
		$dtitle = '<div class="date-group"><a href="javascript:void(0);"'.$dateSty.'>'.$date.': '.
			'qty '.$dated_qty.' <i class="fa fa-list-alt"></i></a></div>';
		return ($dtitle);
	}
