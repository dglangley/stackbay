<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCommission.php';

	$debug = 1;

	$invoice = 0;
	if (isset($_REQUEST['invoice'])) { $invoice = trim($_REQUEST['invoice']); }
	$itemid = 0;
	if (isset($_REQUEST['itemid'])) { $itemid = trim($_REQUEST['itemid']); }

	setCommission($invoice,$itemid);
?>
