<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setAverageCost.php';

	if (! isset($DEBUG)) { $DEBUG = 0; }

	function setInventory($I) {
		$inventoryid = 0;
		if (isset($I['id']) AND $I['id']) { $inventoryid = $I['id']; }

		$INVENTORY = array();
		// check inventory for existing serial, and if already exists, set the inventoryid
		if (! $inventoryid AND isset($I['serial_no']) AND trim($I['serial_no']) AND isset($I['partid']) AND trim($I['partid'])) {
			$INVENTORY = getInventory($I['serial_no'],$I['partid']);
			$inventoryid = $INVENTORY['id'];
		}

		if ($inventoryid) {
			// having existing $INVENTORY data is important for status and conditionid updates, see below for moving a record in and out of inventory
			if (count($INVENTORY)==0) {
				$INVENTORY = getInventory($inventoryid);
			}

			// dynamically get columns from inventory table so we can check $I array for updated values
			$fields = array();
			$query = "SHOW COLUMNS FROM inventory; ";
			$fields = qedb($query);

			$updates = '';
			while ($r = mysqli_fetch_assoc($fields)) {
				if (! isset($I[$r['Field']]) OR $r['Field']=='id') { continue; }
				if ($updates) { $updates .= ", "; }

				$value = trim($I[$r['Field']]);
				if ($r['Field']=='userid' AND ! $value) { $value = '0'; }//cannot be null
				else if ($r['Field']=='date_created' AND ! $value) { $value = $GLOBALS['now']; }

				if ($value===false OR $value===NULL) { $updates .= $r['Field']." = NULL "; } else { $updates .= $r['Field']." = '".res($value)."' "; }
			}
			if ($updates) {
				$query = "UPDATE inventory SET ".$updates;
				$query .= "WHERE id = '".res($inventoryid)."'; ";
				$result = qedb($query);
			}

			$status = '';
			if (isset($I['status'])) { $status = $I['status']; }
			$conditionid = 0;
			if (isset($I['conditionid'])) { $conditionid = $I['conditionid']; }

			// when moving an item in and out of repair, or in and out of bad stock (0<conditionid), we need to track costs for averaging purposes
			if (($status AND $status<>$INVENTORY['status'])
			OR ($conditionid AND (($conditionid>0 AND $INVENTORY['conditionid']<0) OR ($conditionid<0 AND $INVENTORY['conditionid']>0)))) {
				if (($INVENTORY['status']=='received') AND ($status=='in repair' OR $status=='testing' OR $status=='manifest')) {
					/***** moving OUT of active inventory *****/
					setInventoryAvg($inventoryid,$INVENTORY['partid']);
				} else if (($INVENTORY['status']=='in repair' OR $INVENTORY['status']=='testing' OR $INVENTORY['status']=='manifest') AND ($status=='received')) {
					/***** moving INTO active inventory *****/
					$inventory_avg = applyInventoryAvg($inventoryid,$INVENTORY['partid'],$INVENTORY['qty']);
				} else if ($INVENTORY['conditionid']>0 AND $conditionid<0) {
					/***** moving OUT of active inventory *****/
					setInventoryAvg($inventoryid,$INVENTORY['partid']);
				} else if ($INVENTORY['conditionid']<0 AND $conditionid>0) {
					/***** moving INTO active inventory *****/
					$inventory_avg = applyInventoryAvg($inventoryid,$INVENTORY['partid'],$INVENTORY['qty']);
				}
			}
		} else {
			$serial = '';
			if (isset($I['serial_no']) AND trim($I['serial_no'])) { $serial = trim($I['serial_no']); }
			$qty = 1;
			if (isset($I['qty']) AND trim($I['qty'])) { $qty = trim($I['qty']); }
			$partid = 0;
			if (isset($I['partid']) AND trim($I['partid'])) { $partid = trim($I['partid']); }
			$conditionid = false;
			if (isset($I['conditionid'])) { $conditionid = trim($I['conditionid']); }
			$status = 'received';
			if (isset($I['status'])) { $status = trim($I['status']); }
			$locationid = false;
			if (isset($I['locationid'])) { $locationid = trim($I['locationid']); }
			$bin = false;
			if (isset($I['bin'])) { $bin = trim($I['bin']); }
			$pid = false;
			if (isset($I['purchase_item_id'])) { $pid = trim($I['purchase_item_id']); }
			$sid = false;
			if (isset($I['sales_item_id'])) { $sid = trim($I['sales_item_id']); }
			$rmaid = false;
			if (isset($I['returns_item_id'])) { $rmaid = trim($I['returns_item_id']); }
			$rid = false;
			if (isset($I['repair_item_id'])) { $rid = trim($I['repair_item_id']); }
			$userid = $GLOBALS['U']['id'];
			if (isset($I['userid']) AND trim($I['userid'])) { $userid = trim($I['userid']); }
			$datetime = $GLOBALS['now'];
			if (isset($I['date_created']) AND trim($I['date_created'])) { $datetime = trim($I['date_created']); }
			$notes = false;
			if (isset($I['notes']) AND trim($I['notes'])) { $notes = trim($I['notes']); }

			// effectively an INSERT statement:
			$query = "REPLACE inventory (serial_no, qty, partid, conditionid, status, locationid, bin, ";
			$query .= "purchase_item_id, sales_item_id, returns_item_id, repair_item_id, userid, date_created, notes) ";
			$query .= "VALUES (".fres($serial).", $qty, $partid, ";
			if ($conditionid) { $query .= "$conditionid, "; } else { $query .= "NULL, "; }
			if ($status) { $query .= "'".res(strtolower($status))."', "; } else { $query .= "NULL, "; }
			if ($locationid) { $query .= "$locationid, "; } else { $query .= "NULL, "; }
			if ($bin) { $query .= "$bin, "; } else { $query .= "NULL, "; }
			if ($pid) { $query .= "$pid, "; } else { $query .= "NULL, "; }
			if ($sid) { $query .= "$sid, "; } else { $query .= "NULL, "; }
			if ($rmaid) { $query .= "$rmaid, "; } else { $query .= "NULL, "; }
			if ($rid) { $query .= "$rid, "; } else { $query .= "NULL, "; }
			if ($userid) { $query .= "$userid, "; } else { $query .= "0, "; }
			$query .= "'".res($datetime)."',";
			if ($notes) { $query .= "'".res($notes)."' "; } else { $query .= "NULL "; }
			$query .= "); ";
			$result = qedb($query);
			if ($GLOBALS['DEBUG']) {
				$inventoryid = 999999;
			} else {
				$inventoryid = qid();
			}
		}

		if ($GLOBALS['DEBUG']) { return (999999); }
		else { return ($inventoryid); }
	}

	function setInventoryAvg($inventoryid,$partid) {
		// save the item's average cost so we can extract it later; there's no need to update average cost for
		// the partid because removal doesn't impact the average; only addition
		$avg_cost = getCost($partid);

		$query = "SELECT id FROM inventory_costs WHERE inventoryid = '".res($inventoryid)."'; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$costid = $r['id'];

			$query = "UPDATE inventory_costs SET average = '".res($avg_cost)."' WHERE inventoryid = '".res($inventoryid)."'; ";
			$result = qedb($query);
		} else {
			$query = "INSERT INTO inventory_costs (inventoryid, datetime, actual, average) ";
			$query .= "VALUES ('".res($inventoryid)."', '".$GLOBALS['now']."', NULL, '".res($avg_cost)."'); ";
			$result = qedb($query);
		}
	}

	function applyInventoryAvg($inventoryid,$partid,$inventory_qty) {
		$inventory_avg = 0;
		$query = "SELECT average FROM inventory_costs WHERE inventoryid = '".res($inventoryid)."'; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$inventory_avg = $r['average'];
		}

		// subtracting inventory qty because the item has already been updated back into stock but we don't want
		// it to be counted yet since we're using its average inventory_costs value when bringing it back in
		$qty = getQty($partid)-$inventory_qty;

		$avg_cost = getCost($partid);
		$sum_avg = $qty*$avg_cost;
		$sum_avg += ($inventory_qty*$inventory_avg);

		$new_avg = 0;
		if (($qty+$inventory_qty)>0) { $new_avg = $sum_avg/($qty+$inventory_qty); }

		setAverageCost($partid,$new_avg,true);

		// reset inventory average value on inventory_costs now that it's served its purpose and is back in stock
		$query = "UPDATE inventory_costs SET average = NULL WHERE inventoryid = '".res($inventoryid)."'; ";
		$result = qedb($query);

		return ($avg_cost);
	}
?>
