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
?>
