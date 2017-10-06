<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/jsonDie.php';

	$carrierid = 0;
	if (isset($_REQUEST['carrierid'])) { $carrierid = $_REQUEST['carrierid']; }

	$services = array();
	$query = "SELECT id, method text, days, notes FROM freight_services WHERE carrierid = '".res($carrierid)."' ";
	$query .= "ORDER BY days DESC, method ASC; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$services[] = $r;
	}

	header("Content-Type: application/json", true);
	echo json_encode($services);
	exit;
?>
