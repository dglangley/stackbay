<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';

	// Getters
	include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRep.php';

	$DEBUG = 0;
	$ALERT = '';
	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function insertActivity($notes, $T, $taskid) {
		$query = "INSERT INTO activity_log (item_id, item_id_label, datetime, userid, notes) VALUES (".res($taskid).", ".fres($T['item_label']).", ".fres($GLOBALS['now']).", ".res($GLOBALS['U']['id']).", ".fres($notes).");";

		qedb($query);
	}

	function emailActivity($activityid, $order_number, $taskid, $T, $email = true) {
		global $DEV_ENV, $ALERT;

		$message = '';
		$link = '';
		$activity = '';
		$title = '';
		$issue = '';

		$messageid = 0;
		$userid = 0;

		// Get the activity notes
		$query = "SELECT notes, userid FROM activity_log WHERE id = ".res($activityid).";";
		$result = qedb($query);


		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$activity = $r['notes'];
			$userid = $r['userid'];
		}


		if($T['type'] == 'Repair') {
			$title = 'RO# ' . $order_number;
			$issue = 'Issue: ' . $activity;
			$message = $title . ' ' . $issue;
			$link = '/service.php?order_type=Repair&taskid=' . $taskid;
		} else if($T['type'] == 'Service') {
			$title = 'SO# ' . $order_number;
			$issue = 'Issue: ' . $activity;
			$message = $title . ' ' . $issue;
			$link = '/service.php?order_type=Service&taskid=' . $taskid;
		}

		$query = "INSERT INTO messages (datetime, message, userid, link, ref_1, ref_1_label, ref_2, ref_2_label) ";
		$query .= "VALUES ('".$GLOBALS['now']."', ".fres($message).", ".fres($GLOBALS['U']['id']).", ".fres($link).", ".res($activityid).", 'activityid', ".fres($order_number).", '".($T['type'] == 'repair_item_id' ? 'ro_number' : 'so_number')."');";
		qedb($query);

		$messageid = qid();

		$email_name = "task_notification";
		$userids = getSubEmail($email_name, 'userid');

		// Based on the subscription and getting all the userids create a loop that notifies all the users
		foreach($userids as $userid) {
			$query2 = "INSERT INTO notifications (messageid, userid) VALUES (".fres($messageid).", ".res($userid).");";
			$result2 = qedb($query);
		}

		if($result && $DEV_ENV) {
			$recipients = 'andrew@ven-tel.com';
			$email_body_html = getRep($userid)." has submitted an issue for <a target='_blank' href='https://www.stackbay.com".$link."'>".$title."</a> " . $issue;
			$email_subject = 'Issue Submit for '.$title;
			
			$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
			if (! $send_success) {
			    $ALERT = $SEND_ERR;
			} else {
				$ALERT = "Email sucessfully sent!";
			}
		}
	}

	function deleteActivity($id) {
		global $USER_ROLES, $ALERT;

		$userid = 0;

		// First do a check and see if the current user is actually the user on the notes or if the user is an admin then allow deletion
		$query = "SELECT * FROM activity_log WHERE id = ".res($id).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$r = mysqli_fetch_assoc($result);

			$userid = $r['userid'];
		}

		if($userid = $GLOBALS['U']['id'] OR array_intersect($USER_ROLES,array(1,4))) {
			$query = "DELETE FROM activity_log WHERE id = ".res($id).";";
			qedb($query);
		} else {
			$ALERT = "Warning. You are trying to delete another user's notes. Permission denied!";
		}
	}

	$taskid = '';
	if (isset($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }
	$order_number = '';
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	$type = '';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }
	$notes = '';
	if (isset($_REQUEST['notes'])) { $notes = trim($_REQUEST['notes']); }
	$delete = 0;
	if (isset($_REQUEST['delete'])) { $delete = trim($_REQUEST['delete']); }
	$email = 0;
	if (isset($_REQUEST['email'])) { $email = trim($_REQUEST['email']); }
	// $activityid = 0;
	// if (isset($_REQUEST['activityid'])) { $activityid = trim($_REQUEST['activityid']); }

	$T = order_type($type);

	if($delete) {
		deleteActivity($delete);
	} 

	if($notes) {
		insertActivity($notes, $T, $taskid);
	}

	if($email) {
		emailActivity($email, $order_number, $taskid, $T);
	}

	// Responsive Testing
	$responsive = false;
	if (isset($_REQUEST['responsive'])) { $responsive = trim($_REQUEST['responsive']); }

	$link = '/service.php';

	if($responsive) {
		$link = '/responsive_task.php';
	} 

	header('Location: '.$link.'?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=activity' . ($ALERT?'&ALERT='.$ALERT:''));

	// header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=activity' . ($ALERT?'&ALERT='.$ALERT:''));

	exit;
