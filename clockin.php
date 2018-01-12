<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/lici.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$DEBUG = 0;

	// check for assignments against service orders first
	$types = array('Service','Repair');

	$taskid = 0;
	$task_label = '';
	foreach ($types as $order_type) {
		$T = order_type($order_type);
		$ORDER = getOrder(0, $order_type);

		$query = "SELECT i.id FROM service_assignments sa, ".$T['items']." i, ".$T['orders']." o ";
		if (array_key_exists('classid',$ORDER)) { $query .= ", service_classes sc "; }
		$query .= "WHERE sa.item_id = i.id AND o.".$T['order']." = i.".$T['order']." ";
		if (array_key_exists('classid',$ORDER)) { $query .= "AND sc.class_name = 'Internal' AND sc.id = o.classid "; }
		else { $query .= "AND o.companyid = '25' "; }//ventura telephone id
		$query .= "AND sa.item_id_label = '".$T['item_label']."' AND sa.userid = '".res($U['id'])."' ";
		$query .= "AND ((sa.start_datetime IS NULL OR sa.start_datetime < '".res($now)."') AND (sa.end_datetime IS NULL OR sa.end_datetime > '".res($now)."')); ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) { continue; }
		$r = mysqli_fetch_assoc($result);

		$taskid = $r['id'];
		$task_label = $T['item_label'];
		break;
	}

	if (! $taskid) {
		die("You have not been assigned to an internal maintenance task, so you must clock in only directly on a billable job. Please see a manager if you feel this is in error.");
	}

	$order = lici($taskid, $task_label, 'clock');

	header('Location: /');
	exit;
?>
