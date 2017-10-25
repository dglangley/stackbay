<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';

	function getOrderNumber($item_id, $table = 'repair_items', $field = 'ro_number') {
		$order_number = 0;

		$query = "SELECT $field as order_number FROM $table WHERE id = ".res($item_id).";";
		$result = qdb($query) OR die(qe().' '.$query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$order_number = $r['order_number'];
		}

		return $order_number;
	}

	function completeTask($item_id, $order_number, $repair_code_id, $techid, $table = 'repair_activities', $field = 'repair_item_id', $type = 'repair') {

		$notes = '';

		$query = "SELECT description FROM repair_codes WHERE id = ".res($repair_code_id).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		//echo $query;

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$notes = ucwords($type) . ' completed. Final Status: <b>' . $r['description'] . '</b>';
		}

		//$order_number = getOrderNumber($item_id);

		$query = "INSERT INTO $table (ro_number, $field, datetime, techid, notes) VALUES (".res($order_number).",".res($item_id).", '".res($GLOBALS['now'])."', ".res($techid).", '".res($notes)."');";
		//echo $query;
		qdb($query) OR die(qe().' '.$query);

		// Update the repair_code_id to the order
		if($type == 'repair') {
			$query = "UPDATE repair_orders SET repair_code_id = ".res($repair_code_id)." WHERE ro_number = ".res($order_number).";";
			qdb($query) OR die(qe().' '.$query);
		}

	}

	function testTask($item_id, $field = "repair_item_id") {
		$invids = array();

		// Retrieve all inventory ids that belong to this task
		$query = "SELECT id, status FROM inventory WHERE $field = ".res($item_id).";";
		$result = qdb($query) OR die(qe().' '.$query);

		while($r = mysqli_fetch_assoc($result)) {
			$invids[] = $r;
		}

		// Allow to change status of all inventory items associated with this task
		foreach($invids as $data) {
			$status = $data['status'];

			// Only allow options to set the status if status is these 2 listed below
			if($status == 'in repair') {
				$status = 'testing';
			} else if($status == 'testing') {
				$status = 'in repair';
			} else {
				$status = '';
			}

			if(! empty($status)) {
				$query = "UPDATE inventory SET status = '".res($status)."' WHERE id = ".res($data['id']).";";
				qdb($query) OR die(qe().' '.$query);
			}
		}
	}

	$techid = $GLOBALS['U']['id'];
	$order_number = 0;
	$item_id = 0;
	$repair_code_id = 0;

	$type = '';

	if (isset($_REQUEST['order_number'])) { $order_number = $_REQUEST['order_number']; }
	if (isset($_REQUEST['item_id'])) { $item_id = $_REQUEST['item_id']; }
	if (isset($_REQUEST['repair_code_id'])) { $repair_code_id = $_REQUEST['repair_code_id']; }
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }

	$order_number = getOrderNumber($item_id);

	if($type == 'complete'){
		// completeTask($item_id, $repair_code_id, $techid, $table = 'repair_activites', $field = 'repair_item_id', $type = 'repair')
		completeTask($item_id, $order_number, $repair_code_id, $techid);
	} else if($type == 'test') {
		testTask($item_id);
	}

	header('Location: /service.php?order_type=repair&order_number='.$order_number);

	exit;
