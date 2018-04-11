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

	// sort by array-defined element
	function cmp_sort($a, $b, $sortby, $sortorder) {
		$sortorder = strtoupper($sortorder);

		if ($a[$sortby] == $b[$sortby]) {
			return 0;
		}
		if ($sortorder=='DESC') {
			return ($a[$sortby] < $b[$sortby]) ? 1 : -1;
		} else {
			return ($a[$sortby] > $b[$sortby]) ? 1 : -1;
		}
	}

	$CMP = function ($keyname,$order='ASC', $nullAsValue=true) {
		$order = strtoupper($order);

		return function($a, $b) use ($keyname, $order, $nullAsValue) {
			if ($a[$keyname] === $b[$keyname]) {
				return 0;
			}

			// These next 2 if statements pushes the NULL to the bottom of the array
			if(! $nullAsValue) {
				if(! $a[$keyname]) {
					return 1;
				}

				if(! $b[$keyname]) {
					return -1;
				}
			}

			if ($order=='ASC') {
				return (($a[$keyname] > $b[$keyname]) OR ($a[$keyname]!==false AND $b[$keyname]===false)) ? 1 : -1;
			} else {
				return (($a[$keyname] < $b[$keyname]) OR ($a[$keyname]===false AND $b[$keyname]!==false)) ? 1 : -1;
			}
		};
	};
?>
