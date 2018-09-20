<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$DEBUG = 0;

	$months = array();
	$query = "SELECT id, qty, date_created datetime FROM inventory WHERE date_created >= '0000-00-00 00:00:00' ";
	$query .= "AND (status = 'received' OR (status = 'shipped' AND sales_item_id IS NOT NULL)) ";
	$query .= "AND serial_no IS NOT NULL AND qty = '1' ";
//	$query .= "LIMIT 0,500 ";
	$query .= "; ";
	$result = qedb($query);
	while ($r = qrow($result)) {
		$id = $r['id'];
		$date = substr($r['datetime'],0,10);
		$mo = str_replace('-','',substr($r['datetime'],0,7));

		$sale = $now;//assume not sold so today's date counts as shelflife span
		$query2 = "SELECT MIN(date_changed) datetime FROM inventory_history ";
		$query2 .= "WHERE invid = '".$id."' AND date_changed >= '".$r['datetime']."' ";
		$query2 .= "AND ((field_changed = 'sales_item_id' AND value > 0) OR ((value = 'shipped' OR value = 'manifest') AND field_changed = 'status' AND changed_from = 'received')); ";
		$result2 = qedb($query2);
		if (qnum($result2)>0) {
			$r2 = qrow($result2);
			$sale = $r2['datetime'];
		}
		$mo2 = str_replace('-','',substr($sale,0,7));

		if ($DEBUG) { echo $id.': '.$date.' '.substr($sale,0,10).' '; }

/*
		$date1 = new DateTime(substr($date,0,10));
		$date2 = new DateTime(substr($sale,0,10));
		$days = $date2->diff($date1)->format("%a");

//echo '= '.$days.' days';
*/
		if ($DEBUG) { echo '<BR>'; }
		for ($m=$mo; $m<=$mo2; $m++) {
			$months[$m] += $r['qty'];
			if ($DEBUG) { echo ' &nbsp; '.$m.'<BR>'; }

			// after dec month, skip to next year
			if (substr($m,4,2)==12) {
				$m += 88;
			}
		}
		if ($DEBUG) { echo '<BR>'; }
	}

	print "<pre>".print_r($months,true)."</pre>";
?>
