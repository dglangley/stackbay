<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$DEBUG = 0;
	$ALERT = '';

	function completeOrder($T, $taskid, $code) {
		$query = "UPDATE ".$T['items']." SET ".($T['type'] == 'Repair' ? 'repair_code_id' : 'status_code')." = ".res($code)." WHERE id = ".res($taskid).";";

		// echo $query;
		qedb($query);
	}

	$taskid = '';
	if (isset($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }
	$order_number = '';
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	$type = '';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }

	$order_type = '';
	if (isset($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }

	$service_code_id = '';
	if (isset($_REQUEST['service_code_id'])) { $service_code_id = trim($_REQUEST['service_code_id']); }

	$T = order_type($order_type);

	// Responsive Testing
	$responsive = false;
	if (isset($_REQUEST['responsive'])) { $responsive = trim($_REQUEST['responsive']); }

	$link = '/serviceNEW.php';

	if($responsive) {
		$link = '/responsive_task.php';
	} 

	if($type == 'complete') {
		completeOrder($T, $taskid, $service_code_id);
	}

	header('Location: '.$link.'?order_type='.ucwords($order_type).'&taskid=' . $taskid . ($ALERT?'&ALERT='.$ALERT:''));	

	exit;
