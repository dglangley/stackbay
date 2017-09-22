<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

	function triggerActivity($ro_number, $repair_item_id, $inventoryid=0, $notes, $techid, $trigger, $check_in){
		$now = $GLOBALS['now'];

		if ($_REQUEST['type'] == 'test_in' || $_REQUEST['type'] == 'test_out'){
			$status = "in repair";
			$select = "SELECT `status` FROM `inventory` WHERE ";
			if ($inventoryid) { $select .= "id = '".res($inventoryid)."' "; }
			else { $select .= "`repair_item_id` = ".prep($repair_item_id)." "; }
			$select .= "; ";
			$result = qdb($select) OR die(qe()." | $select");
			if(mysqli_num_rows($result)){
				$result = mysqli_fetch_assoc($result);
				$status = $result['status'];
				if(strtolower($status) == 'in repair'){
					$status = 'testing';
				} else {
					$status = 'in repair';
				}
			}

			$query = "UPDATE `inventory` SET `status`='$status' WHERE ";//`repair_item_id` = ".prep($repair_item_id).";";
			if ($inventoryid) { $query .= "id = '".res($inventoryid)."' "; }
			else { $query .= "`repair_item_id` = ".prep($repair_item_id)." "; }
			$query .= "; ";
			qdb($query) or die(qe()." | $query");
		}

		if($trigger == "complete" && $check_in == 'check_out') {
			$query = "INSERT INTO repair_activities (ro_number, repair_item_id, datetime, techid, notes) VALUES (".prep($ro_number).", ".prep($repair_item_id).", ".prep($now).", ".prep($techid).", 'Checked Out');";
			$result = qdb($query) OR die(qe());
		}
		$query = "INSERT INTO repair_activities (ro_number, repair_item_id, datetime, techid, notes) VALUES (".prep($ro_number).", ".prep($repair_item_id).", ".prep(date('Y-m-d H:i:s',strtotime($now) + 1)).", ".prep($techid).", ".prep($notes).");";
		$result = qdb($query) OR die(qe());
		
		if($trigger == "complete"){
			if(!isset($_REQUEST['build'])) {
				$select = "SELECT i.id FROM inventory i, repair_items ri, repair_orders ro WHERE ri.id = i.repair_item_id AND ro.ro_number = ri.ro_number AND ri.price = 0 AND termsid = 15 AND repair_item_id = '$repair_item_id';";
				$invid_result = qdb($select) or die(qe()." | $select");
				if(mysqli_num_rows($invid_result)){
					$invid_arr = mysqli_fetch_assoc($invid_result);
					setCost($invid_arr['id']);
				}

			} else {
				//get the qty of items that have been built
				$qty = 1;
				//Calculate the cost for the build
				$query = "SELECT qty FROM builds WHERE ro_number = ".prep($ro_number).";";
				$result = qdb($query) or die(qe()." | $query");
				if(mysqli_num_rows($result)){
					$result = mysqli_fetch_assoc($result);
					$qty = $result['qty'];
				}

				$costOfTotalRepair = calcRepairCost($ro_number);
				$costPerItem = ($costOfTotalRepair / $qty);

				$query = "UPDATE builds SET price = ".prep($costPerItem)." WHERE id = ".prep($_REQUEST['build']).";";
				qdb($query);
			}
		}
		
	}

	function triggerBuildTest($invid, $ro_number, $repair_item_id, $notes, $techid) {
		$now = $GLOBALS['now'];

		$status = "received";
		$notes = '';

		$select = "SELECT `status` FROM `inventory` where `id` = ".prep($invid).";";
		$result = qdb($select) OR die(qe()." | $select");
		if(mysqli_num_rows($result)){
			$result = mysqli_fetch_assoc($result);
			$status = $result['status'];
			if(strtolower($status) == 'received'){
				$status = 'in testing';
				$notes = getSerialNumber($invid) . ' Marked as `In Testing`';
			} else {
				$notes = getSerialNumber($invid) . ' Marked as `Tested`';
			}
		}

		$query = "UPDATE `inventory` SET `status`='$status' WHERE `id` = ".prep($invid).";";
			qdb($query) or die(qe()." | $query");

		$query = "INSERT INTO repair_activities (ro_number, repair_item_id, datetime, techid, notes) VALUES (".prep($ro_number).", ".prep($repair_item_id).", ".prep(date('Y-m-d H:i:s',strtotime($now) + 1)).", ".prep($techid).", ".prep($notes).");";
		$result = qdb($query) OR die(qe());
	}

	function stockUpdate($repair_item_id, $ro_number, $repair_code){
		$status = '';

		$query = "UPDATE inventory SET status ='in repair' WHERE repair_item_id = ".prep($repair_item_id).";";
		qdb($query) OR die(qe());

		$query = "UPDATE repair_orders SET repair_code_id = ".prep($repair_code)." WHERE ro_number = ".prep($ro_number).";";
		qdb($query) OR die(qe());

		$query = "SELECT description FROM repair_codes WHERE id = ".prep($repair_code)." ;";
		$result = qdb($query) OR die(qe());

		if(mysqli_num_rows($result)){
			$r = mysqli_fetch_assoc($result);
			$status = $r['description'];
		}

		// /repair.php?build=true&on=38
		if(empty($_REQUEST['build'])) {
			$message = 'RO# ' . $ro_number . ' Status: ' . $status;
			$link = '/RO' . $ro_number;
		} else {
			$message = 'BO# ' . $_REQUEST['build'] . ' Status: ' . $status;
			$link = '/repair.php?build=true&on=' . $_REQUEST['build'];
		}

		$query = "INSERT INTO messages (message, datetime, userid, link) ";
		$query .= "VALUES ('".res($message)."','".$GLOBALS['now']."',".$GLOBALS['U']['id'].", '".res($link)."'); ";
		qdb($query) OR reportError('Sorry, there was an error adding your note to the new db. Please notify Admin immediately! ' . $query);
		$messageid = qid();

		$team_users = array(getUser('Sam Campa','name','userid'),getUser('David Langley','name','userid'),getUser('Andrew Kuan','name','userid'));

		foreach ($team_users as $each_userid) {
			//if ($userid==$each_userid) { continue; }
			$query = "INSERT INTO notifications (messageid, userid, read_datetime, click_datetime) ";
			$query .= "VALUES ('".$messageid."','".$each_userid."',NULL,NULL); ";
			$result = qdb($query) OR reportError('Unfortunately, there was an error adding notifications for other users on your note. Please notify Admin immediately!');
		}

		// $query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '6');";
		// qdb($query) or die(qe() . ' ' . $query);
	}

	function addtoStock($place, $instance, $condition, $serial_no){
		$locationid = getLocation($place, $instance);
		foreach ($serial_no as $serial) {
			$query = "UPDATE inventory SET locationid =".prep($locationid).", conditionid = ".prep($condition).", status = 'received', qty = 1 WHERE serial_no = ".prep($serial).";";
			//echo $query . "<br>";
		}
		qdb($query) OR die(qe());
	}

	function getSerialNumber($invid){
		$serial;

		$query = "SELECT serial_no FROM inventory WHERE id = ".prep($invid).";";
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$serial = $result['serial_no'];
		}
		
		return $serial;
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

	if(isset($_REQUEST['type']) && $_REQUEST['type'] != 'receive' || $notes || isset($_REQUEST['build_test'])) {
		//Declare variables within this scope
		$repair_item_id;
		$inventoryid;
		$notes;
		$techid;
		$partid;

		$repair_components;

		$trigger;
		
		if (isset($_REQUEST['repair_item_id'])) { $repair_item_id = $_REQUEST['repair_item_id']; }
		if (isset($_REQUEST['inventoryid'])) { $inventoryid = $_REQUEST['inventoryid']; }
		if (isset($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }
		if (isset($_REQUEST['techid'])) { $techid = $_REQUEST['techid']; }
		if (isset($_REQUEST['partid'])) { $partid = $_REQUEST['partid']; }
		if (isset($_REQUEST['repair_components'])) { $repair_components = $_REQUEST['repair_components']; }
		if (isset($_REQUEST['check_in'])) { $check_in = $_REQUEST['check_in']; }
		if (isset($_REQUEST['repair_code'])) { $repair_code = $_REQUEST['repair_code']; }

		if(!$notes) {
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

				$notes = ($_REQUEST['build'] ? 'Build' : 'Repair Ticket')." Completed. Final Status: <b>" . $repair_text . "</b>";
				$trigger = "complete";
			} else if ($_REQUEST['type'] == 'test_in') {
				$notes = "Marked as `In Testing`";
			} else if($_REQUEST['type'] == 'test_out') {
				$notes = "Marked as `Tested`";
			}
		}

		if (! isset($_REQUEST['build_test'])) {
			triggerActivity($ro_number, $repair_item_id, $inventoryid, $notes, $techid, $trigger, $check_in);
		} else {
			triggerBuildTest($_REQUEST['build_test'], $ro_number, $repair_item_id, $notes, $techid);
		}

		if($trigger == "complete") {
			stockUpdate($repair_item_id, $ro_number, $repair_code);
		}

		header('Location: /repair.php?on=' . ($_REQUEST['build'] ? $_REQUEST['build'] . '&build=true' : $ro_number));

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
