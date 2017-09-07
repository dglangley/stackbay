<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/import_aid.php';

	$rma = 0;
	if (isset($_REQUEST['rma'])) { $rma = trim($_REQUEST['rma']); }

	if (! $rma) { die("Missing RMA!"); }

	$query = "SELECT * FROM inventory_rmaticket r WHERE master_id = '".res($rma,'PIPE')."'; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	if (mysqli_num_rows($result)==0) {
		die("RMA record is invalid");
	}
	$r = mysqli_fetch_assoc($result);
	if (! $r['vendor_rma'] OR ! $r['vendor_orig_po'] OR ! $r['vendor_return']) {
		die("This is not a Vendor Return!");
	}

	$query2 = "SELECT * FROM inventory_solditem si WHERE id = '".$r['item_id']."' AND po = '".$r['vendor_orig_po']."'; ";
	$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	if (mysqli_num_rows($result2)==0) {
		die("Sold Item table does not have this PO for this item");
	}
	$r2 = mysqli_fetch_assoc($result2);
	if ($r2['returned_to_vendor']<>1 OR $r2['so_id'] OR $r2['repair_id']) {
		die("Sold Item table does not think this is an RTV");
	}

	$po_number = $r['vendor_orig_po'];
	$date = $r['vendor_shipped_date'];
	$tracking_no = $r['vendorout_tracking_no'];
	$freight_acct = $r['vendor_ship_acct'];
	$ship_to = $r['vendor_ship_to'];

	$vendor_rma = $r['vendor_rma'];
	$reason = $r['reason'];
	$action_id = $r['action_id'];//our disposition, 3=credit
	$status_id = $r['status_id'];//should be 5 = complete

	$serial_no = $r2['serial'];
	echo 'Serial No. '.$serial_no.'<BR>';
	$inventory_id = $r2['inventory_id'];

	$partid = translateID($inventory_id);
	echo 'Partid '.$partid.'<BR>';

	$query = "SELECT * FROM inventory WHERE partid = $partid AND serial_no = '".res($serial_no)."'; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==0) {
		die("Could not find our inventory record");
	}
	$r = mysqli_fetch_assoc($result);
	$inventoryid = $r['id'];

	if ($r['sales_item_id'] OR $r['repair_item_id']) {
		die("Found a sales/repair item id");
	}

	if (! $r['returns_item_id']) {
		die("No returns item id");
	} else if (! $r['purchase_item_id']) {
		die("No purchase item id");
	}

	$query2 = "SELECT po_number, price FROM purchase_items WHERE id = '".$r['purchase_item_id']."'; ";
	$result2 = qdb($query2) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result2)==0) {
		echo $query2.'<BR>';
		die("No purchase items record");
	}
	$r2 = mysqli_fetch_assoc($result2);
	$price = $r2['price'];
	if ($po_number<>$r2['po_number']) {
		die("Our PO (".$r2['po_number'].") doesn't match BDB PO (".$po_number.")");
	}
	echo 'PO '.$r2['po_number'].'<BR>';

	$query3 = "SELECT companyid FROM purchase_orders WHERE po_number = '".$po_number."'; ";
	$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
	if (mysqli_num_rows($result3)==0) {
		die("No Purchase Orders record for ".$po_number);
	}
	$r3 = mysqli_fetch_assoc($result3);
	$companyid = $r3['companyid'];

	$query3 = "INSERT INTO sales_orders (created, created_by, sales_rep_id, companyid, contactid, cust_ref, ref_ln, ";
	$query3 .= "bill_to_id, ship_to_id, freight_carrier_id, freight_services_id, freight_account_id, termsid, public_notes, private_notes, status) ";
	$query3 .= "VALUES ('".$date." 00:00:00', NULL, NULL, $companyid, NULL, '".trim($vendor_rma)."', NULL, NULL, NULL, NULL, NULL, NULL, 15, ";
	$query3 .= "'".res($ship_to)."', 'RTV from PO# ".$po_number.", was RMA ".$rma.chr(10).$reason.chr(10).$freight_acct."', 'Active'); ";
	$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>';
	$so_number = 999999;
	$so_number = qid();

	$query3 = "INSERT INTO sales_items (partid, so_number, line_number, qty, qty_shipped, price, delivery_date, ship_date, ";
	$query3 .= "ref_1, ref_1_label, ref_2, ref_2_label, warranty, conditionid) ";
	$query3 .= "VALUES ('".$partid."', '".$so_number."', NULL, 1, 1, '0.00', '".$date."', '".$date."', ";
	$query3 .= "'".$r['purchase_item_id']."', 'purchase_item_id', NULL, NULL, 14, 2); ";
	$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>';

	$query3 = "INSERT INTO packages (weight, length, width, height, order_number, order_type, package_no, tracking_no, datetime, freight_amount) ";
	$query3 .= "VALUES (NULL, NULL, NULL, NULL, '".$so_number."', 'Sale', 1, '".$tracking_no."', '".$date." 00:00:00', NULL); ";
	$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>';
	$packageid = qid();

	$query3 = "INSERT INTO package_contents (packageid, serialid) ";
	$query3 .= "VALUES ('".$packageid."', '".$inventoryid."'); ";
	$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>';

	$query3 = "UPDATE inventory SET returns_item_id = NULL WHERE id = '".$inventoryid."'; ";
	$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>';

	$query3 = "DELETE FROM return_items WHERE rma_number = '".$rma."'; ";
	$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>';

	$query3 = "DELETE FROM returns WHERE rma_number = '".$rma."'; ";
	$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>';

	// create purchase credits record
	if ($action_id==3) {
		$query3 = "INSERT INTO purchase_credits (po_number, companyid, memo, date_created, repid) ";
		$query3 .= "VALUES ('".$po_number."', '".$companyid."', '".res($reason)."', '".$date." 00:00:00', NULL); ";
		$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>';
		$pcid = qid();

		$query3 = "INSERT INTO purchase_credit_items (pcid, purchase_item_id, qty, amount) ";
		$query3 .= "VALUES ('".$pcid."', '".$r['purchase_item_id']."', '1', '".$price."'); ";
		$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>';
	}
?>
