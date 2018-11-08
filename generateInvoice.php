<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/finalize_order.php';

	$DEBUG = 3;

	if (! $U['admin']) { exit; }

	$order_number = 0;
	$order_type = '';
	if (isset($_REQUEST['order_number']) AND $_REQUEST['order_number']) { $order_number = trim($_REQUEST['order_number']); }
	if (isset($_REQUEST['order_type']) AND $_REQUEST['order_type']) { $order_type = trim($_REQUEST['order_type']); }
//$order_number = 331308;
//$order_type = 'Repair';

	if (! $order_number OR ! $order_type) { exit; }

	$shipment_time = $GLOBALS['now'];

	finalize_order($order_number,$order_type,$shipment_time);
//	echo create_invoice(20103, "2017-05-10 14:43:58");//, "Sale");

//	setInvoice(401213,'Service');
?>
