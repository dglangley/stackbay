<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/saveFiles.php';

	$DEBUG = 0;
	$ALERT = '';

	function editDetails($taskid, $search_type, $fieldid, $description){
		global $ALERT, $T;

		$label = '';

		if($search_type[0] == 'Site') {
			$label = 'addressid';
		} else if($search_type[0] == 'Part') {
			$label = 'partid';
		}

		// Update the values here
		$query = "UPDATE ".$T['items']." SET item_label = ".fres($label).", item_id = ".fres(reset($fieldid)).", description = ".fres($description)." WHERE id=".res($taskid).";";
		qedb($query);
	}	

	// Order details
	$taskid = '';
	if (isset($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }
	$type = '';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }
	$order_number = '';
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }

	$fieldid = '';
	if (isset($_REQUEST['fieldid'])) { $fieldid = $_REQUEST['fieldid']; }
	$description = '';
	if (isset($_REQUEST['description'])) { $description = trim($_REQUEST['description']); }
	$search_type = '';
	if (isset($_REQUEST['search_type'])) { $search_type = trim($_REQUEST['search_type']); }
	
	$T = order_type($type);

	editDetails($taskid, $search_type, $fieldid, $description);

	header('Location: /quoteNEW.php?taskid=' . $taskid . '&tab=details' . ($ALERT?'&ALERT='.$ALERT:''));

	exit;
