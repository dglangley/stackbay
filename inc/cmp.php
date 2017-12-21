<?php
	// sort by date
	function cmp_timestamp($a, $b) {
		if ($a['timestamp'] == $b['timestamp']) {
			return 0;
		}
		return ($a['timestamp'] > $b['timestamp']) ? 1 : -1;
	}

	function cmp_datetime($a, $b) {
		if ($a['datetime'] == $b['datetime']) {
			return 0;
		}
		return ($a['datetime'] < $b['datetime']) ? 1 : -1;
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

	// sort by count of $b results
	function cmp_rcount($a, $b) {
		if (count($a) == count($b)) {
			return 0;
		}
		return (count($a) > count($b)) ? 1 : -1;
	}

	$CMP = function ($keyname) {
		return function($a, $b) use ($keyname) {
			if ($a[$keyname] == $b[$keyname]) {
				return 0;
			}
			return ($a[$keyname] > $b[$keyname]) ? 1 : -1;
		};
	};
?>
