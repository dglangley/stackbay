<?php

//=============================== Address Default ==============================
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/getAddresses.php';

	$a = grab('address');
	$address = getAddresses($a);

	echo json_encode($address);//array('results'=>$companies,'more'=>false));
	exit;
?>
