<?php
	function array_append(&$arr1,$arr2) {
		foreach ($arr2 as $date => $arr) {
			if (! isset($arr1[$date])) { $arr1[$date] = array(); }

			if (! is_array($arr)) { $arr = array(); }
			foreach ($arr as $result) {
				foreach ($result as $r) {
					$r['price'] = format_price($r['price'],false);
					$arr1[$date][] = $r;
				}
			}
		}
	}
?>
