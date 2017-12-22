<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getServiceClass.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getItemOrder.php';

	$order_type = 'service_quote';//default for quote form
	// $task_edit = true; 
	$T = order_type($order_type);

	$quote = true;
	$EDIT = true;
	$type = 'Service';

	$order_number_details = (isset($_REQUEST['order_number']) ? $_REQUEST['order_number'] : '');
	$tab = (isset($_REQUEST['tab']) ? $_REQUEST['tab'] : '');
	$item_id = (isset($_REQUEST['taskid']) ? $_REQUEST['taskid'] : ''); 

	preg_match_all("/\d+/", $order_number_details, $order_number_split);

	$order_number_split = reset($order_number_split);

	$order_number = ($order_number_split[0] ? $order_number_split[0] : '');
	$line_number = ($order_number_split[1] ? $order_number_split[1] : '');

	// Convert from TaskID 
	if(empty($order_number) AND ! empty($item_id)) {
		preg_match_all("/\d+/", getItemOrder($item_id, 'service_quote_items'), $order_number_split);

		$order_number_split = reset($order_number_split);
		$order_number = ($order_number_split[0] ? $order_number_split[0] : '');
	}

	// echo $order_number;

	if(! empty($order_number)) {
		$EDIT = false;
		$ORDER = getOrder($order_number, $order_type);
	} else {
		$ORDER = getOrder(0, $order_type);
	}

	//Get the item_id if it is not set
	if(empty($item_id)) {
		foreach($ORDER['items'] as $item) {
			if($item['line_number'] == $line_number) {
				$item_id = $item['id'];
			}
		}
	}

	if($ORDER['classid']) {
		$service_class = getServiceClass($ORDER['classid']);
	}
	$ORDER['order_number'] = $order_number;
	$ORDER['order_type'] = $order_type;

	// Popluate the order_number
	$full_order_number = $ORDER['items'][$item_id]['quoteid'] . ($ORDER['items'][$item_id]['line_number'] ? '-' . $ORDER['items'][$item_id]['line_number'] : '');

	// echo $full_order_number;
	// print_r($ORDER);
	$item_details = $ORDER['items'];

	include 'task_view.php';
	exit;
