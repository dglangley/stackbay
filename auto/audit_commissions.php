<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRep.php';

	/*****
		Commissions table fields:
		invoice_no
		rep_id
		amount
		paid
		sold_item_id
		repair_id
		ticket_id (?)
		sinvoice_id
		canceled
		approved
		paid_date
		source_id (see `inventory_commissionsource`)
		orig_amount (used only once for id '83383')
		adjusted (see orig_amount, same here)
		orig_rep_id (see orig_amount, same here)
	*****/

	$RATES = array();
	$query = "SELECT id, commission_rate FROM users WHERE commission_rate > 0; ";
	$result = qdb($query) OR die(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$RATES[$r['id']] = $r['commission_rate'];
	}

	$discrepancy = 0;
	$query = "SELECT i.*, ii.*, ii.id invoice_item_id FROM invoices i, invoice_items ii, payment_details p ";
	$query .= "WHERE i.invoice_no = ii.invoice_no AND p.order_number = i.invoice_no AND p.order_type = 'Invoice' ";
$query .= "AND i.order_type = 'Sale' AND i.date_invoiced >= '2016-03-01 00:00:00' ";
//$query .= "AND i.invoice_no = 17760 ";
	$query .= "ORDER BY i.date_invoiced DESC; ";
echo $query.'<BR>';
	$result = qdb($query) OR die(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$invoice_no = $r['invoice_no'];
		$invoice_item_id = $r['invoice_item_id'];
		$inv_amount = $r['qty']*$r['amount'];
		$avg_cogs = 0;
		$actual_cogs = 0;

		echo '<span style="color:gray">Invoice# '.$invoice_no.', partid '.$r['partid'].', $'.$inv_amount.' line total</span><br>';

$valid_so = true;
		$query2 = "SELECT so.termsid, i.id inventoryid, serial_no ";
		$query2 .= "FROM sales_orders so, sales_items si, packages p, package_contents c, inventory i, invoice_shipments s ";
		$query2 .= "WHERE so.so_number = ".$r['order_number']." AND si.partid = '".$r['partid']."' ";
		$query2 .= "AND so.so_number = si.so_number AND si.partid = i.partid ";
		$query2 .= "AND si.so_number = p.order_number AND p.id = c.packageid AND c.serialid = i.id ";
		$query2 .= "AND s.packageid = p.id AND s.invoice_item_id = '".$invoice_item_id."'; ";
		$result2 = qdb($query2) OR die(qe().' '.$query2);
//echo '<span style="color:gray">'.$query2.'</span><BR>';
		while ($r2 = mysqli_fetch_assoc($result2)) {
//if ($r2['termsid']<>12) { $valid_so = false; break; }
			$avg_cost = 0;
			$actual_cost = 0;
			$query3 = "SELECT MAX(average) avg_cost, MAX(actual) actual_cost ";
			$query3 .= "FROM inventory_costs WHERE inventoryid = ".$r2['inventoryid']."; ";
			$result3 = qdb($query3) OR die(qe().' '.$query3);
//echo '<span style="color:gray">'.$query3.'</span><BR>';
			if (mysqli_num_rows($result3)>0) {
				$r3 = mysqli_fetch_assoc($result3);
				$avg_cost = $r3['avg_cost'];
				$actual_cost = $r3['actual_cost'];
			}

			$returned = false;
			$query3 = "SELECT * FROM return_items ri, returns r ";
			$query3 .= "WHERE inventoryid = '".$r2['inventoryid']."' AND r.order_number = '".$r['order_number']."' AND r.rma_number = ri.rma_number; ";
			$result3 = qdb($query3) OR die(qe().' '.$query3);
			if (mysqli_num_rows($result3)>0) {
echo '<span style="color:gray">'.$query3.'</span><BR>';
				$returned = true;
			}

			if ($avg_cost==0 OR $avg_cost=='0.0000') {
				$avg_cost += $actual_cost;
			}
			$avg_cogs += $avg_cost;
			$actual_cogs += $actual_cost;

echo '<span style="color:gray"> &nbsp; '.$r2['serial_no'].' (invid '.$r2['inventoryid'].') '.$avg_cost.' avg cost = '.($r['amount']-$avg_cost).' profit, '.
	$actual_cost.' actual cost = '.($r['amount']-$actual_cost).' profit, '.
	($returned?"returned sale":" good sale").'</span><br>';

			$net_avg = ($r['amount']-$avg_cost);
			if ($net_avg<0) { $net_avg = 0; }
			$net_actual = ($r['amount']-$actual_cost);
			if ($net_actual<0) { $net_actual = 0; }

			foreach ($RATES as $userid => $rate) {
				$pct = $rate/100;
				$final_amt = round($net_avg*$pct,2);
				if ($returned) { $final_amt = 0; }

				$briandb_comm = 0;
				$comm_date = '0000-00-00 00:00:00';

				$query3 = "SELECT c.amount, paid_date FROM inventory_commission c, inventory_solditem s ";
				$query3 .= "WHERE c.invoice_id = ".$invoice_no." AND c.sold_item_id = s.id AND price = '".$r['amount']."' ";
				if (substr($r2['serial_no'],0,3)<>'VTL') { $query3 .= "AND serial = '".$r2['serial_no']."' "; }
				$query3 .= "AND c.canceled = 0 AND c.rep_id = ";
				if ($userid==1) { $query3 .= "18 "; }
				else if ($userid==2) { $query3 .= "2 "; }
				$query3 .= "; ";
				$result3 = qdb($query3,'PIPE') OR die(qe('PIPE').' '.$query3);
//				if (mysqli_num_rows($result3)>0) {
//					$r3 = mysqli_fetch_assoc($result3);
				while ($r3 = mysqli_fetch_assoc($result3)) {
					$briandb_comm = round($r3['amount'],2);
					$comm_date = $r3['paid_date'].' 00:00:00';

					$query4 = "INSERT INTO commissions (invoice_no, invoice_item_id, inventoryid, datetime, cogs, ";
					$query4 .= "profit, rep_id, commission_rate, commission_amount) ";
					$query4 .= "VALUES ('".$invoice_no."','".$invoice_item_id."','".$r2['inventoryid']."','".$comm_date."','".$avg_cost."',";
					$query4 .= "'".($r['amount']-$avg_cost)."','".$userid."','".$rate."','".$briandb_comm."'); ";
echo $query.'<BR>';
					$result4 = qdb($query4) OR die(qe().' '.$query4);
				}

				if ($final_amt<>$briandb_comm) {
echo '<span style="color:gray">'.$query3.'</span><BR>';
if ($userid==1) { $discrepancy += $final_amt-$briandb_comm; }
					echo ' &nbsp; '.getRep($userid).' '.$rate.'% = '.$final_amt.' on avg, '.($net_actual*$pct).' on actual, '.$briandb_comm.' Brians calcd comm, <strong>'.($final_amt-$briandb_comm).'</strong><BR>';
				}
			}
		}

if ($valid_so===false) { continue; }
/*
			' &nbsp; '.$avg_cogs.' avg cogs = '.$net_avg.' net profit on avg<br>'.
			' &nbsp; '.$actual_cogs.' actual cogs = '.$net_actual.' net profit on actual<br></span>';
*/
	}

	echo 'Final discrepancy: '.$discrepancy.'<BR>';
?>
