<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/lici.php';

	$internal = ($_REQUEST['internal'] ? : '');

	$DEBUG = 0;

	$id = lici(0, '', 'out');

	// If this is an internal clockout (From the task view and not from the user dropdown then clock them back into their assigned internal job)
	if($internal) {
		header('Location: /clockin.php');
	} else 	{
		header('Location: /');
	}
	exit;
?>
