<?php
	$QTYS = array();
	function getQty($partid=0) {
		global $QTYS;

		$qty = false;

		if (! $partid OR ! is_numeric($partid)) { return ($qty); }

		if (isset($QTYS[$partid])) { return ($QTYS[$partid]); }

		// initialize global
		$QTYS[$partid] = $qty;

		$query = "SELECT qty, status, conditionid FROM inventory ";
		$query .= "WHERE partid = '".$partid."'; ";//AND conditionid >= 0 AND (status = 'received' OR status = 'manifest'); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)==0) { return ($qty); }
		$qty = 0;//we now know at least one record exists in inventory, so the lowest-qty scenario is '0' now, indicating zero-but-previous-stock
		while ($r = mysqli_fetch_assoc($result)) {
			if ($r['conditionid']<0) { continue; }// no bad stock
			if ($r['status']<>'received' AND $r['status']<>'manifest') { continue; }//only stock on the shelf or ready to ship (manifest)
			$qty += $r['qty'];
		}
		$QTYS[$partid] = $qty;

		return ($qty);
	}
?>
