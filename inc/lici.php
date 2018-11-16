<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/is_clockedin.php';
	include_once $rootdir.'/inc/getItemOrder.php';
	include_once $rootdir.'/inc/getTravelRate.php';

	if (! isset($ERR)) { $ERR = ''; }//global error catching

	// Type = in or out currently to decide between a clock out or a clock out
	function lici($taskid = 0, $task_label = '', $type = '') {
		global $ERR;

		$data = 0;
		$userid = $GLOBALS['U']['id'];
		$user_rate = 0;

		if($task_label == 'repair' OR $task_label == 'repair_items') {
			$task_label = 'repair_item_id';
		} else if($task_label == 'service' OR $task_label == 'service_items') {
			$task_label = 'service_item_id';
		}

		$query = "SELECT hourly_rate FROM users WHERE id  = ".res($userid).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if (mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$user_rate = $r['hourly_rate'];
		}

		$USER_ROLES = $GLOBALS['USER_ROLES'];
		$manager_access = array_intersect($USER_ROLES,array(1,4));
		$logistic_access = false;
		if ($manager_access) { return false; }

		$user_classes = array();

		if(! $manager_access AND array_intersect($USER_ROLES,array(9))) {
			// We need to be able to check and see if the logistics user is actually on the correct class and not hacking their way in
			// This part gets the current users assigned service classes
			$query = "SELECT classid FROM user_classes WHERE userid = '".res($GLOBALS['U']['id'])."';";
			$result = qedb($query);

			while($r = mysqli_fetch_assoc($result)) {
				$user_classes[] = $r['classid'];
			}

			// This section checks if repair if so then check if the userclass lands on this
			if($task_label == 'repair_item_id') {
				// Currently in the database 1 in service_classes = repair
				$logistic_access = array_intersect($user_classes,array(1));
			// Then checks if this is a service, and if the service classid matches the users
			} else if($task_label == 'service_item_id') {
				// Query the current job and see if the job has the correct class
				$query = "SELECT classid FROM service_orders o, service_items i WHERE i.id = ".res($taskid)." AND i.so_number = o.so_number;";
				$result = qedb($query);

				if(mysqli_num_rows($result) > 0) {
					$r = mysqli_fetch_assoc($result);
					$logistic_access = array_intersect($user_classes,array($r['classid']));
				}
			}
		}

		// Don't invoke this error if the user is logistics
		if (! $user_rate AND ! $logistic_access) {
			$ERR = 'Your employee profile has not been completely setup, please check with your manager!';
			return false;
		}

		if($type == 'travel') {
			$user_rate = getTravelRate($taskid,$task_label);
		}

		if ($type=='init') {
			$clock = is_clockedin($userid, $taskid, $task_label);
		} else {//clocking in or out, end all other sessions
			$clock = is_clockedin($userid);

			while ($clock) {
				$query = "UPDATE timesheets SET clockout = '".res($GLOBALS['now'])."' WHERE id = ".res($clock['id']).";";
				qdb($query) OR die(qe() . ' ' . $query);

				$clock = is_clockedin($userid);
			}

			if ($type=='out') { return ($data); }
		}

		// Check if the user is assigned to this task before allowing the user to clock into this job
		$assigned = false;

		if ($taskid AND $task_label) {
			$query = "SELECT * FROM service_assignments WHERE item_id = '".res($taskid)."' AND item_id_label = '".res($task_label)."' AND userid = '".res($userid)."';";
			$result = qdb($query) OR die(qe() . ' ' . $query);
			if (mysqli_num_rows($result)) { $assigned = true; }
		}

		// Gate keeper for clocked in jobs
		// If the user is logistics then bypass this and allow the user to clock into this job
		if (! $assigned AND ! $logistic_access) {
			// $ERR = "You are not assigned to this task. Please see a manager.";
			return false;
		}

		if($type == "init") {
			if ($taskid<>$clock['taskid'] OR $task_label<>$clock['task_label']) {
				$ERR = "You are about to clock into this task. Please select your Pay Rate...";
			}
		} else {
			$query = "INSERT INTO timesheets (userid, clockin, taskid, task_label, rate) ";
			$query .= "VALUES ('".res($userid)."', '".res($GLOBALS['now'])."', ".fres($taskid).", ".fres($task_label).", '".res($user_rate)."');";
			qdb($query) OR die(qe() . ' ' . $query);
		}

		// this should really be only a clockout scenario but what the heck, extra error check
		if (! $taskid) {
			$ERR = "There was an unknown error, please see an Admin.";
			return false;
		}

		// Convert the taskid to the respective order number for reloading
		$data = getItemOrder($taskid, $task_label);

		return $data;
	}
?>
