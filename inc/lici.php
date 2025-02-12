<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/is_clockedin.php';
	include_once $rootdir.'/inc/getItemOrder.php';
	include_once $rootdir.'/inc/getTravelRate.php';

	if (! isset($ERR)) { $ERR = ''; }//global error catching

	$DEBUG = 0;

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
		$result = qedb($query);

		if (mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$user_rate = $r['hourly_rate'];
		}

		$USER_ROLES = $GLOBALS['USER_ROLES'];
		$manager_access = array_intersect($USER_ROLES,array(1,4));
		$logistics_access = false;
		if ($manager_access) { return false; }

		$classid = 0;
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
				$classid = 1;

			// Then checks if this is a service, and if the service classid matches the users
			} else if($task_label == 'service_item_id') {
				// Query the current job and see if the job has the correct class
				$query = "SELECT classid FROM service_orders o, service_items i WHERE i.id = ".res($taskid)." AND i.so_number = o.so_number;";
				$result = qedb($query);

				if(mysqli_num_rows($result) > 0) {
					$r = mysqli_fetch_assoc($result);
					$classid = $r['classid'];
				}
			}

			if ($classid) {
				$logistics_access = array_intersect($user_classes,array($classid));
			}
		}

		// added 1/8/19 because Joe as a salary logistics person was getting clockin prompts, which is dumb;
		// previously, we were returning the below error only when ! $user_rate AND ! $logistics_access
		if (! $user_rate) {
			// Don't invoke this error if the user is logistics
			if (! $logistics_access) {
				$ERR = 'Your employee profile has not been completely setup, please check with your manager!';
			}
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
				qedb($query);

				$clock = is_clockedin($userid);
				if ($GLOBALS['DEBUG']) { break; }
			}

			if ($type=='out') { return ($data); }
		}

		// Check if the user is assigned to this task before allowing the user to clock into this job
		$assigned = false;

		if ($taskid AND $task_label) {
			$query = "SELECT * FROM service_assignments WHERE item_id = '".res($taskid)."' AND item_id_label = '".res($task_label)."' AND userid = '".res($userid)."';";
			$result = qedb($query);
			if (mysqli_num_rows($result)) { $assigned = true; }
		}

		// Gate keeper for clocked in jobs
		// If the user is logistics then bypass this and allow the user to clock into this job
		if (! $assigned AND ! $logistics_access) {
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
			qedb($query);

			// now check to see if this user is the only existing employee with this class, and if so then auto-add assignment for this user
			if (! $assigned AND $logistics_access) {
				$query = "SELECT uc.userid FROM user_classes uc, users u, contacts c ";
				$query .= "WHERE uc.classid = '".res($classid)."' AND uc.userid = u.id AND u.contactid = c.id AND c.status = 'Active' AND u.hourly_rate > 0; ";
				$result = qedb($query);
				if (qnum($result)==1) {//only one active user with this class
					$r = qrow($result);
					if ($r['userid']==$userid) {//the one active user is the current user
						// auto-add assignment
						$query = "REPLACE INTO service_assignments (item_id, item_id_label, userid, start_datetime, end_datetime) ";
						$query .= "VALUES (".fres($taskid).", ".fres($task_label).", '".res($userid)."', NULL, NULL); ";
						qedb($query);
					}
				}
			}
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
