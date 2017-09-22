<?php
	function setCogs($inventoryid=0,$item_id=0,$item_id_label='',$cogs_avg=0,$cogs_actual=0) {
		$query = "SELECT id, cogs_avg FROM sales_cogs WHERE inventoryid = '".res($inventoryid)."' ";
		$query .= "AND item_id ";
		if ($item_id) { $query .= "= '".res($item_id)."' "; } else { $query .= "IS NULL "; }
		$query .= "AND item_id_label ";
		if ($item_id_label) { $query .= "= '".res($item_id_label)."' "; } else { $query .= "IS NULL "; }
		$query .= "; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			if ($r['cogs_avg']==$cogs_avg) { return ($r['id']); }

			$query2 = "UPDATE sales_cogs SET cogs_avg = '".res($cogs_avg)."' WHERE id = '".$r['id']."'; ";
			$result2 = qdb($query2) OR die("Could not update sales cogs with id ".$r['id']." for ".$cogs_avg);
			return ($r['id']);
		}

		$cogsid = addCOGS($inventoryid,$item_id,$item_id_label,$cogs_avg,$cogs_actual);
		return ($cogsid);
	}

	function addCOGS($inventoryid=0,$item_id=0,$item_id_label='',$cogs_avg=0,$cogs_actual=0) {
		$query = "INSERT INTO sales_cogs (inventoryid, item_id, item_id_label, cogs_avg, cogs_actual) ";
		$query .= "VALUES ('".res($inventoryid)."',";
		if ($item_id) { $query .= "'".res($item_id)."',"; } else { $query .= "NULL,"; }
		if ($item_id_label) { $query .= "'".res($item_id_label)."',"; } else { $query .= "NULL,"; }
		if ($cogs_avg) { $query .= "'".res($cogs_avg)."',"; } else { $query .= "NULL,"; }
		if ($cogs_actual) { $query .= "'".res($cogs_actual)."'"; } else { $query .= "NULL"; }
		$query .= "); ";
// echo $query.'<BR>';
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		return (qid());
	}
?>
