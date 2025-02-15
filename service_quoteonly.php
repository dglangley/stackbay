<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';

	// Check the Mobile
	if(is_mobile()) {
		include_once $_SERVER["ROOT_DIR"].'/responsive_task.php';

		exit;
	}
	
	include_once $_SERVER["ROOT_DIR"] . '/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getServiceClass.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getItemOrder.php';

	$quote = false;
	$ICO = false;

	$type = isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : 'Service';

	// Fear the power of scalability
	$T = order_type($type);

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
		preg_match_all("/\d+/", getItemOrder($item_id, $T['item_label']), $order_number_split);

		$order_number_split = reset($order_number_split);
		$order_number = ($order_number_split[0] ? $order_number_split[0] : '');
	}

	$BUILD = false;
	if ($type=='Build') {
		$query = "SELECT b.ro_number FROM builds b, repair_items ri ";
		$query .= "WHERE b.id = '".res($order_number)."' AND b.ro_number = ri.ro_number; ";
		$result = qedb($query);
		if (qnum($result)>0) {
			$r = qrow($result);

			$BUILD = $order_number;
			$order_number = $r['ro_number'].'-'.$r['line_number'];
			$type = 'Repair';
		}
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
			if($item['line_number'] == $line_number OR (count($ORDER['items'])==$line_number)) {//! $item['line_number'] AND ! $line_number)) {
				$item_id = $item['id'];
			}
		}
	}

	$item_details = $ORDER['items'][$item_id];

	// Popluate the order_number
	$full_order_number = ($item_details['so_number'] ?: $item_details['ro_number']) . ($item_details['line_number'] ? '-' . $item_details['line_number'] : '');

	// Determine here what kind of line item this is...
	if($item_details['os_number']) {
		// If it has this then it must be an Outsourced Order
		$full_order_number = 'Outside Order# ' . $item_details['os_number'] .($item_details['line_number'] ? '-' . $item_details['line_number'] : '');
	} else if($item_details['ref_2_label'] == 'service_item_id') {
		$co_name = $item_details['task_name'];
		$masterid = $item_details['ref_2'];

		// detect if it is an ICO or CCO
		// ICO should not show a cost towards the customer but a cost towards ourselves
		if($item_details['amount'] == 0) {
			$ICO = true;
		}

		// Get the master information here
		$master_title = $ORDER['items'][$masterid]['task_name'] . ' ' . $ORDER['items'][$masterid]['so_number'] . '-' . $ORDER['items'][$masterid]['line_number'];
	}

	// echo $full_order_number;

	// We want Service Details from Outsourced
	if($type == "Outsourced") {
		$type = 'Service';
	}

	if ($order_number AND ! $item_id) {
		$ORDER['order_number'] = $order_number;
		$ORDER['order_type'] = $type;
		include 'order.php';
	} else {
		include 'task_view.php';
	}
	
	exit;
