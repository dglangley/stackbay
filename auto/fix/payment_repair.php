<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

$DEBUG = 0;

	// Get all the records with the flipped invoice or bill on order
	$query = "SELECT * FROM payment_details WHERE order_type = 'Bill' OR order_type = 'Invoice';";
	$result = qedb($query);

	$errors = array();

	$TS = order_type('Invoice');
	$TP = order_type('Bill');

	while($r = qrow($result)) {
		$errors[] = $r;
	}

	$bad_orders = array();

	foreach($errors as $info) {
		$payment_info = array();
		$invoice = array();
		// $orders = array();

		echo 'Payment Details<BR>';
		print '<pre>' . print_r($info, true) . '</pre>';
		echo '<BR>';

		// Attempt to look for the correct order that should be corresponding to this payment

		// Get the payment information for each order
		$query = "SELECT * FROM payments WHERE id = ".$info['paymentid'].";";
		$result = qedb($query);

		if(qnum($result) > 0) {
			$r = qrow($result);
			$payment_info = $r;
		}

		// echo 'Payment <BR>';
		// print '<pre>' . print_r($payment_info, true) . '</pre>';
		// echo '<BR>';

		// With the companyid information, amount, date generated attempt to look into the sales order for invoice or bill for purchase orders to find the corresponding order number and type
		// Set the $T to the corresponding type depending on the payment type

		$T = $TS;
		
		if($info['order_type'] == 'Bill') {
			$T = $TP;
		}
		

		// Get the invoice associated with this messed up order and see if the order type and number is set
		$query = "SELECT * FROM ".$T['orders']." WHERE ".$T['order']." = ".res($info['order_number']).";";
		$result = qedb($query);

		if(qnum($result)) {
			$r = qrow($result);
			$invoice = $r;
		} else {
			echo 'Missing Order from Invoice <BR><BR>';
		}

		echo 'Invoice/Bill Info <BR>';
		print '<pre>' . print_r($invoice, true) . '</pre>';
		echo '<BR>';

		echo '<strong>Order Number:</strong> '.$invoice['order_number'].'<BR>';
		echo '<strong>Order Type:</strong> '.$invoice['order_type'].'<BR>';
		echo '<BR><BR>';

		// Commented stuff for my thought process

		// $query = "SELECT * FROM ".$T['orders']." WHERE companyid = ".res($payment_info['companyid'])." AND created <= ".fres($payment_info['date']).";";
		// $result = qedb($query);

		// while($r = qrow($result)) {
		// 	$orders[] = $r;
		// }

		// echo 'Order Canidates <BR>';
		// print '<pre>' . print_r($orders, true) . '</pre>';
		// echo '<BR>';

		if($invoice['order_number'] AND $invoice['order_type']) {
			// Before updating record all changes to the payment details on a map_payments table so if everything goes wrong we can track exactly which paymentid was altered inccorectly
			$query = "INSERT INTO map_payments (paymentid) VALUES (".res($info['paymentid']).");";
			qedb($query);

			$query = "UPDATE payment_details SET order_number = ".res($invoice['order_number']).", order_type = ".fres($invoice['order_type']).", ref_number = ".res($info['order_number']).", ref_type = ".fres($info['order_type'])." WHERE paymentid = ".res($info['paymentid']).";";
			// echo $query . '<BR><BR>';
			qedb($query);
		} else {
			$bad_orders[] = $info['paymentid'];
			echo $info['paymentid'] . ": there was a missing invoice/bill! <BR><BR>";
		}
	}

	echo 'BAD ORDERS WITH NO ORDER_NUMBER <BR>';
		print '<pre>' . print_r($bad_orders, true) . '</pre>';
		echo '<BR>';

	echo 'COMPLETED';
