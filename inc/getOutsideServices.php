<?php
	function getOutsideServices($jobid) {
		$outsideServices = array();
		$query = "SELECT * FROM services_joboutsideservice WHERE job_id = '".$jobid."'; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$outsideServices[] = $r;
		}
		return ($outsideServices);
	}
?>
