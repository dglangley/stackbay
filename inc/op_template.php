<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_op.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_op.php';

	$API_ERROR = '';
	function api($search) {
		global $API_ERROR;

		$categoryid = 0;

		// Get 0, 10, 20 , 30, 40... Pages (Based on OP)
		foreach (range(0, 500, 10) as $start) {
		    $res = download_op($search, false, 'https://octopart.com/', 'op', $start, $categoryid);
			if ($res===false) { return ($API_ERROR); }

			$resArray = parse_op($res);
		}

		return false;
	}
?>
