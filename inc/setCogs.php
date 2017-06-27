<?php
	function setCogs($inventoryid=0,$sales_item_id=0,$cogs_avg=0,$cogs_actual=0,$ref_1='',$ref_1_label='') {
		$q_invid = 'NULL';
		if ($inventoryid AND is_numeric($inventoryid)) { $q_invid = "'".$inventoryid."'"; }
		$q_si = 'NULL';
		if ($sales_item_id AND is_numeric($sales_item_id)) { $q_si = "'".$sales_item_id."'"; }

		$query = "INSERT INTO sales_cogs (inventoryid, sales_item_id";
		if ($cogs_avg) { $query .= ", cogs_avg"; }
		if ($cogs_actual) { $query .= ", cogs_actual"; }
		if ($ref_1 AND $ref_1_label) { $query .= ", ref_1, ref_1_label"; }
		$query .= ") VALUES ($q_invid,$q_si";
		if ($cogs_avg) { $query .= ",'".res($cogs_avg)."'"; }
		if ($cogs_actual) { $query .= ",'".res($cogs_actual)."'"; }
		if ($ref_1 AND $ref_1_label) { $query .= ", '".res($ref_1)."', '".res($ref_1_label)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		return (qid());
	}
?>
