<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';

	// Check the Mobile
	if(is_mobile()) {
		include_once $_SERVER["ROOT_DIR"].'/responsive_task.php';

		exit;
	}
	
	include_once $_SERVER["ROOT_DIR"] . '/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"] . '/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getItemOrder.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getClass.php';
	
	// If passed in order number then it should contain the line number aka 0000-1
	$order_number = '';
	$quote_order = '';
	$line_number = 0;

	$EDIT = false;
	$QUOTE_TYPE = false;
	
	// Default for now as we have a Repair.php to handle all repairs
	$type = "Service";
	if(isset($_REQUEST['order_type'])) { $type = $_REQUEST['order_type']; }

	$T = order_type($type);

	$taskid = 0;
	if(isset($_REQUEST['taskid']) AND $_REQUEST['taskid']) { 
		$taskid = $_REQUEST['taskid']; 
		$order_number = getItemOrder($taskid, $T['item_label']);
	}

	if(isset($_REQUEST['order_number'])) { 
		$order_number = $_REQUEST['order_number'];  
	}

	preg_match_all("/\d+/", $order_number, $order_line);

	$order_number = reset($order_line)[0];
	$line_number = reset($order_line)[1];

	// If there is no line number then assume it will be the first line
	if(! $line_number) {$line_number = 1; }

	$ORDER = getOrder($order_number, $type);

	// Add this for the order sidebar if statement
	$ORDER['order_number'] = $order_number;
	$ORDER['order_type'] = $type;

	$ORDER_ITEMS = $ORDER['items'];

	$ORDER_DETAILS = array();
	$QUOTE_DETAILS = array();

	// extract the exact line number information from the ORDER variable
	if($taskid) {
		$ORDER_DETAILS = $ORDER_ITEMS[$taskid];
	} else if($line_number) {
		foreach($ORDER_ITEMS as $rowID => $item) {
			$num_items = count($ORDER_ITEMS);
//			if($item['line_number'] == $line_number) {
			if($item['line_number'] == $line_number OR ($num_items==1 AND $line_number==1 AND ! $item['line_number'])) {
				$ORDER_DETAILS = $item;
				$taskid = $rowID;
				break;
			}
		}
	}

	$item_id = $taskid;

	// This order has a quote
	if($ORDER_DETAILS['quote_item_id']) {
		preg_match_all("/\d+/", getItemOrder($ORDER_DETAILS['quote_item_id'], 'service_quote_items'), $quote_order_info);

		$quote_order = reset($quote_order_info)[0];
		$quote_linenumber = reset($quote_order_info)[1];

		$QUOTE = getOrder($quote_order, 'service_quote');

		$QUOTE_DETAILS = $QUOTE['items'][$ORDER_DETAILS['quote_item_id']];
	}

//	$TITLE = ($ORDER_DETAILS['task_name'] ? $ORDER_DETAILS['task_name'] : (getClass($ORDER['classid']) ? : $type)).' '.$ORDER[$T['order']].'-'.$ORDER_DETAILS['line_number'];
	$TITLE = ($ORDER_DETAILS['task_name'] ? $ORDER_DETAILS['task_name'] : (getClass($ORDER['classid']) ? : $type)).' '.$ORDER[$T['order']].($ORDER_DETAILS['line_number'] ? '-'.$ORDER_DETAILS['line_number'] : '');


	// TABS 
	$SERVICE_TABS = array();

	// Generate an example for tabs
	$SERVICE_TABS[] = array('name' => 'Activity', 'icon' => 'fa-folder-open-o', 'price' => '', 'id' => 'activity');
	$SERVICE_TABS[] = array('name' => 'Details', 'icon' => 'fa-list', 'price' => '', 'id' => 'details');
	if($type != 'Repair') {
		$SERVICE_TABS[] = array('name' => 'Documentation', 'icon' => 'fa-file-pdf-o', 'price' => '', 'id' => 'documentation');
	}
	$SERVICE_TABS[] = array('name' => 'Labor', 'icon' => 'fa-users', 'price' => 'SERVICE_LABOR_COST', 'id' => 'labor');
	$SERVICE_TABS[] = array('name' => 'Materials', 'icon' => 'fa-microchip', 'price' => 'SERVICE_MATERIAL_COST', 'id' => 'materials');
	$SERVICE_TABS[] = array('name' => 'Expenses', 'icon' => 'fa-credit-card', 'price' => 'SERVICE_EXPENSE_COST', 'id' => 'expenses');
	$SERVICE_TABS[] = array('name' => 'Outside Services', 'icon' => 'fa-suitcase', 'price' => 'SERVICE_OUTSIDE_COST', 'id' => 'outside');
	$SERVICE_TABS[] = array('name' => 'Images', 'icon' => 'fa-file-image-o', 'price' => '', 'id' => 'images');
	$SERVICE_TABS[] = array('name' => 'Total', 'icon' => 'fa-shopping-cart', 'price' => 'SERVICE_TOTAL_COST', 'id' => 'total');

	include 'task.php';
	
	exit;
