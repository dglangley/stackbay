<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	$bill_items = array();
	if (isset($_REQUEST['bill_items'])) { $bill_items = $_REQUEST['bill_items']; }
	$items = array();
	if (isset($_REQUEST['items'])) { $items = $_REQUEST['items']; }
	$item_labels = array();
	if (isset($_REQUEST['item_labels'])) { $item_labels = $_REQUEST['item_labels']; }
	$partids = array();
	if (isset($_REQUEST['partids'])) { $partids = $_REQUEST['partids']; }
	$qtys = array();
	if (isset($_REQUEST['qtys'])) { $qtys = $_REQUEST['qtys']; }
	$amounts = array();
	if (isset($_REQUEST['amounts'])) { $amounts = $_REQUEST['amounts']; }
	$warranties = array();
	if (isset($_REQUEST['warranties'])) { $warranties = $_REQUEST['warranties']; }
	$lns = array();
	if (isset($_REQUEST['lns'])) { $lns = $_REQUEST['lns']; }
	$inventoryid = array();
	if (isset($_REQUEST['inventoryid'])) { $inventoryid = $_REQUEST['inventoryid']; }

	$bill_no = '';
	if (isset($_REQUEST['bill_no']) AND $_REQUEST['bill_no']) { $bill_no = trim($_REQUEST['bill_no']); }
	$invoice_no = '';
	if (isset($_REQUEST['invoice_no']) AND $_REQUEST['invoice_no']) { $invoice_no = trim($_REQUEST['invoice_no']); }
	if (! $invoice_no) { die("How is there no invoice number??"); }
	$due_date = '';
	if (isset($_REQUEST['due_date']) AND $_REQUEST['due_date']) { $due_date = trim($_REQUEST['due_date']); }
	$notes = '';
	if (isset($_REQUEST['notes']) AND $_REQUEST['notes']) { $notes = trim($_REQUEST['notes']); }
	$order_number = '';
	if (isset($_REQUEST['order_number']) AND $_REQUEST['order_number']) { $order_number = trim($_REQUEST['order_number']); }
	if (! $order_number) { die("How is there no order number??"); }
	$companyid = '';
	if (isset($_REQUEST['companyid']) AND $_REQUEST['companyid']) { $companyid = trim($_REQUEST['companyid']); }
	if (! $companyid) { die("How is there no companyid??"); }

	$due_date = format_date($due_date,'Y-m-d');

	if ($bill_no) {
		$query = "UPDATE bills SET invoice_no = '".res($invoice_no)."', due_date = '".res($due_date)."' ";
		$query .= "WHERE bill_no = '".res($bill_no)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
	} else {
		$query = "REPLACE bills (invoice_no, date_created, due_date, po_number, companyid, notes, status) ";
		$query .= "VALUES ('".res($invoice_no)."','".$now."','".res($due_date)."','".res($order_number)."',";
		$query .= "'".res($companyid)."',";
		if ($notes) { $query .= "'".res($notes)."',"; } else { $query .= "NULL,"; }
		$query .= "'Completed'); ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		$bill_no = qid();
	}

	foreach ($bill_items as $i => $id) {
		$partid = 0;
		if (isset($partids[$i])) { $partid = $partids[$i]; }
		$item_id = 0;
		if (isset($items[$i])) { $item_id = $items[$i]; }
		$item_id_label = '';
		if (isset($item_labels[$i])) { $item_id_label = $item_labels[$i]; }
		if ($item_id AND ! $item_id_label) { $item_id_label = 'purchase_item_id'; }
		$qty = 0;
		if (isset($qtys[$i])) { $qty = $qtys[$i]; }
		$amount = '';
		if (isset($amounts[$i])) { $amount = $amounts[$i]; }
		$warrantyid = 0;
		if (isset($warranties[$i])) { $warrantyid = $warranties[$i]; }
		$ln = 0;
		if (isset($lns[$i])) { $ln = $lns[$i]; }

		$query = "REPLACE bill_items (bill_no, partid, memo, qty, amount, item_id, item_id_label, warranty, line_number";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES ('".$bill_no."','".$partid."',NULL,'".$qty."','".$amount."',";
		if ($item_id) { $query .= "'".res($item_id)."','".res($item_id_label)."',"; } else { $query .= "NULL,NULL,"; }
		$query .= "'".$warrantyid."',";
		if ($ln OR $ln===0) { $query .= "'".$ln."'"; } else { $query .= "NULL"; }
		if ($id) { $query .= ",'".$id."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);

		// delete before re-adding all
		if ($id) {
			$query = "DELETE FROM bill_shipments WHERE bill_item_id = '".$id."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
		} else {
			$id = qid();//from insert above
		}

		$inv = array();
		if (isset($inventoryid[$i])) { $inv = $inventoryid[$i]; }
		foreach ($inv as $invid) {
			$query = "REPLACE bill_shipments (inventoryid, packageid, bill_item_id) ";
			$query .= "VALUES ('".$invid."',NULL,'".$id."'); ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
		}
	}

	header('Location: /bill.php?bill='.$bill_no);
	exit;
?>
