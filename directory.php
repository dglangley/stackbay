<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$VIEW_MODE = true;
	$_REQUEST['companyid'] = $U['companyid'];

	include 'profile.php';
	exit;
?>
