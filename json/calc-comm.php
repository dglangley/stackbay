<?php
	$NO_CACHE = true;
	include_once '../inc/dbconnect.php';
	include_once '../inc/setCommission.php';
	header("Content-Type: application/json", true);

	function reportError($err) {
		echo json_encode(array('message'=>$err));
		exit;
	}

	$invoice = 0;
	$invoice_item_id = 0;
	$inventoryid = 0;
	$repid = 0;
	if (isset($_REQUEST['invoice']) AND is_numeric($_REQUEST['invoice']) AND $_REQUEST['invoice']>0) { $invoice = $_REQUEST['invoice']; }
	if (isset($_REQUEST['invoice_item_id']) AND is_numeric($_REQUEST['invoice_item_id']) AND $_REQUEST['invoice_item_id']>0) { $invoice_item_id = $_REQUEST['invoice_item_id']; }
	if (isset($_REQUEST['inventoryid']) AND is_numeric($_REQUEST['inventoryid']) AND $_REQUEST['inventoryid']>0) { $inventoryid = $_REQUEST['inventoryid']; }
	if (isset($_REQUEST['repid']) AND is_numeric($_REQUEST['repid']) AND $_REQUEST['repid']>0) { $repid = $_REQUEST['repid']; }

	if (! $invoice OR ! $inventoryid OR ! $repid) {
		reportError("Missing invoice or inventoryid or repid!");
	}

	// get invoice item id to ensure data is valid
	$query = "SELECT ii.id FROM invoices i, invoice_items ii, invoice_shipments s, package_contents pc ";
	$query .= "WHERE i.invoice_no = '".res($invoice)."' AND i.invoice_no = ii.invoice_no AND ii.id = s.invoice_item_id ";
	$query .= "AND s.invoice_item_id = '".res($invoice_item_id)."' AND s.packageid = pc.packageid AND pc.serialid = '".res($inventoryid)."'; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==0) {
		reportError('Inventory ID "'.$inventoryid.'" could not be found on the shipment for this Invoice "'.$invoice.'"!'.chr(10).$query);
	}

	$debug = 0;
	$comm_err = setCommission($invoice,$invoice_item_id,$inventoryid,$repid);

	if ($comm_err) {
		echo json_encode(array('message'=>$comm_err));
	} else {
		echo json_encode(array('message'=>'Success'));
	}
	exit;
?>
