<?php
	if (! isset($debug)) { $debug = 0; }
	function setCostsLog($inventoryid,$eventid,$event_type,$amount,$dt=false) {
		if (! $dt) { $dt = $GLOBALS['now']; }

		$amount = round($amount,4);

		$log_amount = 0;
		$query3 = "SELECT SUM(amount) amount FROM inventory_costs_log ";
		$query3 .= "WHERE inventoryid = '".res($inventoryid)."' AND eventid ";
		if ($eventid) { $query3 .= "= '".res($eventid)."' "; } else { $query3 .= "IS NULL "; }
		$query3 .= "AND event_type = '".res($event_type)."'; ";
		$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
		// insert new log entry only if not previously-entered so that we preserve datetime stamp on cogs
		if (mysqli_num_rows($result3)>0) {
			$r3 = mysqli_fetch_assoc($result3);
			$log_amount = round($r3['amount'],4);
		}
		// update the logged amount if it doesn't match what's already stored for this event, and update datetime stamp
		if ($log_amount<>$amount) {
			$log_diff = $amount-$log_amount;

			$query3 = "REPLACE inventory_costs_log (inventoryid, eventid, event_type, datetime, amount) ";
			$query3 .= "VALUES ('".res($inventoryid)."',";
			if ($eventid) { $query3 .= "'".res($eventid)."',"; } else { $query3 .= "NULL,"; }
			$query3 .= "'".res($event_type)."','".$dt."','".$log_diff."'); ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			if ($GLOBALS['debug']==1) { echo $query3.'<BR>'; }
		}
	}
?>
