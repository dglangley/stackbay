<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_mdg.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_mdg.php';

	$API_ERROR = '';
	function api($search) {
		global $API_ERROR;

		$res = download_mdg($search,false,'http://www.mdgsales.com/','mdg');
		if ($res===false) { return ($API_ERROR); }

		$resArray = parse_mdg($res);

		return false;
	}
?>
