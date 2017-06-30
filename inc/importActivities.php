<?php
	if (! isset($debug)) { $debug = 0; }

	$TECHS = array(
		18 => 14,/*Jr*/
		10 => 15,/*Joe*/
		2 => 17,/*Brian*/
		5 => 13,/*Sabedra*/
		14 => 1,/*Langley*/
		16 => 16,/*Rathna*/
		15 => 18,/*Other tech guy (but now Bleiweiss)*/
	);

	function importActivities($ro_number,$repair_item_id,$inventoryid) {
		global $TECHS;
		$activities = array();

		$query = "SELECT * FROM inventory_repaircheckout WHERE repair_id = '".res($ro_number)."'; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			// this is unit being put into testing from inventory
			updateInventory($inventoryid,'status','in repair',$r['datetime_out'],$TECHS[$r['checkout_by_id']]);

			// this is repair going back out to inventory
			if ($r['datetime_in']) {
				updateInventory($inventoryid,'status','shelved',$r['datetime_in'],$TECHS[$r['checkin_by_id']]);
			}
			//Notes from the checkout/in. Pull this from 
			$activities[$r['datetime_out']][] = array('notes'=>$r['notes'],'techid'=>$TECHS[$r['tech_id']]);
		}

		$query = "SELECT * FROM inventory_repairlog WHERE repair_id = '".res($ro_number)."' AND notes IS NOT NULL AND notes <> ''; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$activities[$r['timestamp']][] = array('notes'=>$r['notes'],'techid'=>$TECHS[$r['tech_id']]);
		}

		$query = "SELECT * FROM inventory_repairtest WHERE repair_id = '".res($ro_number)."'; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			// this is unit being put into testing from inventory
			updateInventory($inventoryid,'status','testing',$r['datetime_out'],$TECHS[$r['checkout_by_id']]);

			// this is repair going back out to inventory
			if ($r['datetime_in']) {
				updateInventory($inventoryid,'status','shelved',$r['datetime_in'],$TECHS[$r['checkin_by_id']]);
			}

			$activities[$r['timestamp']][] = array('notes'=>$r['notes'],'techid'=>$TECHS[$r['tech_id']]);
		}

		ksort($activities);
		foreach ($activities as $datetime => $events) {
			ksort($events);
			foreach ($events as $k => $r) {
				$notes = trim(substr(str_replace(chr(10),' ',trim($r['notes'])),0,255));
				$techid = $r['techid'];

				$activityid = 0;
				$query = "SELECT id FROM repair_activities WHERE ro_number = '".$ro_number."' ";
				if ($repair_item_id) { $query .= "AND repair_item_id = '".$repair_item_id."' "; }
				else { $query .= "AND repair_item_id IS NULL "; }
				$query .= "AND datetime = '".$datetime."' ";
				if ($techid) { $query .= "AND techid = '".$techid."' "; } else { $query .= "AND techid IS NULL "; }
				$query .= "; ";
				$result = qdb($query) OR die(qe().'<BR>'.$query);
				if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$activityid = $r['id'];
				}

				$query = "REPLACE repair_activities (ro_number, repair_item_id, datetime, techid, notes ";
				if ($activityid) { $query .= ", id"; }
				$query .= ") VALUES ('".$ro_number."',";
				if ($repair_item_id) { $query .= "'".$repair_item_id."',"; } else { $query .= "NULL,"; }
				$query .= "'".$datetime."',";
				if ($techid) { $query .= "'".$techid."',"; } else { $query .= "NULL,"; }
				if ($notes) { $query .= "'".res($notes)."'"; } else { $query .= "NULL"; }
				if ($activityid) { $query .= ",'".$activityid."'"; }
				$query .= "); ";
				if (! $GLOBALS['debug']) { $result = qdb($query) OR die(qe().'<BR>'.$query); }
echo $query.'<BR>';
			}
		}
	}

	function updateInventory($inventoryid,$field,$value,$datetime,$userid=0) {
		if (! $userid) {
			$equals = 'IS';
			$user = 'NULL';
		} else {
			$equals = '=';
			$user = "'".$userid."'";
		}

		// check for existing entry in inventory_history for this serial
		$query = "SELECT * FROM inventory_history ";
		$query .= "WHERE date_changed = '".$datetime."' AND userid $equals $user ";
		$query .= "AND invid = '".$inventoryid."' AND field_changed = '".res($field)."' AND value = '".res($value)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			$query = "UPDATE `inventory` SET `".$field."` = '".res($value)."' ";
			$query .= "WHERE `id` = '".$inventoryid."'; ";
			if (! $GLOBALS['debug']) { $result = qdb($query) OR die(qe().'<BR>'.$query); }

			$query = "UPDATE inventory_history SET date_changed = '".res($datetime)."', userid = $user ";
			$query .= "WHERE invid = '".$inventoryid."' AND field_changed = '".$field."' AND value = '".res($value)."'; ";
			if (! $GLOBALS['debug']) { $result = qdb($query) OR die(qe().'<BR>'.$query); }
		}

		return (true);
	}
?>
