<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_api.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_api.php';

	$API_ERROR = '';
	function api($search='',$logout=false,$base='',$remote='') {
		global $API_ERROR;

		$res = download_api($search,$logout,$base,$remote);
		if ($res===false) { return ($API_ERROR); }

		$resArray = parse_api($res);

		return false;
	}
?>
