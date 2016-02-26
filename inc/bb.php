<?php
	include_once 'download_bb.php';
	include_once 'parse_bb.php';

	$BB_ERROR = '';
	function bb($search) {
		global $BB_ERROR;

		$res = download_bb($search);
		if ($res===false) { return ($BB_ERROR); }

		$resArray = parse_bb($res);
//print_r($resArray);

		return false;
	}
?>
