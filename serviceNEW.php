<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"] . '/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getItemOrder.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getClass.php';
	
	// If passed in order number then it should contain the line number aka 0000-1
	$order_number = '';
	$line_number = 0;

	// Default for now as we have a Repair.php to handle all repairs
	$type = "Service";
	if(isset($_REQUEST['order_type'])) { $type = $_REQUEST['order_type']; }

	$T = order_type($type);

	$taskid = 0;
	if(isset($_REQUEST['taskid'])) { 
		$taskid = $_REQUEST['taskid']; 
		$order_number = getItemOrder($taskid, $T['item_label']);
	}

	if(isset($_REQUEST['order_number'])) { 
		$order_number = $_REQUEST['order_number'];  
	}

	preg_match_all("/\d+/", $order_number, $order_line);

	$order_number = reset($order_line)[0];
	$line_number = reset($order_line)[1];

	$ORDER = getOrder($order_number, $type);

	$ORDER_ITEMS = $ORDER['items'];

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

	$TITLE = getClass($ORDER['classid']).' '.$ORDER[$T['order']].'-'.$ORDER_DETAILS['line_number'];

	// print_r($ORDER);

	// print_r($ORDER);

	include 'task.php';
	
	exit;
