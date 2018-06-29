<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';

	$DEBUG = 3;

	function retrieveInvoice($order_type, $order_number, $inventoryid, $partid) {
		global $TRANS_NUM;
		// Dig for the invoice number from the package content (Also check and make sure the package is attached to the correct order) (serialid) -> invoice shipment (packageid) -> invoice_items (invoice_item_id -> invoice #)
		$query = "SELECT i.*, ii.*, pc.packageid FROM packages p, package_contents pc, invoice_shipments s, invoice_items ii, invoices i ";
		$query .= "WHERE pc.packageid = p.id AND p.order_type =  ".fres($order_type)." AND p.order_number = ".fres($order_number)." AND pc.serialid = ".fres($inventoryid)." AND pc.packageid = s.packageid AND s.invoice_item_id = ii.id AND i.invoice_no = ii.invoice_no AND ii.item_id = ".res($partid)." AND ii.item_label = 'partid';";
		$result = qedb($query);

		if(qnum($result) > 1) {
			// echo '<strong>Multi Line Invoice Found</strong><BR>';
			while($r = qrow($result)) {
				// print '<pre>' . print_r($r, true) . '</pre>';

				if($r['invoice_no'] == 17102) {
					$TRANS_NUM = $r['invoice_no'];
				}
			}

			// echo '<BR><BR>';
		}

		if(qnum($result) == 1) {
			$r = qrow($result);
			// echo '<strong>Invoice Info</strong><BR>';
			// print '<pre>' . print_r($r, true) . '</pre>';
			// echo '<BR>';

			$TRANS_NUM = $r['invoice_no'];
		}

		if(qnum($result) == 0) {
			fixInvoice($order_type, $order_number, $inventoryid, $partid);
		}
	}

	function fixInvoice($order_type, $order_number, $inventoryid, $partid) {
		global $TRANS_NUM;
		// Based on a trend seems like the invoice exists for the order_number and order_type
		// Retreieve that and see if we can manipulate it to work in our favor
		$invoice_item_id = 0;
		$packageid = 0;
		$invoice_info = array();

		$query = "SELECT * FROM invoices i, invoice_items ii WHERE order_number = ".res($order_number)." AND order_type = ".fres($order_type)." AND i.invoice_no = ii.invoice_no AND ii.item_label = 'partid' AND ii.item_id = ".res($partid).";";
		$result = qedb($query);

		while($r = qrow($result)) {
			$invoice_info[] = $r;
		}

		// echo '<strong>Invoice Canidates</strong><BR>';
		// print '<pre>' . print_r($invoice_info, true) . '</pre>';
		// echo '<BR>';

		// Based on tests this hasn't happened but this is used to track if there is an invoice 
		// That fill the conditions and have multiple invoice_no records
		if(qnum($result) > 1) {
			// echo 'Multi Line Invoice Found<BR><BR>';
		}

		// No invoice found for the rma with order_number, order_type, inventoryid and partid
		if(count($invoice_info) == 0) {
			// echo 'NO CANIDATES<BR><BR>';

			if($order_type == 'Sale' AND $order_number == '15559') {
				$TRANS_NUM = 15466;
			}
		}

		// 1 Record found for the invoice so set the invoice_item_id
		if(count($invoice_info) == 1) {
			$invoice_item_id = reset($invoice_info)['id'];

			// SET the global trans number (invoice number to the record)
			$TRANS_NUM = reset($invoice_info)['invoice_no'];
		}

		$query = "SELECT * FROM packages p, package_contents pc WHERE pc.packageid = p.id AND p.order_type =  ".fres($order_type)." AND p.order_number = ".fres($order_number)." AND pc.serialid = ".fres($inventoryid).";";
		$result = qedb($query);

		if(qnum($result) == 1) {
			$r = qrow($result);
			// echo '<strong>Package Info</strong><BR>';
			// print '<pre>' . print_r($r, true) . '</pre>';
			// echo '<BR>';

			$packageid = $r['packageid'];
		}

		// IF there is a packageid and invoice_item_id found then add it into the invoice_shipments table
		if($invoice_item_id AND $packageid) {
			$query = "INSERT INTO invoice_shipments (invoice_item_id, packageid) VALUES (".res($invoice_item_id).", ".res($packageid).");";
			qedb($query);
		}
	}

	function retreiveCOGS($data, $T) {
		global $AMOUNT;
		// Get all of the sales cogs based on this inventoryid
		// Comebine it with sales items to find the sales item id
		$query = "SELECT sc.*, ref_1, ref_1_label, ref_2, ref_2_label FROM sales_cogs sc, ".$T['items']." si WHERE inventoryid = ".res($data['inventoryid'])." AND si.id = sc.item_id AND si.".$T['order']." = ".res($data['order_number'])." AND si.partid = ".res($data['partid']).";";
		$result = qedb($query);

		if(qnum($result) == 0) {
			// echo '<strong>NO Sales COGS</strong><BR>';
			// echo '<BR>';

			$item_id = 0;

			// Dig into the actual order and check if there is only 1 line item, in that case make that the item_id
			$query2 = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = ".res($data['order_number'])." AND price > 0;";
			$result2 = qedb($query2);

			if(qnum($result2) == 1) {
				$r2 = qrow($result2);
				$item_id = $r2['id'];
			} else if(qnum($result2) > 1) {
				// echo '<BR>Multiple Item Ids<BR>';
				while($r2 = qrow($result2)) {
					if($data['partid'] == $r2['partid']) {
						$item_id = $r2['id'];
						// echo 'Resolved<BR>';
						break;
					}

					// print '<pre>' . print_r($r2, true) . '</pre>';
				}
			} else if(qnum($result2) == 0) {
				// echo '<BR>Could not fetch item_id<BR>';
				echo $query2;
			}

			if($item_id) {
				//If there is no sales cogs then generate one with 0 cogs_avg etc
				$query2 = "INSERT INTO sales_cogs (inventoryid, item_id, item_id_label) VALUES (".res($data['inventoryid']).", ".res($item_id).", ".fres($T['item_label']).");";
				qedb($query2);
			}
		}

		if(qnum($result) > 1) {
			// echo '<strong>Sales COGS (Multi)</strong><BR>';
			while($r = qrow($result)) {
				// print '<pre>' . print_r($r, true) . '</pre>';
				// If multi check the items table for a return_items_id to match it
				if(($data['id'] == $r['ref_1'] AND $data['ref_1_label'] == 'return_item_id') OR ($data['id'] == $r['ref_2'] AND $data['ref_2_label'] == 'return_item_id')){
					$AMOUNT = $r['cogs_avg'];
				}
			}
			// echo '<BR>';
		}

		if(qnum($result) == 1) {
			$r = qrow($result);

			// echo '<strong>Sales COGS</strong><BR>';
			// print '<pre>' . print_r($r, true) . '</pre>';
			// echo '<BR>';

			$AMOUNT = $r['cogs_avg'];
		}
	}

	// Get all the return orders after the sepcified date of May 1, 2017 or 5/1/17
	// Receive date is based off of the inventory history with change of returns_item_id and the date it was stamped with
	$query = "SELECT r.*, ri.* FROM returns r, return_items ri, inventory_history ih WHERE ih.invid = ri.inventoryid AND ih.field_changed = 'returns_item_id' AND ri.id = ih.value  AND ih.date_changed >= '2017-05-01' AND r.rma_number = ri.rma_number AND (r.order_number <> '331123' OR r.order_type <> 'Repair');";
	$result = qedb($query);

	while($r = qrow($result)) {

		if(! $r['order_number'] OR ! $r['order_type']) {
			continue;
		} 

		$T = order_type($r['order_type']);

		// Set all the variables used in setJournalEntry()
		// In this process we are getting the items back so Ivnentory Asset Debit and COGS credit
		$debit_account = 'Inventory Asset';
		$credit_account = 'Inventory Sale COGS';

		$AMOUNT = 0;

		$memo = 'COGS for RMA Return #'.$r['rma_number'];

		$TRANS_NUM = 0;
		$trans_type='invoice';

		// echo '<strong>RMA Info</strong><BR>';
		// print '<pre>' . print_r($r, true) . '</pre>';
		// echo '<BR>';

		retrieveInvoice($r['order_type'], $r['order_number'], $r['inventoryid'], $r['partid']);

		retreiveCOGS($r, $T);

		// echo '<BR>AMOUNT: '.$AMOUNT.' INVOICE: '.$TRANS_NUM.'<BR><BR>';

		// echo $debit_account.'<BR>';
		// echo $credit_account.'<BR>';
		// echo $memo.'<BR>';
		// echo $AMOUNT.'<BR>';
		// echo $TRANS_NUM.'<BR><BR>';

		if(! $AMOUNT) {
			$AMOUNT = 0;
		}

		setJournalEntry(false,$GLOBALS['now'],$debit_account,$credit_account,$memo,$AMOUNT,$TRANS_NUM,$trans_type,$confirmed=false,$confirmed_by=false);
	}

	echo '<strong>COMPLETED</strong>';
