<?php
	if (! isset($_REQUEST['invoice']) OR ! $_REQUEST['invoice']) { $create_order = 'Invoice'; }

	include 'order.php';
	exit;
?>
