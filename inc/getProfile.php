<?php
	$PROFILES = array();
	function getProfile($id,$input='id',$output='fullname') {
		global $PROFILES;

		if (! $id) { return false; }

		if (isset($PROFILES[$id][$input])) { return ($PROFILES[$id][$input][$output]); }

		$PROFILES[$id][$input] = array($output=>false);
		$query = "SELECT * FROM services_userprofile WHERE id = '".$id."'; ";
		$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);
		if (mysqli_num_rows($result)>0) {
			$PROFILES[$id][$input] = mysqli_fetch_assoc($result);
		}
		return ($PROFILES[$id][$input][$output]);
	}
?>
