<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_ar.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_ar.php';

	$API_ERROR = '';
	function api() {

		// No Search

		global $API_ERROR;

		//Search / Logout / Base / Remote
		$res = download_ar('', false,'http://assetrecovery.com/data/data.json','ar');
		if ($res===false) { return ($API_ERROR); }

		// print_r($res); die();
		$resArray = parse_ar($res);

		return false;
	}
?>
