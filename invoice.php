<?php
	if (! isset($_REQUEST['invoice']) OR ! $_REQUEST['invoice']) { $create_invoice = true; }

	include 'order.php';
	exit;
?>
