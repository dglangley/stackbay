<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';

	$DEBUG = 3;

	function setInvoice($order_number,$order_type,$taskid=false) {
		if (! $order_type) {
			die("Order type must be set!");
		}
		$T = order_type($order_type);

		$ORDER = getOrder($order_number,$order_type);
/*
		$query = "SELECT * FROM ".$T['orders']." WHERE ".$T['order']." = '".res($order_number)."'; ";
		$result = qedb($query);

		// generate invoice with items
		$query = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = '".res($order_number)."'; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
		}
*/
		print "<pre>".print_r($ORDER,true)."</pre>";


		// calculate cost, and subsequent profits


		// generate commissions
	}
?>
