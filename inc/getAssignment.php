<?php
	function getAssignment($userid, $taskid, $task_label){
//		global $quote;

		// Guilty until proven innocent
		$access = false;

		if (! $taskid OR ! $task_label) { return $access; }

//		if(! $quote) {
			$query = "SELECT * FROM service_assignments ";
			$query .= "WHERE item_id = '".res($taskid)."' AND item_id_label = '".res($task_label)."' ";
			$query .= "AND userid = '".res($userid)."' ";
			$query .= "AND ((start_datetime IS NULL OR start_datetime <= '".$GLOBALS['now']."') ";
			$query .= "AND (end_datetime IS NULL OR end_datetime > '".$GLOBALS['now']."')) ";
			$query .= "; ";
			$result = qedb($query);
			if (qnum($result)) {
				$r = qrow($result);
				if ($r['lead']) {
					$access = 'LEAD';
				} else {
					$access = true;
				}
			}
//		}

		return $access;
	}
?>
