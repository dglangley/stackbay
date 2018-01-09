<?php
	$rma_number = '';
	if (isset($_REQUEST['order_number'])) { $rma_number = trim($_REQUEST['order_number']); }

	include 'rma.php';
?>
