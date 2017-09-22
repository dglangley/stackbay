<?php
	header('Content-Type: application/json');

	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/getCost.php';
	include_once $rootdir.'/inc/setAverageCost.php';
	include_once $rootdir.'/inc/jsonDie.php';

	$debug = 0;
	if (isset($_REQUEST['debug']) AND $_REQUEST['debug']>0) { $debug = 1; }

	$invid = grab('invid');
	$q_invid = prep($invid);
	$partid = grab('partid');
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
	// target avg is the current avg of the new partid
	$target_avgcost = getCost($partid);

	$update = "UPDATE `inventory` SET `partid`= $q_partid WHERE `id` = $q_invid;";
	if (! $debug) { $result = qdb($update) OR jsonDie(qe().' '.$update); }

	// if none in stock, we're absolutely overriding any past average cost records that are now irrelevant
	if (! $QTYS[$partid]) {
		$avg_cost = setAverageCost($partid,$incoming_avgcost,true);
	} else {
		$diff = $incoming_avgcost-$target_avgcost;
		$avg_cost = setAverageCost($partid,($diff*$source_qty));
	}

	if ($debug) {
		$addl_info .= "<BR>SELECT qty, serial_no FROM inventory WHERE partid = '".$partid."' AND (status = 'received') AND conditionid >= 0 AND qty > 0;";
		echo json_encode(array('message'=>'Incoming avg cost: '.$incoming_avgcost.', Target avg cost: '.$target_avgcost.', Diff: '.$diff.' ('.($diff*$source_qty).'), New Avg Cost: '.$avg_cost.$addl_info,'data'=>''));
	} else {
		echo json_encode(array('message'=>'Success','data'=>$avg_cost));
	}
	exit;
?>
