<?php

//Prepare the page as a JSON type
header('Content-Type: application/json');

$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/invoice.php';

	$so_number = grab('order_number');
		
	$result = create_invoice($so_number, $now, "Sale");
		
	echo json_encode($result);
    exit;
