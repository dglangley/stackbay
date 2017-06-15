<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';

	function getCost($partid,$cost_basis='average',$purchase_item_id=0) {
		$partids = array();
		$csv_partids = '';
		if (is_array($partid)) {
			$partids = $partid;
		} else if (strstr($partid,',')) {
			$csv_partids = $partid;
		} else {
			$partids[] = trim($partid);
		}

		// $csv_partids can be passed in as $partid so only do the following if an array or variable has been passed in
		if (! $csv_partids) {
			foreach ($partids as $partid) {
				if (! $partid OR ! is_numeric($partid)) { continue; }

				if ($csv_partids) { $csv_partids .= ','; }
				$csv_partids .= $partid;
			}
		}

		if (! $csv_partids) { return false; }

		$actual_sum = 0;
		$average_sum = 0;
		$qty_sum = 0;
		$query = "SELECT * FROM inventory WHERE partid IN (".$csv_partids.") AND (status = 'received' OR status = 'shelved') ";
		$query .= "AND conditionid > 0; ";//AND qty > 0; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$qty = 1;
			if ($r['qty']>0) { $qty = $r['qty']; }
			$qty_sum += $qty;

			$query2 = "SELECT average, actual FROM inventory_costs WHERE inventoryid = '".$r['id']."' ORDER BY id DESC LIMIT 0,1; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$actual_sum += $r2['actual']/$qty;
				$average_sum += $r2['average']/$qty;
			}
		}
		$actual_cost = 0;
		$average_cost = 0;
		if ($qty_sum>0) {
			$actual_cost = $actual_sum/$qty_sum;
			$average_cost = $average_sum/$qty_sum;
		}

		if ($cost_basis=='average' AND (! $actual_cost OR $average_cost>0)) {
			return ($average_cost);
		} else {
			return ($actual_cost);
		}
	}
?>
