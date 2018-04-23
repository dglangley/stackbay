<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_dk.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_dk.php';

	$API_ERROR = '';
	function dk_api($search) {
		global $API_ERROR;

		$res = download_dk($search);
		if ($res===false) { return ($API_ERROR); }

		$resArray = parse_dk($res);

		return false;
	}
?>
