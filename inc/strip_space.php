<?php
	function strip_space($str) {
	    $address = str_replace(chr(10),'<BR>',preg_replace('/([[:^print:]]*[\r\n]+[[:^print:]]*)+$/','',$str));
	}
?>
