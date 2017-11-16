<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$order_number = 0;
	if (isset($_REQUEST['order_number']) AND trim($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	$order_type = '';
	if (isset($_REQUEST['order_type']) AND trim($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }

	$EDIT = true;
	$QUOTE = getOrder($order_number,$order_type);

	// prepare parameters for a new order form, basically
	$T = order_type($order_type);
	$items_table = $T['items'];
	$order_number = 0;
	unset($_REQUEST['order_number']);
	$order_type = $T['order_type'];
	//$_REQUEST['order_type'] = $order_type;

	$T = order_type($order_type);
	$T['items'] = $items_table;
	$T['record_type'] = 'quote';
	$ORDER = getOrder(0,$order_type);
	// now go back through $QUOTE and populate values into $ORDER so we can leverage the fields
	// from $ORDER for the order form, but retaining the values from $QUOTE for conversion
	foreach ($QUOTE as $k => $v) {
		$ORDER[$k] = $v;
	}

	include 'order.php';
	exit;
?>
