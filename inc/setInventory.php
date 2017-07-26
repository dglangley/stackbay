<?php
	if (! isset($debug)) { $debug = 0; }
	function setInventory($ser,$partid,$item_id,$id_field,$status,$stock_date,$qty=false) {
		global $debug;

		$status = prep($status);

		// check new inventory system for existing serial, and if already added during PO's above,
		// just set `sales_item_id` on the existing record
		$query3 = "SELECT id FROM inventory WHERE serial_no = '".res($ser)."' AND partid = $partid; ";
		$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
		if (mysqli_num_rows($result3)>0) {
			$r3 = mysqli_fetch_assoc($result3);
			$serialid = $r3['id'];

			//$query3 = "UPDATE inventory SET sales_item_id = $so_item_id, qty = 0 WHERE id = $serialid; ";
			$query3 = "UPDATE inventory SET $id_field = $item_id, status = $status ";
			if ($qty!==false) { $query3 .= ", qty = $qty "; }
			$query3 .= "WHERE id = $serialid; ";
			if ($debug) { echo $query3.'<BR>'; }
			else { $result3 = qdb($query3) OR die(qe().'<BR>'.$query3); }

			$query3 = "UPDATE inventory_history SET date_changed = '".$stock_date."' ";
			$query3 .= "WHERE invid = $serialid AND field_changed = '".$id_field."' AND value = $item_id; ";
			if ($debug) { echo $query3.'<BR>'; }
			else { $result3 = qdb($query3) OR die(qe().'<BR>'.$query3); }
		} else {
			$query3 = "REPLACE inventory (serial_no, qty, partid, conditionid, status, locationid, ";
			$query3 .= "purchase_item_id, sales_item_id, returns_item_id, userid, date_created, notes) ";
			$query3 .= "VALUES ('".res($ser)."', $qty, $partid, 2, $status, 1, ";
			//$query3 .= "NULL, $so_item_id, NULL, 0, '".$stock_date."', NULL); ";
			if ($id_field=="purchase_item_id") { $query3 .= "$item_id, "; } else { $query3 .= "NULL, "; }
			if ($id_field=="sales_item_id") { $query3 .= "$item_id, "; } else { $query3 .= "NULL, "; }
			if ($id_field=="returns_item_id") { $query3 .= "$item_id, "; } else { $query3 .= "NULL, "; }
			$query3 .= "0, '".$stock_date."', NULL); ";
			if ($debug) { echo $query3.'<BR>'; }
			else { $result3 = qdb($query3) OR die(qe().'<BR>'.$query3); }
			$serialid = qid();

			$query3 = "UPDATE inventory_history SET date_changed = '".$stock_date."' ";
			$query3 .= "WHERE invid = $serialid; ";
			if ($debug) { echo $query3.'<BR>'; }
			else { $result3 = qdb($query3) OR die(qe().'<BR>'.$query3); }
		}
		return ($serialid);
	}
?>
