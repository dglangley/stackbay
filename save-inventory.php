<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';

	$inventoryid = 0;
	if (isset($_REQUEST['inventoryid']) AND is_numeric($_REQUEST['inventoryid'])) { $inventoryid = $_REQUEST['inventoryid']; }
	$partid = 0;
	if (isset($_REQUEST['inventory-partid']) AND is_numeric($_REQUEST['inventory-partid'])) { $partid = $_REQUEST['inventory-partid']; }
	$locationid = 0;
	if (isset($_REQUEST['inventory-locationid']) AND is_numeric($_REQUEST['inventory-locationid'])) { $locationid = $_REQUEST['inventory-locationid']; }
	$conditionid = 0;
	if (isset($_REQUEST['inventory-conditionid']) AND is_numeric($_REQUEST['inventory-conditionid'])) { $conditionid = $_REQUEST['inventory-conditionid']; }
	$serial = '';
	if (isset($_REQUEST['inventory-serial']) AND trim($_REQUEST['inventory-serial'])) { $serial = trim($_REQUEST['inventory-serial']); }
	$notes = '';
	if (isset($_REQUEST['inventory-notes']) AND trim($_REQUEST['inventory-notes'])) { $notes = trim($_REQUEST['inventory-notes']); }

	$serial = strtoupper($serial);

	if (! $inventoryid) {
		jsonDie("No inventoryid!");
	}

	$query = "UPDATE inventory SET ";
	$query .= "serial_no = ";
	if ($serial) { $query .= "'".res($serial)."', "; } else { $query .= "NULL, "; }
	$query .= "partid = '".res($partid)."', ";
	$query .= "locationid = '".res($locationid)."', ";
	$query .= "conditionid = '".res($conditionid)."', ";
	$query .= "notes = ";
	if ($notes) { $query .= "'".res($notes)."' "; } else { $query .= "NULL "; }
	$query .= "WHERE id = '".res($inventoryid)."' LIMIT 1; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);

	$params = '';
	if (isset($_REQUEST['order_search'])) {
		if ($params) { $params .= '&'; }
		$params .= 'order_search='.trim($_REQUEST['order_search']);
	}
	if (isset($_REQUEST['START_DATE'])) {
		if ($params) { $params .= '&'; }
		$params .= 'START_DATE='.trim($_REQUEST['START_DATE']);
	}
	if (isset($_REQUEST['END_DATE'])) {
		if ($params) { $params .= '&'; }
		$params .= 'END_DATE='.trim($_REQUEST['END_DATE']);
	}
	if (isset($_REQUEST['s2'])) {
		if ($params) { $params .= '&'; }
		$params .= 's2='.trim($_REQUEST['s2']);
	}
	if (isset($_REQUEST['locationid'])) {
		if ($params) { $params .= '&'; }
		$params .= 'locationid='.trim($_REQUEST['locationid']);
	}
	if (isset($_REQUEST['companyid'])) {
		if ($params) { $params .= '&'; }
		$params .= 'companyid='.trim($_REQUEST['companyid']);
	}

	if ($params) { $params = '?'.$params; }

	header('Location: /inventory-beta.php'.$params);
	exit;
?>
