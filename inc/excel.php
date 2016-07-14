<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/download_excel.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_excel.php';

	$EXCEL_ERROR = '';
	function excel($search) {
		global $EXCEL_ERROR;

		$res = download_excel($search);
		if ($res===false) { return ($EXCEL_ERROR); }

		$resArray = parse_excel($res);

		return false;
	}
?>
