<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getTimesheet.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUnitFreight.php';

	$MATERIALS_COST = 0;
	function calcTaskCost($item_id,$item_label,$include_freight=true) {
		global $MATERIALS_COST;

		$cost = 0;

		if (! $item_id OR ! $item_label) { return ($cost); }

		$T = order_type($item_label);

		$order_number = 0;
		$query = "SELECT ".$T['order']." order_number FROM ".$T['items']." WHERE id = '".res($item_id)."'; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) {
			die("Not a valid item id for any existing order");
		}
		$r = mysqli_fetch_assoc($result);
		$order_number = $r['order_number'];

		/***** LABOR COST (if applicable) *****/
		if ($T['labor_cost']) {//as of 12/15/17, only Service type has this as 'true', in other words we don't include labor costs for repairs
			$query = "SELECT userid FROM timesheets WHERE taskid = '".res($item_id)."' AND task_label = '".res($item_label)."' GROUP BY userid; ";
			$result = qedb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				$timesheets = getTimesheet($r['userid'],$item_id,$item_label);

				foreach ($timesheets as $timesheetid => $timesheet) {
					//print "<pre>".print_r($timesheet,true)."</pre>";
					$cost += $timesheet['laborCost'];
				}
			}
		}

		/***** MATERIALS COST *****/
		$MATERIALS_COST = 0;
		if ($item_label=='service_item_id') {
			$query = "SELECT m.qty, m.inventoryid ";
			$query .= "FROM service_materials m, inventory i ";
			$query .= "WHERE m.service_item_id = '".res($item_id)."' AND i.id = m.inventoryid; ";
		} else {
			$query = "SELECT rc.qty, i.id inventoryid FROM repair_components rc, inventory i ";
			$query .= "WHERE rc.item_id = '".res($item_id)."' AND rc.item_id_label = '".res($item_label)."' ";
			$query .= "AND rc.invid = i.id; ";
		}
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$price = 0;

			// cost is based in `inventory_costs` not from purchase price (cost averaging model)
			$query2 = "SELECT actual FROM inventory_costs WHERE inventoryid = '".$r['inventoryid']."'; ";
			$result2 = qedb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				// price is the TOTAL actual cost, not based per qty
				$price = $r2['actual'];// /$r['qty'];
			} else {
//				echo $order_number.' = '.$r['qty'].'*'.$r['price'].':'.$query2.'<BR>';
				// no results from inventory costs results in no cost
			}

			$MATERIALS_COST += $price;
		}
		$cost += $MATERIALS_COST;

		/***** EXPENSES COST *****/
		$expenses_cost = 0;
		$query = "SELECT * FROM expenses WHERE item_id = '".res($item_id)."' AND item_id_label = '".res($item_label)."' AND status <> 'Void'; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$expenses_cost += $r['units']*$r['amount'];
		}
		$cost += $expenses_cost;

		/***** GET ALL 3RD PARTY ORDERS/SERVICES AND REPLACEMENT-FREIGHT COSTS *****/
		$query = "SELECT os_number FROM outsourced_orders os ";
		$query .= "WHERE order_number = '".res($order_number)."' AND order_type = '".$T['type']."'; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$os = $r['os_number'];

			$query2 = "SELECT qty, price FROM outsourced_items oi WHERE oi.os_number = '".res($os)."' ";
			$query2 .= "AND (ref_2 = '".res($item_id)."' AND ref_2_label = '".res($item_label)."'); ";
			$result2 = qedb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$cost += ($r2['qty']*$r2['price']);
			}
		}

/*
		// in addition to below freight cost method, also try to get freight cost from packages
		// associated with outbound SO, rather than just Repair association / legacy method
		if ($include_freight) {
			$query2 = "SELECT so_number FROM sales_items ";
			$query2 .= "WHERE ((ref_1 = '".res($item_id)."' AND ref_1_label = '".res($item_label)."') ";
			$query2 .= "OR (ref_2 = '".res($item_id)."' AND ref_2_label = '".res($item_label)."')) ";
			$query2 .= "GROUP BY so_number; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$freight_cost = getUnitFreight($inventoryid,$r2['so_number'],'Sale');
				if ($freight_cost>0) { $cost += $freight_cost; }
			}
		}

		// get freight cost per unit based on the package's freight that it was in, divided by #pcs inside
		if ($include_freight) {
			$freight_cost = getUnitFreight($inventoryid,$ro_number,$T['type']);
			if ($freight_cost>0) { $cost += $freight_cost; }
		}
*/

		return ($cost);
	}
?>
