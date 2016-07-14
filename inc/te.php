<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_te.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_te.php';

	$TE_ERROR = '';
	function te($search) {
		global $TE_ERROR;

		$res = download_te($search);
		if ($res===false) { return ($TE_ERROR); }

		$resArray = parse_te($res);

		return false;
	}
?>
