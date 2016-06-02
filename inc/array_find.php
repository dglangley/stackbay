<?php
	function array_find($needle, $haystack) {
		$f = false;
		foreach ($haystack as $k => $item) {
			//strpos() is haystack, needle
			if (strpos($item, $needle) !== FALSE) {
				// cannot have dups of same column
				if ($f!==false) { return false; }
				$f = $k;//identify the key but keep searching for dups
			}
		}
		return ($f);
	}
?>
