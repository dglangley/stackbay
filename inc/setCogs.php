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

		$query = "SELECT id, cogs_avg FROM sales_cogs WHERE inventoryid = $q_invid ";
		$query .= "AND item_id ";
		if ($q_id=='NULL') { $query .= "IS NULL "; } else { $query .= "= $q_id "; }
		$query .= "AND item_id_label ";
		if ($q_label=='NULL') { $query .= "IS NULL "; } else { $query .= "= $q_label "; }
		$query .= "; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			if ($r['cogs_avg']==$cogs_avg) { return ($r['id']); }

			$query2 = "UPDATE sales_cogs SET cogs_avg = '".res($cogs_avg)."' WHERE id = '".$r['id']."'; ";
			$result2 = qdb($query2) OR die("Could not update sales cogs with id ".$r['id']." for ".$cogs_avg);
			return ($r['id']);
		}

		$query = "INSERT INTO sales_cogs (inventoryid, item_id, item_id_label";
		if ($cogs_avg) { $query .= ", cogs_avg"; }
		if ($cogs_actual) { $query .= ", cogs_actual"; }
		$query .= ") VALUES ($q_invid,$q_id,$q_label";
		if ($cogs_avg) { $query .= ",'".res($cogs_avg)."'"; }
		if ($cogs_actual) { $query .= ",'".res($cogs_actual)."'"; }
		$query .= "); ";
echo $query.'<BR>';
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		return (qid());
	}
?>
