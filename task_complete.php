<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/lici.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';

	$DEBUG = 0;
	$ALERT = '';

	function completeOrder($T, $taskid, $code) {
		$query = "UPDATE ".$T['items']." SET ".($T['type'] == 'Repair' ? 'repair_code_id' : 'status_code')." = ".res($code)." WHERE id = ".res($taskid).";";

		// echo $query;
		qedb($query);

		// Now that the task has been set to complete based on a code
		// Log the User out and set them to the internal job
		if(! $GLOBALS['U']['manager'] OR ! $GLOBALS['U']['admin']) {
			lici(0, '', 'out');

			// Using David's clock in mechanism search for an assigned internal job
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
				else { $query .= "AND o.companyid = '".$PROFILE['companyid']."' "; }//ventura telephone id
				$query .= "AND sa.item_id_label = '".$T['item_label']."' AND sa.userid = '".res($GLOBALS['U']['id'])."' ";
				$query .= "AND ((sa.start_datetime IS NULL OR sa.start_datetime < '".res($now)."') AND (sa.end_datetime IS NULL OR sa.end_datetime > '".res($now)."')); ";
				$result = qedb($query);

				// echo $query;
				if (mysqli_num_rows($result)==0) { continue; }
				$r = mysqli_fetch_assoc($result);

				$taskid = $r['id'];
				$task_label = $T['item_label'];
				break;
			}

			lici($taskid, $task_label, 'clock');
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
