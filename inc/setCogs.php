<?php
	function setCogs($inventoryid=0,$item_id=0,$item_id_label='',$cogs_avg=0,$cogs_actual=0,$ref_1='',$ref_1_label='') {
		$q_invid = 'NULL';
		if ($inventoryid AND is_numeric($inventoryid)) { $q_invid = "'".$inventoryid."'"; }
		$q_id = 'NULL';
		$q_label = 'NULL';
		if ($item_id AND is_numeric($item_id)) {
			$q_id = "'".$item_id."'";
			if ($item_id_label) { $q_label = "'".$item_id_label."'"; }
		}

		$query = "INSERT INTO sales_cogs (inventoryid, item_id, item_id_label";
		if ($cogs_avg) { $query .= ", cogs_avg"; }
		if ($cogs_actual) { $query .= ", cogs_actual"; }
//		if ($ref_1 AND $ref_1_label) { $query .= ", ref_1, ref_1_label"; }
		$query .= ") VALUES ($q_invid,$q_id,$q_label";
		if ($cogs_avg) { $query .= ",'".res($cogs_avg)."'"; }
		if ($cogs_actual) { $query .= ",'".res($cogs_actual)."'"; }
//		if ($ref_1 AND $ref_1_label) { $query .= ", '".res($ref_1)."', '".res($ref_1_label)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		return (qid());
	}
?>
