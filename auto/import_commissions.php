<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/import_aid.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setProfits.php';

	/*****
		Brian's commissions table fields:
		invoice_no
		pinvoice_id (completely unused, possibly 'purchase_invoice_id'?)
		rep_id
		amount
		paid
		sold_item_id
		repair_id
		ticket_id (?)
		sinvoice_id (sales_invoice_id)
		canceled
		approved
		paid_date
		source_id (see `inventory_commissionsource`)
		orig_amount (used only once for id '83383')
		adjusted (see orig_amount, same here)
		orig_rep_id (see orig_amount, same here)
	*****/

	/*****
		Our commissions tables:

		Commissions is amount to be paid
		invoice_no
		invoice_item_id
		inventoryid
		datetime
		cogs
		profit
		rep_id
		commission_rate
		commission_amount
		id

		Commissions_payouts is what has been paid (based on commissionid)
		commissionid
		paid_date
		amount
		userid
		id
	*****/

	$sold_items = array();
	$invoice_items = array();
	$serials = array();
	$query = "SELECT c.id, invoice_id, pinvoice_id, rep_id, amount, paid, sold_item_id, repair_id, ticket_id, sinvoice_id, ";
	$query .= "canceled, approved, paid_date, source_id, orig_amount, adjusted, orig_rep_id, name ";
	$query .= "FROM inventory_commission c, inventory_commissionsource s ";
	// these conditions are non-negotiable
	$query .= "WHERE c.source_id = s.id AND (orig_amount <> 0 OR amount <> 0) ";

	$query .= "AND canceled = '0' AND (approved = '1' OR paid_date IS NOT NULL) ";
//	$query .= "AND canceled = '0' AND approved = '0' ";//AND (approved = '1' OR paid_date IS NOT NULL) ";
$query .= "AND repair_id IS NULL AND ticket_id IS NULL AND sold_item_id IS NOT NULL ";
//	$query .= "LIMIT 0,1000 ";
	$query .= "; ";
echo $query.'<BR>';
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
//		print "<pre>".print_r($r,true)."</pre>";
		$invoice = $r['invoice_id'];
		if (! isset($INVOICES[$r['invoice_id']])) {
			$query2 = "SELECT date FROM inventory_invoice WHERE id = '".$r['invoice_id']."'; ";
			$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$INVOICES[$r['invoice_id']] = "'".$r2['date']." 15:00:00'";
			}
		}
		if (isset($INVOICES[$r['invoice_id']])) {
			$comm_date = $INVOICES[$r['invoice_id']];
		} else {
			$comm_date = 'NULL';
		}
		$paid_date = $r['paid_date'];
		if ($paid_date) { $paid_date = "'".$paid_date." 09:00:00'"; }
		else { $paid_date = 'NULL'; }
		$rep_id = mapUser($r['rep_id']);
		$inventoryid = 0;

		// BDB data points
		$serial = '';
		$inventory_id = 0;
		$avg_cost = 0;
		$sale_price = 0;
		if (! $r['sold_item_id']) {
die("Wait what happened here?!");
continue;
		} else {
			if (! isset($sold_items[$r['sold_item_id']])) {
				$sold_items[$r['sold_item_id']] = array('serial'=>'');

				$query2 = "SELECT serial, inventory_id, avg_cost, price FROM inventory_solditem WHERE id = '".$r['sold_item_id']."'; ";
				$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
				if (mysqli_num_rows($result2)==0) { continue; }//need to resolve these missing records, by my count there are 14 on 6/7/17
				$r2 = mysqli_fetch_assoc($result2);
				$serial = trim($r2['serial']);
				$inventory_id = $r2['inventory_id'];
				$avg_cost = $r2['avg_cost'];
				$sale_price = $r2['price'];

				$sold_items[$r['sold_item_id']] = array('serial'=>$serial,'inventory_id'=>$r2['inventory_id'],'avg_cost'=>$avg_cost,'sale_price'=>$sale_price);
			} else {
				$serial = $sold_items[$r['sold_item_id']]['serial'];
				$inventory_id = $sold_items[$r['sold_item_id']]['inventory_id'];
				$avg_cost = $sold_items[$r['sold_item_id']]['avg_cost'];
				$sale_price = $sold_items[$r['sold_item_id']]['sale_price'];
			}

			if ($serial=='000' OR $serial=='0') {
				if (! isset($serials[$inventory_id])) { $serials[$inventory_id] = array(); }
				$partid = translateID($inventory_id);

				// get VTL serial in our db from PO#, possibly cost, and other related terms
				$query2 = "SELECT id FROM inventory i WHERE serial_no LIKE 'VTL%' AND partid = '$partid' ";
				if (count($serials[$inventory_id])>0) {
					$csv_ids = '';
					foreach ($serials[$inventory_id] as $id) {
						if ($csv_ids) { $csv_ids .= ','; }
						$csv_ids .= $id;
					}
					$query2 .= "AND id NOT IN (".$csv_ids.") ";
				}
				$query2 .= "; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$inventoryid = $r2['id'];
					$serials[$inventory_id][] = $inventoryid;
				}
			} else {
/*
				$query2 = "SELECT * FROM inventory i, package_contents pc, packages p, invoice_shipments is, invoice_items ii ";
				$query2 = "WHERE serial = '".res($serial)."' AND i.id = pc.serialid AND pc.packageid = ";
*/
				$partid = 0;
/*
				if ($serial = 'TBD' OR $serial = 'NA') {
					$partid = translateID($inventory_id);
				}
*/
				$query2 = "SELECT id FROM inventory WHERE serial_no = '".$serial."' ";
				if ($partid) { $query2 .= "AND partid = '".$partid."' "; }
				$query2 .= "; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$inventoryid = "'".$r2['id']."'";
				}
			}
		}

		$sales_item_id = 0;
		if ($inventoryid) {
			$query2 = "SELECT si.id FROM inventory i, inventory_history h, sales_items si ";
			$query2 .= "WHERE si.so_number = '".$r2['so_id']."' AND si.id = h.value AND h.field_changed = 'sales_item_id' ";
			$query2 .= "AND h.invid = i.id AND i.id = $inventoryid; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$sales_item_id = "'".$r2['id']."'";
			}
		}

		$cogs = 0;
		if ($avg_cost>0) {
			$cogs = "'".$avg_cost."'";
			$profit = "'".($sale_price-$avg_cost)."'";
		} else {
			$profit = "'".$sale_price."'";
		}

		$profitid = 0;
		$commissionid = 0;

		if ($sales_item_id OR $inventoryid) {
			$profitid = setProfits($inventoryid,$sales_item_id,$cogs,$profit);
echo $serial.' '.$query2.'<BR>';
		}

		if (! $inventoryid) { $inventoryid = 'NULL'; }
		$query2 = "INSERT INTO commissions (invoice_no, invoice_item_id, inventoryid, datetime, ";
		$query2 .= "rep_id, commission_rate, commission_amount) ";
		$query2 .= "VALUES ('$invoice', NULL, $inventoryid, $comm_date, ";
		$query2 .= "'$rep_id', NULL, '".$r['amount']."'); ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		$commissionid = qid();
echo $query2.'<BR>';

		if (! $r['canceled'] AND $paid_date<>'NULL') {
			$query2 = "INSERT INTO commission_payouts (commissionid, paid_date, amount, userid) ";
			$query2 .= "VALUES ('$commissionid', $paid_date, '".$r['amount']."', '".$rep_id."'); ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
echo $query2.'<BR>';
		}
continue;

		$serial = '';
		if ($r['sold_item_id']) {
			$query2 = "SELECT serial FROM inventory_solditem WHERE id = '".$r['sold_item_id']."'; ";
			$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
			if (mysqli_num_rows($result2)==0) { continue; }//need to resolve these missing records, by my count there are 14 on 6/7/17
			$r2 = mysqli_fetch_assoc($result2);
			$serial = trim($r2['serial']);

			if ($serial=='000' OR $serial = 'TBD' OR $serial = 'NA' OR $serial = '0') {
			} else {
				$query2 = "SELECT * FROM inventory i, package_contents pc, packages p, invoice_shipments is, invoice_items ii ";
				$query2 = "WHERE serial = '".res($serial)."' AND i.id = pc.serialid AND pc.packageid = ";
			}

			$query2 = "SELECT memo FROM inventory_invoiceli WHERE invoice_id = '$invoice'; ";
			$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {

				if (! isset($invoice_items[$invoice.'|'.$r2['memo']])) {
					$invoice_items[$invoice.'|'.$r2['memo']] = array();

					$query3 = "SELECT * FROM invoice_items WHERE invoice_no = '$invoice' AND memo = '".res($r2['memo'])."'; ";
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					if (mysqli_num_rows($result3)==0) {
						echo $invoice.' '.$r2['memo'].'<BR>';
						continue;
					}
					$r3 = mysqli_fetch_assoc($result3);
					$invoice_items[$invoice.'|'.$r2['memo']] = $r3;
				}
			}
continue;
echo $invoice.' '.$serial.'<BR>';
		}
	}
?>
