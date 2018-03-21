<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/keywords.php';
	include_once '../inc/jsonDie.php';
	include_once '../inc/order_type.php';

	$companyid = '';
	if (isset($_REQUEST['companyid']) AND trim($_REQUEST['companyid'])) { $companyid = trim($_REQUEST['companyid']); }

	$order_type = '';
	if (isset($_REQUEST['order_type']) AND trim($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }

	$T = order_type($order_type);
	$results = array();
	
	$query = "SELECT ".$T['order']." FROM ".$T['orders']." WHERE companyid = ".res($companyid).";";
	$result = qedb($query);

	while($r = mysqli_fetch_assoc($result)) {

		$results[$r[$T['order']]] = array('id'=>$r[$T['order']],'text'=>trim($r[$T['order']]));
	}

	header("Content-Type: application/json", true);
	echo json_encode($results);
	exit;
?>
