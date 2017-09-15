<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/getLocation.php';
	include_once '../inc/getCondition.php';
	include_once '../inc/jsonDie.php';

	$inventoryid = 0;
	if (isset($_REQUEST['inventoryid']) AND is_numeric($_REQUEST['inventoryid']) AND $_REQUEST['inventoryid']>0) { $inventoryid = trim($_REQUEST['inventoryid']); }
	if (! $inventoryid) {
		jsonDie("Missing inventoryid!");
	}

	$query = "SELECT i.*, p.part, p.heci ";
	$query .= "FROM inventory i, parts p WHERE i.id = '".res($inventoryid)."' AND p.id = i.partid; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==0) {
		jsonDie("Could not find inventory record ".$inventoryid);
	}
	$r = mysqli_fetch_assoc($result);
	$r['name'] = trim($r['part'].' '.$r['heci']);
	$r['location'] = getLocation($r['locationid']);
	$r['condition'] = getCondition($r['conditionid']);
	$r['status'] = ucwords($r['status']);

	header("Content-Type: application/json", true);
	echo json_encode($r);
	exit;
?>
