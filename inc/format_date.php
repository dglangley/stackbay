<?php
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
		if (isset($DATE_TIMES[$hr.$mn.$sc.$mo.$dy.$yr.$date_format])) {
			return ($DATE_TIMES[$hr.$mn.$sc.$mo.$dy.$yr.$date_format]);
		}

		$new_date = date($date_format,mktime(($t[0]+$A['h']),($t[1]+$A['i']),$sc,($m+$A['m']),($d+$A['d']),($y+$A['y'])));
		$DATE_TIMES[$hr.$mn.$sc.$mo.$dy.$yr.$date_format] = $new_date;
		return ($new_date);
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
	$this_month = substr($today,0,7).'-01';
	$summary_past = format_date($today,'Y-m-01',array('m'=>-1));
	function summarize_date($date) {
		global $today,$summary_yesterday,$summary_lastweek,$summary_lastyear,$this_month,$summary_past;
		$date = substr($date,0,10);

		if (strlen($date)==7) { $date .= '-01'; }

		if ($date==$today) { $date = 'Today'; }
		else if ($date==$summary_yesterday) { $date = 'Yesterday'; }
		else if ($date>$summary_lastweek) { $date = format_date($date,'D'); }
		else if ($date>=$summary_past) { $date = format_date($date,'M j'); }
		else if ($date>=$summary_lastyear) { $date = format_date($date,'M'); }
		else { $date = format_date($date,"M 'y"); }

		return ($date);
	}
?>
