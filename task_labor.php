<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$DEBUG = 0;
	$ALERT = '';

	$manager_access = array_intersect($USER_ROLES,array(1,4));

	function insertLabor($userid, $taskid, $T, $start, $end) {
		global $ALERT, $manager_access;

		// No hacks allowed the manager should only be the one to add users
		if($manager_access) {
			$start = ($start ? date("Y-m-d H:i:s", strtotime($start)) : 0);
			$end = ($end ? date("Y-m-d H:i:s", strtotime($end)) : 0);

			$query = "INSERT INTO service_assignments (item_id, item_id_label, userid, start_datetime, end_datetime) ";
			$query .= "VALUES (".res($taskid).", ".fres($T['item_label']).", ".res($userid).", ".fres($start).", ".fres($end).");";
			qedb($query);
		} else {
			$ALERT = 'You do not have the privilege level to add the user from this task!';
		}
	}

	function deleteLabor($userid, $taskid, $T) {
		global $ALERT, $manager_access;

		// First make sure the person is allowed to unassign a user based on the current users privilege
		if($manager_access) {
			$query = "DELETE FROM service_assignments WHERE item_id=".res($taskid)." AND item_id_label=".fres($T['item_label'])." AND userid=".res($userid).";";
			qedb($query);
		} else {
			$ALERT = 'You do not have the privilege level to remove the user from this task!';
		}
	}

	function editQuoteRate($taskid, $labor_hours, $labor_rate) {
		global $T;

		$query = "UPDATE ".$T['items']." SET labor_hours = ".fres($labor_hours).", labor_rate = ".fres($labor_rate)." WHERE id=".res($taskid).";";
		qedb($query);
	}

	$taskid = '';
	if (isset($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }
	$type = '';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }

	$userid = '';
	if (isset($_REQUEST['userid'])) { $userid = trim($_REQUEST['userid']); }
	$start = '';
	if (isset($_REQUEST['start'])) { $start = trim($_REQUEST['start']); }
	$end = '';
	if (isset($_REQUEST['end'])) { $end = trim($_REQUEST['end']); }
	$delete = '';
	if (isset($_REQUEST['delete'])) { $delete = trim($_REQUEST['delete']); }

	$T = order_type($type);

	// This is a quote to treat it as an update to a quote
	if($type == 'service_quote') {
		$labor_hours = 0;
		if (isset($_REQUEST['labor_hours'])) { $labor_hours = trim($_REQUEST['labor_hours']); }
		$labor_rate = 0;
		if (isset($_REQUEST['labor_rate'])) { $labor_rate = trim($_REQUEST['labor_rate']); }
		
		editQuoteRate($taskid, $labor_hours, $labor_rate);

		header('Location: /quoteNEW.php?taskid=' . $taskid . '&tab=labor' . ($ALERT?'&ALERT='.$ALERT:''));
		exit;
	}

	if($delete) {
		deleteLabor($delete, $taskid, $T);
	} else {
		insertLabor($userid, $taskid, $T, $start, $end);
	}

	header('Location: /serviceNEW.php?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=labor' . ($ALERT?'&ALERT='.$ALERT:''));

	exit;
