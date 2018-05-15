<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"] . '/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getItemOrder.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getClass.php';
	
	// If passed in order number then it should contain the line number aka 0000-1
	$order_number = '';
	$line_number = 0;

	// Edit is always true for quotes
	$EDIT = true;
	$QUOTE_TYPE = true;

	// Allow users to define the order_type also such as Repair Quote / Outsourced Quote etc...
	$type = "service_quote";
	if(isset($_REQUEST['order_type'])) { $type = $_REQUEST['order_type']; }

	$T = order_type($type);

	$taskid = 0;
	if(isset($_REQUEST['taskid'])) { 
		$taskid = $_REQUEST['taskid']; 
		$order_number = getItemOrder($taskid, 'service_quote_items');
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
	$ORDER_ITEMS = $ORDER['items'];

	$ORDER['order_number'] = $order_number;
	$ORDER['order_type'] = $type;

	$ORDER_DETAILS = array();

	// extract the exact line number information from the ORDER variable
	if($taskid) {
		$ORDER_DETAILS = $ORDER_ITEMS[$taskid];
	} else if($line_number) {
		foreach($ORDER_ITEMS as $item) {
			if($item['line_number'] == $line_number) {
				$ORDER_DETAILS = $item;
				break;
			}
		}
	}

	if($taskid) {
		$TITLE = (getClass($ORDER['classid']) ? : $T['abbrev']).' '.$ORDER[$T['order']].'-'.$ORDER_DETAILS['line_number'];
	} else {
		$TITLE = 'New ' . $T['abbrev'];
	}
	
	$item_id = $taskid;

	// TABS 
	$SERVICE_TABS = array();

	if(empty($ORDER_DETAILS)) {
		$NEW_QUOTE = true;
	}

	// Generate an example for tabs
	$SERVICE_TABS[] = array('name' => 'Details', 'icon' => 'fa-list', 'price' => '', 'id' => 'details');
	$SERVICE_TABS[] = array('name' => 'Labor', 'icon' => 'fa-users', 'price' => 'SERVICE_LABOR_QUOTE', 'id' => 'labor');
	$SERVICE_TABS[] = array('name' => 'Materials', 'icon' => 'fa-microchip', 'price' => 'SERVICE_MATERIAL_COST', 'id' => 'materials');
	$SERVICE_TABS[] = array('name' => 'Outside Services', 'icon' => 'fa-suitcase', 'price' => 'SERVICE_OUTSIDE_COST', 'id' => 'outside');
	$SERVICE_TABS[] = array('name' => 'Total', 'icon' => 'fa-shopping-cart', 'price' => 'SERVICE_TOTAL_COST', 'id' => 'total');

	// Set the active tab here, if it is not set then Activity will be the default
	// $ACTIVE = 'details';

	include 'task.php';
	
	exit;