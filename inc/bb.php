<?php
	// New Items
	if ($DEV_ENV) {
	 	include_once $_SERVER["ROOT_DIR"].'/inc/download_bb.php';
	 	include_once $_SERVER["ROOT_DIR"].'/inc/parse_bb.php';
	} else {
		include_once $_SERVER["ROOT_DIR"].'/inc/bbsoap.php';
		include_once $_SERVER["ROOT_DIR"].'/inc/parse_bbSoap.php';

		$bbSoap = new bbSoap('davidlangley','DLavid411$$$','jCC6ZnNoRmZtQaQK',"brokerbin","./soap_auth/");
	}

	if (! isset($DEBUG)) { $DEBUG = 0; }

	function bb($search) {
		global $BB_ERROR,$DEV_ENV;

		if ($DEV_ENV) {
	 		$res = download_bb($search);
 			if ($res===false) { return ($BB_ERROR); }

 			$resArray = parse_bb($res);
		} else {
			global $bbSoap;

			$res = $bbSoap->soapSearch($search);
			if ($res===false) { return ($BB_ERROR); }

			if ($GLOBALS['DEBUG']) {
				$resArray = parse_bbSoap($res,'array');
				return ($resArray);
			} else {
				$resArray = parse_bbSoap($res);
			}
		}

		return false;
	}
