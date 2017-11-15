<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';

	function addComponent($partid, $qty, $conditionid = '5', $place, $instance, $bin, $purchase_item_id, $order_num, $repair_item_id, $userid, $now){
		$locationid;
		$ro_number;

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
		// print_r($qty);
		// exit;
		foreach($qty as $key => $amount) {
			if($amount > 0 && $key) {
				$newpartid;

				//Updates the amount received
				$query = "UPDATE purchase_items SET qty_received = ".prep($amount)." WHERE id = ".prep($key).";";
				qdb($query) OR die(qe());

				$query = "SELECT partid FROM purchase_items WHERE id = ".prep($key).";";

				$result = qdb($query);
				if (mysqli_num_rows($result)) {
					$result = mysqli_fetch_assoc($result);
					$newpartid = $result['partid'];
				}

				$I = array(
					'qty'=>$amount,
					'partid'=>$newpartid,
					'conditionid'=>$conditionid,
					'locationid'=>$locationid,
					'bin'=>$bin,
					'purchase_item_id'=>$key,
					'status'=>'received',
				);
				$inventoryid = setInventory($I);

				setCost($inventoryid);

/* 11-15-17
				$query = "INSERT INTO inventory (qty, partid, conditionid, status, locationid, bin, purchase_item_id, userid, date_created) VALUES (
					".prep($amount).",
					".prep($newpartid).",
					".prep($conditionid).",
					".prep($status).",
					".prep($locationid).",
					".prep($bin).",
					".prep($key).",
					".prep($userid).",
					".prep($now)."
				);";
				//echo $query;
				qdb($query) OR die(qe());
*/

				$partid = $newpartid;
			}
		}

		$query_repair = "SELECT ro_number FROM repair_items WHERE id = ".prep($repair_item_id).";";
		$repair_result = qdb($query_repair) or die(qe() . ' ' . $query_repair);

		if(mysqli_num_rows($repair_result)) {
			$repair_item = mysqli_fetch_assoc($repair_result);
			$ro_number = $repair_item['ro_number'];

			$query_request = "SELECT techid FROM purchase_requests WHERE ro_number = ".prep($ro_number)." AND partid = ".prep($partid)." LIMIT 1;";
			$request_result = qdb($query_request) or die(qe() . ' ' . $query_request);

			if(mysqli_num_rows($request_result)) {
				$request_item = mysqli_fetch_assoc($request_result);
				//$query = "INSERT INTO notifications (partid, userid) VALUES (".prep($partid).", ".$request_item['techid'].");";
				// $query = "INSERT INTO notifications (partid, userid) VALUES (".prep($partid).", '16');";
				// $result = qdb($query) or die(qe() . ' ' . $query);

				$query = "SELECT * FROM parts WHERE id = ".prep($partid)."; ";
				$result = qdb($query);

				if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$part = $r['part'];
				}

				$message = 'received for Repair# ' . $ro_number;

				$link = '/repair.php?on='.$ro_number;

				$query = "INSERT INTO messages (datetime, message, userid, link, ref_1, ref_1_label, ref_2, ref_2_label) ";
				$query .= "VALUES ('".$now."', ".prep($message).", ".prep($userid).", ".prep($link).", ".prep($partid).", 'partid', ".prep($ro_number).", 'ro_number');";

				qdb($query) or die(qe() . ' ' . $query);
				$messageid = qid();

				$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '16');";
				$result = qdb($query) or die(qe() . ' ' . $query);
			}
		}
	}
	
	//Declare variables
	$partid;
	$qty; 
	$conditionid; 
	$locationid;
	$purchase_item_id;

	$order_num; 

	$userid = $U['id'];
	
	if (isset($_REQUEST['order_num'])) { $order_num = $_REQUEST['order_num']; }
	if (isset($_REQUEST['partid'])) { $partid = $_REQUEST['partid']; }
	if (isset($_REQUEST['componentQTY'])) { $qty = $_REQUEST['componentQTY']; }
	if (isset($_REQUEST['conditionid'])) { $conditionid = $_REQUEST['conditionid']; }
	if (isset($_REQUEST['place'])) { $place = $_REQUEST['place']; }
	if (isset($_REQUEST['instance'])) { $instance = $_REQUEST['instance']; }
	if (isset($_REQUEST['bin'])) { $bin = $_REQUEST['bin']; }
	if (isset($_REQUEST['purchase_item_id'])) { $purchase_item_id = $_REQUEST['purchase_item_id']; }
	if (isset($_REQUEST['repair_item_id'])) { $repair_item_id = $_REQUEST['repair_item_id']; }

	//echo $partid . ' | ' . $qty . ' | ' . $conditionid . ' | ' . $locationid . ' | ' . $purchase_item_id . ' | ' . $repair_item_id . ' | ' . $userid . ' | ' . $now;

	addComponent($partid, $qty, $conditionid, $place, $instance, $bin, $purchase_item_id, $order_num, $repair_item_id, $userid, $now);
	
	header('Location: /inventory_add.php?on=' . $order_num);

	exit;
