<?php
	function getCostsLog($inventoryid,$eventid,$event_type) {
		$amount = false;
		$query = "SELECT SUM(amount) amount FROM inventory_costs_log ";
		$query .= "WHERE inventoryid = '".res($inventoryid)."' AND eventid = '".res($eventid)."' AND event_type = '".res($event_type)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return ($amount); }
		$r = mysqli_fetch_assoc($result);
		$amount = $r['amount'];
		return ($amount);
	}
?>
