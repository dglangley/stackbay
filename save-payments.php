<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';

	function updatePayments($payment_type, $payment_ID, $payment_date, $journal_entry, $payment_amount, $companyid, $notes = '') {
		$payment_date = prep(format_date($payment_date, 'Y-m-d'));
		if(!empty($payment_ID)) {
			$id = 0;
			//This section checks if the payment id or number is already in the table
			//If so update and sum the amounts together with the new payment
			$query = "SELECT amount, id FROM payments WHERE number = ".res($payment_ID).";";
			$result = qdb($query) OR die(qe().' '.$query);
			
			if (mysqli_num_rows($result)>0) {
	        	$r = mysqli_fetch_assoc($result);
				$payment_amount += $r['amount'];
				$id = $r['id'];
	        }
	        
			$query = "REPLACE payments (companyid, date, number, amount, payment_type, notes";
			if ($id) { $query .= ", id"; }
			$query .= ") VALUES (".res($companyid).", ".$payment_date.", ".res($payment_ID).", ".res($payment_amount).", '".res($payment_type)."', '".res($notes)."'";
			if ($id) { $query .= ",'".$id."'"; }
			$query .= ");";
			qdb($query) OR die(qe().' '.$query);
			//Get the payment id
			if (! $id) { $id = qid(); }
			
			return $id;
		} 
	}
	
	function updatePaymentDetails($order, $type, $ref_num = 'null', $ref_type = 'null', $check_amount, $id) {
		//get the amount of the specific PO or SO
		$order_amount = 0;
		$paid = 0;
		//$paidTotal = 0;
		$query = '';
		
		//This query is mainly to get the total amount of cost for the order
		if($type == 'po') {
			$query = "SELECT SUM(qty * price) AS item_total FROM purchase_items WHERE po_number = ".res($order).";";
		} else {
			$query = "SELECT SUM(qty * price) AS item_total FROM sales_items WHERE so_number = ".res($order).";";
		}
		
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)>0) {
        	$r = mysqli_fetch_assoc($result);
			$order_amount += $r['item_total'];
        }
        
        //Find the amount paid so far for this order on this payment number
        //In most cases I do not believe the user will pay recursively with the same check# (this is most likely a fall back that will never be used)
		$query_details = "SELECT amount FROM payment_details WHERE order_number = ".res($order)." AND order_type = '".res($type)."' AND paymentid = ".res($id).";";
		
		$result = qdb($query_details) OR die(qe().' '.$query_details);
		if (mysqli_num_rows($result)>0) {
        	$r = mysqli_fetch_assoc($result);
			$paid += $r['amount'];
        }
        
        //Sum the new payment with the old payment amount for the payment ID on the details level
        $check_amount += $paid;

        //Find the total amount paid so far for this order (UNUSED)
		$query_details = "SELECT SUM(amount) AS item_total FROM payment_details WHERE order_number = ".res($order)." AND order_type = '".res($type)."';";
		
		//Just used to update the total amount paid for the current order amongst all payments given
		// $result = qdb($query_details) OR die(qe().' '.$query_details);
		// if (mysqli_num_rows($result)>0) {
  //      	$r = mysqli_fetch_assoc($result);
		// 	$paidTotal += $r['item_total'];
  //      }
        
		$query = "REPLACE payment_details (order_number, order_type, ref_number, ref_type, amount, paymentid) VALUES (".res($order).", '".res($type)."', ".res($ref_num).", ".res($ref_type).", $check_amount, ".res($id).");";
			qdb($query) OR die(qe().' '.$query);
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
	
	//Declare variables
	$payment = 'false';
	$payment_ID;
	$payment_date;
	$journal_entry;
	$payment_amount;
	
	$so_order;
	$po_order;
	
	$payment_type;
	$notes;
	
	if (isset($_REQUEST['payment_type'])) { $payment_type = $_REQUEST['payment_type']; }
	if (isset($_REQUEST['payment_ID'])) { $payment_ID = $_REQUEST['payment_ID']; }
	if (isset($_REQUEST['payment_date'])) { $payment_date = $_REQUEST['payment_date']; }
	if (isset($_REQUEST['journal_entry'])) { $journal_entry = $_REQUEST['journal_entry']; }
	if (isset($_REQUEST['payment_amount'])) { $payment_amount = $_REQUEST['payment_amount']; }
	
	//Determine if this is an so or po
	if (isset($_REQUEST['so_order'])) { $so_order = $_REQUEST['so_order']; }
	if (isset($_REQUEST['po_order'])) { $po_order = $_REQUEST['po_order']; }
	
	if (isset($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }
	
	if (!$payment_ID) {
		$msg = 'Missing valid input data';
	} else {
		$order_no = (!empty($so_order) ?  $so_order : $po_order );
		$order_type = (!empty($so_order) ?  'so' : 'po' );
		
		$companyid = (!empty($so_order) ? getCompanyID($so_order,'sales_orders') : getCompanyID($po_order,'purchase_orders'));

		$pid = updatePayments($payment_type, $payment_ID, $payment_date, $journal_entry, $payment_amount, $companyid, $notes);
		
		//Insert into the details table
		if($pid) {
			//Ref_Num and Ref_Type = the 2 empty parameters for future dev
			updatePaymentDetails($order_no, $order_type, 'null', 'null', $payment_amount, $pid);
		}
		
		$payment = 'true';
	}

	header('Location: /order_form.php?'.(!empty($so_order) ?  'ps=Sale&on=' . $so_order : 'ps=Purchase&on=' . $po_order ).'&payment=' . $payment);
	exit;
