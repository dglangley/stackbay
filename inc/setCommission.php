<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCommRate.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCogs.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

/*
	$COMM_REPS = array();
	$query = "SELECT u.id, u.commission_rate FROM users u, contacts c WHERE u.contactid = c.id AND u.commission_rate > 0 AND c.status = 'Active'; ";
	$result = qedb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$COMM_REPS[$r['id']] = $r['commission_rate'];
	}
*/

	function setCommission($invoice,$invoice_item_id=0,$inventoryid=0,$comm_repid=0) {
//		global $COMM_REPS;

		$comm_output = '';

		if ($GLOBALS['DEBUG']==1 OR $GLOBALS['DEBUG']==3) {
			$invoice = 18595;
			$invoice_item_id = 13724;
		}

		// get order# and order type (ie, "110101"/"Sale")
		$query = "SELECT companyid, order_number, order_type FROM invoices WHERE invoice_no = '".res($invoice)."'; ";
		$result = qedb($query);
		if (! $GLOBALS['DEBUG'] AND mysqli_num_rows($result)==0) {
			return false;
		}
		$r = mysqli_fetch_assoc($result);

		$companyid = $r['companyid'];
		$order_number = $r['order_number'];
		$order_type = $r['order_type'];

		$COMM_REPS = getCommRate($companyid,0,$GLOBALS['now'],$GLOBALS['now']);

		// get invoice items data (ie, amount) as it relates to packaged contents so that we can narrow down to serial-level
		// information and calculate commissions based on the cogs of each individual piece that we invoiced
		$query2 = "SELECT ii.id, ii.amount, serialid inventoryid, ii.item_id partid, i.partid inventory_partid ";
		$query2 .= "FROM invoice_items ii, invoice_shipments s, package_contents c, inventory i ";
		$query2 .= "WHERE ii.invoice_no = '".res($invoice)."' ";
		if ($invoice_item_id) { $query2 .= "AND ii.id = '".res($invoice_item_id)."' "; }
		if ($inventoryid) { $query2 .= "AND serialid = '".res($inventoryid)."' "; }
		$query2 .= "AND ii.id = s.invoice_item_id AND s.packageid = c.packageid ";
		$query2 .= "AND c.serialid = i.id ";//AND i.partid = ii.partid ";
		// match partids to weed out duplicate or bogus results in the same package from other records
		$query2 .= "; ";
		$result2 = qedb($query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			// assume we don't have a match on partid, which would exclude us from proceeding below
			$partid_match = false;
			if ($r2['inventory_partid']==$r2['partid']) {
				$partid_match = true;
			} else {
				$query3 = "SELECT value, changed_from FROM inventory_history h WHERE field_changed = 'partid' AND invid = ".$r2['inventoryid'].";";
				$result3 = qedb($query3);
				while ($r3 = mysqli_fetch_assoc($result3)) {
					// either current or past partid is acceptable; so long as it has been this partid at some point
					if ($r3['value']==$r2['partid'] OR $r3['changed_from']==$r2['partid']) { $partid_match = $r2['partid']; }
				}
			}
			if (! $partid_match) { continue; }

			$T = order_type($order_type);

			// based on information from history table (in case most recent fields have changed on inventory table),
			// cross-reference against tables as determined above ("Sale" or "Repair") to get that table's id, and,
			// subsequently, COGS from the records in those tables; the COGS helps us determine profits, and ultimately, commissions
			$items = array();
			if ($r2['taskid'] AND $r2['task_label']) {
				$items[] = array('id'=>$r2['taskid'],'label'=>$r2['task_label']);
			} else {
				$query3 = "SELECT ".$T['items'].".price, ".$T['items'].".id FROM inventory_history h, ".$T['items']." ";
				$query3 .= "WHERE h.invid = '".$r2['inventoryid']."' AND h.value = ".$T['items'].".id AND h.field_changed = '".$T['item_label']."' ";
				$query3 .= "AND ".$T['order']." = '".$order_number."' ";
				$query3 .= "GROUP BY h.invid, h.value; ";
				$result3 = qedb($query3);
				while ($r3 = mysqli_fetch_assoc($result3)) {
					$items[] = array('id'=>$r3['id'],'label'=>$T['item_label']);
				}
			}

			foreach ($items as $item) {
				$taskid = $item['id'];

				/***** COGS *****/
				//$cogs = 0;
				//$cogsid = 0;
				$cogsids = array();
				$query4 = "SELECT cogs_avg, id FROM sales_cogs WHERE inventoryid ";
				if ($r2['inventoryid']) { $query4 .= "= '".$r2['inventoryid']."' "; } else { $query4 .= "IS NULL "; }
				$query4 .= "AND taskid = '".$item['id']."' AND task_label = '".$item['label']."'; ";
				$result4 = qedb($query4);
				if (mysqli_num_rows($result4)==0) {
					// calculate it cuz it's missing, yeah?
					$cogs = getCost($r2['partid']);//get existing avg cost at this point in time
					$cogsid = setCogs($r2['inventoryid'], $taskid, $item['label'], $cogs, 0, $invoice, $r2['id']);

					$cogsids[$cogsid] = $cogs;
				}
				while ($r4 = mysqli_fetch_assoc($result4)) {
					$cogs = $r4['cogs_avg'];
					$cogsid = $r4['id'];

					$cogsids[$cogsid] = $cogs;
				}

				if (! $r2['inventoryid']) { continue; }

				foreach ($COMM_REPS as $rep_id => $rate) {
					// only calculate for selected rep, if passed in
					if (($comm_repid AND $rep_id<>$comm_repid) OR $rep_id==26 OR $rep_id==27) { continue; }

					foreach ($cogsids as $cogsid => $cogs) {
//						if ($GLOBALS['DEBUG']) { $cogs += 25; }

						// this is profit from cogs in each sales_cogs record, and comm due based on that profit
						$profit = $r2['amount']-$cogs;
						$comm_due = ($profit*($rate/100));

						$commissionid = saveCommission($invoice,$r2['id'],$taskid,$item['label'],$cogsid,$rep_id,$comm_due,$rate,$r2['inventoryid']);
					}
				}
			}
		}

		return ($comm_output);
	}

	function saveCommission($invoice_no,$invoice_item_id,$item_id,$item_label,$cogsid,$rep_id,$comm_due,$rate,$inventoryid=0) {

						// determine how much comm has been paid out to date, and if diff than $comm_due above, insert new comm amount
						$sum_comm = 0;
						$query4 = "SELECT commission_amount FROM commissions ";
						$query4 .= "WHERE invoice_no = '".res($invoice_no)."' AND invoice_item_id = '".$invoice_item_id."' ";
						if ($inventoryid) { $query4 .= "AND inventoryid = '".$inventoryid."' "; } else { $query4 .= "AND inventoryid IS NULL "; }
						$query4 .= "AND item_id = '".res($item_id)."' AND item_id_label = '".$item_label."' ";
						if ($cogsid) { $query4 .= "AND cogsid = '".res($cogsid)."' "; } else { $query4 .= "AND cogsid IS NULL "; }
						$query4 .= "AND rep_id = '".res($rep_id)."'; ";
						$result4 = qedb($query4);
						while ($r4 = mysqli_fetch_assoc($result4)) {
							$sum_comm += $r4['commission_amount'];
						}

						$comm_due -= $sum_comm;
						$comm_due = round($comm_due,2);

						if ($comm_due<>0) {
							$query4 = "INSERT INTO commissions (invoice_no, invoice_item_id, inventoryid, item_id, item_id_label, datetime, ";
							$query4 .= "cogsid, rep_id, commission_rate, commission_amount) ";
							$query4 .= "VALUES ('".res($invoice_no)."', '".res($invoice_item_id)."', ".fres($inventoryid).", ";
							$query4 .= "'".res($item_id)."', '".$item_label."', '".$GLOBALS['now']."', ".fres($cogsid).", ";
							$query4 .= "'".res($rep_id)."', '".$rate."', '".$comm_due."'); ";
							$result4 = qedb($query4);
							$commissionid = qid();
							if ($GLOBALS['DEBUG']) {
								$commissionid = 999999;
								$comm_output .= '$'.$comm_due.', '.$commissionid.': '.$query4.'<BR>';
							}
						}
	}
?>
