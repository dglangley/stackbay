<?php
	print "<pre>".print_r($_REQUEST,true)."</pre>";
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/saveFiles.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';

	$debug = 1;

	$order_number = 0;
	$order_type = '';
	if (isset($_REQUEST['order_number']) AND $_REQUEST['order_number']) { $order_number = $_REQUEST['order_number']; }
	if (isset($_REQUEST['order_type']) AND $_REQUEST['order_type']) { $order_type = $_REQUEST['order_type']; }

	$T = order_type($order_type);

	$ref_ln = '';
	// user is replacing ref ln (uploaded order)
	if (isset($_REQUEST['order_upload']) AND $_REQUEST['order_upload']) { $ref_ln = $_REQUEST['order_upload']; }
	else if (isset($_REQUEST['ref_ln']) AND $_REQUEST['ref_ln']) { $ref_ln = $_REQUEST['ref_ln']; }

	$file_url = '';
	if (isset($_FILES) AND count($_FILES)>0 AND $_SERVER['REQUEST_METHOD'] == 'POST') {
		$file_url = saveFiles($_FILES);
	}

	$datetime = $now;
	$created_by = $U['id'];
	$sales_rep_id = $U['id'];//default unless passed in
	if ($order_number) {
		$ORDER = getOrder($order_number, $order_type);
		$datetime = $ORDER['dt'];
		$created_by = $ORDER['created_by'];
		$sales_rep_id = $ORDER['sales_rep_id'];//retain existing unless passed in below
	}
	if (isset($_REQUEST['sales_rep_id']) AND $_REQUEST['sales_rep_id']) { $sales_rep_id = $_REQUEST['sales_rep_id']; }

	$query = "REPLACE ".$T['orders']." (".$T['order'].", ".$T['datetime'].", created_by, sales_rep_id, ";
	$query .= "companyid, contactid, cust_ref, ref_ln, ".$T['addressid'].", ship_to_id, ";
	$query .= "freight_carrier_id, freight_services_id, freight_account_id, termsid, ";
	$query .= "public_notes, private_notes) ";
	$query .= "VALUES ('".res($order_number)."', '".$datetime."', ".fres($created_by).", ".fres($sales_rep_id).", ";
	$query .= "); ";
echo $query.'<BR>';

	$items = array();
	if (isset($_REQUEST['partid'])) { $items = $_REQUEST['partid']; }
	$ln = array();
	if (isset($_REQUEST['ln'])) { $ln = $_REQUEST['ln']; }
	$qty = array();
	if (isset($_REQUEST['qty'])) { $qty = $_REQUEST['qty']; }
	$amount = array();
	if (isset($_REQUEST['amount'])) { $amount = $_REQUEST['amount']; }
	$delivery_date = array();
	if (isset($_REQUEST['delivery_date'])) { $delivery_date = $_REQUEST['delivery_date']; }
	$ref_1 = array();
	if (isset($_REQUEST['ref_1'])) { $ref_1 = $_REQUEST['ref_1']; }
	$ref_1_label = array();
	if (isset($_REQUEST['ref_1_label'])) { $ref_1_label = $_REQUEST['ref_1_label']; }
	$ref_2 = array();
	if (isset($_REQUEST['ref_2'])) { $ref_2 = $_REQUEST['ref_2']; }
	$ref_2_label = array();
	if (isset($_REQUEST['ref_2_label'])) { $ref_2_label = $_REQUEST['ref_2_label']; }
	$warrantyid = array();
	if (isset($_REQUEST['warrantyid'])) { $warrantyid = $_REQUEST['warrantyid']; }
	$conditionid = array();
	if (isset($_REQUEST['conditionid'])) { $conditionid = $_REQUEST['conditionid']; }

	foreach ($items as $id => $partid) {
		$query = "REPLACE ".$T['items']." (partid, ".$T['order'].", line_number, qty, ";
		if ($T['amount']) { $query .= $T['amount'].", "; }
		if ($T['delivery_date']) { $query .= $T['delivery_date'].", "; }
		if ($T['items']<>'return_items') { $query .= "ref_1, ref_1_label, ref_2, ref_2_label, "; }
		if ($T['warranty']) { $query .= $T['warranty'].", "; }
		if ($T['condition']) { $query .= $T['condition']." "; }
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES ('".res($partid)."', '".res($order_number)."', ".fres($ln[$id]).", '".res($qty[$id])."', ";
		if ($T['amount']) { $query .= fres($amount[$id]).", "; }
		if ($T['delivery_date']) { $query .= fres($delivery_date[$id]).", "; }
		if ($T['items']<>'return_items') {
			// clear the label values if no ref numbers
			$r1 = trim($ref_1[$id]);
			$r1l = $ref_1_label[$id];
			if (! $r1) { $r1l = ''; }
			$r2 = trim($ref_2[$id]);
			$r2l = $ref_2_label[$id];
			if (! $r2) { $r2l = ''; }

			$query .= fres($r1).", ".fres($r1l).", ".fres($r2).", ".fres($r2l).", ";
		}
		if ($T['warranty']) { $query .= fres($warrantyid[$id]).", "; }
		if ($T['condition']) { $query .= fres($conditionid[$id])." "; }
		if ($id) { $query .= ", '".res($id)."'"; }
		$query .= "); ";
//		$result = qdb($query) OR die(qe().'<BR>'.$query);
echo $query.'<BR>';
	}
?>
