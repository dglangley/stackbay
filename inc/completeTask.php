<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRep.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/isBuild.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/calcTaskCost.php';

	// Add Lici to completing tasks
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/lici.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';

	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function completeTask($item_id, $service_code_id, $table = 'activity_log', $field = 'item_id', $label, $notes = '') {

		$query = '';
		$status_desc = '';

		// Signify if the job is having the status changed we can assume it is a manager doing it only
		$mang_change = false;

		if($label == 'repair_item_id') {
			$query = "SELECT description FROM repair_codes WHERE id = ".res($service_code_id).";";
		} else {
			$query = "SELECT description FROM status_codes WHERE id = ".res($service_code_id).";";
		}
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			if(! $notes) {
				$notes = ucwords(($label == 'service_item_id' ? 'Service' : 'Repair')) . ' completed. Final Status: <b>' . $r['description'] . '</b>';
			}
			$status_desc = $r['description'];
		}

		//$order_number = getOrderNumber($item_id);

		$query = "INSERT INTO $table ($field, item_id_label, datetime, userid, notes) VALUES (".res($item_id).", ".fres($label).", '".res($GLOBALS['now'])."', ".res($GLOBALS['U']['id']).", '".res($notes)."');";
		qedb($query);

		// Update the service_code_id to the order
		if($label == 'repair_item_id') {
			// Quick check to see if a repair code has been set or not
			$query = "SELECT * FROM repair_items WHERE repair_code_id IS NOT NULL AND id = ".res($item_id).";";
			$result = qedb($query);

			if(mysqli_num_rows($result)) {
				$mang_change = true;
			}

			// Repaired or No Trouble Found
			if($service_code_id == 1 OR $service_code_id == 2) {
				$query = "SELECT id FROM inventory WHERE repair_item_id = ".res($item_id).";";
				$result = qedb($query);

				if(mysqli_num_rows($result) > 0) {
					$r = mysqli_fetch_assoc($result);

					$inventoryid = $r['id'];
				}

				// If no trouble found or repaired then set the condition to repaired(untested)
				if($inventoryid) {
					$I = array('conditionid'=>5,'id'=>$inventoryid);
					$inventoryid = setInventory($I);
				}
			}

			// Updates the repair item with the corresponding repair code
			$query = "UPDATE repair_items SET repair_code_id = ".res($service_code_id)." WHERE id = ".res($item_id).";";
			qedb($query);

			// is this a build? in that case, Completing the repair entails a whole other set of events
			$BUILD = isBuild($item_id);

			if ($BUILD) {
				$query = "SELECT * FROM builds WHERE id = '".res($BUILD)."';";
				$result = qedb($query);
				if (qnum($result)==0) { die("We have a problem with Build ".$BUILD."...cant find it"); }
				$b = qrow($result);
				$build_qty = $b['qty'];
				$ro_number = $b['ro_number'];

				$build_cost = calcTaskCost($item_id,'repair_item_id');
                $cpu = ($build_cost / $build_qty);//cost per unit

                $query = "UPDATE builds SET price = '".res($cpu)."' WHERE id = '".res($BUILD)."';";
				qedb($query);
			}

			$query = "SELECT sales_rep_id, ro.ro_number as order_number, ri.line_number FROM repair_items ri, repair_orders ro WHERE id = ".res($item_id)." AND ri.ro_number = ro.ro_number;";
		} else {
			// Quick check to see if a status code has been set or not
			$query = "SELECT * FROM service_items WHERE status_code IS NOT NULL AND id = ".res($item_id).";";
			$result = qedb($query);

			if(mysqli_num_rows($result)) {
				$mang_change = true;
			}

			$query = "UPDATE service_items SET status_code = ".res($service_code_id)." WHERE id = ".res($item_id).";";
			qedb($query);

			$query = "SELECT sales_rep_id, so.so_number as order_number, si.line_number FROM service_items si, service_orders so WHERE id = ".res($item_id)." AND si.so_number = so.so_number;";
		}

		// Now that the task has been set to complete based on a code
		// Log the User out and set them to the internal job
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

		// Do not die for a manager or admin
		if (! $taskid AND ! array_intersect($USER_ROLES, array(1,4))) {
			die("You have not been assigned to an internal maintenance task, so you must clock in only directly on a billable job. Please see a manager if you feel this is in error.");
		}

		// If user is not admin or manager then clock them in
		if(! array_intersect($USER_ROLES, array(1,4))) {
			lici($taskid, $task_label, 'clock');
		}

		// Send Nofication and Email to the User or Manager depending on approval type
		// Get the Orignal creator userid from the order level (Assuming as Manager)
		$order_number = 0;
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			// Get the sales rep ID and set it as the user else set to Scott which is 8
			$managerid = ($r['sales_rep_id']?: '6');
			$order_number = $r['order_number'] . ($r['line_number'] ? '-' . $r['line_number'] : '-1');
		}

		$title = '';
		$message = '';
		$link = '';

		// First time submittion of a status
		if(! $mang_change) {

			if($label == 'repair_item_id') {
				//$status_desc = getStatus($service_code_id, 'repair_codes');
				$email_name = "repair_complete";

				$recipients = getSubEmail($email_name);

				$title = 'RO# ' . $order_number;
				$issue = 'Status: ' . $status_desc;
				$message = $title . ' ' . $issue;
				$link = '/service.php?order_type=repair&order_number=' . $order_number;
				// $recipients = 'ssabedra@ven-tel.com';
			} else if($label == 'service_item_id') {
				//$status_desc = getStatus($service_code_id, 'status_codes');
				$email_name = "service_complete";

				$recipients = getSubEmail($email_name);

				$title = 'SO# ' . $order_number;
				$issue = 'Status: ' . $status_desc;
				$message = $title . ' ' . $issue;
				$link = '/service.php?order_type=Service&order_number=' . $order_number;
				// $recipients = 'scott@ven-tel.com';
			}

			$query = "INSERT INTO messages (datetime, message, userid, link, ref_1, ref_1_label, ref_2, ref_2_label) ";
				$query .= "VALUES ('".$GLOBALS['now']."', ".fres($message).", ".fres($GLOBALS['U']['id']).", ".fres($link).", NULL, NULL, ".fres($order_number).", '".($label == 'repair_item_id' ? 'ro_number' : 'so_number')."');";
			qedb($query);

			$messageid = qid();

			$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '".res($managerid)."');";
			$result = qedb($query);

			if($result && ! $DEV_ENV) {
				$email_body_html = getRep($GLOBALS['U']['id'])." has completed <a target='_blank' href='".$_SERVER['HTTP_HOST'].$link."'>".$title."</a> " . $issue;
				$email_subject = 'Status Submit for '.$title;
				// $bcc = 'dev@ven-tel.com';
				
				$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
				if ($send_success) {
				    // echo json_encode(array('message'=>'Success'));
				} else {
				    $this->setError(json_encode(array('message'=>$SEND_ERR)));
				}
			}
		} else {
			// Status code was previously set and is now being changed by a manager based on access
			// Notify the techs of the change & notes that the manager might have set

			$techids = getTechs($item_id, $label);

			if($label == 'repair_item_id') {
				//$status_desc = getStatus($service_code_id, 'repair_codes');

				$title = 'RO# ' . $order_number;
				$issue = 'Status Changed: ' . ($status_desc ? $status_desc : ' Open');
				$message = $title . ' ' . $issue;
				$link = '/service.php?order_type=repair&order_number=' . $order_number;
			} else if($label == 'service_item_id') {
				//$status_desc = getStatus($service_code_id, 'status_codes');

				$title = 'SO# ' . $order_number;
				$issue = 'Status Changed: ' . ($status_desc ? $status_desc : ' Open');
				$message = $title . ' ' . $issue;
				$link = '/service.php?order_type=Service&order_number=' . $order_number;
			}

			foreach($techids as $userid) {
				$query = "INSERT INTO messages (datetime, message, userid, link, ref_1, ref_1_label, ref_2, ref_2_label) ";
				$query .= "VALUES ('".$GLOBALS['now']."', ".fres($message).", ".fres($GLOBALS['U']['id']).", ".fres($link).", NULL, NULL, ".fres($order_number).", '".($label == 'repair_item_id' ? 'ro_number' : 'so_number')."');";
				qedb($query);

				$messageid = qid();

				$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '".res($userid)."');";
				$result = qedb($query);
			}
		}
	}

	// This function gets all the techs involved in a job as an array
	function getTechs($item_id, $label = 'service_item_id') {
		$techids = array();

		$query = "SELECT * FROM service_assignments WHERE item_id_label = ".fres($label)." AND item_id = ".res($item_id).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$techids[] = $r['userid'];
		}

		return $techids;
	}

	function getStatus($status_code_id, $table = 'status_codes') {
		$status = '';

		$query = "SELECT description FROM ".res($table)." WHERE id = ".res($status_code_id).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$status = $r['description'];
		}

		return $status;
	}
?>
