<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/completeTask.php';

	function getOrderNumber($item_id, $table = 'repair_items', $field = 'ro_number') {
		$order_number = 0;

		$query = "SELECT $field as order_number, line_number FROM $table WHERE id = ".res($item_id).";";
		$result = qdb($query) OR die(qe().' '.$query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$order_number = $r['order_number'] . ($r['line_number'] ? '-' . $r['line_number'] : '');
		}

		return $order_number;
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
	$label = '';
	$service_code_id = 0;
	$notes = '';

	$type = '';

	if (isset($_REQUEST['order_number'])) { $order_number = $_REQUEST['order_number']; }
	if (isset($_REQUEST['item_id'])) { $item_id = $_REQUEST['item_id']; }
	if (isset($_REQUEST['item_id_label'])) { $label = $_REQUEST['item_id_label']; }
	if (isset($_REQUEST['service_code_id'])) { $service_code_id = $_REQUEST['service_code_id']; }
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }
	if (isset($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }

	$order_number = getOrderNumber($item_id, ($label == 'service_item_id' ? 'service_items' : 'repair_items'), ($label == 'service_item_id' ? 'so_number' : 'ro_number'));

	if($type == 'complete'){
		// completeTask($item_id, $service_code_id, $techid, $table = 'repair_activites', $field = 'repair_item_id', $type)
		completeTask($item_id, $service_code_id, 'activity_log', 'item_id', $label, $notes);
	} else if($type == 'test') {
		testTask($item_id);
	}

	header('Location: /service.php?order_type='.($label == 'service_item_id' ? 'Service' : 'Repair').'&order_number='.$order_number);

	exit;
