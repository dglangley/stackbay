<?php
	function getCOGS($inventoryid,$item_id,$item_id_label,$debug=false) {
		$cogs = 0;

		$query = "SELECT cogs_avg FROM sales_cogs ";
		$query .= "WHERE inventoryid = '".res($inventoryid)."' ";
		$query .= "AND taskid = '".res($item_id)."' AND task_label = '".res($item_id_label)."'; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$cogs = $r['cogs_avg'];
		} else {
			// see P&L usage, where we echo the query to find where we're missing data
			if ($debug) { echo $query.'<BR>'; }
		}

		return ($cogs);
	}
?>
