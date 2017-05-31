<?php

//Prepare the page as a JSON type
header('Content-Type: application/json');

$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	
	$components = $_REQUEST['requested_items'];
	$order_number = $_REQUEST['order_number'];
	$techid = $U['id'];
	$requested = $now;
	$repair_item_id = $_REQUEST['repair_item_id'];
	
	function saveReq($techid, $order_number, $requested, $components, $repair_item_id) {
		$query;
		$result;

		foreach($components as $item) {
			$query = "INSERT INTO purchase_requests (techid, ro_number, requested, partid, qty) VALUES (".prep($techid).", ".prep($order_number).", ".prep($requested).", ".prep($item['part']).", ".prep($item['qty']).");";
			$result = qdb($query);

			// if($result) {
			// 	$notes = "Part #" . $item['part'] . " Requested. Qty: " . $item['qty'];
			// 	triggerActivity($order_number, $repair_item_id, $notes, $techid, $requested);
			// }
		}

		return $result;
	}

	// function triggerActivity($ro_number, $repair_item_id, $notes, $techid, $now){
	// 	$query = "INSERT INTO repairs_activities (ro_number, repair_item_id, datetime, techid, notes) VALUES (".prep($ro_number).", ".prep($repair_item_id).", ".prep($now).", ".prep($techid).", ".prep($notes).");";
	// 	$result = qdb($query) OR die(qe());
	// }
	
	$result = saveReq($techid, $order_number, $requested, $components, $repair_item_id);
		
	echo json_encode($result);
    exit;
