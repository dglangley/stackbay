<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';

	function createPayment($payment_type, $payment_number, $payment_date, $payment_amount, $payment_orders, $companyid, $notes = '', $paymentid) {
		$id = 0;

		// Query to check if the payment already exists when trying to add a new payment
		$query = "SELECT id FROM payments WHERE companyid = ".res($companyid)." AND payment_type = '".res($payment_type)."' AND number = '".res($payment_number)."';";
		$result = qdb($query) OR die(qe().' '.$query);

		if(mysqli_num_rows($result) && empty($paymentid)) {
			$r = mysqli_fetch_assoc($result);

			$paymentid = $r['id'];

			echo 'Error: the payment already exists. Please edit the existing payment# ' . $r['id']; die();
		}

		$query = "REPLACE payments (companyid, date, payment_type, number, amount, notes, userid, datetime ";
		if($paymentid) { $query .= ", id"; }
		$query .= ") VALUES (".prep($companyid).", ".prep(format_date($payment_date, 'Y-m-d')).", ".prep($payment_type).", ".prep(trim($payment_number)).", ".prep($payment_amount).", ".prep(trim($notes)).", ".prep($GLOBALS['U']['id']).", ".prep($GLOBALS['now']);
		if($paymentid) { $query .= ", ".prep($paymentid); }
		$query .= ");";

		qdb($query) OR die(qe().' '.$query);
		$id = qid();

		createPaymentDetails($payment_orders, $id, $paymentid);
	}
	
	function createPaymentDetails($payment_orders, $id, $paymentid) {
		//Current state due to payment details not having a unique identifer, delete all payment details with paymentid and recreate them for safety check
		if($paymentid) {
			$query = "DELETE FROM payment_details WHERE paymentid = ".res($paymentid).";";
			qdb($query) OR die(qe() . ' ' . $query);
		}

		foreach($payment_orders as $order => $details) {
			$order_number = explode('.', $order)[1];
			$order_type = explode('.', $order)[0];
			foreach($details as $invoice => $inv_detail) {
				if($inv_detail['check'] == 'on') {
					$inv_number = explode('.', $invoice)[1];
					$inv_type = explode('.', $invoice)[0];

					// Null record for non invoiced and non billed items
					if($inv_type == "Purchase" OR $inv_type == "Sale" OR $inv_type == "Repair") {
						$inv_type = '';
						$inv_number = '';
					}

					$query = "INSERT INTO payment_details (order_number, order_type, ref_number, ref_type, amount, paymentid) VALUES (".prep($order_number).", ".prep($order_type).", ".prep($inv_number).", ".prep($inv_type).", ".prep($inv_detail['amount']).", '$id');";
					qdb($query) OR die(qe().' '.$query);
				}
			}
		}
	}
	
	// Get the CompanyID from Order
	function getCompanyID($order, $type) {
		$companyid = 0; 

		$order_field = '';
		if ($type=='sale') { $order_field = 'so_number'; }
		else if ($type=='purchase') { $order_field = 'po_number'; }
		else if ($type=='repair') { $order_field = 'ro_number'; }
		
		$query = "SELECT companyid FROM $type WHERE $order_field = '".$order."'; ";
		$result = qdb($query) or die(qe());
        if (mysqli_num_rows($result)>0) {
        	$r = mysqli_fetch_assoc($result);
			$companyid = $r['companyid'];
        }
		
		return $companyid;
	}

	// IF PaymentID exists then we are editing an Existing Payment
	$paymentid = 0;
	
	//Declare variables
	$payment_type = '';
	$payment_number = '';
	$payment_date = '';
	$payment_amount = 0.00;
	$payment_orders = array();
	$companyid = 0;

	$notes = '';

	// Declare Variables
	$summary;
	$start;
	$end;
	$table;
	$order;
	$companyid_search;
	$filter;

	// Special Case paymentid
	if (isset($_REQUEST['paymentid'])) { $paymentid = $_REQUEST['paymentid']; }

	//Filters to be incorporated
	if (isset($_REQUEST['summary'])) { $summary = $_REQUEST['summary']; }
	if (isset($_REQUEST['start'])) { $start = $_REQUEST['start']; }
	if (isset($_REQUEST['end'])) { $end = $_REQUEST['end']; }
	if (isset($_REQUEST['table'])) { $table = $_REQUEST['table']; }
	if (isset($_REQUEST['order'])) { $order = $_REQUEST['order']; }
	if (isset($_REQUEST['companyid'])) { $companyid_search = $_REQUEST['companyid']; }
	if (isset($_REQUEST['filter'])) { $filter = $_REQUEST['filter']; }

	if (isset($_REQUEST['payment_type'])) { $payment_type = $_REQUEST['payment_type']; }
	if (isset($_REQUEST['payment_number'])) { $payment_number = $_REQUEST['payment_number']; }
	if (isset($_REQUEST['payment_date'])) { $payment_date = $_REQUEST['payment_date']; }
	if (isset($_REQUEST['payment_amount'])) { $payment_amount = $_REQUEST['payment_amount']; }
	if (isset($_REQUEST['payment_orders'])) { $payment_orders = $_REQUEST['payment_orders']; }
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }

	if (isset($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }

	createPayment($payment_type, $payment_number, $payment_date, $payment_amount, $payment_orders, $companyid, $notes, $paymentid);

	//print "<pre>".print_r($_REQUEST,true)."</pre>";exit;

	header('Location: /accounts.php?payment=true&report_type='.(! empty($summary) ? $summary : '').'&START_DATE='.(! empty($start) ? $start : '').'&END_DATE='.(! empty($end) ? $end : '').'&orders_table='.(! empty($table) ? $table : '').'&order='.(! empty($order) ? $order : '').'&companyid='.(! empty($companyid_search) ? $companyid_search : '').'&filter='.(! empty($filter) ? $filter : '').'');

	exit;
