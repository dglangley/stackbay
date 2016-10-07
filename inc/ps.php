<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_ps.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_ps.php';

	$PS_ERROR = '';
	function ps($search='') {
		global $PS_ERROR;

		$res = download_ps($search);
		if ($res===false) { return ($PS_ERROR); }
//		echo $res;exit;

		$resArray = parse_ps($res);
//		print_r($resArray);

		return false;
	}
?>
