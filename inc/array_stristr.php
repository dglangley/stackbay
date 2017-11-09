<?php
	function array_stristr($haystack,$needle,$multi=false) {
		foreach ($haystack as $key => $straw) {
//			echo $straw.':'.$needle.':'.stripos($straw,$needle).'<BR>';
			if (stripos($straw,$needle)!==false OR ($multi AND stripos($needle,$straw)!==false)) {
				return ($key);
			}
		}
		return false;
	}
?>
