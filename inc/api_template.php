<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_api.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_api.php';

	$API_ERROR = '';
	function api($search) {
		global $API_ERROR;

		//Search / Logout / Base / Remote
		$res = download_api($search, false,'','');
		if ($res===false) { return ($API_ERROR); }

		$resArray = parse_api($res);

		return false;
	}
?>
