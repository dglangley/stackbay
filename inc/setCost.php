<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/calcRepairCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCostsLog.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setAverageCost.php';

	if (! isset($debug)) { $debug = 0; }

	function setCost($inventoryid=0) {
		if (! $inventoryid) { return false; }

		$debug = $GLOBALS['debug'];

		// get qty of inventory record in case it's a lot purchase price
		$cost = 0;
		$query = "SELECT qty, serial_no, partid FROM inventory WHERE id = '".res($inventoryid)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return false; }
		$r = mysqli_fetch_assoc($result);
		$qty = 1;
		if ($r['qty']>0) { $qty = $r['qty']; }
		$serial = $r['serial_no'];
		$partid = $r['partid'];

		// get all purchase records in case we've purchased it multiple times
		$query = "SELECT * FROM inventory_history h WHERE invid = '".res($inventoryid)."' ";
		$query .= "AND field_changed = 'purchase_item_id' AND value IS NOT NULL; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$pi_id = $r['value'];
			if (! $pi_id) { continue; }

			$query2 = "SELECT price, po_number FROM purchase_items WHERE id = '".res($pi_id)."'; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_fetch_assoc($result2)==0) { continue; }
			$r2 = mysqli_fetch_assoc($result2);
			$po_number = $r2['po_number'];
			// use this for reference below in case there's an RTV
			$purchase_cost = $r2['price']/$qty;

			// update costs log
			$cost += $purchase_cost;
			setCostsLog($inventoryid,$pi_id,'purchase_item_id',$purchase_cost);

			// get purchase-related freight costs here
			$freight_cost = getUnitFreight($inventoryid,$po_number,'Purchase');
			if ($freight_cost>0) {
				$cost += $freight_cost;
				setCostsLog($inventoryid,$r2['packageid'],'packageid',$freight_cost);
			}

			// discount rtv's here
			$query2 = "SELECT * FROM sales_items si, inventory_history h, inventory i ";
			$query2 .= "WHERE ((ref_1 = '".$pi_id."' AND ref_1_label = 'purchase_item_id') OR (ref_2 = '".$pi_id."' AND ref_2_label = 'purchase_item_id')) ";
			$query2 .= "AND si.id = h.value AND h.field_changed = 'sales_item_id' AND h.invid = i.id; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$cost -= $purchase_cost;
				setCostsLog($inventoryid,$r2['so_number'],'rtv',$purchase_cost);
			}
		}

		// get rma-related freight costs here
		$query = "SELECT * FROM inventory_history h WHERE invid = '".res($inventoryid)."' ";
		$query .= "AND field_changed = 'returns_item_id' AND value IS NOT NULL; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			// DAVID what to do here? we don't have a way of tracking returned units freight cost, currently
		}

		// get repair costs here
		$query = "SELECT value repair_item_id FROM inventory_history h WHERE invid = '".res($inventoryid)."' ";
		$query .= "AND field_changed = 'repair_item_id' AND value IS NOT NULL; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$query2 = "SELECT ro_number FROM repair_items WHERE id = '".$r['repair_item_id']."'; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) {
				$repair_cost = calcRepairCost($r2['ro_number'],$r['repair_item_id'],$inventoryid);
				$cost += $repair_cost;
				setCostsLog($inventoryid,$r['repair_item_id'],'repair_item_id',$repair_cost);
			}
		}

		// calculate diff in cost so we can adjust cost average
		$actual = 0;
		$query = "SELECT actual FROM inventory_costs WHERE inventoryid = '".$inventoryid."' ORDER BY id DESC LIMIT 0,1; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$actual = $r['actual'];
		}
		$diff = $cost-$actual;//ex: $100 cost - $0 (no previous cost) = $100; ex 2: $100 cost - $85 (previous cost) = $15 (newly-added freight, for example)
		setAverageCost($partid,($diff*$qty));//multiply by qty because our cost per inventory record is not necessarily per UNIT, but per RECORD

		// reset inventory costs with latest cost data
		$query = "DELETE FROM inventory_costs WHERE inventoryid = '".$inventoryid."'; ";
		if ($debug==1) { echo $query.'<BR>'; }
		else { $result = qdb($query) OR die(qe().'<BR>'.$query); }

		$query = "REPLACE inventory_costs (inventoryid, datetime, actual, average) ";
		$query .= "VALUES ('".$inventoryid."','".$GLOBALS['now']."','".$cost."','".$cost."'); ";
		if ($debug==1) { echo $query.'<BR>'; }
		else { $result = qdb($query) OR die(qe().'<BR>'.$query); }

		// until we've migrated repairs completely, this will stand in as our cost calculator for repairs
//		calcLegacyRepair($serial);
	}

	function calcLegacyRepair($serial='') {
		if (! $serial) { return (0); }

		$cost = 0;
		$avg_cost = 0;
		$total_repair = 0;
		// get repair costs (currently from old db, but this will change)
		if ($serial AND $serial<>'000') {
			$query3 = "SELECT cost, avg_cost FROM inventory_solditem WHERE serial = '".$serial."'; ";
			$result3 = qdb($query3,'PIPE') OR die(qe('PIPE').'<BR>'.$query3);
			if (mysqli_num_rows($result3)>0) {
				$r3 = mysqli_fetch_assoc($result3);
				$cost = $r3['cost'];
				$avg_cost = $r3['avg_cost'];
			} else {
				$query3 = "SELECT cost FROM inventory_itemlocation WHERE serial = '".$serial."'; ";
				$result3 = qdb($query3,'PIPE') OR die(qe('PIPE').'<BR>'.$query3);
				if (mysqli_num_rows($result3)>0) {
					$r3 = mysqli_fetch_assoc($result3);
					$cost = $r3['cost'];
					$avg_cost = $r3['cost'];
				}
			}

			$query3 = "SELECT r.ticket_number, r.inventory_id, s.id item_id, r.date_out ";//, s.repair_id ";
			$query3 .= "FROM inventory_repair r, inventory_solditem s ";
			$query3 .= "WHERE r.serials = '".$serial."' AND r.serials = s.serial; ";
			$result3 = qdb($query3,'PIPE') OR die(qe('PIPE').'<BR>'.$query3);
			while ($r3 = mysqli_fetch_assoc($result3)) {
				$repair = array(
					'serial'=>$serial,
					'repair_id'=>$r3['ticket_number'],
					'inventory_id'=>$r3['inventory_id'],
					'po'=>'',
					'id'=>$r3['item_id'],
				);
				$repair_cost = calcRepairCost($repair);
				$total_repair += $repair_cost;
				if ($repair_cost>0) { echo 'Serial '.$serial.' Repair '.$r3['ticket_number'].' cost: '.$repair_cost.'<BR>'; }

				// set the cost of repair
				if ($repair_cost>0) {
					setCostsLog($r2['id'],$r3['ticket_number'],'repair_id',$repair_cost,$r3['date_out']." 16:00:00");

					$serials[$r2['id']]['repair'] += $repair_cost;
					$serials[$r2['id']]['total'] += $repair_cost;
				}
			}
		}

		$serials[$r2['id']]['b_avg'] = $avg_cost;
		$serials[$r2['id']]['b_cost'] = $cost;
	}
?>
