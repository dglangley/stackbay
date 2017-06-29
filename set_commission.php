<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCommission.php';

	$invoice = 0;
	if (isset($_REQUEST['i'])) { $invoice = trim($_REQUEST['i']); }

	setCommission($invoice);
?>
