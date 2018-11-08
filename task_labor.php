<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	if(! isset($EDIT)) {
		$EDIT = false;
	}

	$DEBUG = 0;
	$ALERT = '';

	$manager_access = array_intersect($USER_ROLES,array(1,4));

	function insertLabor($userid, $taskid, $T, $start, $end) {
		global $ALERT, $manager_access;

		// No hacks allowed the manager should only be the one to add users
		if($manager_access) {
			$start = ($start ? date("Y-m-d H:i:s", strtotime($start)) : 0);
			$end = ($end ? date("Y-m-d H:i:s", strtotime($end)) : 0);

			// A quick search to see if the user has already been assigned to this task
			$query = "SELECT * FROM service_assignments WHERE item_id = ".res($taskid)." AND item_id_label = ".fres($T['item_label'])." AND userid = ".res($userid).";";
			$result = qedb($query);

			if(mysqli_num_rows($result) > 0) {
				// User already exists, currently we will jsut replace the current assignment and warn the manager or whoever is editing that it already exists
				$ALERT = 'Warning: User is already assigned to this order, access has been updated.';
			}

			$query = "REPLACE INTO service_assignments (item_id, item_id_label, userid, start_datetime, end_datetime) ";
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

	function editLeadRole($userid, $taskid, $T) {
		global $ALERT, $manager_access;

		// First make sure the person is allowed to unassign a user based on the current users privilege
		if($manager_access) {
			// Clear any of the past lead role on any users that are on this job
			$query = "UPDATE service_assignments SET lead = '0' WHERE item_id=".res($taskid)." AND item_id_label=".fres($T['item_label']).";";
			qedb($query);

			// Set the new defined lead role to the specified user
			$query = "UPDATE service_assignments SET lead = '1' WHERE item_id=".res($taskid)." AND item_id_label=".fres($T['item_label'])." AND userid=".res($userid).";";
			qedb($query);
		} else {
			$ALERT = 'You do not have the privilege level to edit the user from this task!';
		}
	}

	function editQuoteRate($taskid, $labor_hours, $labor_rate) {
		global $T;

		$query = "UPDATE service_quote_items SET labor_hours = ".fres($labor_hours).", labor_rate = ".fres($labor_rate)." WHERE id=".res($taskid).";";
		qedb($query);
	}

	// IF edit then bypass this initial call
	if(! $EDIT) {
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

		$lead = 0;
		if (isset($_REQUEST['lead'])) { $lead = trim($_REQUEST['lead']); }

		$T = order_type($type);
	}

	// This is a quote to treat it as an update to a quote
	if($type == 'service_quote') {
		$labor_hours = 0;
		if (isset($_REQUEST['labor_hours'])) { $labor_hours = trim($_REQUEST['labor_hours']); }
		$labor_rate = 0;
		if (isset($_REQUEST['labor_rate'])) { $labor_rate = trim($_REQUEST['labor_rate']); }
		
		editQuoteRate($taskid, $labor_hours, $labor_rate);
		
		// Bypass edit to prevent redirect when form posting multiple for quotes
		if(! $EDIT) {
			header('Location: /quoteNEW.php?taskid=' . $taskid . '&tab=labor' . ($ALERT?'&ALERT='.$ALERT:''));
			exit;
		}
	} else {

		if($delete) {
			deleteLabor($delete, $taskid, $T);
		} else if($lead) {
			editLeadRole($lead, $taskid, $T);
		} else {
			insertLabor($userid, $taskid, $T, $start, $end);
		}

	}

	if ($DEBUG) { exit; }

	if(! $EDIT) {
		// Responsive Testing
		$responsive = false;
		if (isset($_REQUEST['responsive'])) { $responsive = trim($_REQUEST['responsive']); }

		$link = '/service.php';

		if($responsive) {
			$link = '/responsive_task.php';
		} 

		header('Location: '.$link.'?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=labor' . ($ALERT?'&ALERT='.$ALERT:''));
		
		// header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=labor' . ($ALERT?'&ALERT='.$ALERT:''));

		exit;
	}
