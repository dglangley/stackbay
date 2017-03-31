<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';

	function updatePayments($payment_ID, $payment_date, $journal_entry, $payment_amount, $companyid) {
		$payment_date = prep(format_date($payment_date, 'Y-m-d'));
		if(!empty($payment_ID)) {
			$query = "REPLACE payments (companyid, date, number, amount) VALUES (".res($companyid).", ".$payment_date.", ".res($payment_ID).", ".res($payment_amount).");";
			qdb($query) OR die(qe().' '.$query);
		} 
	}
	
	// Get the CompanyID from Order
	function getCompanyID($order, $type) {
		$companyid = 0; 
		
		$query = "SELECT companyid FROM $type WHERE ".($type == 'sales_orders' ? 'so_number' : 'po_number')." = '$order';";
		//echo $query; die;
		$result = qdb($query) or die(qe());
        if (mysqli_num_rows($result)>0) {
        	$r = mysqli_fetch_assoc($result);
			$companyid = $r['companyid'];
        }
		
		return $companyid;
	}
	
	$payment = 'false';
	
	
	if (isset($_REQUEST['payment_ID'])) { $payment_ID = $_REQUEST['payment_ID']; }
	if (isset($_REQUEST['payment_date'])) { $payment_date = $_REQUEST['payment_date']; }
	if (isset($_REQUEST['journal_entry'])) { $journal_entry = $_REQUEST['journal_entry']; }
	if (isset($_REQUEST['payment_amount'])) { $payment_amount = $_REQUEST['payment_amount']; }
	
	//Determine if this is an so or po
	if (isset($_REQUEST['so_order'])) { $so_order = $_REQUEST['so_order']; }
	if (isset($_REQUEST['po_order'])) { $po_order = $_REQUEST['po_order']; }
	
	if (!$payment_ID) {
		$msg = 'Missing valid input data';
	} else {
		$companyid = (!empty($so_order) ? getCompanyID($so_order,'sales_orders') : getCompanyID($po_order,'purchase_orders'));

		updatePayments($payment_ID, $payment_date, $journal_entry, $payment_amount, $companyid);
		
		$payment = 'true';
	}

	header('Location: /order_form.php?'.(!empty($so_order) ?  'ps=Sale&on=' . $so_order : 'ps=Purchase&on=' . $po_order ).'&payment=' . $payment);
	exit;
