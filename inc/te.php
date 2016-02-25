<?php
	include_once 'download_te.php';
	include_once 'parse_te.php';

	$TE_ERROR = '';
	function te($search) {
		global $TE_ERROR;

		$res = download_te($search);
		if ($res===false) { return ($TE_ERROR); }

		$resArray = parse_te($res);

		return false;
	}
?>
