<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/saveFiles.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRep.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_address.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';

	$debug = 1;
	if ($debug) { print "<pre>".print_r($_REQUEST,true)."</pre>"; }

	/***** ORDER CONFIRMATION *****/
	// do this check for user email first, before creating order, in case there are errors/warnings
    $email_to = '';
	if (isset($_REQUEST['email_to'])) { $email_to = $_REQUEST['email_to']; }
    $email_confirmation = '';
	if (isset($_REQUEST['email_confirmation'])) { $email_confirmation = $_REQUEST['email_confirmation']; }

	$addl_recp_email = "";
	$addl_recp_name = "";
	if ($email_confirmation AND ! $debug) {
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
	$companyid = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$contactid = 0;
	if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid'])) { $contactid = $_REQUEST['contactid']; }
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
	$termsid = 0;
	if (isset($_REQUEST['termsid']) AND is_numeric($_REQUEST['termsid'])) { $termsid = $_REQUEST['termsid']; }
	$cust_ref = '';
	if (isset($_REQUEST['cust_ref'])) { $cust_ref = trim($_REQUEST['cust_ref']); }
	$public_notes = '';
	if (isset($_REQUEST['public_notes'])) { $public_notes = $_REQUEST['public_notes']; }
	$private_notes = '';
	if (isset($_REQUEST['private_notes'])) { $private_notes = $_REQUEST['private_notes']; }
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

	$datetime = $now;
	$created_by = $U['id'];
	$sales_rep_id = $U['id'];//default unless passed in
	if ($order_number) {
		$ORDER = getOrder($order_number, $order_type);
		$datetime = $ORDER['dt'];
		$created_by = $ORDER['created_by'];
		$sales_rep_id = $ORDER['sales_rep_id'];//retain existing unless passed in below
		if (! $file_url) { $file_url = $ORDER['ref_ln']; }
	}
	if (! $file_url AND $ref_ln) { $file_url = $ref_ln; }
	if (isset($_REQUEST['sales_rep_id']) AND $_REQUEST['sales_rep_id']) { $sales_rep_id = $_REQUEST['sales_rep_id']; }

	$query = "REPLACE ".$T['orders']." (";
	if ($order_number) { $query .= $T['order'].", "; }
	$query .= $T['datetime'].", created_by, sales_rep_id, ";
	$query .= "companyid, contactid, cust_ref, ref_ln, ".$T['addressid'].", ship_to_id, ";
	$query .= "freight_carrier_id, freight_services_id, freight_account_id, termsid, ";
	$query .= "public_notes, private_notes, status) ";
	$query .= "VALUES (";
	if ($order_number) { $query .= "'".res($order_number)."', "; }
	$query .= "'".$datetime."', ".fres($created_by).", ".fres($sales_rep_id).", ";
	$query .= "'".res($companyid)."', ".fres($contactid).", ".fres($cust_ref).", ".fres($file_url).", ";
	$query .= fres($bill_to_id).", ".fres($ship_to_id).", ";
	$query .= fres($freight_carrier_id).", ".fres($freight_services_id).", ".fres($freight_account_id).", ";
	$query .= fres($termsid).", ".fres($public_notes).", ".fres($private_notes).", ".fres($status);
	$query .= "); ";
	if ($debug) {
		echo $query.'<BR>';
		if (! $order_number) { $order_number = 999999; }
	} else {
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		$order_number = qid();
	}

	$items = array();
	if (isset($_REQUEST['partid'])) { $items = $_REQUEST['partid']; }
	$item_id = array();
	if (isset($_REQUEST['item_id'])) { $item_id = $_REQUEST['item_id']; }
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

	$email_rows = array();
	foreach ($items as $key => $partid) {
		$id = $item_id[$key];

		$query = "REPLACE ".$T['items']." (partid, ".$T['order'].", line_number, qty, ";
		if ($T['amount']) { $query .= $T['amount'].", "; }
		if ($T['delivery_date']) { $query .= $T['delivery_date'].", "; }
		if ($T['items']<>'return_items') { $query .= "ref_1, ref_1_label, ref_2, ref_2_label, "; }
		if ($T['warranty']) { $query .= $T['warranty'].", "; }
		if ($T['condition']) { $query .= $T['condition']." "; }
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES ('".res($partid)."', '".res($order_number)."', ".fres($ln[$key]).", '".res($qty[$key])."', ";
		if ($T['amount']) { $query .= fres($amount[$key]).", "; }
		if ($T['delivery_date']) { $query .= fres(format_date($delivery_date[$key],'Y-m-d')).", "; }
		if ($T['items']<>'return_items') {
			// clear the label values if no ref numbers
			$r1 = trim($ref_1[$key]);
			$r1l = $ref_1_label[$key];
			if (! $r1) { $r1l = ''; }
			$r2 = trim($ref_2[$key]);
			$r2l = $ref_2_label[$key];
			if (! $r2) { $r2l = ''; }

			$query .= fres($r1).", ".fres($r1l).", ".fres($r2).", ".fres($r2l).", ";
		}
		if ($T['warranty']) { $query .= fres($warrantyid[$key]).", "; }
		if ($T['condition']) { $query .= fres($conditionid[$key])." "; }
		if ($id) { $query .= ", '".res($id)."'"; }
		$query .= "); ";
		if ($debug) { echo $query.'<BR>'; }
		else { $result = qdb($query) OR die(qe().'<BR>'.$query); }

		$query2 = "SELECT part, heci FROM parts WHERE id = '".res($partid)."'; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
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
			if (! isset($email_rows[$partkey])) { $email_rows[$partkey] = array('qty'=>0,'part'=>$part_strs[0],'heci'=>$heci,'ln'=>$ln[$id]); }
			$email_rows[$partkey]['qty'] += $qty[$id];
		}
	}

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
			if ($debug) { echo $query.'<BR>'; }
			continue;
		}

		$query = "REPLACE ".$T['charges']." (".$T['order'].", memo, qty, price";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES ('".$order_number."', '".res($descr)."', '".res($charge_qty[$id])."', '".res(trim($charge_amount[$id]))."'";
		if ($id) { $query .= ", '".res($id)."'"; }
		$query .= "); ";
		if ($debug) { echo $query.'<BR>'; }
		else { $result = qdb($query) OR die(qe().'<BR>'.$query); }
	}

	// build freight service and terms descriptors for email confirmation
	$freight_service = '';
	$freight_terms = '';
	if ($email_confirmation AND ! $debug) {
		$query = "SELECT method, name FROM freight_services fs, freight_carriers fc, companies c ";
		$query .= "WHERE fs.id = '".res($freight_services_id)."' AND fs.carrierid = fc.id AND fc.companyid = c.id; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$freight_service = $r['name'].' '.$r['method'];
		}
		if ($freight_account_id) {
			$query = "SELECT account_no FROM freight_accounts WHERE id = '".res($freight_account_id)."'; ";
			$result = qdb($query) OR jsonDie(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$freight_terms = $r['account_no'];
			}
		} else {
			$freight_terms = 'Prepay and Bill';
		}
		$sbj = 'Order '.$cust_ref.' Confirmation';
		// build confirmation email headers, then line items below
		$msg = "<p>Here's your confirmation for order number ".$cust_ref.". <em>Please review for accuracy.</em></p><br/><br/>";
		$msg .= "<p><strong>Order number:</strong> ".$cust_ref."</p>";
		$msg .= "<p><strong>Shipping Service:</strong> ".$freight_service."</p>";
		$msg .= "<p><strong>Shipping Terms:</strong> ".$freight_terms."</p>";
		$msg .= "<p><strong>Shipping Address:</strong><br/>";
		$msg .= format_address($ship_to_id)."</p>";

		if (count($items)>0) {
			$msg .= "<p><strong>Item Details:</strong><br/>";
		}

		// send order confirmation
//		if ($email_confirmation AND ! $DEV_ENV) {
		foreach ($rows as $partkey => $r) {
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
		$send_success = send_gmail($msg,$sbj,$recps,$bcc);
		if ($send_success) {
//			die('Success');
		} else {
//			die($SEND_ERR);
		}
	}

	if ($debug) { exit; }

	header('Location: /order.php?order_number='.$order_number.'&order_type='.$order_type);
	exit;
?>
