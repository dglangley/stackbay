<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/calcLegacyRepairCost.php';

	$debug = 1;
	function setCost($order_number,$partid=0) {
		$debug = $GLOBALS['debug'];

		$serials = array();//stores all cost-related details per serial, including cost total
		$total = 0;
		$qtys = 0;
		$purch_cost = 0;

		// get all items with that PO# and optional $partid, then we'll grab all related inventory records
		if ($debug) { echo 'PO'.$order_number.'<BR>'; }
		$query = "SELECT * FROM purchase_items pi WHERE po_number = '".$order_number."' ";
		if ($partid) { $query .= "AND partid = '".$partid."' "; }
		$query .= "; ";
		$result = qdb($query) OR die(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			// grab inventory records related to the purchase item id
			$query2 = "SELECT * FROM inventory i WHERE i.purchase_item_id = '".$r['id']."'; ";
			$result2 = qdb($query2) OR die(qe().' '.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$serial = $r2['serial_no'];
				if ($debug) { echo 'Serial '.$serial.'<BR>'; }
				$qty = 1;
				// for import purposes, we want dates to be according to inventory dates; later, we just want '$now'
				// for when we capture the cost
				$inv_dt = $r2['date_created'];

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
					setCostsLog($r2['id'],$r['id'],'purchase_item_id',$ext);//,$inv_dt);

					$serials[$r2['id']]['purchase'] += $ext;
					$serials[$r2['id']]['total'] += $ext;
				}

/*
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
						$repair_cost = calcLegacyRepairCost($repair);
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
*/
				$purch_cost += ($ext+$total_repair);
			}
		}
		if ($qtys>0) { $total = round($purch_cost/$qtys,4); }

		$total_freight = 0;
		$freight_pcs = 0;
		$freight = 0;
		$query = "SELECT freight_amount, datetime, id FROM packages WHERE order_number = '".$order_number."'; ";
		$result = qdb($query) OR die(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$n = 0;//number of items on this PO/partid for this package
			$pkg_dt = $r['datetime'];

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
					setCostsLog($r2['serialid'],$r['id'],'packageid',$freight_per);//,$pkg_dt);
					$serials[$r2['serialid']]['freight'] += $freight_per;
					$serials[$r2['serialid']]['total'] += $freight_per;
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

			// insert adjustment to log, and set the actual cost with consideration to the adjustment
			if ($r['b_cost']>0 AND $r['b_cost']<>$actual) {
				echo $r['serial'].'<BR>';
				$corr = $r['b_cost']-$actual;
				$actual += $corr;
				setCostsLog($invid,false,'import adjustment');//,$corr);
			}

			$query3 = "DELETE FROM inventory_costs WHERE inventoryid = '".$invid."'; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			if ($debug==1) { echo $query3.'<BR>'; }

			$query3 = "REPLACE inventory_costs (inventoryid, datetime, actual, average) ";
			$query3 .= "VALUES ('".$invid."','".$GLOBALS['now']."','".$actual."','".$average."'); ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			if ($debug==1) { echo $query3.'<BR>'; }
		}

		return ($total);
	}
?>
