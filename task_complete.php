<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$DEBUG = 0;
	$ALERT = '';

	$taskid = '';
	if (isset($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }
	$order_number = '';
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	$type = '';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }

	$T = order_type($type);

	// Responsive Testing
	$responsive = false;
	if (isset($_REQUEST['responsive'])) { $responsive = trim($_REQUEST['responsive']); }

	$link = '/serviceNEW.php';

	if($responsive) {
		$link = '/responsive_task.php';
	} 

	header('Location: '.$link.'?order_type='.ucwords($type).'&taskid=' . $taskid . ($ALERT?'&ALERT='.$ALERT:''));	

	//header('Location: /serviceNEW.php?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=activity' . ($ALERT?'&ALERT='.$ALERT:''));

	exit;
