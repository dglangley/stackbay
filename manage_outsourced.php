<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$DEBUG = 0;

	$order_number = 0;
	if (isset($_REQUEST['order_number']) AND trim($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	$order_type = '';
	if (isset($_REQUEST['order_type']) AND trim($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }
	$taskid = '';
	if (isset($_REQUEST['taskid']) AND trim($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }

	$EDIT = true;
	$create_order = 'Outsourced';

	//unset($_REQUEST['order_number']);
	//$_REQUEST['order_type'] = 'Outsourced';
	//unset($_REQUEST['order_type']);

	$ORDER = getOrder(0,'Outsourced');
	$ORDER['order_number'] = $order_number;
	$ORDER['order_type'] = $order_type;

	$order_number = 0;
	$order_type = 'Outsourced';
	$T = order_type($order_type);

	if ($DEBUG) { exit; }

	include 'order.php';
	exit;
?>
