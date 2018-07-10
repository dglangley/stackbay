<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';

	$DEBUG = 0;

	function createPayment($payment_type, $payment_number, $payment_date, $payment_amount, $payment_orders, $companyid, $notes = '', $paymentid, $financial_account) {
		$id = 0;

		// Query to check if the payment already exists when trying to add a new payment
		$query = "SELECT id FROM payments WHERE companyid = ".res($companyid)." AND payment_type = '".res($payment_type)."' AND number = '".res($payment_number)."';";
		$result = qedb($query);

		if(mysqli_num_rows($result) && empty($paymentid)) {
			$r = mysqli_fetch_assoc($result);

			$paymentid = $r['id'];

			echo 'Error: the payment already exists. Please edit the existing payment# ' . $r['id']; die();
		}

		$query = "REPLACE payments (companyid, date, payment_type, number, amount, notes, userid, datetime, financeid ";
		if($paymentid) { $query .= ", id"; }
		$query .= ") VALUES (".fres($companyid).", ".fres(format_date($payment_date, 'Y-m-d')).", ".fres($payment_type).", ".fres(trim($payment_number)).", ".fres($payment_amount).", ".fres(trim($notes)).", ".fres($GLOBALS['U']['id']).", ".fres($GLOBALS['now']).", ".fres($financial_account);
		if($paymentid) { $query .= ", ".fres($paymentid); }
		$query .= ");";

		qedb($query);
		$id = qid();

		createPaymentDetails($payment_orders, $id, $paymentid);
	}
	
	function createPaymentDetails($payment_orders, $id, $paymentid) {
		//Current state due to payment details not having a unique identifer, delete all payment details with paymentid and recreate them for safety check
		if($paymentid) {
			$query = "DELETE FROM payment_details WHERE paymentid = ".res($paymentid).";";
			qedb($query);
		}

		foreach($payment_orders as $order => $details) {
			$order_number = explode('.', $order)[1];
			$order_type = explode('.', $order)[0];
			foreach($details as $invoice => $inv_detail) {
				if($inv_detail['check'] == 'on') {
					$inv_number = explode('.', $invoice)[1];
					$inv_type = explode('.', $invoice)[0];

					// Null record for non invoiced and non billed items
					if($inv_type == "Purchase" OR $inv_type == "Sale" OR $inv_type == "Repair" OR $inv_type == "Service") {
						$inv_type = '';
						$inv_number = '';
					}

					$query = "INSERT INTO payment_details (order_number, order_type, ref_number, ref_type, amount, paymentid) VALUES (".fres($order_number).", ".fres($order_type).", ".fres($inv_number).", ".fres($inv_type).", ".fres($inv_detail['amount']).", '$id');";
					qedb($query);
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
		$result = qedb($query);
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
	$filter;

	// Special Case paymentid
	if (isset($_REQUEST['paymentid'])) { $paymentid = $_REQUEST['paymentid']; }

	//Filters to be incorporated
	if (isset($_REQUEST['summary'])) { $summary = $_REQUEST['summary']; }
	if (isset($_REQUEST['start'])) { $start = $_REQUEST['start']; }
	if (isset($_REQUEST['end'])) { $end = $_REQUEST['end']; }
	if (isset($_REQUEST['table'])) { $table = $_REQUEST['table']; }
	if (isset($_REQUEST['order'])) { $order = $_REQUEST['order']; }
	if (isset($_REQUEST['filter'])) { $filter = $_REQUEST['filter']; }
	if (isset($_REQUEST['order_type'])) { $order_type = $_REQUEST['order_type']; }

	if (isset($_REQUEST['payment_type'])) { $payment_type = $_REQUEST['payment_type']; }
	if (isset($_REQUEST['payment_number'])) { $payment_number = $_REQUEST['payment_number']; }
	if (isset($_REQUEST['financial_account'])) { $financial_account = $_REQUEST['financial_account']; }
	if (isset($_REQUEST['payment_date'])) { $payment_date = $_REQUEST['payment_date']; }
	if (isset($_REQUEST['payment_amount'])) { $payment_amount = $_REQUEST['payment_amount']; }
	if (isset($_REQUEST['payment_orders'])) { $payment_orders = $_REQUEST['payment_orders']; }
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }

	if (isset($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }

	createPayment($payment_type, $payment_number, $payment_date, $payment_amount, $payment_orders, $companyid, $notes, $paymentid, $financial_account);

	//print "<pre>".print_r($_REQUEST,true)."</pre>";exit;
	$order_type_url = '';

	foreach($order_type as $type) {
		$order_type_url .= '&order_type[]=' . $type;
	}

	$url = '/accounts.php?payment=true&report_type='.(! empty($summary) ? $summary : '').
		'&START_DATE='.(! empty($start) ? $start : '').'&END_DATE='.(! empty($end) ? $end : '').
		'&orders_table='.(! empty($table) ? $table : '').'&order='.(! empty($order) ? $order : '').
		'&companyid='.(! empty($companyid) ? $companyid : '').'&filter='.(! empty($filter) ? $filter : '').$order_type_url;

	if ($DEBUG) { die($url); }
	header('Location: '.$url);

	exit;
