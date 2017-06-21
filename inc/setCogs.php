<?php
	function setCogs($inventoryid=0,$sales_item_id=0,$cogs_avg=0,$cogs_actual=0) {
		$q_invid = 'NULL';
		if ($inventoryid AND is_numeric($inventoryid)) { $q_invid = "'".$inventoryid."'"; }
		$q_si = 'NULL';
		if ($sales_item_id AND is_numeric($sales_item_id)) { $q_si = "'".$sales_item_id."'"; }

		$query = "REPLACE sales_cogs (inventoryid, sales_item_id";
		if ($cogs_avg) { $query .= ", cogs_avg"; }
		if ($cogs_actual) { $query .= ", cogs_actual"; }
		$query .= ") VALUES ($q_invid,$q_si";
		if ($cogs_avg) { $query .= ",'".res($cogs_avg)."'"; }
		if ($cogs_actual) { $query .= ",'".res($cogs_actual)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		return (qid());
	}
?>