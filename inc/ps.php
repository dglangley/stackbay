<?php
	include_once 'download_ps.php';
	include_once 'parse_ps.php';

	$PS_ERROR = '';
	function ps($search) {
		global $PS_ERROR;

		$res = download_ps($search);
		if ($res===false) { return ($PS_ERROR); }
//		echo $res;exit;

		$resArray = parse_ps($res,false);
//		print_r($resArray);

		return false;
	}
?>
