<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_mdg.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_mdg.php';

	$DEBUG = 3;

	$MDG_ERROR = '';
	function mdg($search) {
		global $MDG_ERROR;

		$categories = array('avaya-alcatel-lucent-att/', 'networking-telecom/', 'nortel-networks/', 'other/');

		// if(! $search) {
		foreach($categories as $category) {
			$resArray = array();
			foreach (range(1, 500) as $page) {
				$res = download_mdg($search,false,'http://www.mdgsales.com/' . $category,'mdg', $page);
				if ($res===false) { return ($API_ERROR); }

				$resArray = parse_mdg($res);

				// If the resArray is empty assume the page does not exists
				if(empty($resArray) OR ! $resArray) {
					break;
				}
			}
		}
		// } else {
		// 	$res = download_mdg($search,false,'http://www.mdgsales.com/','mdg');
		// 	if ($res===false) { return ($API_ERROR); }

		// 	$resArray = parse_mdg($res);

		// }

		return false;
	}
?>
