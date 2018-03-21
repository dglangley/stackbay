<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrderNumber.php';

	$DEBUG = 0;
	$ERR = 0;

	function swapLines($item_ids, $order_type, $new_order) {
		global $ERR;

		$T = order_type($order_type);
		// $order_number = getOrderNumber($item_ids[0], $T['items'], $T['order'], false);
		foreach($item_ids as $line) {

			// Check if the line_number has ever been invoiced being either bill or anything else
			if($T['collection']) {
				$Ti = order_type($T['collection']);

				$query = "SELECT * FROM ".$Ti['items']." WHERE taskid = ".res($line)." AND task_label = ".fres($T['item_label']).";";
				$result = qedb($query);

				if(mysqli_num_rows($result)>0) {
					// line item has been invoiced
					$ERR = 1;

					continue;
				}
			}

			// Update the line_items with the new
			$query = "UPDATE ".$T['items']." SET ".$T['order']." = ".res($new_order)." WHERE id = ".res($line).";";
			qedb($query);
		}

		return true;
	}

	function assignTech($item_ids, $order_type, $techid, $start_datetime, $end_datetime) {
		global $ERR;

		$T = order_type($order_type);

		if ($start_datetime) { $start_datetime = date("Y-m-d H:i:s", strtotime($start_datetime)); }
		if ($end_datetime) { $end_datetime = date("Y-m-d H:i:s", strtotime($end_datetime)); }

		// $order_number = getOrderNumber($item_ids[0], $T['items'], $T['order'], false);

		foreach($item_ids as $line) {

			// Check if the user is already assigned
			$query = "SELECT * FROM service_assignments WHERE item_id = ".$line." AND item_id_label = ".fres($T['item_label'])." AND userid = ".res($techid).";";
			$result = qedb($query);

			if(mysqli_num_rows($result)==0) {
				// User is not yet assigned
				$query = "INSERT INTO service_assignments (item_id, item_id_label, userid, start_datetime,end_datetime) VALUES (".res($line).",".fres($T['item_label']).",".res($techid).", ".fres($start_datetime).", ".fres($end_datetime).");";
				qedb($query);
			}
		}

		return true;
	}

	$order_number = '';
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	$order_type = '';
	if (isset($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }
	$item_ids = array();
	if (isset($_REQUEST['item_ids'])) { $item_ids = $_REQUEST['item_ids']; }
	$techid = 0;
	if (isset($_REQUEST['techid'])) { $techid = trim($_REQUEST['techid']); }
	$start_datetime = 0;
	if (isset($_REQUEST['start_datetime'])) { $start_datetime = trim($_REQUEST['start_datetime']); }
	$end_datetime = 0;
	if (isset($_REQUEST['end_datetime'])) { $end_datetime = trim($_REQUEST['end_datetime']); }
	$new_order = 0;
	if (isset($_REQUEST['new_order'])) { $new_order = trim($_REQUEST['new_order']); }

	$assigned = false;
	$swapped = false;

	// print_r($_REQUEST);
	if($new_order) {
		$swapped = swapLines($item_ids, $order_type, $new_order);
	}

	if($techid) {
		$assigned = assignTech($item_ids, $order_type, $techid, $start_datetime, $end_datetime);
	}

	$parameter = '';

	if($swapped) {
		$parameter = '&success=swap';
	} else if($assigned) {
		$parameter = '&success=assign';
	}

	// Redirect also contains the current scanned parameters to be passed back that way the user doesn't need to reselect
	header('Location: /maintenance.php?order_type='.$order_type.'&order_number='.(($new_order AND ! $ERR)?$new_order:$order_number).($ERR ? '&ERR=' . $ERR:$parameter));

	exit;

	if ($DEBUG) { exit; }
