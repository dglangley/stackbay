<?php
	$QTYS = array();
	function getQty($partid=0) {
		global $QTYS;

		$qty = 0;

		if (! $partid OR ! is_numeric($partid)) { return ($qty); }

		if (isset($QTYS[$partid])) { return ($QTYS[$partid]); }

		$QTYS[$partid] = 0;
		$query = "SELECT SUM(qty) qty FROM inventory WHERE partid = '".$partid."' ";
		$query .= "AND conditionid >= 0 AND (status = 'received' OR status = 'manifest'); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)==0) { return ($qty); }
		$r = mysqli_fetch_assoc($result);
		$qty = $r['qty'];
		$QTYS[$partid] = $qty;

		return ($qty);
	}
?>
