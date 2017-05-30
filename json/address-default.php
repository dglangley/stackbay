<?php

//=============================== Address Default ==============================
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/order_parameters.php';
	include_once $rootdir.'/inc/default_addresses.php';

	
    $companyid = grab('company');
    $order_type = grab('order');
	$results = array();
	$results = default_addresses($companyid,$order_type);
	
	echo json_encode($results);//array('results'=>$companies,'more'=>false));
	exit;
?>


