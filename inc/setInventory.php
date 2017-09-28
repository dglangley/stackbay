<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getInventory.php';

	if (! isset($debug)) { $debug = 0; }

//	function setInventory($serial,$qty,$partid,$conditionid=2,$status='received',$locationid=0,$bin=false,$pid=false,$sid=false,$rmaid=false,$rid=false,$userid=0,$datetime=false,$notes=false,$inventoryid=false) {
	function setInventory($I) {
		global $debug;

		$inventoryid = 0;
		if (isset($I['id']) AND $I['id']) { $inventoryid = $I['id']; }

		$INVENTORY = array();
		// check inventory for existing serial, and if already exists, set the inventoryid
		if (! $inventoryid AND isset($I['serial_no']) AND trim($I['serial_no']) AND isset($I['partid']) AND trim($I['partid'])) {
			$INVENTORY = getInventory($serial,$partid);
			$inventoryid = $INVENTORY['id'];
		}

		if ($inventoryid) {
			// dynamically get columns from inventory table so we can check $I array for updated values
			$fields = array();
			$query = "SHOW COLUMNS FROM inventory; ";
			$fields = qdb($query) OR die(qe().'<BR>'.$query);

			$updates = '';
			while ($r = mysqli_fetch_assoc($fields)) {
				if (! isset($I[$r['Field']])) { continue; }
				if ($updates) { $updates .= ", "; }

				$value = trim($I[$r['Field']]);
				if ($r['Field']=='userid' AND ! $value) { $value = '0'; }//cannot be null
				else if ($r['Field']=='date_created' AND ! $value) { $value = $GLOBALS['now']; }

				if ($value!==false) { $updates .= $r['Field']." = '".res($value)."' "; } else { $updates .= $r['Field']." = NULL "; }
			}
			if ($updates) {
				$query = "UPDATE inventory SET ".$updates;
				$query .= "WHERE id = '".res($inventoryid)."'; ";
				if ($debug) { echo $query.'<BR>'; }
				else { $result = qdb($query) OR die(qe().'<BR>'.$query); }
			}

			// when moving an item in and out of repair, or in and out of bad stock (0<conditionid), we need to track costs for averaging purposes
			if ($status<>$INVENTORY['status'] AND (($conditionid>0 AND $INVENTORY['conditionid']<0) OR ($conditionid<0 AND $INVENTORY['conditionid']>0))) {
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
			$query .= "VALUES ('".res($serial)."', $qty, $partid, ";
			if ($conditionid) { $query .= "$conditionid, "; } else { $query .= "NULL, "; }
			if ($status) { $query .= "'".res($status)."', "; } else { $query .= "NULL, "; }
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
			if ($debug) {
				echo $query.'<BR>';
				$inventoryid = 999999;
			} else {
				$result = qdb($query) OR die(qe().'<BR>'.$query);
				$inventoryid = qid();
			}
		}
		return ($inventoryid);
	}
?>
