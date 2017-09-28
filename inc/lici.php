<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';

	// Type = in or out currently to decide between a clock out or a clock out
	function lici($taskid, $task_label, $type) {
		$order_number = 0;
		$userid = $GLOBALS['U']['id'];
		$user_rate = 0;

		$query = "SELECT hourly_rate FROM users WHERE id  = ".res($userid).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if (mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$user_rate = $r['hourly_rate'];
		}

		// Grab the last clock in for the user where the clockout time has not been set yet and limit it by 1 (In totality there will always only be 1 record open per userid)
		$query = "SELECT id FROM timesheets WHERE userid  = ".res($userid)." AND clockout IS NULL ORDER BY id DESC LIMIT 1;";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		// If there is a result and the user isn't trying to clock in then update and simulate a clock out
		if (mysqli_num_rows($result) && $type != 'in') {
			$r = mysqli_fetch_assoc($result);

			$query = "UPDATE timesheets SET clockout = '".res($GLOBALS['now'])."' WHERE id = ".res($r['id']).";";
			qdb($query) OR die(qe() . ' ' . $query);

		// If there is not record and we are requesting a user login then create a clockin line for the user
		}  else if (! mysqli_num_rows($result) && $type == 'in') {

			$query = "INSERT INTO timesheets (userid, clockin, taskid, task_label, rate) VALUES (".res($userid).", '".res($GLOBALS['now'])."', ".res($taskid).", '".res($task_label)."', '".res($user_rate)."');";
			qdb($query) OR die(qe() . ' ' . $query);
		}

		if($task_label == 'repair') {
			$query = "SELECT ro_number FROM repair_items WHERE id = ".res($taskid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if (mysqli_num_rows($result)){
				$r = mysqli_fetch_assoc($result);

				$order_number = $r['ro_number'];
			}
		}

		// Return true or false depending on if the update or insert was successful or not
		return $order_number;
	}
?>
