<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setItem.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getInventory.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/split_inventory.php';

	$DEBUG = 0;

	function setAssignment($inventoryid,$assignmentid,$notes) {
		if (! $inventoryid) { return false; }

		// find if currently assigned out to someone else, and if so then UNassign it; note that this is the default behavior
		// if (! $assignmentid), which is indicative of the user putting something back into stock that was previously assigned out
		$query = "SELECT id FROM inventory_dni ";
		$query .= "WHERE inventoryid = '".res($inventoryid)."' AND unassigned IS NULL; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$query2 = "UPDATE inventory_dni SET unassigned = '".$GLOBALS['now']."' WHERE id = '".res($r['id'])."'; ";
			$result2 = qedb($query2);
		}

		// nothing to do past here if no assignmentid
		if (! $assignmentid) { return false; }

		$query = "INSERT INTO inventory_dni (inventoryid, ownerid, userid, datetime, notes) ";
		$query .= "VALUES ('".res($inventoryid)."', '".res($assignmentid)."', '".res($GLOBALS['U']['id'])."', '".$GLOBALS['now']."', ".fres($notes)."); ";
		$result = qedb($query);

		// update status in inventory to be sure it's 'internal use'
		$I = array(
			'id' => $inventoryid,
			'status' => 'internal use',
		);
		setInventory($I);
//		$query = "UPDATE inventory SET status = 'internal use' WHERE id = '".res($inventoryid)."'; ";
//		$result = qedb($query);
	}

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
	$assignments_notes = '';
	if (isset($_REQUEST['assignments-notes']) AND trim($_REQUEST['assignments-notes'])) { $assignments_notes = trim($_REQUEST['assignments-notes']); }
	$status = '';
	if (isset($_REQUEST['inventory-status']) AND trim($_REQUEST['inventory-status'])) { $status = trim($_REQUEST['inventory-status']); }
	$ownerid = 0;
	if (isset($_REQUEST['ownerid']) AND trim($_REQUEST['ownerid'])) { $ownerid = trim($_REQUEST['ownerid']); }
	$assignmentid = 0;
	if (isset($_REQUEST['assignmentid']) AND trim($_REQUEST['assignmentid'])) { $assignmentid = trim($_REQUEST['assignmentid']); }
	$assignments = 0;
	if (isset($_REQUEST['assignments']) AND trim($_REQUEST['assignments'])) { $assignments = trim($_REQUEST['assignments']); }
	if ($DEBUG) { print "<pre>".print_r($_REQUEST,true)."</pre>"; }

	$serial = strtoupper($serial);

	if (! $inventoryid) {
		die("No inventoryid!");
	}

	$order_number = 0;

	// in group-edit cases, $inventoryid is passed in as a CSV string
	$inventoryids = explode(',',$inventoryid);
	$num_inventoryids = count($inventoryids);

	// set globally if used below for creating RO(s)
	$due_date = format_date($today,'Y-m-d',array('d'=>30));

	foreach ($inventoryids as $id) {
		$I = array('id'=>$id);

		$source_partid = 0;
		$source_qty = 0;
		$source_avgcost = 0;
		$target_avgcost = 0;

		// status change is a singular event, and does not happen in coordination with other updates as in serial/location/condition
		if ($status AND $status<>$I['status']) {
			$I['status'] = $status;

			if ($status=='in repair') {
				// get partid for creating repair items record
				$INVENTORY = getInventory($inventoryid);
				$partid = $INVENTORY['partid'];

				// if $serial is passed in, user is simply editing the status and we are not intending to SEND to repair;
				// otherwise, sending to repair carries a boatload of actions laid out below...
				if (! $serial) {
					// when sending a unit to repair, generate the repair order here and then use the repair item id as part
					// of the inventory update query that we started above
					$order_number = setOrder('Repair');
					$repair_item_id = setItem('Repair',$order_number,$partid,1,1,'0.00',$due_date,$inventoryid);

					// add new repair item id to inventory array for updating below
					$I['repair_item_id'] = $repair_item_id;
				}
			}
		}
		if ($assignments) {
			setAssignment($inventoryid,$assignmentid,$assignments_notes);
		} else {
			// this is not the place where we would be resetting a serial, so only update it if passed in
			if ($serial) { 
				$I['serial_no'] = $serial; 

				$INV = getInventory($I['id']);

				// Adding function to detemine if a split is required for this record... Cases is when serializing a component or non serialized item
				if($INV['qty'] > 1) {
					// If this record is greater than 1
					$split = 1;
					// Splitting the record to 1 less than current
					// True means to recalc the cost of the new inventory split out
					split_inventory($I['id'], $split, true);
				}

				// Else just set the 1 to the serial and not have to do anything else
			}

			// we're not resetting partid to null, so only update if it's passed in; this is also where we RM a part, and update average costs
			if ($partid) {
				$I['partid'] = $partid;

				/***** RM PROCESSOR AND AVERAGE COSTS UPDATER *****/
				$INVENTORY = getInventory($id);
				$source_partid = $INVENTORY['partid'];
				$source_qty = $INVENTORY['qty'];
				// just in case, considering legacy qty method
				if (! $source_qty) { $source_qty = 1; }

				// see setCost() for comparison of doing this method; get source cost, then target cost, and use it to calc
				// $diff which is used by setAverageCost() for updating...
				$source_avgcost = getCost($source_partid);
				// target avg is the current avg of the new partid
				$target_avgcost = getCost($partid);//$partid is target/new partid
			}
			$I['locationid'] = $locationid;
			$I['conditionid'] = $conditionid;
			$I['notes'] = $notes;
		}

		setInventory($I);

		// if RMing a part, $source_partid will be set from above; since the partid needs to be updated first, we have this
		// section of code post-setInventory() above
		if ($source_partid) {
			// if none in stock, we're absolutely overriding any past average cost records that are now irrelevant
			if (! $QTYS[$partid]) {
				$avg_cost = setAverageCost($partid,$source_avgcost,true);
			} else {
				$diff = $source_avgcost-$target_avgcost;
				$avg_cost = setAverageCost($partid,($diff*$source_qty));
			}
		}
	}

	if ($DEBUG) { exit; }

	if ($status=='in repair' AND $order_number) {
		header('Location: /order.php?order_type=Repair&order_number='.$order_number);
		exit;
	}

	$params = '';
	if (isset($_REQUEST['order_search']) AND $_REQUEST['order_search']) {
		if ($params) { $params .= '&'; }
		$params .= 'order_search='.trim($_REQUEST['order_search']);
	}
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
		if ($params) { $params .= '&'; }
		$params .= 'START_DATE='.trim($_REQUEST['START_DATE']);
	}
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']) {
		if ($params) { $params .= '&'; }
		$params .= 'END_DATE='.trim($_REQUEST['END_DATE']);
	}
	if (isset($_REQUEST['s2']) AND $_REQUEST['s2']) {
		if ($params) { $params .= '&'; }
		$params .= 's2='.trim(urlencode($_REQUEST['s2']));
	}
	if (isset($_REQUEST['locationid']) AND $_REQUEST['locationid']) {
		if ($params) { $params .= '&'; }
		$params .= 'locationid='.trim($_REQUEST['locationid']);
	}
	if (isset($_REQUEST['companyid']) AND $_REQUEST['companyid']) {
		if ($params) { $params .= '&'; }
		$params .= 'companyid='.trim($_REQUEST['companyid']);
	}

	if ($params) { $params = '?'.$params; }

	if ($assignments) {
		header('Location: /tools.php'.$params);
	} else {
		header('Location: /inventory.php'.$params);
	}
	exit;
?>
