<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_op.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_op.php';

	$OP_ERROR = '';
	function op($search) {
		global $OP_ERROR;

		$categoryid = 0;

		// Get 0, 10, 20 , 30, 40... Pages (Based on OP)
		foreach (range(0, 500, 10) as $start) {
		    $res = download_op($search, false, 'https://octopart.com/', 'op', $start, $categoryid);
			if ($res===false) { return ($OP_ERROR); }

			$resArray = parse_op($res);
		}

		return false;
	}
?>
