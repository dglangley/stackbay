<?php
// 	include_once $_SERVER["ROOT_DIR"].'/inc/download_bb.php';
// 	include_once $_SERVER["ROOT_DIR"].'/inc/parse_bb.php';

// 	$BB_ERROR = '';
// 	function bb($search) {
// 		global $BB_ERROR;

// 		$res = download_bb($search);
// 		if ($res===false) { return ($BB_ERROR); }

// 		$resArray = parse_bb($res);
// //print_r($resArray);

// 		return false;
// 	}

	// New Items
	include_once $_SERVER["ROOT_DIR"].'/inc/bbsoap.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/parse_bbSoap.php';

	$bbSoap = new bbSoap('davidlangley','DLavid411$$$','jCC6ZnNoRmZtQaQK',"brokerbin","./soap_auth/");

	function bb($search) {
		global $BB_ERROR;
		global $bbSoap;

		$res = $bbSoap->soapSearch($search);
		if ($res===false) { return ($BB_ERROR); }

		$resArray = parse_bbSoap($res);

		//print_r($resArray);

		return false;
	}