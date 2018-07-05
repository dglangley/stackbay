<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';

	$DEBUG = 3;

	// Get all the invoices with an order_type repair after the sepcified date of May 1, 2017 or 5/1/17
	$query = "SELECT i.invoice_no, ri.*, sc.cogs_avg FROM invoices i, repair_items ri, sales_cogs sc ";
	// Join invoices to the respective ro_number on repair_items
	$query .= "WHERE i.date_invoiced >= '2017-05-01' AND i.order_type = 'Repair' AND i.order_number = ri.ro_number ";
	// Attempt to join the repair_item_id to a respective sales_cog with the correct item_label
	// Also make sure the cogs_avg is actually set and is greater than 0
	$query .= "AND sc.item_id = ri.id AND sc.item_id_label = 'repair_item_id' AND sc.cogs_avg > 0;";
	$result = qedb($query);

	// Results produce all invoice with repair items that have a valid sales cog attached to it
	// The rest are ignored
	while($r = qrow($result)) {

		// print "<pre>" . print_r($r, true) . "</pre>";

		// // Set all the variables used in setJournalEntry()
		// // For repair cogs it debits r cogs and credits components inv asset
		$debit_account = 'Repair COGS';
		$credit_account = 'Component Inventory Asset';

		$AMOUNT = $r['cogs_avg'];
		$TRANS_NUM = $r['invoice_no'];
		$trans_type='invoice';

		$memo = 'COGS for Invoice #'.$TRANS_NUM;

		setJournalEntry(false,$GLOBALS['now'],$debit_account,$credit_account,$memo,$AMOUNT,$TRANS_NUM,$trans_type,$confirmed=false,$confirmed_by=false);
	}

	echo '<strong>COMPLETED</strong>';
