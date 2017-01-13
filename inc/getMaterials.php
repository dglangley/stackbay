<?php
	$MATERIALS = array();
	function getMaterials($jobid) {
		global $MATERIALS;

		if (! $jobid) { return false; }
		if (isset($MATERIALS[$jobid])) { return ($MATERIALS[$jobid]); }

		$materials = array();
		$query = "SELECT * FROM services_component c, services_jobbulkinventory jbi ";
		$query .= "LEFT JOIN services_jobmaterialpo jpo ON jbi.po_id = jpo.id ";
		$query .= "WHERE job_id = '".$jobid."' AND component_id = c.id; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$materials[] = $r;
		}

		$MATERIALS[$jobid] = $materials;
		return ($materials);
	}
?>
