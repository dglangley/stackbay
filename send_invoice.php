<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/sendInvoice.php';

	$invoice = 0;
	if (isset($_REQUEST['invoice']) AND is_numeric(trim($_REQUEST['invoice']))) { $invoice = trim($_REQUEST['invoice']); }

	if (! $invoice) { die("Missing Invoice No."); }

	$send_err = sendInvoice($invoice);
	if ($send_err) {
		die($send_err);
	}

	header('Location: invoice.php?invoice='.$invoice);
	exit;
?>
