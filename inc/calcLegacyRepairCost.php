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
	function calcLegacyRepairCost($r) {
		global $REPAIRS,$queries;

		$repair_cost = 0;
		$key = $r['serial'].'.'.$r['repair_id'].'.'.$r['inventory_id'];
		if (! $key) { return ($repair_cost); }

		if (isset($REPAIRS[$key])) { return ($REPAIRS[$key]); }

		$results = array();

		// calc third party repair costs
		$query2  = "SELECT ro.cost, ro.qty filled, '' order_id FROM inventory_repair r, inventory_repairoffer ro ";
		$query2 .= "WHERE r.serials = '".$r['serial']."' AND third_party_success = 1 ";
		if ($r['repair_id']) { $query2 .= "AND r.ticket_number = '".$r['repair_id']."' "; }
		if ($r['po']) { $query2 .= "AND r.purchase_order = '".$r['po']."' "; }
		else { $query2 .= "AND r.inventory_id = '".$r['inventory_id']."' "; }
		$query2 .= "AND ro.id = r.tpquote_id ";
		$query2 .= "; ";
$queries[] = $query2;
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$results[] = $r2;
		}

		$freight_costs = array();
		// if we have the PO, search db against the related purchase order with matching serial
		$repairs_csv = '';
		$freight_cost = array();
		$query2 = "SELECT cr.filled, cr.cost, co.received, co.price, cr.order_id, cr.repair_id, r.freight_cost ";
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
			if ($repairs_csv) { $repairs_csv .= ',';}
			$repairs_csv .= $r2['repair_id'];
			$results[] = $r2;
			$freight_costs[$r2['repair_id']] = $r2['freight_cost'];//key by repair id to avoid duplication below
		}

		// add subsequent rma repairs to the array for additional cost evaluation
//		$query2 = "SELECT repair_id FROM inventory_rmaticket WHERE item_id = '".$r['id']."'; ";
		$query2 = "SELECT cr.filled, cr.cost, co.received, co.price, cr.order_id, cr.repair_id, r.freight_cost ";
		$query2 .= "FROM inventory_repair r, inventory_rmaticket rt, inventory_componentrepair cr ";
		$query2 .= "LEFT JOIN inventory_componentorder co ON co.id = cr.order_id ";
		$query2 .= "WHERE cr.repair_id = r.ticket_number AND cr.repair_id = rt.repair_id ";
		if ($repairs_csv) { $query2 .= "AND cr.repair_id NOT IN (".$repairs_csv.") "; }
		$query2 .= "AND rt.item_id = '".$r['id']."'; ";
$queries[] = $query2;
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$results[] = $r2;
			$freight_costs[$r2['repair_id']] = $r2['freight_cost'];//key by repair id to avoid duplication from above
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

		foreach ($freight_costs as $repair_id => $amt) {
			$repair_cost += $amt;
		}
if ($repair_cost>0) {
//echo $r['po'].': '.$repair_cost.' repair cost<BR>';
}

		$REPAIRS[$key] = $repair_cost;

		return ($REPAIRS[$key]);
	}
