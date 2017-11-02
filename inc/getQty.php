<?php
	$QTYS = array();
	function getQty($partid=0,$itemid=false,$itemlabel=false) {
		global $QTYS;

		$qty = false;

		// if no partid passed in AND no itemid /label passed in, then return false
		if ((! $partid OR ! is_numeric($partid)) AND (! $itemid OR ! $itemlabel)) { return ($qty); }

		if ($partid) {
			if (isset($QTYS[$partid])) { return ($QTYS[$partid]); }

			// initialize global
			$QTYS[$partid] = $qty;
		}

		$query = "SELECT i.qty, i.status, i.conditionid FROM inventory i ";
		if ($itemid AND $itemlabel) {
			$query .= ", inventory_history h ";
		}
		$query .= "WHERE ";
		if ($partid) { $query .= "i.partid = '".$partid."' "; }
		if ($itemid AND $itemlabel) {
			if ($partid) { $query .= "AND "; }

			$query .= "h.value = '".$itemid."' AND h.field_changed = '".$itemlabel."' AND h.invid = i.id ";
			$query .= "GROUP BY h.invid ";
		}
		$query .= "; ";
		//AND conditionid >= 0 AND (status = 'received' OR status = 'manifest'); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)==0) { return ($qty); }
		$qty = 0;//we now know at least one record exists in inventory, so the lowest-qty scenario is '0' now, indicating zero-but-previous-stock
		while ($r = mysqli_fetch_assoc($result)) {
			if (! $itemid AND $r['conditionid']<0) { continue; }// no bad stock
			//if ($r['status']<>'received' AND $r['status']<>'manifest') { continue; }//only stock on the shelf or ready to ship (manifest)
			if (! $itemid AND $r['status']<>'received') { continue; }//only stock on the shelf
			$qty += $r['qty'];
		}

		if ($partid) {
			$QTYS[$partid] = $qty;
		}

		return ($qty);
	}
?>
