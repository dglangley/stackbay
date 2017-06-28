<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$inventoryid = 0;
	if (isset($_REQUEST['id'])) { $inventoryid = trim($_REQUEST['id']); }

	$query = "SELECT partid FROM inventoryid WHERE id = $inventoryid; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);

	$query = "SELECT * FROM average_

	setCost($inventoryid);
?>
