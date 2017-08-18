<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/credit_functions.php';

	$rma_number = grab('rma_number');
	$order_number = grab('order_number');
	$order_type = grab('order_type');

	if (qualifyCredit($rma_number)) {
		$cmid = createCredit($rma_number);
		if (! $cmid) {
			die("Failed");
		}
	} else {
		die("Failed");
	}

	header('Location: /'.strtoupper(substr($order_type,0,1)).'O'.$order_number);
	exit;
?>
