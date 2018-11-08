<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/lici.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getStatusCode.php';

	$DEBUG = 0;
	$ALERT = '';

	function completeOrder($T, $taskid, $code) {
		$query = "UPDATE ".$T['items']." SET ".($T['type'] == 'Repair' ? 'repair_code_id' : 'status_code')." = ".fres($code)." WHERE id = ".res($taskid).";";

		// echo $query;
		qedb($query);

		// Now that the task has been set to complete based on a code
		// Log the User out and set them to the internal job
		if(! $GLOBALS['U']['manager'] OR ! $GLOBALS['U']['admin']) {
			lici(0, '', 'out');

			// Using David's clock in mechanism search for an assigned internal job
			// check for assignments against service orders first
			$types = array('Service','Repair');

			$id = 0;
			$task_label = '';

			foreach ($types as $order_type) {
				$T2 = order_type($order_type);
				$ORDER = getOrder(0, $order_type);

				$query = "SELECT i.id FROM service_assignments sa, ".$T2['items']." i, ".$T2['orders']." o ";
				if (array_key_exists('classid',$ORDER)) { $query .= ", service_classes sc "; }
				$query .= "WHERE sa.item_id = i.id AND o.".$T2['order']." = i.".$T2['order']." ";
				if (array_key_exists('classid',$ORDER)) { $query .= "AND sc.class_name = 'Internal' AND sc.id = o.classid "; }
				else { $query .= "AND o.companyid = '".$PROFILE['companyid']."' "; }//ventura telephone id
				$query .= "AND sa.item_id_label = '".$T2['item_label']."' AND sa.userid = '".res($GLOBALS['U']['id'])."' ";
				$query .= "AND ((sa.start_datetime IS NULL OR sa.start_datetime < '".res($GLOBALS['now'])."') AND (sa.end_datetime IS NULL OR sa.end_datetime > '".res($GLOBALS['now'])."')); ";
				$result = qedb($query);

				// if no assignments then next?? not sure what's going on
				if (mysqli_num_rows($result)==0) { continue; }
				$r = mysqli_fetch_assoc($result);

				$id = $r['id'];
				$task_label = $T2['item_label'];
				break;//get the first and then break out of loop? finding this 11/7/18 and not sure what's going on
			}

			lici($id, $task_label, 'clock');

			// stamp status update into activity log
			if ($code) {
				$notes = $GLOBALS['order_type'].' status updated: '.getStatusCode($code,$GLOBALS['order_type']);

				$query = "INSERT INTO activity_log (item_id, item_id_label, datetime, userid, notes) ";
				$query .= "VALUES (".fres($taskid).", ".fres($T['item_label']).", '".res($GLOBALS['now'])."', ".res($GLOBALS['U']['id']).", ".fres($notes).");";
				qedb($query);
			}
		}
	}

	$taskid = '';
	if (isset($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }
	$order_number = '';
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	$type = '';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }

	$order_type = '';
	if (isset($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }

	$status_code = '';
	if (isset($_REQUEST['service_code_id']) AND $_REQUEST['service_code_id']) { $status_code = trim($_REQUEST['service_code_id']); }

	$T = order_type($order_type);

	// Responsive Testing
	$responsive = false;
	if (isset($_REQUEST['responsive'])) { $responsive = trim($_REQUEST['responsive']); }

	$link = '/service.php';

	if($responsive) {
		$link = '/responsive_task.php';
	} 

	if($type == 'complete') {
		completeOrder($T, $taskid, $status_code);
	}

	if ($DEBUG) { exit; }

	header('Location: '.$link.'?order_type='.ucwords($order_type).'&taskid=' . $taskid . ($ALERT?'&ALERT='.$ALERT:''));	

	exit;
