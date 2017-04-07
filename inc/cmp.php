<?php
	// sort by date
	function cmp_timestamp($a, $b) {
		if ($a['timestamp'] == $b['timestamp']) {
			return 0;
		}
		return ($a['timestamp'] > $b['timestamp']) ? 1 : -1;
	}

	// sort by n
	function cmp_n($a, $b) {
		if ($a['n'] == $b['n']) {
			return 0;
		}
		return ($a['n'] < $b['n']) ? 1 : -1;
	}

	// sort by count of $b results
	function cmp_count($a, $b) {
		if (count($a) == count($b)) {
			return 0;
		}
		return (count($a) < count($b)) ? 1 : -1;
	}
?>
