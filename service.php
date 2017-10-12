<?php
	$quote = false;

	$type = isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : $type;
	$order_number_details = (isset($_REQUEST['order_number']) ? $_REQUEST['order_number'] : '');
	$task_edit = (isset($_REQUEST['edit']) ? $_REQUEST['edit'] : false);
	$tab = (isset($_REQUEST['tab']) ? $_REQUEST['tab'] : ''); 

	preg_match_all("/\d+/", $order_number_details, $order_number_split);

	$order_number_split = reset($order_number_split);

	$order_number = ($order_number_split[0] ? $order_number_split[0] : '');
	$task_number = ($order_number_split[1] ? $order_number_split[1] : '');

	if(empty($task_number)) {
		$task_number = 1;
	}

	include 'task_view.php';
	exit;
