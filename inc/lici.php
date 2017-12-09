<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/is_clockedin.php';
	include_once $rootdir.'/inc/getItemOrder.php';

	if (! isset($ERR)) { $ERR = ''; }//global error catching

	// Type = in or out currently to decide between a clock out or a clock out
	function lici($taskid = 0, $task_label = '', $type = '') {
		global $ERR;

		$data = 0;
		$userid = $GLOBALS['U']['id'];
		$user_rate = 0;

		if($task_label == 'repair') {
			$task_label = 'repair_item_id';
		} else if($task_label == 'service') {
			$task_label = 'service_item_id';
		}

		$query = "SELECT hourly_rate FROM users WHERE id  = ".res($userid).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if (mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$user_rate = $r['hourly_rate'];
		}
		if (! $user_rate) {
			$ERR = 'Your employee profile has not been completely setup, please check with your manager!';
			return false;
		}

		if($type == 'travel') {
			$user_rate = '11.00';
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
		if (! $assigned) {
			$ERR = "You are not assigned to this task. Please see a manager.";
			return false;
		}

		if($type == "init") {
			if ($taskid<>$clock['taskid'] OR $task_label<>$clock['task_label']) {
				$ERR = "You are about to clock into this task. Please select your Pay Rate...";
			}
		} else {
			$query = "INSERT INTO timesheets (userid, clockin, taskid, task_label, rate) VALUES (".res($userid).", '".res($GLOBALS['now'])."', ".res($taskid).", '".res($task_label)."', '".res($user_rate)."');";
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
