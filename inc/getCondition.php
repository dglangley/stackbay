<?php
	$CONDITIONS = array();
	function getCondition($search_string=0) {
		global $CONDITIONS;

		// if already set previously, just re-use from memory
		if (count($CONDITIONS)>0) {
			return ($CONDITIONS[$search_string]);
		}

		$query = "SELECT * FROM conditions; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$CONDITIONS[$r['id']] = $r['condition'];
		}

		if ($search_string) {
			return ($CONDITIONS[$search_string]);
		} else {
			return false;
		}
	}
?>
