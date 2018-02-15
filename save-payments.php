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
			$query = "SELECT amount, id FROM payments WHERE number = ".prep($payment_ID).";";
			$result = qdb($query) OR die(qe().' '.$query);
			
			if (mysqli_num_rows($result)>0) {
	        	$r = mysqli_fetch_assoc($result);
				$payment_amount += $r['amount'];
				$id = $r['id'];
	        }
	        
			$query = "REPLACE payments (companyid, date, number, amount, payment_type, notes";
			if ($id) { $query .= ", id"; }
			$query .= ") VALUES (".prep($companyid).", ".$payment_date.", ".prep($payment_ID).", ".prep($payment_amount).", ".prep($payment_type).", ".prep($notes)."";
			if ($id) { $query .= ",".prep($id); }
			$query .= ");";
			qdb($query) OR die(qe().' '.$query);
			//Get the payment id
			if (! $id) { $id = qid(); }
			
			return $id;
		} 
	}
	
	function updatePaymentDetails($order, $type, $ref_type = 'null', $ref_num = 'null', $check_amount, $id) {
		//get the amount of the specific PO or SO
		$order_amount = 0;
		$paid = 0;
		//$paidTotal = 0;
		$query = '';
		
		//This query is mainly to get the total amount of cost for the order
		if($type == 'po') {
			$query = "SELECT SUM(qty * price) AS item_total FROM purchase_items WHERE po_number = ".prep($order).";";
		} else {
			$query = "SELECT SUM(qty * price) AS item_total FROM sales_items WHERE so_number = ".prep($order).";";
		}
		
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)>0) {
        	$r = mysqli_fetch_assoc($result);
			$order_amount += $r['item_total'];
        }
        
        //Find the amount paid so far for this order on this payment number
        //In most cases I do not believe the user will pay recursively with the same check# (this is most likely a fall back that will never be used)
		$query_details = "SELECT amount FROM payment_details WHERE order_number = ".prep($order)." AND order_type = ".prep($type)." AND paymentid = ".prep($id).";";
		
		$result = qdb($query_details) OR die(qe().' '.$query_details);
		if (mysqli_num_rows($result)>0) {
        	$r = mysqli_fetch_assoc($result);
			$paid += $r['amount'];
        }

        $paid = 0;
        
        //Sum the new payment with the old payment amount for the payment ID on the details level
        $check_amount += $paid;

        //Find the total amount paid so far for this order (UNUSED)
		$query_details = "SELECT SUM(amount) AS item_total FROM payment_details WHERE order_number = ".prep($order)." AND order_type = ".prep($type).";";
		
		//Just used to update the total amount paid for the current order amongst all payments given
		// $result = qdb($query_details) OR die(qe().' '.$query_details);
		// if (mysqli_num_rows($result)>0) {
  //      	$r = mysqli_fetch_assoc($result);
		// 	$paidTotal += $r['item_total'];
  //      }
        
		$query = "REPLACE payment_details (order_number, order_type, ref_number, ref_type, amount, paymentid) VALUES (".prep($order).", ".prep($type).", ".prep($ref_num).", ".prep($ref_type).", $check_amount, ".prep($id).");";
			qdb($query) OR die(qe().' '.$query);
	}
	
	// Get the CompanyID from Order
	function getCompanyID($order, $type) {
		$companyid = 0; 

		$order_field = '';
		if ($type=='sales_orders') { $order_field = 'so_number'; }
		else if ($type=='purchase_orders') { $order_field = 'po_number'; }
		else if ($type=='repair_orders') { $order_field = 'ro_number'; }
		
		$query = "SELECT companyid FROM $type WHERE $order_field = '".$order."'; ";
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
	
	$ref_grab;
	$ref_type;
	$ref_num;
	
	$so_order;
	$po_order;
	$ro_order;
	
	$payment_type;
	$notes;

	//Filters to be incorporated
	if (isset($_REQUEST['summary'])) { $summary = $_REQUEST['summary']; }
	if (isset($_REQUEST['start'])) { $start = $_REQUEST['start']; }
	if (isset($_REQUEST['end'])) { $end = $_REQUEST['end']; }
	if (isset($_REQUEST['table'])) { $table = $_REQUEST['table']; }
	if (isset($_REQUEST['order'])) { $order = $_REQUEST['order']; }
	if (isset($_REQUEST['companyid'])) { $companyid_search = $_REQUEST['companyid']; }
	if (isset($_REQUEST['filter'])) { $filter = $_REQUEST['filter']; }
	
	if (isset($_REQUEST['payment_type'])) { $payment_type = $_REQUEST['payment_type']; }
	if (isset($_REQUEST['payment_ID'])) { $payment_ID = $_REQUEST['payment_ID']; }
	if (isset($_REQUEST['payment_date'])) { $payment_date = $_REQUEST['payment_date']; }
	if (isset($_REQUEST['journal_entry'])) { $journal_entry = $_REQUEST['journal_entry']; }
	if (isset($_REQUEST['payment_amount'])) { $payment_amount = $_REQUEST['payment_amount']; }
	
	if (isset($_REQUEST['reference_button'])) { $ref_grab = explode(" ",$_REQUEST['reference_button']); }
	$ref_type = $ref_grab[0];
	$ref_num = $ref_grab[1];
	
	//Determine if this is an so or po
	if (isset($_REQUEST['so_order'])) {
		$so_order = $_REQUEST['so_order'];
		$order_type = 'so';
		$order_table = 'sales_orders';
		$order_name = 'Sale';
	}
	if (isset($_REQUEST['po_order'])) {
		$po_order = $_REQUEST['po_order'];
		$order_type = 'po';
		$order_table = 'purchase_orders';
		$order_name = 'Purchase';
	}
	if (isset($_REQUEST['ro_order'])) {
		$ro_order = $_REQUEST['ro_order'];
		$order_type = 'ro';
		$order_table = 'repair_orders';
		$order_name = 'Repair';
	}
	
	if (isset($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }

	//Special case for accounting page
	if (isset($_REQUEST['accounting_page'])) { $accounting_page = $_REQUEST['accounting_page']; }
	
	if (!$payment_ID) {
		$msg = 'Missing valid input data';
	} else {
		if ($so_order) { $order_no = $so_order; }
		else if ($po_order) { $order_no = $po_order; }
		else if ($ro_order) { $order_no = $ro_order; }
		
		$companyid = getCompanyID($order_no,$order_table);

		$pid = updatePayments($payment_type, $payment_ID, $payment_date, $journal_entry, $payment_amount, $companyid, $notes);
		
		//Insert into the details table
		if($pid) {
			//Ref_Num and Ref_Type = the 2 empty parameters for future dev
			updatePaymentDetails($order_no, $order_type, $ref_type, $ref_num, $payment_amount, $pid);
		}
		
		$payment = 'true';
	}

	if(!$accounting_page) {
		//header('Location: /order_form.php?'.(!empty($so_order) ?  'ps=Sale&on=' . $so_order : 'ps=Purchase&on=' . $po_order ).'&payment=' . $payment);
		header('Location: /order.php?ps='.$order_name.'&on='.$order_no.'&payment='.$payment);
	} else {
		header('Location: /accounts.php?payment=true&report_type='.(!empty($summary) ? $summary : '').'&START_DATE='.(!empty($start) ? $start : '').'&END_DATE='.(!empty($end) ? $end : '').'&orders_table='.(!empty($table) ? $table : '').'&order='.(!empty($order) ? $order : '').'&companyid='.(!empty($companyid_search) ? $companyid_search : '').'&filter='.(!empty($filter) ? $filter : '').'');
	}
	exit;
