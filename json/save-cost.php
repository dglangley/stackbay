<?php
	header("Content-Type: application/json", true);
	include_once '../inc/dbconnect.php';
	include_once '../inc/setAverageCost.php';
	include_once '../inc/jsonDie.php';

	$partid = 0;
	if (isset($_REQUEST['partid'])) { $partid = trim($_REQUEST['partid']); }
	$average_cost = false;
	if (isset($_REQUEST['average_cost']) AND $_REQUEST['average_cost']<>'') { $average_cost = trim($_REQUEST['average_cost']); }

	setAverageCost($partid,$average_cost,true);

	$average_cost = number_format($average_cost,2,'.','');
	echo json_encode(array('message'=>'Success','cost'=>$average_cost));
	exit;
?>
