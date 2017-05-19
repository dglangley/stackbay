<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRepairCost.php';

	function calcCost($order_number,$partid=0) {
if ($GLOBALS['U']['id']<>1) { return 0; }
		$total = 0;
		$qtys = 0;
		$purch_cost = 0;
echo 'PO'.$order_number.'<BR>';
		$query = "SELECT * FROM purchase_items pi WHERE po_number = '".$order_number."' ";
		if ($partid) { $query .= "AND partid = '".$partid."' "; }
		$query .= "; ";
		$result = qdb($query) OR die(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$query2 = "SELECT * FROM inventory i WHERE i.purchase_item_id = '".$r['id']."'; ";
			$result2 = qdb($query2) OR die(qe().' '.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$qty = 1;
				if (! $r2['serial_no'] AND $r2['qty']>1) { $qty = $r2['qty']; }
				$qtys += $qty;
				$ext = ($qty*$r['price']);

				$total_repair = 0;
				// get repair costs (currently from old db, but this will change)
				if ($r2['serial_no'] AND $r2['serial_no']<>'000') {
					$query3 = "SELECT r.ticket_number, r.inventory_id, s.repair_id, s.id item_id ";
					$query3 .= "FROM inventory_repair r, inventory_solditem s ";
					$query3 .= "WHERE r.serials = '".$r2['serial_no']."' AND r.ticket_number = s.repair_id; ";
					$result3 = qdb($query3,'PIPE') OR die(qe('PIPE').'<BR>'.$query3);
					while ($r3 = mysqli_fetch_assoc($result3)) {
						$repair = array(
							'serial'=>$r2['serial_no'],
							'repair_id'=>$r3['ticket_number'],
							'inventory_id'=>$r3['inventory_id'],
							'id'=>$r3['item_id'],
						);
						$repair_cost = calcRepairCost($repair);
echo 'Repair '.$r3['ticket_number'].' cost: '.$repair_cost.'<BR>';
						$total_repair += $repair_cost;
					}
				}
				$purch_cost += ($ext+$total_repair);
echo $qty.' * '.$r['price'].' + '.$total_repair.' = '.$ext.'<BR>';
			}
		}
		$total = round($purch_cost/$qtys,4);

		$total_freight = 0;
		$freight_pcs = 0;
		$freight = 0;
		$query = "SELECT freight_amount, id FROM packages WHERE order_number = '".$order_number."'; ";
		$result = qdb($query) OR die(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$n = 0;//number of items on this PO/partid for this package

			$query2 = "SELECT COUNT(package_contents.id) n FROM package_contents ";
			// if $partid is passed in, get only the packages related to the part
			if ($partid) { $query2 .= ", inventory "; }
			$query2 .= "WHERE packageid = '".$r['id']."' ";
			if ($partid) { $query2 .= "AND package_contents.serialid = inventory.id AND inventory.partid = '".$partid."' "; }
			$query2 .= "; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$n = $r2['n'];
			}

			// count the freight only if we're getting 'n' results from query above
			// (if $partid, this part is in box; if not, there are pieces at all on this PO in this box)
			if ($n>0) {
				$total_freight += $r['freight_amount'];
				$freight_pcs += $r2['n'];
			}
		}
		// if no specific pieces in package_contents, assume the freight found above is for the entire PO
		if ($total_freight>0 AND $freight_pcs==0) { $freight_pcs = $qtys; }
		if ($freight_pcs>0) {
			$freight = round($total_freight/$freight_pcs,4);
			echo 'Freight: '.$total_freight.' ('.$freight.')<BR>';
		}

		$total += $freight;

		return ($total);
	}
?>
