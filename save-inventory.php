<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setItem.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';

	$debug = 0;

	$inventoryid = 0;
	if (isset($_REQUEST['inventoryid'])) { $inventoryid = $_REQUEST['inventoryid']; }
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
	$status = '';
	if (isset($_REQUEST['inventory-status']) AND trim($_REQUEST['inventory-status'])) { $status = trim($_REQUEST['inventory-status']); }

	$serial = strtoupper($serial);

	if (! $inventoryid) {
		jsonDie("No inventoryid!");
	}

	$order_number = 0;

	// in group-edit cases, $inventoryid is passed in as a CSV string
	$inventoryids = explode(',',$inventoryid);
	$num_inventoryids = count($inventoryids);

	$query = "UPDATE inventory SET ";
	if ($status) {
		$query .= "status = '".res($status)."' ";

		if ($status=='in repair') {
			// get partid for creating repair items record
			$query2 = "SELECT partid FROM inventory WHERE id = '".res($inventoryid)."'; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)==0) {
				die("Could not find inventory record for $inventoryid");
			}
			$r2 = mysqli_fetch_assoc($result2);
			$partid = $r2['partid'];

			$due_date = format_date($today,'Y-m-d',array('d'=>30));

			// when sending a unit to repair, generate the repair order here and then use the repair item id as part
			// of the inventory update query that we started above
			$order_number = setOrder('Repair');
			$repair_item_id = setItem('Repair',$order_number,$partid,1,1,'0.00',$due_date,$inventoryid);

			$query .= ", repair_item_id = '".res($repair_item_id)."' ";
		}
	} else {
		$query .= "serial_no = ";
		if ($serial) { $query .= "'".res($serial)."', "; } else { $query .= "NULL, "; }
		$query .= "partid = '".res($partid)."', ";
		$query .= "locationid = '".res($locationid)."', ";
		$query .= "conditionid = '".res($conditionid)."', ";
		$query .= "notes = ";
		if ($notes) { $query .= "'".res($notes)."' "; } else { $query .= "NULL "; }
	}
	$query .= "WHERE id IN (".res($inventoryid).") LIMIT ".$num_inventoryids."; ";
	if ($debug) { echo $query.'<BR>'; }
	else { $result = qdb($query) OR die(qe().'<BR>'.$query); }

	if ($debug) { exit; }

	if ($status=='in repair' AND $order_number) {
		header('Location: /order_form.php?ps=repair&on='.$order_number);
		exit;
	}

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

	header('Location: /inventory.php'.$params);
	exit;
?>
