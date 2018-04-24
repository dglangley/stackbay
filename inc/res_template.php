<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_res.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_res.php';

	$API_ERROR = '';
	function api($search) {
		global $API_ERROR;

		$res = download_res($search,false,'https://www.resion.com/','res');
		if ($res===false) { return ($API_ERROR); }

		$resArray = parse_res($res);

		return false;
	}
?>
