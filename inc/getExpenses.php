<?php
	function getExpenses($jobid) {
		$expenses = array();

		$query = "SELECT * FROM services_expense WHERE job_id = '".$jobid."'; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			if (strtolower(substr($r['label'],0,3))=='gas') { $r['type'] = '<i class="fa fa-car"></i>'; }
			else { $r['type'] = '<i class="fa fa-money"></i>'; }
			$expenses[] = $r;
		}

		return ($expenses);
	}
?>
