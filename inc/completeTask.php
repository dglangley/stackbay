<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRep.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';

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
		
		$result = qdb($query) OR die(qe() . ' ' . $query);

		//echo $query;

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			if(! $notes) {
				$notes = ucwords(($label == 'service_item_id' ? 'Service' : 'Repair')) . ' completed. Final Status: <b>' . $r['description'] . '</b>';
			}
			$status_desc = $r['description'];
		}

		//$order_number = getOrderNumber($item_id);

		$query = "INSERT INTO $table ($field, item_id_label, datetime, userid, notes) VALUES (".res($item_id).", ".fres($label).", '".res($GLOBALS['now'])."', ".res($GLOBALS['U']['id']).", '".res($notes)."');";
		// echo $query;
		qdb($query) OR die(qe().' '.$query);

		// Update the service_code_id to the order
		if($label == 'repair_item_id') {
			// Quick check to see if a repair code as been set or not
			$query = "SELECT * FROM repair_items WHERE repair_code_id IS NOT NULL AND id = ".res($item_id).";";
			$result = qdb($query) OR die(qe() . '<BR>'.$query);

			if(mysqli_num_rows($result)) {
				$mang_change = true;
			}

			$query = "UPDATE repair_items SET repair_code_id = ".res($service_code_id)." WHERE id = ".res($item_id).";";
			qdb($query) OR die(qe().' '.$query);

			$query = "SELECT sales_rep_id, ro.ro_number as order_number, ri.line_number FROM repair_items ri, repair_orders ro WHERE id = ".res($item_id)." AND ri.ro_number = ro.ro_number;";
		} else {
			// Quick check to see if a repair code as been set or not
			$query = "SELECT * FROM service_items WHERE status_code IS NOT NULL AND id = ".res($item_id).";";
			$result = qdb($query) OR die(qe() . '<BR>'.$query);

			if(mysqli_num_rows($result)) {
				$mang_change = true;
			}

			$query = "UPDATE service_items SET status_code = ".res($service_code_id)." WHERE id = ".res($item_id).";";
			qdb($query) OR die(qe().' '.$query);

			$query = "SELECT sales_rep_id, so.so_number as order_number, si.line_number FROM service_items si, service_orders so WHERE id = ".res($item_id)." AND si.so_number = so.so_number;";
		}

		// Send Nofication and Email to the User or Manager depending on approval type
		// Get the Orignal creator userid from the order level (Assuming as Manager)
		$order_number = 0;
		$result = qdb($query) OR die(qe() . '<BR>' . $query);

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

				$title = 'RO# ' . $order_number;
				$issue = 'Status: ' . $status_desc;
				$message = $title . ' ' . $issue;
				$link = '/service.php?order_type=repair&order_number=' . $order_number;
			} else if($label == 'service_item_id') {
				//$status_desc = getStatus($service_code_id, 'status_codes');

				$title = 'SO# ' . $order_number;
				$issue = 'Status: ' . $status_desc;
				$message = $title . ' ' . $issue;
				$link = '/service.php?order_type=Service&order_number=' . $order_number;
			}

			$query = "INSERT INTO messages (datetime, message, userid, link, ref_1, ref_1_label, ref_2, ref_2_label) ";
				$query .= "VALUES ('".$GLOBALS['now']."', ".fres($message).", ".fres($GLOBALS['U']['id']).", ".fres($link).", NULL, NULL, ".fres($order_number).", '".($label == 'repair_item_id' ? 'ro_number' : 'so_number')."');";
			qdb($query) OR die(qe() . '<BR>' . $query);

			$messageid = qid();

			$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '".res($managerid)."');";
			$result = qdb($query) or die(qe() . '<BR>' . $query);

			if($result && ! $DEV_ENV) {
				$email_body_html = getRep($userid)." has completed a job for <a target='_blank' href='".$_SERVER['HTTP_HOST'].$link."'>".$title."</a> " . $issue;
				$email_subject = 'Status Submit for '.$title;
				$recipients = 'scott@ven-tel.com';
				//$recipients = 'ssabedra@ven-tel.com';
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
				qdb($query) OR die(qe() . '<BR>' . $query);

				$messageid = qid();

				$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '".res($userid)."');";
				$result = qdb($query) or die(qe() . '<BR>' . $query);
			}
		}
	}

	// This function gets all the techs involved in a job as an array
	function getTechs($item_id, $label = 'service_item_id') {
		$techids = array();

		$query = "SELECT * FROM service_assignments WHERE item_id_label = ".fres($label)." AND item_id = ".res($item_id).";";
		$result = qdb($query) OR die(qe()."<BR>".$query);

		while($r = mysqli_fetch_assoc($result)) {
			$techids[] = $r['userid'];
		}

		return $techids;
	}

	function getStatus($status_code_id, $table = 'status_codes') {
		$status = '';

		$query = "SELECT description FROM ".res($table)." WHERE id = ".res($status_code_id).";";
		$result = qdb($query) OR die(qe() . '<BR>'.$query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$status = $r['description'];
		}

		return $status;
	}
?>
