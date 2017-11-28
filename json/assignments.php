<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/jsonDie.php';
	include_once '../inc/format_date.php';
	include_once '../inc/getUser.php';

	$inventoryid = 0;
	if (isset($_REQUEST['inventoryid']) AND is_numeric($_REQUEST['inventoryid']) AND $_REQUEST['inventoryid']>0) { $inventoryid = trim($_REQUEST['inventoryid']); }
	if (! $inventoryid) {
		jsonDie("Missing inventoryid!");
	}

	$status = '';
	$query = "SELECT * FROM inventory WHERE id = '".res($inventoryid)."'; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==0) {
		jsonDie("Could not find inventory record ".$inventoryid);
	}
	$r = mysqli_fetch_assoc($result);
	$status = $r['status'];

	$assignments = '';
	$query = "SELECT dni.* ";
	$query .= "FROM inventory_dni dni WHERE dni.inventoryid = '".res($inventoryid)."' ";
	$query .= "ORDER BY dni.datetime DESC; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		if ($assignments) { $assignments .= '<br/>'; }
		$assignments .= format_date($r['datetime'],'n/j/y g:ia').' Assigned to '.getUser($r['ownerid']).' '.$r['notes'];
	}

	header("Content-Type: application/json", true);
	echo json_encode(array('assignments'=>$assignments,'status'=>$status,'message'=>''));
	exit;
?>
