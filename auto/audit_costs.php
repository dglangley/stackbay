<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';

	$IDS = array();

	$serial = '';
	$query = "SELECT * FROM inventory WHERE purchase_item_id IS NOT NULL AND serial_no LIKE 'VTL%'; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$serial = $r['serial_no'];

		$cost = 0;
		$query2 = "SELECT SUM(actual) cost FROM inventory_costs WHERE inventoryid = '".$r['id']."'; ";
		$result2 = qdb($query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$cost = $r2['cost'];
		}

		$bdb_cost = 0;
		$query3 = "SELECT cost FROM inventory_itemlocation WHERE serial = '".$serial."'; ";
		$result3 = qdb($query3,'PIPE');
		if (mysqli_num_rows($result3)==0) {
			$query3 = "SELECT cost FROM inventory_solditem WHERE serial = '".$serial."'; ";
			$result3 = qdb($query3,'PIPE');
		}
		if (mysqli_num_rows($result3)>0) {
			$r3 = mysqli_fetch_assoc($result3);
			$bdb_cost = $r3['cost'];
		}

if ($bdb_cost>0 AND $cost<>$bdb_cost) {
	echo $serial.'<BR>';
}
	}
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
echo $serial.' serial with old db inventory id '.$r['inventory_id'].' ($'.$cost.' real cost, $'.$avg.' avg cost)<BR> &nbsp; &nbsp; new part id '.$partid.' ($'.$actual.' real cost, $'.$average.' avg cost)<BR>';
}
	}
?>
