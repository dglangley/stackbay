<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCogs.php';

	$COMM_REPS = array();
	$query = "SELECT u.id, u.commission_rate FROM users u, contacts c WHERE u.contactid = c.id AND u.commission_rate > 0; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$COMM_REPS[$r['id']] = $r['commission_rate'];
	}

	if (! isset($debug)) { $debug = 0; }

	function setCommission($invoice,$invoice_item_id=0,$inventoryid=0,$comm_repid=0) {
		global $COMM_REPS;

		$comm_output = '';

		// get order# and order type (ie, "110101"/"Sale")
		$query = "SELECT order_number, order_type FROM invoices WHERE invoice_no = '".res($invoice)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			return false;
		}
		$r = mysqli_fetch_assoc($result);
		$order_number = $r['order_number'];
		$order_type = $r['order_type'];

		// try to gather all partids for this inventory record from history so that we can match in shipments below
/*
		$csv_partid = '';
		if ($inventoryid) {
			$partids = array();
			$query2 = "SELECT value, changed_from FROM inventory_history h WHERE field_changed = 'partid' AND invid = $inventoryid;";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				if ($r2['value']) {
					$partids[$r2['value']] = $r2['value'];
				}
				if ($r2['changed_from']) {
					$partids[$r2['changed_from']] = $r2['changed_from'];
				}
			}
			foreach($partids as $pid) {
				if ($csv_partid) { $csv_partid .= ','; }
				$csv_partid .= $pid;
			}
		}
*/

		// get invoice items data (ie, amount) as it relates to packaged contents so that we can narrow down to serial-level
		// information and calculate commissions based on the cogs of each individual piece that we invoiced
		$query2 = "SELECT ii.id, ii.amount, serialid inventoryid, ii.partid, i.partid inventory_partid ";
		$query2 .= "FROM invoice_items ii, invoice_shipments s, package_contents c, inventory i ";
		$query2 .= "WHERE ii.invoice_no = '".res($invoice)."' ";
		if ($invoice_item_id) { $query2 .= "AND ii.id = '".res($invoice_item_id)."' "; }
		if ($inventoryid) { $query2 .= "AND serialid = '".res($inventoryid)."' "; }
		$query2 .= "AND ii.id = s.invoice_item_id AND s.packageid = c.packageid ";
		$query2 .= "AND c.serialid = i.id ";//AND i.partid = ii.partid ";
		// match partids to weed out duplicate or bogus results in the same package from other records
//changed method 7/14/17 as soon as I made it, so that we can catch ALL occasions, not just when $inventoryid is passed in (see commented query above)
//		if ($csv_partid) { $query2 .= "AND ii.partid IN (".$csv_partid.") "; }
		$query2 .= "; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			// assume we don't have a match on partid, which would exclude us from proceeding below
			$partid_match = false;
			if ($r2['inventory_partid']==$r2['partid']) {
				$partid_match = true;
			} else {
				$query3 = "SELECT value, changed_from FROM inventory_history h WHERE field_changed = 'partid' AND invid = ".$r2['inventoryid'].";";
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
				while ($r3 = mysqli_fetch_assoc($result3)) {
					// either current or past partid is acceptable; so long as it has been this partid at some point
					if ($r3['value']==$r2['partid'] OR $r3['changed_from']==$r2['partid']) { $partid_match = $r2['partid']; }
				}
			}
			if (! $partid_match) { continue; }


			switch ($order_type) {
				case 'Sale':
					$id_field = 'sales_item_id';
					$id_table = 'sales_items';
					$order_field = 'so_number';
					break;

				case 'Repair':
					$id_field = 'repair_item_id';
					$id_table = 'repair_items';
					$order_field = 'ro_number';
					break;

				default:
					break;
			}

			// based on information from history table (in case most recent fields have changed on inventory table),
			// cross-reference against tables as determined above ("Sale" or "Repair") to get that table's id, and,
			// subsequently, COGS from the records in those tables; the COGS helps us determine profits, and ultimately, commissions
			$query3 = "SELECT $id_table.price, $id_table.id FROM inventory_history h, $id_table ";
			$query3 .= "WHERE h.invid = '".$r2['inventoryid']."' AND h.value = $id_table.id AND h.field_changed = '".$id_field."' ";
			$query3 .= "AND $order_field = '".$order_number."' ";
			$query3 .= "GROUP BY h.invid, h.value; ";
			$result3 = qdb($query3) OR die(qe()."<BR>".$query3);
			while ($r3 = mysqli_fetch_assoc($result3)) {
				$item_id = $r3['id'];
				$item_id_label = $id_field;

				/***** COGS *****/
				$cogs = 0;
				$cogsid = 0;
				$query4 = "SELECT cogs_avg, id FROM sales_cogs WHERE inventoryid = '".$r2['inventoryid']."' ";
				$query4 .= "AND item_id = '".$r3['id']."' AND item_id_label = '".$id_field."'; ";
				if ($GLOBALS['debug']) { $comm_output .= $query4.' <br/>'.chr(10); }
				$result4 = qdb($query4) OR die(qe().'<BR>'.$query4);
				if (mysqli_num_rows($result4)>0) {
					$r4 = mysqli_fetch_assoc($result4);
					$cogs = $r4['cogs_avg'];
					$cogsid = $r4['id'];
				} else {
					// calculate it cuz it's missing, yeah?
					$cogs = getCost($r2['partid']);//get existing avg cost at this point in time
					$cogsid = setCogs($r2['inventoryid'], $item_id, $item_id_label, $cogs);
				}

				$q_id = "NULL";
				if ($r2['inventoryid']) { $q_id = $r2['inventoryid']; }

				foreach ($COMM_REPS as $rep_id => $rate) {
					// only calculate for selected rep, if passed in
					if ($comm_repid AND $rep_id<>$comm_repid) { continue; }

					$profit = $r2['amount']-$cogs;
					$comm = ($profit*($rate/100));

					$query4 = "INSERT INTO commissions (invoice_no, invoice_item_id, inventoryid, item_id, item_id_label, datetime, ";
					$query4 .= "cogsid, rep_id, commission_rate, commission_amount) ";
					$query4 .= "VALUES ($invoice, '".$r2['id']."', $q_id, $item_id, '".$item_id_label."', '".$GLOBALS['now']."', ";
					if ($cogsid) { $query4 .= "$cogsid, "; } else { $query4 .= "NULL, "; }
					$query4 .= "$rep_id, '".$rate."', '".$comm."'); ";
					$result4 = qdb($query4) OR die(qe().'<BR>'.$query4);
					$commissionid = qid();
					if ($GLOBALS['debug']) { $comm_output .= $commissionid.': '.$query4.'<BR>'; }
				}
			}
		}

		return ($comm_output);
	}
?>
