<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';

	// Type = in or out currently to decide between a clock out or a clock out
	function lici($taskid = 0, $task_label = 0, $type = '') {
		$data = 0;
		$userid = $GLOBALS['U']['id'];
		$user_rate = 0;

		$query = "SELECT hourly_rate FROM users WHERE id  = ".res($userid).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if (mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$user_rate = $r['hourly_rate'];
		}

		// Grab the last clock in for the user where the clockout time has not been set yet and limit it by 1 (In totality there will always only be 1 record open per userid)
		$query = "SELECT id, taskid FROM timesheets WHERE userid  = ".res($userid)." AND clockout IS NULL ORDER BY id DESC LIMIT 1;";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		// If there is a result and the user isn't trying to clock in then update and simulate a clock out
		if (mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			if($type != 'in' && $type != 'init') {
				$query = "UPDATE timesheets SET clockout = '".res($GLOBALS['now'])."' WHERE id = ".res($r['id']).";";
				qdb($query) OR die(qe() . ' ' . $query);
			} else if($r['taskid'] != $taskid) {
				// User is attempting to login to a new job without using the conventional clock out clock in method (The job selection method but using a URL instead)
				$data = "You are about to log in to another task. Please confirm you want to log out of your previous task or cancel to view the task.";
			}

		// If there is no record and we are requesting a user login then create a clockin line for the user
		}  else if (! mysqli_num_rows($result) && ($type == 'in' OR $type == 'init')) {
			// Check if the user is assigned to this task before allowing the user to clock into this job
			$query = "SELECT * FROM service_assignments WHERE service_item_id = ".res($taskid)." AND userid = ".res($userid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			// Gate keeper for clocked in jobs
			if (mysqli_num_rows($result)) {
				$query = "INSERT INTO timesheets (userid, clockin, taskid, task_label, rate) VALUES (".res($userid).", '".res($GLOBALS['now'])."', ".res($taskid).", '".res($task_label)."', '".res($user_rate)."');";
				qdb($query) OR die(qe() . ' ' . $query);
			}

		}

		// Convert the taskid to the respective order number for reloading
		if($task_label == 'repair') {
			$query = "SELECT ro_number FROM repair_items WHERE id = ".res($taskid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if (mysqli_num_rows($result)){
				$r = mysqli_fetch_assoc($result);
				if(empty($data)) {
					$data = $r['ro_number'];
				}
			}
		}

		// Return true or false depending on if the update or insert was successful or not
		return $data;
	}
?>
