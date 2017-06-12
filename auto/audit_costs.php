<?php
	set_time_limit(0);
	ini_set('memory_limit', '2000M');

$debug = 0;
	$root_dir = $_SERVER["ROOT_DIR"];
	include_once $root_dir.'/inc/dbconnect.php';
	include_once $root_dir.'/inc/pipe.php';
	include_once $root_dir.'/inc/getPartId.php';
	include_once $root_dir.'/inc/mergeInventories.php';

	$IDS = array();

	$serial = '';
	$query = "SELECT * FROM inventory; ";// WHERE sales_item_id IS NULL AND (purchase_item_id IS NULL OR serial_no LIKE 'VTL%'); ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$serial = $r['serial_no'];
		$inventoryid = $r['id'];

		$cost = 0;
		//$query2 = "SELECT SUM(actual) cost FROM inventory_costs WHERE inventoryid = '".$r['id']."'; ";
		$query2 = "SELECT average cost FROM inventory_costs WHERE inventoryid = '".$r['id']."'; ";
		$result2 = qdb($query2);
		$num_costs = mysqli_num_rows($result2);
		if ($num_costs>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$cost = $r2['cost'];
		}

		$bdb_cost = 0;
		$po = '';
		$repair = '';
/*
		$query3 = "SELECT cost, po_id po, '' repair, '' repair_id FROM inventory_itemlocation WHERE serial = '".$serial."'; ";
		$result3 = qdb($query3,'PIPE');
		if (mysqli_num_rows($result3)==0) {
			$query3 = "SELECT cost, po, repair, repair_id FROM inventory_solditem WHERE serial = '".$serial."'; ";
			$result3 = qdb($query3,'PIPE');
			if (mysqli_num_rows($result3)==0) {
				continue;//must not need it, or let's face it, at this point, do we care??
			}
		}
*/
		$query3 = "SELECT avg_cost cost, po, repair, repair_id FROM inventory_solditem WHERE serial = '".$serial."' ORDER BY id DESC LIMIT 0,1; ";
		$result3 = qdb($query3,'PIPE') OR die(qe('PIPE').'<BR>'.$query3);
		if (mysqli_num_rows($result3)==0) {
			continue;//must not need it, or let's face it, at this point, do we care??
		}
		$r3 = mysqli_fetch_assoc($result3);
		$bdb_cost = $r3['cost'];
		if ($r3['po']) { $po = $r3['po']; }
		if ($r3['repair']) { $repair = $r3['repair']; }
		else if ($r3['repair_id']) { $repair = $r3['repair_id']; }

		if ($bdb_cost>0 AND $cost<>$bdb_cost) {
			echo $serial.' (id '.$r['id'].'), BDB $'.$bdb_cost.', Ours $'.$cost.'<BR>'.chr(10);

			// if just one inventory record for this serial, and just one costs record, update the average cost
			if ($num_costs==1) {
				$query2 = "SELECT id FROM inventory WHERE serial_no = '".res($serial)."'; ";
				$result2 = qdb($query2) or die(qe().'<BR>'.chr(10).$query2);
				if (mysqli_num_rows($result2)==1) {
					$query3 = "UPDATE inventory_costs SET average = '".$bdb_cost."' WHERE inventoryid = '".$inventoryid."'; ";
					if (! $debug) { $result3 = qdb($query3) OR die(qe().'<BR>'.chr(10).$query3); }
echo $query3.'<BR>'.chr(10);
				}
			}

continue;
			mergeInventories($serial);

/*
        $query3 = "INSERT INTO inventory_costs (inventoryid, datetime, actual) ";
        $query3 .= "VALUES ('".$r['id']."','".$now."','".$bdb_cost."'); ";
echo $query3.'<BR>'.chr(10);
        $result3 = qdb($query3) OR die(qe().'<BR>'.$query3);

        $query3 = "INSERT INTO inventory_costs_log (inventoryid, eventid, event_type, datetime, amount) ";
        $query3 .= "VALUES ('".$r['id']."','".$repair."','repair_id','".$now."','".$bdb_cost."'); ";
echo $query3.'<BR>'.chr(10);
        $result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
*/
		}
	}
echo 'Finished!'.chr(10);
exit;

	$query = "SELECT serial, clei, heci, part_number, cost, inventory_id, item.avg_cost ";
	$query .= "FROM inventory_solditem item, inventory_inventory i ";
	$query .= "WHERE serial IS NOT NULL AND serial <> '' AND serial <> '000' ";
$query .= "AND so_id IS NOT NULL ";
	$query .= "AND inventory_id = i.id; ";
echo $query.'<BR>';
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$serial = strtoupper(trim($r['serial']));

		if ($r['clei']) { $r['heci'] = $r['clei']; }
		else if (strlen($r['heci'])<>7 OR is_numeric($r['heci']) OR preg_match('/[^[:alnum:]]+/',$r['heci'])) { $r['heci'] = ''; }
		else { $r['heci'] .= 'VTL'; }//append fake ending to make the 7-digit a 10-digit string

		if (isset($IDS[$r['part_number'].'.'.$r['heci']])) {
			$partid = $IDS[$r['part_number'].'.'.$r['heci']];
		} else {
			$partid = getPartId($r['part_number'],$r['heci']);
			$IDS[$r['part_number'].'.'.$r['heci']] = $partid;
		}

		$cost = $r['cost'];
		$avg = $r['avg_cost'];
		$actual = 0;
		$average = 0;
		$cost_match = false;
		$query2 = "SELECT * FROM inventory i, inventory_costs c ";
		$query2 .= "WHERE serial_no = '".$serial."' AND inventoryid = i.id; ";
//		echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$actual = $r2['actual'];
			$average = $r2['average'];

			if (round($actual,2)==round($cost,2) AND round($average,2)==round($avg,2)) {
				$cost_match = true;
				break;
			}
		}
if ($cost_match===false) {
echo $serial.' serial with old db inventory id '.$r['inventory_id'].' ($'.$cost.' real cost, $'.$avg.' avg cost)<BR> &nbsp; &nbsp; new part id '.$partid.' ($'.$actual.' real cost, $'.$average.' avg cost)<BR>'.chr(10);
}
	}
?>
