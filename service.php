<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getServiceClass.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getItemOrder.php';

	$quote = false;

	$type = isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : 'Service';
	$type = ucwords($type);
	$order_number_details = (isset($_REQUEST['order_number']) ? $_REQUEST['order_number'] : '');
	$task_edit = (isset($_REQUEST['edit']) ? $_REQUEST['edit'] : false);
	$tab = (isset($_REQUEST['tab']) ? $_REQUEST['tab'] : ''); 

	$master_title = '';

	$item_id = (isset($_REQUEST['taskid']) ? $_REQUEST['taskid'] : ''); 

	preg_match_all("/\d+/", $order_number_details, $order_number_split);

	$order_number_split = reset($order_number_split);
	$order_number = ($order_number_split[0] ? $order_number_split[0] : '');
	$line_number = ($order_number_split[1] ? $order_number_split[1] : '');

	if(empty($line_number)) {
	 	$line_number = 1;
	}

	if(empty($order_number) AND ! empty($item_id)) {
		preg_match_all("/\d+/", getItemOrder($item_id, 'service_items'), $order_number_split);

		$order_number_split = reset($order_number_split);
		$order_number = ($order_number_split[0] ? $order_number_split[0] : '');
	}

	$ORDER = getOrder($order_number, $type);
	
	if($ORDER['classid']) {
		$service_class = getServiceClass($ORDER['classid']);
	}

	$ORDER['order_number'] = $order_number;
	$ORDER['order_type'] = $type;

	//Get the item_id if it is not set
	if(empty($item_id)) {
		foreach($ORDER['items'] as $item) {
			if($item['line_number'] == $line_number) {
				$item_id = $item['id'];
			}
		}
	}

	// Popluate the order_number
	$full_order_number = ($ORDER['items'][$item_id]['so_number'] ?: $ORDER['items'][$item_id]['ro_number']) . ($ORDER['items'][$item_id]['line_number'] ? '-' . $ORDER['items'][$item_id]['line_number'] : '');

	// Determine here what kind of line item this is...
	if($ORDER['items'][$item_id]['ref_2_label'] == 'service_item_id') {
		$co_name = $ORDER['items'][$item_id]['task_name'];
		$masterid = $ORDER['items'][$item_id]['ref_2'];

		// Get the master information here
		$master_title = $ORDER['items'][$masterid]['task_name'] . ' ' . $ORDER['items'][$masterid]['so_number'] . '-' . $ORDER['items'][$masterid]['line_number'];
	}

	include 'task_view.php';
	
	exit;
