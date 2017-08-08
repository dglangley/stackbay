<?php
//	include_once 'getPipeQty.php';

	function getQty($partid=0) {
		global $PIPEIDS_STR,$PIPE_NOTES,$NOTES;//won't need this after migration, I believe

		$qty = 0;

		if (! $partid OR ! is_numeric($partid)) { return ($qty); }

		$query = "SELECT SUM(qty) qty FROM inventory WHERE partid = '".$partid."' ";
		$query .= "AND conditionid >= 0 AND status <> 'scrapped' AND status <> 'in repair'; ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)==0) { return ($qty); }
		$r = mysqli_fetch_assoc($result);
		$qty = $r['qty'];

		return ($qty);
	}
?>
