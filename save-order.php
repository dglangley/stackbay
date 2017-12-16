<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/saveFiles.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRep.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getItems.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_address.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/calcTaskCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCogs.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCommission.php';

	$DEBUG = 0;
	if ($DEBUG) { print "<pre>".print_r($_REQUEST,true)."</pre>"; }

	/***** ORDER CONFIRMATION *****/
	// do this check for user email first, before creating order, in case there are errors/warnings
    $email_to = '';
	if (isset($_REQUEST['email_to'])) { $email_to = $_REQUEST['email_to']; }
    $email_confirmation = '';
	if (isset($_REQUEST['email_confirmation'])) { $email_confirmation = $_REQUEST['email_confirmation']; }

	$addl_recp_email = "";
	$addl_recp_name = "";
	if ($email_confirmation AND ! $DEBUG) {
		if ($email_to) {
			$addl_recp_email = getContact($email_to,'id','email');
			if ($addl_recp_email) {
				$addl_recp_name = getContact($email_to,'id','name');
			} else {
				die(getContact($email_to)." does not have an email! Please update their profile first, or remove them from Order Confirmation in order to continue.");
			}
		}

		// initializes Amea's gmail API session
		setGoogleAccessToken(5);
	}

	$order_number = 0;
	if (isset($_REQUEST['order_number']) AND $_REQUEST['order_number']) { $order_number = $_REQUEST['order_number']; }
	$order_type = '';
	if (isset($_REQUEST['order_type']) AND $_REQUEST['order_type']) { $order_type = $_REQUEST['order_type']; }
	$create_invoice = false;
	if (isset($_REQUEST['create_invoice']) AND $_REQUEST['create_invoice']) { $create_invoice = $_REQUEST['create_invoice']; }
	$companyid = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$contactid = 0;
	if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid'])) { $contactid = $_REQUEST['contactid']; }
	$classid = 0;
	if (isset($_REQUEST['classid']) AND $_REQUEST['classid']) { $classid = $_REQUEST['classid']; }
	$bill_to_id = 0;
	if (isset($_REQUEST['bill_to_id']) AND is_numeric($_REQUEST['bill_to_id'])) { $bill_to_id = $_REQUEST['bill_to_id']; }
	$ship_to_id = 0;
	if (isset($_REQUEST['ship_to_id']) AND is_numeric($_REQUEST['ship_to_id'])) { $ship_to_id = $_REQUEST['ship_to_id']; }
	$freight_carrier_id = 0;
	if (isset($_REQUEST['carrierid']) AND is_numeric($_REQUEST['carrierid'])) { $freight_carrier_id = $_REQUEST['carrierid']; }
	$freight_services_id = 0;
	if (isset($_REQUEST['freight_services_id']) AND is_numeric($_REQUEST['freight_services_id'])) { $freight_services_id = $_REQUEST['freight_services_id']; }
	$freight_account_id = 0;
	if (isset($_REQUEST['freight_account_id']) AND is_numeric($_REQUEST['freight_account_id'])) { $freight_account_id = $_REQUEST['freight_account_id']; }
	$freight = 0;
	if (isset($_REQUEST['freight']) AND trim($_REQUEST['freight'])>0) { $freight = $_REQUEST['freight']; }
	$shipmentid = 0;
	if (isset($_REQUEST['shipmentid']) AND $_REQUEST['shipmentid']) { $shipmentid = $_REQUEST['shipmentid']; }
	$termsid = 0;
	if (isset($_REQUEST['termsid']) AND is_numeric($_REQUEST['termsid'])) { $termsid = $_REQUEST['termsid']; }
	$cust_ref = '';
	if (isset($_REQUEST['cust_ref'])) { $cust_ref = strtoupper(trim($_REQUEST['cust_ref'])); }
	$public_notes = '';
	if (isset($_REQUEST['public_notes'])) { $public_notes = trim($_REQUEST['public_notes']); }
	$private_notes = '';
	if (isset($_REQUEST['private_notes'])) { $private_notes = trim($_REQUEST['private_notes']); }
	$repair_code_id = 0;
	if (isset($_REQUEST['repair_code_id'])) { $repair_code_id = $_REQUEST['repair_code_id']; }
	$status = 'Active';
	if (isset($_REQUEST['status'])) { $status = $_REQUEST['status']; }

	$T = order_type($order_type);

	$ref_ln = false;
	// user is replacing ref ln (uploaded order)
	if (isset($_REQUEST['order_upload']) AND $_REQUEST['order_upload']) { $ref_ln = $_REQUEST['order_upload']; }
	else if (isset($_REQUEST['ref_ln']) AND $_REQUEST['ref_ln']) { $ref_ln = $_REQUEST['ref_ln']; }

	$file_url = false;
	if (isset($_FILES) AND count($_FILES)>0 AND $_SERVER['REQUEST_METHOD'] == 'POST') {
		$file_url = saveFiles($_FILES);
	}

	// if re-saving a form that has a tmp file instead of uploaded to S3, retry the upload since maybe
	// the connection was previously down; we don't want to be in the habit of keeping uploads in our tmp folder
	if (! $file_url AND strstr($ref_ln,$TEMP_DIR)) {
		// file uploads are arrays, so we're simulating the same array for the saveFile() function, which expects an upload
		$file = array('name'=>str_replace($TEMP_DIR,'',$ref_ln),'tmp_name'=>$ref_ln);
		$file_url = saveFile($file);
	}

	$datetime = $now;
	$created_by = $U['id'];
	$sales_rep_id = $U['id'];//default unless passed in
	if ($order_number AND ! $create_invoice) {
		$ORDER = getOrder($order_number, $order_type);
		$datetime = $ORDER['dt'];
		$created_by = $ORDER['created_by'];
		$sales_rep_id = $ORDER['sales_rep_id'];//retain existing unless passed in below
		if (! $file_url) { $file_url = $ORDER['ref_ln']; }
	} else {
		if ($create_invoice) {
			$classid = 0;
			$datetime = $now;
			$ORDER = getOrder(0,'Invoice');
			$ORDER['order_number'] = $order_number;
			$ORDER['order_type'] = $order_type;
			$T2 = order_type($order_type);
			$order_number = 0;
			$order_type = 'Invoice';
			$T = order_type($order_type);
		} else {
			// if in quote data, user is converting to order
			if ($T['record_type']=='quote') {
//				$quoteid = $order_number;//use this for reference to the quote in the order table
				$order_number = 0;//new order
				$order_type = $T['order_type'];

				$T = order_type($order_type);
			}

			$ORDER = getOrder('',$order_type);//fill the array with fields to determine below what we should add and what to skip
		}
	}

	if (! $file_url AND $ref_ln) { $file_url = $ref_ln; }
	if (isset($_REQUEST['sales_rep_id']) AND $_REQUEST['sales_rep_id']) { $sales_rep_id = $_REQUEST['sales_rep_id']; }

	$query = "REPLACE ".$T['orders']." (";
	if ($order_number) { $query .= $T['order'].", "; }
	if (array_key_exists('classid',$ORDER)) { $query .= "classid, "; }
	$query .= $T['datetime'].", ";
	if (array_key_exists('created_by',$ORDER)) { $query .= "created_by, "; }
	if (array_key_exists('sales_rep_id',$ORDER)) { $query .= "sales_rep_id, "; }
	$query .= "companyid, ";
	if (array_key_exists('order_number',$ORDER)) {
		$query .= "order_number, order_type, ";
	}
	if (array_key_exists('shipmentid',$ORDER)) { $query .= "shipmentid, "; }
	if (array_key_exists('freight',$ORDER)) { $query .= "freight, "; }
	if (array_key_exists('contactid',$ORDER)) { $query .= "contactid, "; }
	if ($T['cust_ref']) { $query .= $T['cust_ref'].", ref_ln, "; }
	if (array_key_exists($T['addressid'],$ORDER)) { $query .= $T['addressid'].", "; }
	// all shipping-related fields
	if (array_key_exists('ship_to_id',$ORDER)) {
		$query .= "ship_to_id, freight_carrier_id, freight_services_id, freight_account_id, ";
	}
	if (array_key_exists('termsid',$ORDER)) { $query .= "termsid, "; }
	$query .= "public_notes, ";
	if (array_key_exists('private_notes',$ORDER)) { $query .= "private_notes, "; }
	if (array_key_exists('repair_code_id',$ORDER)) { $query .= "repair_code_id, "; }
	$query .= "status) ";

	$query .= "VALUES (";

	if ($order_number) { $query .= "'".res($order_number)."', "; }
	if ($classid) { $query .= "'".res($classid)."', "; }
	$query .= "'".$datetime."', ";
	if (array_key_exists('created_by',$ORDER)) { $query .= fres($created_by).", "; }
	if (array_key_exists('sales_rep_id',$ORDER)) { $query .= fres($sales_rep_id).", "; }
	$query .= "'".res($companyid)."', ";
	if (array_key_exists('order_number',$ORDER)) {
		$query .= fres($ORDER['order_number']).", ".fres($ORDER['order_type']).", ";
	}
	if (array_key_exists('shipmentid',$ORDER)) { $query .= fres($shipmentid).", "; }
	if (array_key_exists('freight',$ORDER)) { $query .= fres($freight).", "; }
	if (array_key_exists('contactid',$ORDER)) { $query .= fres($contactid).", "; }
	if ($T['cust_ref']) { $query .= fres($cust_ref).", ".fres($file_url).", "; }
	if (array_key_exists($T['addressid'],$ORDER)) { $query .= fres($bill_to_id).", "; }
	// all shipping-related fields
	if (array_key_exists('ship_to_id',$ORDER)) {
		$query .= fres($ship_to_id).", ";
		$query .= fres($freight_carrier_id).", ".fres($freight_services_id).", ".fres($freight_account_id).", ";
	}
	if (array_key_exists('termsid',$ORDER)) { $query .= fres($termsid).", "; }
	$query .= fres($public_notes).", ";
	if (array_key_exists('private_notes',$ORDER)) { $query .= fres($private_notes).", "; }
	if (array_key_exists('repair_code_id',$ORDER)) { $query .= fres($repair_code_id).", "; }
	$query .= fres($status)."); ";
	$result = qedb($query);
	$order_number = qid();
	if ($DEBUG AND ! $order_number) { $order_number = 999999; }

	$items = array();
//	if (isset($_REQUEST['partid'])) { $items = $_REQUEST['partid']; }
//	else if (isset($_REQUEST['addressid'])) { $items = $_REQUEST['addressid']; }
//	else if (isset($_REQUEST['item_id'])) { $items = $_REQUEST['item_id']; }
	if (isset($_REQUEST['items'])) { $items = $_REQUEST['items']; }

	$fieldid = array();
	if (isset($_REQUEST['fieldid'])) { $fieldid = $_REQUEST['fieldid']; }

	$search_type = array();
	if (isset($_REQUEST['search_type'])) { $search_type = $_REQUEST['search_type']; }
	$item_id = array();
	if (isset($_REQUEST['item_id'])) { $item_id = $_REQUEST['item_id']; }
	$item_label = array();
	if (isset($_REQUEST['item_label'])) { $item_label = $_REQUEST['item_label']; }
	$ln = array();
	if (isset($_REQUEST['ln'])) { $ln = $_REQUEST['ln']; }
	$qty = array();
	if (isset($_REQUEST['qty'])) { $qty = $_REQUEST['qty']; }
	$amount = array();
	if (isset($_REQUEST['amount'])) { $amount = $_REQUEST['amount']; }
	$descr = array();
	if (isset($_REQUEST['description'])) { $descr = $_REQUEST['description']; }
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

	$email_rows = array();
	foreach ($items as $key => $id) {//fieldid) {
//		$id = $item_id[$key];
//		if (! $fieldid OR ! $qty[$key]) { continue; }
		if (! $qty[$key]) { continue; }

		$type = 'Part';
		if (isset($search_type[$key]) AND $search_type[$key]=='Site') { $type = 'Site'; }

		$F = getItems($order_type);
		// if saving an item by item id rather than partid/addressid, $fieldid is actually empty and shouldn't carry a value
//		if (! isset($search_type[$key])) { $fieldid = 0; }

		$query = "REPLACE ".$T['items']." (";
		if (isset($F['partid'])) { $query .= "partid, "; }
		else if (isset($F['addressid'])) { $query .= "addressid, "; }
		else if (isset($F['item_id']) AND isset($F['item_label'])) { $query .= "item_id, item_label, "; }
		$query .= $T['order'].", line_number, qty";
		if (array_key_exists('qty_shipped',$F)) { $query .= ", qty_shipped"; }
		else if (array_key_exists('qty_received',$F)) { $query .= ", qty_received"; }
		if ($T['amount']) { $query .= ", ".$T['amount']; }
		if (($create_invoice AND $id) OR isset($F['task_label'])) { $query .= ", taskid, task_label"; }
		if ($T['description']) { $query .= ", ".$T['description']; }
		if ($T['delivery_date']) { $query .= ", ".$T['delivery_date']; }
		if ($T['items']<>'return_items') { $query .= ", ref_1, ref_1_label, ref_2, ref_2_label"; }
		if ($T['warranty']) { $query .= ", ".$T['warranty']; }
		if ($T['condition']) { $query .= ", ".$T['condition']; }
		if ($id AND ! $create_invoice) { $query .= ", id"; }

		$query .= ") VALUES (".fres($fieldid[$key]).", ";

		if (isset($F['item_label'])) {
			if ($fieldid[$key]) { $query .= "'addressid', "; }
			else { $query .= "NULL, "; }
		}
		$query .= "'".res($order_number)."', ".fres($ln[$key]).", '".res($qty[$key])."'";
		if (array_key_exists('qty_shipped',$F)) {
			$qty_shipped = 0;
			if (isset($ORDER['items'][$id]['qty_shipped'])) { $qty_shipped = $ORDER['items'][$id]['qty_shipped']; }
			$query .= ", '".res($qty_shipped)."'";
		} else if (array_key_exists('qty_received',$F)) {
			$qty_received = 0;
			if (isset($ORDER['items'][$id]['qty_received'])) { $qty_received = $ORDER['items'][$id]['qty_received']; }
			$query .= ", '".res($qty_received)."'";
		}
		if ($T['amount']) { $query .= ", ".fres($amount[$key]); }
		if ($create_invoice AND $id) { $query .= ", '".res($id)."', '".res($T2['item_label'])."'"; }
		else if (isset($F['task_label'])) { $query .= ", '".res($ORDER['items'][$id]['taskid'])."', '".res($ORDER['items'][$id]['task_label'])."'"; }
		if ($T['description']) { $query .= ", ".fres($descr[$key]); }
		if ($T['delivery_date']) { $query .= ", ".fres(format_date($delivery_date[$key],'Y-m-d')); }
		if ($T['items']<>'return_items') {
			// clear the label values if no ref numbers
			$r1 = trim($ref_1[$key]);
			$r1l = $ref_1_label[$key];
			if (! $r1) { $r1l = ''; }
			$r2 = trim($ref_2[$key]);
			$r2l = $ref_2_label[$key];
			if (! $r2) { $r2l = ''; }

			$query .= ", ".fres($r1).", ".fres($r1l).", ".fres($r2).", ".fres($r2l);
		}
		if ($T['warranty']) { $query .= ", ".fres($warrantyid[$key]); }
		if ($T['condition']) { $query .= ", ".fres($conditionid[$key])." "; }
		if ($id AND ! $create_invoice) { $query .= ", '".res($id)."'"; }
		$query .= "); ";
		$result = qedb($query);
		$saved_id = qid();

		if ($create_invoice AND $id AND $ORDER['order_type']=='Service') {
			// calculate cost of task in order to determine profit, then calculate commission based on profit
			$cost = calcTaskCost($id,$T2['item_label']);

			// $MATERIALS_COST is a global variable summed in calcTaskCost() so we can use it for setting into sales cogs
			$cogsid = setCogs(0, $id, $T2['item_label'], $MATERIALS_COST);

			$profit = $amount[$key]-$cost;

			$rate = 10;
			$rep_id = 27;
			$comm_due = ($profit*($rate/100));
			$commissionid = saveCommission($order_number,$saved_id,$id,$T2['item_label'],$cogsid,$rep_id,$comm_due,$rate);
		}

		if ($fieldid[$key]) {
			$query2 = "SELECT part, heci FROM parts WHERE id = '".res($fieldid[$key])."'; ";
			$result2 = qedb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$part_strs = explode(' ',$r2['part']);
				$partkey = '';
				if ($ln[$id]) { $partkey = $ln[$id]; }
				$heci = '';
				if ($r2['heci']) {
					$heci = substr($r2['heci'],0,7);
					$partkey .= '.'.$heci;
				} else {
					$partkey .= '.'.$part_strs[0];
				}
				if (! isset($email_rows[$partkey])) { $email_rows[$partkey] = array('qty'=>0,'part'=>$part_strs[0],'heci'=>$heci,'ln'=>$ln[$key]); }
				$email_rows[$partkey]['qty'] += $qty[$key];
			}
		}

		if (in_array("service_item_id", $ref_1_label)  OR in_array("repair_item_id", $ref_1_label) OR in_array("service_item_id", $ref_2_label)  OR in_array("repair_item_id", $ref_2_label)) {
			//print_r($key);
			$query = "UPDATE purchase_requests SET po_number = $order_number WHERE id = $key";
			qedb($query);
		}
	}

//	if ($create_invoice) { $order_type = 'Invoice'; }

	$charges = array();
	if (isset($_REQUEST['charge_description'])) { $charges = $_REQUEST['charge_description']; }
	$charge_qty = array();
	if (isset($_REQUEST['charge_qty'])) { $charge_qty = $_REQUEST['charge_qty']; }
	$charge_amount = array();
	if (isset($_REQUEST['charge_amount'])) { $charge_amount = $_REQUEST['charge_amount']; }

	foreach ($charges as $id => $descr) {
		// if empty charges that have not been added
		if (! $id AND (! $charge_amount[$id] OR trim($charge_amount[$id])=='0.00')) { continue; }

		// deleting charge by zeroing out amount
		if ($id AND (! $charge_amount[$id] OR trim($charge_amount[$id]=='0.00'))) {
			$query = "DELETE FROM ".$T['charges']." WHERE id = '".res($id)."'; ";
			$result = qedb($query);
			continue;
		}

		$query = "REPLACE ".$T['charges']." (".$T['order'].", memo, qty, price";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES ('".$order_number."', '".res($descr)."', '".res($charge_qty[$id])."', '".res(trim($charge_amount[$id]))."'";
		if ($id) { $query .= ", '".res($id)."'"; }
		$query .= "); ";
		$result = qedb($query);
	}

	// build freight service and terms descriptors for email confirmation
	$freight_service = '';
	$freight_terms = '';
	if ($email_confirmation) {
		$query = "SELECT method, name FROM freight_services fs, freight_carriers fc, companies c ";
		$query .= "WHERE fs.id = '".res($freight_services_id)."' AND fs.carrierid = fc.id AND fc.companyid = c.id; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$freight_service = $r['name'].' '.$r['method'];
		}
		if ($freight_account_id) {
			$query = "SELECT account_no FROM freight_accounts WHERE id = '".res($freight_account_id)."'; ";
			$result = qedb($query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$freight_terms = $r['account_no'];
			}
		} else {
			$freight_terms = 'Prepay and Bill';
		}
		$sbj = 'Order '.$cust_ref.' Confirmation';
		// build confirmation email headers, then line items below
		$msg = "<p>Your confirmation number is ".$T['abbrev'].$order_number.". <em>Please review the details below for accuracy.</em></p><br/><br/>";
		if ($order_type=='Repair') {
			$msg .= "<p><strong>Send your unit(s) to:</strong><br/>";
			$msg .= "Attn: ".$T['abbrev'].$order_number."<br/>";
			$msg .= format_address(91)."</p>";
			$msg .= "<p><strong>Turn-Around Time:</strong> 30-day standard</p>";
		}
		$msg .= "<p><strong>Order Number:</strong> ".$cust_ref."</p>";
		$msg .= "<p><strong>Shipping Service:</strong> ".$freight_service."</p>";
		$msg .= "<p><strong>Shipping Terms:</strong> ".$freight_terms."</p>";
		$msg .= "<p><strong>Shipping Address:</strong><br/>";
		$msg .= format_address($ship_to_id)."</p>";

		if (count($items)>0) {
			$msg .= "<p><strong>Item Details:</strong><br/>";
		}

		// send order confirmation
//		if ($email_confirmation AND ! $DEV_ENV) {
		foreach ($email_rows as $partkey => $r) {
			if ($r['ln']) { $msg .= '<span style="color:#aaa">'.$r['ln'].'.</span> '; }
			if ($r['heci']) { $msg .= $r['heci'].' '; }
			$msg .= $r['part'];
			if ($r['qty']) { $msg .= ' qty '.$r['qty']; }
			$msg .= '<br/>';
		}
		$recps = array();
		if ($contactid) {
			if ($DEV_ENV) {
				$recps[] = array('david@ven-tel.com','David Langley');
			} else {
				$contact_email = getContact($contactid,'id','email');
				if ($contact_email) {
					$recps[] = array($contact_email,getContact($contactid,'id','name'));
				}
			}
		}
		if ($addl_recp_email AND ! $DEV_ENV) {
			$recps[] = array($addl_recp_email,$addl_recp_name);
		}
		if (! $DEV_ENV) {
			$recps[] = array('shipping@ven-tel.com','VenTel Shipping');
		}
		$bcc = false;
		if ($sales_rep_id) {
			$rep_contactid = getRep($sales_rep_id,'id','contactid');
			$rep_email = getContact($rep_contactid,'id','email');
			$bcc = $rep_email;
		}
		if ($public_notes) {
			$msg .= '<br/>'.str_replace(chr(10),'<BR/>',$public_notes).'<br/>';
		}
		if ($DEBUG) {
			echo $msg.'<BR><BR>';
		} else {
			$send_success = send_gmail($msg,$sbj,$recps,$bcc);
			if ($send_success) {
//				die('Success');
			} else {
//				die($SEND_ERR);
			}
		}
	}

	if ($DEBUG) { exit; }

	header('Location: /order.php?order_type='.$order_type.'&order_number='.$order_number);
	exit;
?>
