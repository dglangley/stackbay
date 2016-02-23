<?php
    include_once 'format_date.php';
	if (! isset($now)) { $now = date("Y-m-d H:i:s"); }
	if (! isset($test)) { $test = 0; }
	if (! isset($SUPER_ADMIN)) { $SUPER_ADMIN = false; }

	function insertMarket($partid,$qty,$companyid,$date,$source,$price=false) {
		if (! $partid OR ! $companyid) { return; }
		if (! $date) { $date = $GLOBALS['now']; }

		// get today's marketid already stored, if any; also try to get previous price for this company
		$marketid = 0;
		$query2 = "SELECT * FROM market WHERE partid = '".res($partid)."' AND companyid = '".res($companyid)."' ";
		$query2 .= "AND (datetime LIKE '".res($date)."%' OR price > 0) ";
		if ($source) { $query2 .= "AND source = '".res($source)."' "; }
		$query2 .= "ORDER BY datetime DESC; ";
//		echo $query2.chr(10);
		$result2 = qdb($query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			if (substr($r2['datetime'],0,10)==substr($date,0,10)) {
				$marketid = $r2['id'];
				// if on the same list, sum qtys (see TSS)
				if (is_numeric($source)) { $qty += $r2['qty']; }
//				if ($price>0) { break; }
			}
			if (! $price) { $price = $r2['price']; }
		}

		$query2 = "REPLACE market (partid, qty, companyid, datetime, price, source";
		if ($marketid) { $query2 .= ", id"; }
		$query2 .= ") VALUES ('".res($partid)."','".res($qty)."','".res($companyid)."',";
		$query2 .= "'".res($date)."',";
		if ($price) { $query2 .= "'".res($price)."',"; } else { $query2 .= "NULL,"; }
		$query2 .= "'".res($source)."'";
		if ($marketid) { $query2 .= ",'".res($marketid)."'"; }
		$query2 .= "); ";
		if ($GLOBALS['SUPER_ADMIN'] AND $GLOBALS['test']) { echo $query2.'<BR>'.chr(10); }
		else { $result2 = qdb($query2) OR die(qe().' '.$query2); }

		return (true);
	}
?>
