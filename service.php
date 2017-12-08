<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getServiceClass.php';

	$quote = false;

	$ico = false;
	$cco = false;

	// $ico = isset($_REQUEST['ico']) ? true : false;
	// $cco = isset($_REQUEST['cco']) ? true : false;

	$type = isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : 'Service';
	$order_number_details = (isset($_REQUEST['order_number']) ? $_REQUEST['order_number'] : '');
	$task_edit = (isset($_REQUEST['edit']) ? $_REQUEST['edit'] : false);
	$tab = (isset($_REQUEST['tab']) ? $_REQUEST['tab'] : ''); 
	$view_mode = (isset($_REQUEST['view']) ? true : false); 

	// echo $view_mode . ' test';

	preg_match_all("/\d+/", $order_number_details, $order_number_split);

	$order_number_split = reset($order_number_split);

	$order_number = ($order_number_split[0] ? $order_number_split[0] : '');
	$task_number = ($order_number_split[1] ? $order_number_split[1] : '');

	if(empty($task_number)) {
	 	$task_number = 1;
	}

	$ORDER = getOrder($order_number, ucwords($type));
	
	if($ORDER['classid']) {
		$service_class = getServiceClass($ORDER['classid']);
	}
	$ORDER['order_number'] = $order_number;
	$ORDER['order_type'] = $type;

	include 'task_view.php';
	
	exit;
