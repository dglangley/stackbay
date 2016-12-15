<?php
	include_once 'inc/dbconnect.php';
	include_once 'inc/pipe.php';

	$BOGUS_COST = 0;//a catch-all for components purchased, then zeroed, and never received, but that Brian was including in project costs
	$PROJECTS = array();

	// track projects we've already checked so we don't repeat the same data below
	$used_project = array();

	function calcProjectCost($r) {
		global $used_project;

		$project = array();
		if (! $r['project_id']) { return; }

		$project = getProject($r['project_id']);
		if (! $project) {
			die("Missing project ".$r['project_id']."! solditem id ".$r['id']);
		}

//		print "<pre>".print_r($project,true)."</pre>";
		if ($r['avg_cost']>0) { $item_cost = $r['avg_cost']; }
		else { $item_cost = $r['cost']; }

		if (round($project['cpi'],2)<>round($item_cost,2)) {
			// only set this project and display output from it once
			if (! isset($used_project[$r['project_id']])) {
				$used_project[$r['project_id']] = true;
				print "<pre>".print_r($r,true)."</pre>";
				print "<pre>".print_r($project,true)."</pre>";
			}
		}
	}

	$PURCHASES = array();
	function calcPurchaseCost($r) {
		global $PURCHASES,$queries;

		if (! $r['iq_id']) {
			if ($r['freight_cost']) { return ($r['freight_cost']); }
			else { return 0; }
		}

		if (isset($PURCHASES[$r['iq_id']])) {
			$cost = $PURCHASES[$r['iq_id']];
		} else {
			$cost = 0;
			$freight_cogs = 0;
			$query2 = "SELECT iq.price, iq.quantity, iq.id iq_id, po.purchasequote_ptr_id po_id ";
			$query2 .= "FROM inventory_incoming_quote iq, inventory_purchasequote pq, inventory_purchaseorder po ";
			$query2 .= "WHERE iq.id = '".$r['iq_id']."' AND iq.quote_id = pq.id AND pq.id = po.purchasequote_ptr_id; ";
$queries[] = $query2;
			$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').' '.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$freight_cogs += getFreightCogs($r2);

				$cost += round($r2['price'],4);//+$freight_cogs,4);
			}
			$PURCHASES[$r['iq_id']] = $cost;
		}

		$freight_cogs = round($freight_cogs,4);
//echo $cost.' cost, '.round($freight_cogs,4).' freight cogs, '.round($r['freight_cost'],4).' freight cost, ';
		if ($r['freight_cost']>0) { $cost += $r['freight_cost']; }
		else if ($freight_cogs>0) { $cost += $freight_cogs; }
//echo $cost.' final cost<BR>';

		return $cost;
	}

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

	$FC = array();//global array to store purchase freight cogs from invoice data so we can avoid repetitious queries
	function getFreightCogs($r) {
		global $FC,$queries;

		//$key = $r['po_id'];
		$key = $r['iq_id'];
		if (isset($FC[$key])) { return $FC[$key]; }

		$FC[$key] = 0;//initialize
		$freight_cogs = 0;
		$num_freight = 0;

		// get purchase invoice which contains freight details, then query backwards into iqi table to get full qty on that shipment,
		// to calculate freight cost divided by all qty on that freight shipment
		$query2 = "SELECT purchase_invoice_id FROM inventory_incoming_quote_invoice iqi WHERE iq_id = '".$r['iq_id']."'; ";
$queries[] = $query2;
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$query3 = "SELECT SUM(quantity) qty, freight_cogs FROM inventory_incoming_quote_invoice iqi, inventory_purchaseinvoice pi ";
			$query3 .= "WHERE pi.id = '".$r2['purchase_invoice_id']."' AND iqi.purchase_invoice_id = pi.id; ";
			$result3 = qdb($query3,'PIPE') OR die(qe('PIPE').' '.$query3);
			$num_freight += mysqli_num_rows($result3);
			while ($r3 = mysqli_fetch_assoc($result3)) {
				$freight_cogs += $r3['freight_cogs']/$r3['qty'];
			}
		}

		$FC[$key] = $freight_cogs/$num_freight;

		return ($FC[$key]);
	}

	function getProject($project_id) {
		global $PROJECTS,$BOGUS_COST;
		if (isset($PROJECTS[$project_id])) { return ($PROJECTS[$project_id]); }

		$query2 = "SELECT * FROM inventory_project WHERE id = '".$project_id."'; ";
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').' '.$query2);
		if (mysqli_num_rows($result2)==0) {
			return false;
		}
		$project = mysqli_fetch_assoc($result2);

		$project_cost = 0;
		$query2 = "SELECT co.component_id, co.price, co.quantity ordered_qty, co.received recd_qty, co.supplier_id, co.cpo_id, ";
		$query2 .= "co.billed, co.repair_id, co.date, co.freight_cost, c.part_number, c.description, c.cost_per_unit ";
		$query2 .= "FROM inventory_componentorder co, inventory_component c ";
		$query2 .= "WHERE project_id = '".$project_id."' AND co.component_id = c.id ";
		$query2 .= "ORDER BY date ASC, co.id ASC; ";
//echo $query2.'<BR>';
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			// qty to be included is Ordered Qty (not recd) + Received Qty
			$qty = $r2['recd_qty']+$r2['ordered_qty'];

			// Brian was including items with 0 qty (0 ordered, 0 received = canceled order); I, on the other hand, reject inaccurate data
// leaving this commented for now since I know of this bug and don't want to receive alerts to inaccuracies during the audit
//				if ($qty==0) { $BOGUS_COST += $r2['cost_per_unit']; continue; }
//echo $r2['part_number'].' '.$r2['description'].'; '.$r2['recd_qty'].' qty ('.$r2['billed'].' billed), $ '.$r2['cost_per_unit'].' cpu, '.$r2['freight_cost'].' freight<BR>';

			// Price can be associated with item on order, or cpu on component item
			if ($r2['price']>0) { $cost = $qty*$r2['price']; }
			else { $cost = $r2['cost_per_unit']; }

			$project_cost += $cost;
		}

		$project['cost'] = $project_cost;

		// get items in stock with this project and calculate cost per item
		$project['items'] = 0;

		$query2 = "SELECT * FROM inventory_itemlocation WHERE project_id = '".$project_id."'; ";
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').' '.$query2);
		$project['items'] += mysqli_num_rows($result2);

		$query2 = "SELECT * FROM inventory_solditem WHERE project_id = '".$project_id."'; ";
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').' '.$query2);
		$project['items'] += mysqli_num_rows($result2);

		if ($project['cost_override']>0) {
			$project['cpi'] = $project['cost_override'];
		} else {
			$project['cpi'] = round($project['cost']/$project['items'],4);
		}

		$PROJECTS[$project_id] = $project;

		return ($project);
	}

	// start with in-stock items
	$query = "SELECT il.id, il.serial, il.inventory_id, il.cost, il.ci_id, il.iqi_id, il.freight_cost, il.po_id, ";
	$query .= "il.project_id, il.orig_cost, il.orig_inv_id, il.iq_id, ";
	$query .= "i.part_number, i.clei heci ";
	$query .= "FROM inventory_itemlocation il, inventory_inventory i ";
	$query .= "WHERE inventory_id = i.id; ";
/*
echo $query.'<BR>';
	$result = qdb($query,'PIPE') OR die(qe('PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		// do something
	}
*/

	// process sold items, the much trickier beast
	$query = "SELECT si.id, si.serial, si.inventory_id, si.cost, si.so_id, si.price, si.returned, si.returned_to_vendor, ";
	$query .= "si.returned_from_vendor, si.project_id, si.freight_cost, si.po, si.avg_cost, si.invoice_id, si.orig_cost, ";
	$query .= "si.ci_id, si.ci_bill_id, si.orig_inv_id, si.iq_id, si.oq_id, si.repair_id, ";
	$query .= "i.part_number, i.clei heci ";
	$query .= "FROM inventory_solditem si, inventory_inventory i ";
	$query .= "WHERE inventory_id = i.id AND project_id IS NULL ";
	// excluding KS-23908's which were audited in
	$query .= "AND inventory_id <> 234134 ";
	// excluding 1400422-0019's which seem to be an old consigned item or something
	$query .= "AND inventory_id <> 234601 ";
	$query .= "ORDER BY si.id DESC LIMIT 800,200 ";
	$query .= "; ";
echo $query.'<BR><BR>';
	$result = qdb($query,'PIPE') OR die(qe('PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
//if ($r['inventory_id']<>248133) { continue; }
//		print "<pre>".print_r($r,true)."</pre>";
		$queries = array();//reset debugging queries every array

		/*** CALCULATE PROJECT COSTS ***/
		calcProjectCost($r);

		/*** CALCULATE PURCHASE COST ***/
		$cost = calcPurchaseCost($r);

		/*** ADD FREIGHT COST (oftentimes from return RMA's) ***/
//		$cost += $r['freight_cost'];

		/*** CALCULATE REPAIR COSTS, IF ANY ***/
		$repair_cost = calcRepairCost($r);
		$r['purch_cost'] = $cost;
		$r['repair_cost'] = $repair_cost;
		$r['cost_plus_repair'] = $cost+$repair_cost;
		if (round($r['cost_plus_repair'],2)<>round($r['cost'],2)) {
			// check for dumb 'brian audit'
			$query2 = "SELECT * FROM inventory_shipping ";
			$query2 .= "WHERE (inventory_id = '".$r['inventory_id']."' OR inventory_id = '".$r['orig_inv_id']."') AND note LIKE '%Audit%'; ";
			$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').' '.$query2);
			if (mysqli_num_rows($result2)>0) { continue; }

			foreach ($queries as $q) {
				echo $q.'<BR>';
			}
			print "<pre>".print_r($r,true)."</pre>";
		}
	}
//echo $BOGUS_COST.'<BR>';
?>
