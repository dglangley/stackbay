<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_mdg.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_mdg.php';

	$API_ERROR = '';
	function api($search) {
		global $API_ERROR;

		// if(! $search) {
			foreach (range(1, 500) as $page) {
				$res = download_mdg($search,false,'http://www.mdgsales.com/','mdg', $page);
				if ($res===false) { return ($API_ERROR); }

				$resArray = parse_mdg($res);

				// If the resArray is empty assume the page does not exists
				if(empty($resArray)) {
					break;
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
