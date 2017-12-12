<?php
	function is_clockedin($userid, $taskid=0, $task_label='') {
		$clock = false;

		// Grab the last clock in for the user where the clockout time has not been set yet and limit it by 1 (In totality there will always only be 1 record open per userid)
		$query = "SELECT * FROM timesheets WHERE userid  = ".res($userid)." ";
//		if ($taskid AND $task_label) { $query .= "AND taskid = '".res($taskid)."' AND task_label = '".res($task_label)."' "; }
		$query .= "AND clockout IS NULL ";
		$query .= "ORDER BY id DESC; ";// LIMIT 1;";
		$result = qdb($query) OR die(qe() . ' ' . $query);
		if (mysqli_num_rows($result)==0) {
			return ($clock);
		} else if (mysqli_num_rows($result)>1) {
			die("You are clocked into more than one job at a time. Please see a manager immediately!");
		}
		while ($r = mysqli_fetch_assoc($result)) {
			if (($r['taskid']==$taskid AND $r['task_label']==$task_label) OR (! $taskid AND ! $task_label)) { $clock = $r; }
		}

		return ($clock);
	}

	// This checks if a user that is hourly has been clocked on any job for more than 5 hours at a time
	function is_idle($userid) {
		$idle = false;

		$query = "SELECT * FROM timesheets WHERE userid  = ".res($userid)." ";
		$query .= "AND clockout IS NULL ";
		$query .= "ORDER BY id DESC; ";// LIMIT 1;";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if (mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$ts1 = strtotime($r['clockin']);
			$ts2 = strtotime($GLOBALS['now']);
			$hours = abs($ts1 - $ts2) / 3600; // 3600 = seconds to minutes to hours

			if($hours > 5) {
				$ALERTS[] = "Warning: You have exceeded 5 hours of clock in time.";
				$idle = true;
			}
		}

		return $idle;
	}