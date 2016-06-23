<?php
	function array_stristr($haystack,$needle) {
		foreach ($haystack as $key => $straw) {
//			echo $straw.':'.$needle.':'.stripos($straw,$needle).'<BR>';
			if (stripos($straw,$needle)!==false) {
				return ($key);
			}
		}
		return false;
	}
?>
