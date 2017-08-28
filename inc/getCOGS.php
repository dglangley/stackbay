<?php
	function getCOGS($inventoryid,$item_id,$item_id_label,$get_old_data=false) {
		$cogs = 0;

		$query = "SELECT cogs_avg FROM sales_cogs ";
		$query .= "WHERE inventoryid = '".res($inventoryid)."' ";
		$query .= "AND item_id = '".res($item_id)."' AND item_id_label = '".res($item_id_label)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$cogs = $r['cogs_avg'];
/*
		} else if ($get_old_data) {
			$query = "SELECT average FROM inventory_costs WHERE inventoryid = '".$inventoryid."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$cogs = $r['average'];
			}
*/
		} else {
//uncomment this to debug the missing data in sales_cogs
echo $query.'<BR>';
		}

		return ($cogs);
	}
?>
