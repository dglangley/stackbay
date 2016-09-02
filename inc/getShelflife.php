<?php
	function getShelflife($id_array) {
		$shelflife = "";
		if (! $id_array) { return ($shelflife); }

		$query = "SELECT AVG(DATEDIFF(CURDATE(),DATE_SUB(rep_exp_date, INTERVAL 90 DAY))) days, COUNT(inventory_itemlocation.id) n ";
		$query .= "FROM inventory_itemlocation, inventory_inventory ";
		$query .= "WHERE inventory_id IN (".$id_array.") AND rep_exp_date IS NOT NULL AND inventory_inventory.id = inventory_id; ";
		//$query .= "GROUP BY username, inventory_id; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE'));
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$shelflife = round($r['days']).' day';
			if ($r['days']<>1) { $shelflife .= 's'; }
		}

		return ($shelflife);
	}
?>
