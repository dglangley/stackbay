<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';

	function calcCost($partid,$purchase_item_id=0) {
		$partid = trim($partid);
		if (! $partid OR ! is_numeric($partid)) { return false; }

		$pid = prep($partid);

		$actual_sum = 0;
		$average_sum = 0;
		$qty_sum = 0;
		$query = "SELECT * FROM inventory WHERE partid = $pid AND qty > 0; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$qty_sum += $r['qty'];

			$query2 = "SELECT average, actual FROM inventory_costs WHERE inventoryid = '".$r['id']."' ORDER BY id DESC LIMIT 0,1; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$actual_sum += $r2['actual']/$r['qty'];
				$average_sum += $r2['average']/$r['qty'];
			}
		}
		$actual_cost = $actual_sum/$qty_sum;
		$average_cost = $average_cost/$qty_sum;
	}
?>
