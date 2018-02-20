<?php
	if (! isset($_REQUEST['invoice']) OR ! $_REQUEST['invoice']) { $create_order = 'Invoice'; }
	else { $ref_no = $_REQUEST['invoice']; }

	include 'order.php';
	exit;
?>
