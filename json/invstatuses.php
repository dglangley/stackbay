<?php
	include '../inc/dbconnect.php';
	include '../inc/getEnum.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }

	$statuses = getEnum('inventory','status',$q);

	$results = array();
	foreach ($statuses as $status) {
		$results[] = array('id'=>$status, 'text'=>ucwords($status));
	}

	header("Content-Type: application/json", true);
	echo json_encode($results);
	exit;
?>
