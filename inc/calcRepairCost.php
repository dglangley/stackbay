<?php
	/**** FUNCTION REQUIREMENTS ****
		Requires the following parameters as part of $r[] array:
			'serial' (must be exact serial in repair table)
			'repair_id' (optional, but if passed in must correspond to repair table id)
			'inventory_id' (must correspond to inventory table id)
			'po' (optional, but if passed in must correspond to purchase_order in repair table)
			'id' (must be item_id from rmaticket table, which corresponds to 'id' field of solditem table)
	****/

	$REPAIRS = array();
	function calcRepairCost($r) {
		global $REPAIRS,$queries;

		$repair_cost = 0;
		$key = $r['serial'].'.'.$r['repair_id'].'.'.$r['inventory_id'];
		if (! $key) { return ($repair_cost); }

		if (isset($REPAIRS[$key])) { return ($REPAIRS[$key]); }

		$results = array();

		// if we have the PO, search db against the related purchase order with matching serial
		$query2 = "SELECT cr.filled, cr.cost, co.received, co.price, cr.order_id ";
		$query2 .= "FROM inventory_repair r, inventory_componentrepair cr ";
		$query2 .= "LEFT JOIN inventory_componentorder co ON co.id = cr.order_id ";
//		$query2 .= "WHERE co.repair_id = r.ticket_number AND r.serials = '".$r['serial']."' ";
		$query2 .= "WHERE cr.repair_id = r.ticket_number AND r.serials = '".$r['serial']."' ";
		if ($r['repair_id']) { $query2 .= "AND cr.repair_id = '".$r['repair_id']."' "; }
		if ($r['po']) { $query2 .= "AND r.purchase_order = '".$r['po']."' "; }
		else { $query2 .= "AND r.inventory_id = '".$r['inventory_id']."' "; }
		$query2 .= "; ";
$queries[] = $query2;
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$results[] = $r2;
		}

		// add subsequent rma repairs to the array for additional cost evaluation
//		$query2 = "SELECT repair_id FROM inventory_rmaticket WHERE item_id = '".$r['id']."'; ";
		$query2 = "SELECT cr.filled, cr.cost, co.received, co.price, cr.order_id ";
		$query2 .= "FROM inventory_repair r, inventory_rmaticket rt, inventory_componentrepair cr ";
		$query2 .= "LEFT JOIN inventory_componentorder co ON co.id = cr.order_id ";
		$query2 .= "WHERE cr.repair_id = r.ticket_number AND cr.repair_id = rt.repair_id ";
		$query2 .= "AND rt.item_id = '".$r['id']."'; ";
$queries[] = $query2;
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$results[] = $r2;
		}

		foreach ($results as $r2) {
			$price = 0;
			if ($r2['order_id']) {
				$price = $r2['price'];
				$qty = $r2['received'];
				// in component orders, repair cost is qty*price
				$repair_cost += ($qty*$price);
			} else {
				$price = $r2['cost'];
				$qty = $r2['filled'];
				// in component stock, repair cost is already summed
				$repair_cost += $r2['cost'];
			}
		}
if ($repair_cost>0) {
//echo $r['po'].': '.$repair_cost.' repair cost<BR>';
}

		$REPAIRS[$key] = $repair_cost;

		return ($REPAIRS[$key]);
	}
