<?php
	if (! isset($_REQUEST['bill']) OR ! $_REQUEST['bill']) { $create_order = 'Bill'; }
	else { $ref_no = $_REQUEST['bill']; }

	include 'order.php';
	exit;
?>
