<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_api.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_api.php';

	$API_ERROR = '';
	function api($search) {
		global $API_ERROR;

		$res = download_op($search, false, 'https://octopart.com/', 'op');
		if ($res===false) { return ($API_ERROR); }

		$resArray = parse_op($res);

		return false;
	}
?>
