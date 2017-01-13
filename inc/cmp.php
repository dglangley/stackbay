<?php
	// sort by date
	function cmp_timestamp($a, $b) {
		if ($a['timestamp'] == $b['timestamp']) {
			return 0;
		}
		return ($a['timestamp'] > $b['timestamp']) ? 1 : -1;
	}
?>
