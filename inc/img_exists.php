<?php
	function img_exists($imgurl) {
		$headers = @get_headers($imgurl);
		if (strpos($headers[0], '404 Not Found')) { return false; } else { return true; }
	}
?>
