<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';

	function triggerActivity($ro_number, $repair_item_id, $notes, $techid, $now, $trigger, $check_in){
		if ($_REQUEST['type'] == 'test_changer'){
			$status = "in repair";
			$select = "SELECT `status` FROM `inventory` where `repair_item_id` = ".prep($repair_item_id).";";
			$result = qdb($select) OR die(qe()." | $select");
			if(mysqli_num_rows($result)){
				$result = mysqli_fetch_assoc($result);
				$status = $result['status'];
				if(strtolower($status) == 'in repair'){
					$status = 'in testing';
				} else {
					$status = 'in repair';
				}
			}
			
			$query = "UPDATE `inventory` SET `status`='$status' WHERE `repair_item_id` = ".prep($repair_item_id).";";
			qdb($query) or die(qe()." | $query");
		}

		if($trigger == "complete" && $check_in == 'check_out') {
			$query = "INSERT INTO repair_activities (ro_number, repair_item_id, datetime, techid, notes) VALUES (".prep($ro_number).", ".prep($repair_item_id).", ".prep($now).", ".prep($techid).", 'Checked Out');";
			$result = qdb($query) OR die(qe());
		}
		$query = "INSERT INTO repair_activities (ro_number, repair_item_id, datetime, techid, notes) VALUES (".prep($ro_number).", ".prep($repair_item_id).", ".prep(date('Y-m-d H:i:s',strtotime($now) + 1)).", ".prep($techid).", ".prep($notes).");";
		$result = qdb($query) OR die(qe());
		
		if($trigger == "complete"){
			$select = "SELECT `id` FROM `inventory` where repair_item_id = '$repair_item_id';";
			$invid_result = qdb($select) or die(qe()." | $select");
			if(mysqli_num_rows($invid_result)){
				$invid_arr = mysqli_fetch_assoc($invid_result);
				setCost($invid_arr['id']);
			}
		}
		
	}

	function stockUpdate($repair_item_id, $ro_number, $repair_code){

		$query = "UPDATE inventory SET status ='in repair' WHERE repair_item_id = ".prep($repair_item_id).";";
		$result = qdb($query) OR die(qe());

		$query = "UPDATE repair_orders SET repair_code_id = ".prep($repair_code)." WHERE ro_number = ".prep($ro_number).";";
		$result = qdb($query) OR die(qe());
	}

	function addtoStock($place, $instance, $condition, $serial_no){
		$locationid = getLocation($place, $instance);
		foreach ($serial_no as $serial) {
			$query = "UPDATE inventory SET locationid =".prep($locationid).", conditionid = ".prep($condition).", status = 'shelved', qty = 1 WHERE serial_no = ".prep($serial).";";
			//echo $query . "<br>";
		}
		qdb($query) OR die(qe());
	}

	function getLocation($place, $instance) {
		$locationid;
		$query;
		
		//Get the location ID based on the preset ones in the table
		if($instance != '') {
			$query = "SELECT id FROM locations WHERE place = '". res($place) ."' AND instance = '". res($instance) ."';";
		} else {
			$query = "SELECT id FROM locations WHERE place = '". res($place) ."' AND instance is NULL;";
		}
		
		$locationResult = qdb($query);
		
		if (mysqli_num_rows($locationResult)>0) {
			$locationResult = mysqli_fetch_assoc($locationResult);
			$locationid = $locationResult['id'];
		}
		
		return $locationid;
	}

	$ro_number;
	if (isset($_REQUEST['ro_number'])) { $ro_number = $_REQUEST['ro_number']; }

	if(isset($_REQUEST['type']) && $_REQUEST['type'] != 'receive') {
		//Declare variables within this scope
		$repair_item_id;
		$notes;
		$techid;
		$partid;

		$repair_components;

		$trigger;
		
		if (isset($_REQUEST['repair_item_id'])) { $repair_item_id = $_REQUEST['repair_item_id']; }
		if (isset($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }
		if (isset($_REQUEST['techid'])) { $techid = $_REQUEST['techid']; }
		if (isset($_REQUEST['partid'])) { $partid = $_REQUEST['partid']; }
		if (isset($_REQUEST['repair_components'])) { $repair_components = $_REQUEST['repair_components']; }
		if (isset($_REQUEST['check_in'])) { $check_in = $_REQUEST['check_in']; }
		if (isset($_REQUEST['repair_code'])) { $repair_code = $_REQUEST['repair_code']; }

		if($_REQUEST['type'] == 'claim'){
			$notes = "Claimed Ticket";
		} else if($_REQUEST['type'] == 'check_in'){
			$notes = "Checked In";
		} else if($_REQUEST['type'] == 'check_out'){
			$notes = "Checked Out";
		} else if($_REQUEST['type'] == 'complete_ticket'){
			$repair_text = "";

			$select = "SELECT description FROM repair_codes WHERE id = ".prep($repair_code).";";
			$results = qdb($select) or die(qe()." | $select");
	
			if (mysqli_num_rows($results)>0) {
				$results = mysqli_fetch_assoc($results);
				$repair_text = $results['description'];
			}

			$notes = "Repair Ticket Completed. Final Status: <b>" . $repair_text . "</b>";
			$trigger = "complete";
		} else if ($_REQUEST['type'] == 'test_changer'){
			$notes = "Marked as `In Testing`";
		}

		triggerActivity($ro_number, $repair_item_id, $notes, $techid, $now, $trigger, $check_in);

		if($trigger == "complete") {
			stockUpdate($repair_item_id, $ro_number, $repair_code);
		}

		header('Location: /repair.php?on=' . $ro_number);

	} else if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'receive') { 
		//Declare Variables within this scope
		$place;
		$instance;
		$condition;
		$serial_no;

		if (isset($_REQUEST['place'])) { $place = $_REQUEST['place']; }
		if (isset($_REQUEST['instance'])) { $instance = $_REQUEST['instance']; }
		if (isset($_REQUEST['condition'])) { $condition = $_REQUEST['condition']; }
		if (isset($_REQUEST['serial_no'])) { $serial_no = $_REQUEST['serial_no']; }
		if (isset($_REQUEST['bill_option'])) { $bill_option = $_REQUEST['bill_option']; }

		addtoStock($place, $instance, $condition, $serial_no);

		header('Location: /order_form.php?ps=repair&on=' . $ro_number);
	}

	exit;
