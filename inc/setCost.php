<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/calcRepairCost.php';

	function setCost($order_number,$partid=0) {
		$debug = 0;

		$serials = array();//stores all cost-related details per serial, including cost total
		$total = 0;
		$qtys = 0;
		$purch_cost = 0;

		if ($debug) { echo 'PO'.$order_number.'<BR>'; }
		$query = "SELECT * FROM purchase_items pi WHERE po_number = '".$order_number."' ";
		if ($partid) { $query .= "AND partid = '".$partid."' "; }
		$query .= "; ";
		$result = qdb($query) OR die(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$serial = '';
			$query2 = "SELECT * FROM inventory i WHERE i.purchase_item_id = '".$r['id']."'; ";
			$result2 = qdb($query2) OR die(qe().' '.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$serial = $r2['serial_no'];
				$qty = 1;
				// if no serial number, then we might be dealing with 2+ qty, so check it before we wreck it
				// like Wreck It Ralph
				if (! $serial AND $r2['qty']>1) { $qty = $r2['qty']; }

				$qtys += $qty;
				$ext = ($qty*$r['price']);
				if (! isset($serials[$r2['id']])) {
					$serials[$r2['id']] = array('purchase'=>0,'freight'=>0,'repair'=>0,'total'=>0,'serial'=>$serial,'b_avg'=>0,'b_cost'=>0);
				}

				// set the cost of the purchase price itself
				if ($ext>0) {
					$query3 = "REPLACE inventory_costs_log (inventoryid, eventid, event_type, amount) ";
					$query3 .= "VALUES ('".$r2['id']."','".$r['id']."','purchase_item_id','".$ext."'); ";
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					$serials[$r2['id']]['purchase'] += $ext;
					$serials[$r2['id']]['total'] += $ext;
					if ($debug==1) { echo $query3.'<BR>'; }
				}

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

					$query3 = "SELECT r.ticket_number, r.inventory_id, s.id item_id ";//, s.repair_id ";
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
							$query4 = "REPLACE inventory_costs_log (inventoryid, eventid, event_type, amount) ";
							$query4 .= "VALUES ('".$r2['id']."','".$r3['ticket_number']."','repair_id','".$repair_cost."'); ";
							$result4 = qdb($query4) OR die(qe().'<BR>'.$query4);
							$serials[$r2['id']]['repair'] += $repair_cost;
							$serials[$r2['id']]['total'] += $repair_cost;
							if ($debug==1) { echo $query4.'<BR>'; }
						}
					}
				}

				$serials[$r2['id']]['b_avg'] = $avg_cost;
				$serials[$r2['id']]['b_cost'] = $cost;
				$purch_cost += ($ext+$total_repair);
			}
		}
		if ($qtys>0) { $total = round($purch_cost/$qtys,4); }

		$total_freight = 0;
		$freight_pcs = 0;
		$freight = 0;
		$query = "SELECT freight_amount, id FROM packages WHERE order_number = '".$order_number."'; ";
		$result = qdb($query) OR die(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$n = 0;//number of items on this PO/partid for this package

			//$query2 = "SELECT COUNT(package_contents.id) n FROM package_contents ";
			$query2 = "SELECT package_contents.id, package_contents.serialid FROM package_contents ";
			// if $partid is passed in, get only the packages related to the part
			if ($partid) { $query2 .= ", inventory "; }
			$query2 .= "WHERE packageid = '".$r['id']."' ";
			if ($partid) { $query2 .= "AND package_contents.serialid = inventory.id AND inventory.partid = '".$partid."' "; }
			$query2 .= "; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);

			$n = mysqli_num_rows($result2);
			$freight_per = 0;
			// count the freight only if we're getting 'n' results from query above
			// (if $partid, this part is in box; if not, there are pieces at all on this PO in this box)
			if ($n>0) {
				$total_freight += $r['freight_amount'];
				$freight_pcs += $n;
				$freight_per = ($r['freight_amount']/$n);
			}
			while ($r2 = mysqli_fetch_assoc($result2)) {
//				$n = $r2['n'];
//5/23 why did I have this here? (was $packages[])
//				$serials[$r2['serialid']] = $freight_per;
				if (! isset($serials[$r2['serialid']])) {
					$serial = '';
					$query3 = "SELECT serial_no FROM inventory WHERE id = '".$r2['serialid']."'; ";
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					if (mysqli_num_rows($result3)>0) {
						$r3 = mysqli_fetch_assoc($result3);
						$serial = $r3['serial_no'];
					}
					$serials[$r2['serialid']] = array('purchase'=>0,'freight'=>0,'repair'=>0,'total'=>0,'serial'=>$serial,'b_avg'=>0,'b_cost'=>0);
				}

				// set the cost of the purchase price itself
				if ($freight_per>0) {
					$query3 = "REPLACE inventory_costs_log (inventoryid, eventid, event_type, amount) ";
					$query3 .= "VALUES ('".$r2['serialid']."','".$r['id']."','package_id','".$freight_per."'); ";
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					$serials[$r2['serialid']]['freight'] += $freight_per;
					$serials[$r2['serialid']]['total'] += $freight_per;
					if ($debug==1) { echo $query3.'<BR>'; }
				}
			}
		}
		// if no specific pieces in package_contents, assume the freight found above is for the entire PO
		if ($total_freight>0 AND $freight_pcs==0) { $freight_pcs = $qtys; }

		if ($freight_pcs>0) {
			$freight = round($total_freight/$freight_pcs,4);
			if ($debug==1) { echo 'Freight: '.$total_freight.' ('.$freight.' avg per)<BR>'; }
		}

		$total += $freight;

		foreach ($serials as $invid => $r) {
			if ($r['total']==0) { continue; }

			$actual = round($r['total'],4);
			$average = $actual;

			$avg_query = "SELECT average FROM inventory_costs WHERE inventoryid = '".$invid."'; ";
			$result3 = qdb($avg_query) OR die(qe().'<BR>'.$avg_query);
			if (mysqli_num_rows($result3)>0) {
				$r3 = mysqli_fetch_assoc($result3);
				$average = $r3['average'];
			}

			$query3 = "DELETE FROM inventory_costs WHERE inventoryid = '".$invid."'; ";
//			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			if ($debug==1) { echo $query3.'<BR>'; }

			$query3 = "REPLACE inventory_costs (inventoryid, datetime, actual, average) ";
			$query3 .= "VALUES ('".$invid."','".$GLOBALS['now']."','".$actual."','".$average."'); ";
//			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			if ($debug==1) { echo $query3.'<BR>'; }


if ($r['b_cost']<>$actual) {
			echo $r['serial'].'<BR>';
			$corr = $r['b_cost']-$actual;
			$query3 = "SELECT * FROM inventory_costs_log ";
			$query3 .= "WHERE inventoryid = '".$invid."' AND eventid IS NULL AND event_type = 'import adjustment'; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			if (mysqli_num_rows($result3)==0) {
				$query3 = "REPLACE inventory_costs_log (inventoryid, eventid, event_type, amount) ";
				$query3 .= "VALUES ('".$invid."',NULL,'import adjustment','".$corr."'); ";
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR><BR>';
			}
/*
	echo $r['serial'].' ';
	if ($debug==1) { echo $qty.' * '.$r['price'].' + '.$total_repair.' = '.$ext.' '; }
	echo '(Brians cost: '.$r['b_cost'].', avg: '.$r['b_avg'].')<BR>';
	echo $query3.'<BR>';
	if ($average<>$r['b_avg']) {
		echo $avg_query.'<BR>';
	}
	echo '<BR>';
*/
}
		}

		return ($total);
	}
?>
