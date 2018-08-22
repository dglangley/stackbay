<?php
	$debug = 0;
	$results = array();
	$k = 0;

	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/auto/inventory_export.php';

	echo json_encode(array('message'=>'Successfully exported '.count($results).' part(s) totaling '.$k.' item(s)'));
	exit;
?>
