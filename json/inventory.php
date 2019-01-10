<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/getLocation.php';
	include_once '../inc/getCondition.php';
	include_once '../inc/jsonDie.php';

	$inventoryids = array();
	if (isset($_REQUEST['inventoryid']) AND is_numeric($_REQUEST['inventoryid']) AND $_REQUEST['inventoryid']>0) {
		$inventoryids = array(trim($_REQUEST['inventoryid']));
	} else if (isset($_REQUEST['inventoryids'])) {
		if (is_numeric($_REQUEST['inventoryids']) AND $_REQUEST['inventoryids']>0) {
			$inventoryids = array($_REQUEST['inventoryids']);
		} else if (is_array($_REQUEST['inventoryids'])) {
			$inventoryids = $_REQUEST['inventoryids'];
		} else {
			$inventoryids = explode(',',$_REQUEST['inventoryids']);
		}
	}

	if (count($inventoryids)==0) {
		jsonDie("Missing inventory id(s)!");
	}

	$id_csv = '';
	foreach ($inventoryids as $id) {
		if ($id_csv) { $id_csv .= ','; }
		$id_csv .= $id;
	}

	$query = "SELECT i.*, p.part, p.heci ";
	//$query .= "FROM inventory i, parts p WHERE i.id = '".res($inventoryid)."' AND p.id = i.partid; ";
	$query .= "FROM inventory i, parts p WHERE i.id IN (".res($id_csv).") AND p.id = i.partid ";
	$query .= "GROUP BY i.partid, locationid, conditionid, status; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	$n = qnum($result);
	if ($n==0) {
		jsonDie("Could not find inventory records related to inventory id(s)");
	} else if ($n>1) {
		jsonDie("Inventory id(s) can't be grouped like that!");
	}
	$r = mysqli_fetch_assoc($result);
	$r['name'] = trim($r['part'].' '.$r['heci']);
	$r['location'] = getLocation($r['locationid']);
	$r['condition'] = getCondition($r['conditionid']);
	$r['status'] = ucwords($r['status']);

	// when editing groups, disable certain fields
	if (count($inventoryids)>1) {
		unset($r['serial_no']);
		unset($r['qty']);
		unset($r['notes']);
		$r['id'] = $id_csv;//id will be all id's separated by comma
	}

	header("Content-Type: application/json", true);
	echo json_encode($r);
	exit;
?>
