<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';

	function triggerActivity($ro_number, $repair_item_id, $notes, $techid, $now){
		$query = "INSERT INTO repair_activities (ro_number, repair_item_id, datetime, techid, notes) VALUES (".prep($ro_number).", ".prep($repair_item_id).", ".prep($now).", ".prep($techid).", ".prep($notes).");";
		$result = qdb($query) OR die(qe());
	}

	function stockUpdate($repair_item_id, $ro_number){
		// $query = "UPDATE inventory SET status ='shelved' WHERE repair_item_id = ".prep($repair_item_id).";";
		// $result = qdb($query) OR die(qe());

		$query = "UPDATE repair_orders SET status ='Completed' WHERE ro_number = ".prep($ro_number).";";
		$result = qdb($query) OR die(qe());
	}
	
	//Declare variables
	$ro_number;
	$repair_item_id;
	$notes;
	$techid;
	$partid;

	$trigger;
	
	if (isset($_REQUEST['ro_number'])) { $ro_number = $_REQUEST['ro_number']; }
	if (isset($_REQUEST['repair_item_id'])) { $repair_item_id = $_REQUEST['repair_item_id']; }
	if (isset($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }
	if (isset($_REQUEST['techid'])) { $techid = $_REQUEST['techid']; }
	if (isset($_REQUEST['partid'])) { $partid = $_REQUEST['partid']; }

	if (isset($_REQUEST['type'])) { 
		if($_REQUEST['type'] == 'claim'){
			$notes = "Claimed Ticket";
		} else if($_REQUEST['type'] == 'check_in'){
			$notes = "Checked In";
		} else if($_REQUEST['type'] == 'check_out'){
			$notes = "Checked Out";
		} else if($_REQUEST['type'] == 'complete_ticket'){
			$notes = "Repair Ticket Completed";
			$trigger = "complete";
		} 
	}

	triggerActivity($ro_number, $repair_item_id, $notes, $techid, $now);

	if($trigger == "complete") {
		stockUpdate($repair_item_id, $ro_number);
	}
	
	header('Location: /repair.php?on=' . $ro_number);

	exit;
