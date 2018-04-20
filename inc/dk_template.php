<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_dk.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_dk.php';

	$API_ERROR = '';
	function api($search) {
		global $API_ERROR;

		$res = download_api($search);
		if ($res===false) { return ($API_ERROR); }

		$resArray = parse_api($res);

		return false;
	}
?>
