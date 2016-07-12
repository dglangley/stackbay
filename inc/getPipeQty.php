<?php
	function getPipeQty($pipe_id) {
		$qty = 0;
		$query = "SELECT COUNT(inventory_itemlocation.id) AS qty ";
		$query .= "FROM inventory_itemlocation, inventory_location ";
		$query .= "WHERE inventory_id = '".$pipe_id."' AND no_sales = '0' ";
		$query .= "AND inventory_itemlocation.location_id = inventory_location.id; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE'));
		while ($r = mysqli_fetch_assoc($result)) {
			$qty += $r['qty'];
		}

		return ($qty);
	}
?>
