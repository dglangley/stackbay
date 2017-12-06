<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCogs.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCommission.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInvoiceCOGS.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$debug = 1;

	$sum_costs = 0;
	$query = "SELECT i.id FROM purchase_items pi, inventory i
LEFT JOIN inventory_costs c ON i.id = c.inventoryid
WHERE pi.id >= 5711 AND pi.id <= 6026 AND i.purchase_item_id = pi.id
/*AND serial_no IS NULL*/ AND i.status = 'installed'
AND c.inventoryid IS NULL;";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$inventoryid = $r['id'];
		echo $inventoryid.' id<BR>';

		$query2 = "SELECT * FROM sales_cogs WHERE inventoryid = '".res($inventoryid)."'; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
//		if (mysqli_num_rows($result2)==0) { echo $r['status'].'<BR>'; continue; }

		$query2 = "SELECT * FROM repair_components WHERE invid = '".res($inventoryid)."'; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)==0) {
			die("Could not find pulled record");
		}
		$r2 = mysqli_fetch_assoc($result2);
		$repair_item_id = $r2['item_id'];
		if ($repair_item_id==3661 OR $repair_item_id==3678) { continue; }

		setCost($inventoryid);
		$cogs = $DEBUG_COST;
		$sum_costs += $cogs;
		$DEBUG_COST = 0;

		$repair_invid = 0;
		$query3 = "SELECT id FROM inventory WHERE repair_item_id = '".res($repair_item_id)."'; ";
		$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
		if (mysqli_num_rows($result3)>0) {
			$r3 = mysqli_fetch_assoc($result3);
			$repair_invid = $r3['id'];
		}

		// if already sold and shipped, then we will have an associated invoice, which we need for updating commissions
		$query3 = "SELECT invoice_no, invoice_item_id FROM commissions WHERE inventoryid = '".$repair_invid."' ";
		$query3 .= "AND item_id = '".$repair_item_id."' AND item_id_label = 'repair_item_id' ";//AND cogsid = '".$cogsid."' ";
		$query3 .= "GROUP BY invoice_no, invoice_item_id; ";
		$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);

		// was not invoiced previously - maybe this was an internal or RMA repair?
		if (mysqli_num_rows($result3)==0) {
			// get associated RMA
			$query4 = "SELECT i.invid, r.order_number, r.order_type FROM repair_items i, return_items ri, returns r ";
			$query4 .= "WHERE i.id = '".res($repair_item_id)."' AND i.invid = ri.inventoryid AND ri.rma_number = r.rma_number; ";
			$result4 = qdb($query4) OR die(qe().'<BR>'.$query4);
			// yes, RMA indeed
			if (mysqli_num_rows($result4)>0) {
				$r4 = mysqli_fetch_assoc($result4);
				$invid = $r4['invid'];
				// was this originally a sale, or a repair? reverse comms on original comms
				$T = order_type($r4['order_type']);

				$query4 = "SELECT items.id FROM ".$T['items']." items, inventory i ";
				$query4 .= "WHERE i.id = '".res($invid)."' AND items.".$T['order']." = '".res($r4['order_number'])."'; ";
				$result4 = qdb($query4) OR die(qe().'<BR>'.$query4);
				if (mysqli_num_rows($result4)==0) {
					die("Invalid original ".$r4['order_type']." ".$r4['order_number']);
				}
				$r4 = mysqli_fetch_assoc($result4);

				$existing_cogs = 0;
				$query2 = "SELECT * FROM sales_cogs ";
				$query2 .= "WHERE inventoryid = '".$invid."' AND item_id = '".res($r4['id'])."' AND item_id_label = '".$T['item_label']."' AND cogs_avg > 0; ";
echo $query2.'<BR>';
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$existing_cogs = $r2['cogs_avg'];
				}
				$cogs += $existing_cogs;

				$cogsid = setCogs($invid, $r4['id'], $T['item_label'], $cogs);
			} else {//no RMA
echo $query3."<BR>";
			}
		} else {//yes, invoiced so billable
			$existing_cogs = 0;
			$query2 = "SELECT * FROM sales_cogs ";
			$query2 .= "WHERE inventoryid = '".$repair_invid."' AND item_id = '".res($repair_item_id)."' AND item_id_label = 'repair_item_id' AND cogs_avg > 0; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$existing_cogs = $r2['cogs_avg'];
			}
			$cogs += $existing_cogs;

			$cogsid = setCogs($repair_invid, $repair_item_id, 'repair_item_id', $cogs);

			while ($r3 = mysqli_fetch_assoc($result3)) {
				// this function should be called every time COGS is updated after invoicing!
				setCommission($r3['invoice_no'],$r3['invoice_item_id'],$repair_invid);
			}
		}

		// get all related invoices and update COGS to Journal Entries
		$query2 = "SELECT i.invoice_no, i.order_type ";
		$query2 .= "FROM invoices i, invoice_items ii, invoice_shipments s, package_contents pc ";
		$query2 .= "WHERE pc.serialid = '".$repair_invid."' AND i.invoice_no = ii.invoice_no ";
		$query2 .= "AND s.invoice_item_id = ii.id AND pc.packageid = s.packageid ";
		$query2 .= "GROUP BY i.invoice_no; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
echo $query2.'<BR>';
		while ($r2 = mysqli_fetch_assoc($result2)) {
//			setInvoiceCOGS($r2['invoice_no'],$order_type);
		}
echo '<BR><BR>';
continue;
	}

	echo $sum_costs.'<BR>';
?>
