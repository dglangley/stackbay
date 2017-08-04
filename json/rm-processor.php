<?php
	header('Content-Type: application/json');

	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/getCost.php';
	include_once $rootdir.'/inc/setAverageCost.php';
	include_once $rootdir.'/inc/jsonDie.php';

	$invid = grab('invid');
	$q_invid = prep($invid);
	$partid = grab('part');
	$q_partid = prep($partid);

	// get existing partid for this inventory record so we can get current average cost
	$query = "SELECT partid, qty FROM inventory WHERE id = $q_invid; ";
	$result = qdb($query) OR jsonDie(qe().' '.$query);
	if (mysqli_num_rows($result)==0) {
		jsonDie("Part id missing for inventory record $invid");
	}
	$r = mysqli_fetch_assoc($result);
	$source_partid = $r['partid'];
	$source_qty = $r['qty'];
	if (! $source_qty) { $source_qty = 1; }

	// see setCost() for comparison of doing this method; get source cost, then target cost, and use it to calc
	// $diff which is used by setAverageCost() for updating...
	$incoming_avgcost = getCost($source_partid);
	$target_avgcost = getCost($partid);

	$update = "UPDATE `inventory` SET `partid`= $q_partid WHERE `id` = $q_invid;";
	$result = qdb($update) OR jsonDie(qe().' '.$update);

	$diff = $incoming_avgcost-$target_avgcost;
	setAverageCost($partid,($diff*$source_qty));

	echo json_encode($result);
	exit;
?>
