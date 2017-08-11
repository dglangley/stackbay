<?php
	$CONDITIONS = array();
	function getCondition($search_string=0, $restriction = '') {
		global $CONDITIONS;

		// if already set previously, just re-use from memory
//		if (count($CONDITIONS)>0) {
		if (isset($CONDITIONS[$search_string])) {
			return ($CONDITIONS[$search_string]);
		}

		$CONDITIONS = array();

		$query = "SELECT * FROM conditions ".($restriction ? 'WHERE id < 0' : '')."; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$CONDITIONS[$r['id']] = $r['condition'];
		}

		if ($search_string AND isset($CONDITIONS[$search_string])) {
			return ($CONDITIONS[$search_string]);
		} else {
			return false;
		}
	}
?>
