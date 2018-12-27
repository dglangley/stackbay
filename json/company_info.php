<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getQ2S.php';

	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$list_type = 'Sale';
	if (isset($_REQUEST['list_type'])) { $list_type = $_REQUEST['list_type']; }

	$info = getQ2S($companyid,$list_type);

	header("Content-Type: application/json", true);
	echo json_encode($info);
	exit;
?>
