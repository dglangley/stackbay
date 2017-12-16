<?php
	function setCogs($inventoryid=0,$item_id=0,$item_id_label='',$cogs_avg=0,$cogs_actual=0) {
		global $debug;

		$query = "SELECT id, cogs_avg FROM sales_cogs WHERE inventoryid ";
		if ($inventoryid) { $query .= "= '".res($inventoryid)."' "; } else { $query .= "IS NULL "; }
		$query .= "AND item_id ";
		if ($item_id) { $query .= "= '".res($item_id)."' "; } else { $query .= "IS NULL "; }
		$query .= "AND item_id_label ";
		if ($item_id_label) { $query .= "= '".res($item_id_label)."' "; } else { $query .= "IS NULL "; }
		$query .= "; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			if ($r['cogs_avg']==$cogs_avg) { return ($r['id']); }

			$query2 = "UPDATE sales_cogs SET cogs_avg = '".res($cogs_avg)."' WHERE id = '".$r['id']."'; ";
			if ($debug) {
				echo $query2.'<BR>';
			} else {
				$result2 = qedb($query2);
			}
			return ($r['id']);
		}

		$cogsid = addCOGS($inventoryid,$item_id,$item_id_label,$cogs_avg,$cogs_actual);
		return ($cogsid);
	}

	function addCOGS($inventoryid=0,$item_id=0,$item_id_label='',$cogs_avg=0,$cogs_actual=0) {
		global $debug;

		$query = "INSERT INTO sales_cogs (inventoryid, item_id, item_id_label, cogs_avg, cogs_actual) ";
		$query .= "VALUES (".fres($inventoryid).",".fres($item_id).",".fres($item_id_label).",";
		$query .= fres($cogs_avg).",".fres($cogs_actual)."); ";
		if ($debug) {
			echo $query.'<BR>';
			$cogsid = 999999;
		} else {
			$result = qedb($query);
			$cogsid = qid();
		}
		return ($cogsid);
	}
?>
