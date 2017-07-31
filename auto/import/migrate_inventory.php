<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';

	$results = array();//contains all parts data that will be consolidated between db's for export
	$parts = array();

	$query = "SELECT category_id_id, part_number part, clean_part_number, heci, clei, short_description description, ";
	$query .= "inventory_manufacturer.name manf, system_name system, inventory_id, ";
	$query .= "COUNT(inventory_itemlocation.id) AS qty, previous_export_qty visible_qty ";
	$query .= "FROM inventory_itemlocation, inventory_location, inventory_inventory, inventory_manufacturer ";
	$query .= "WHERE no_sales = '0' AND inventory_inventory.id = inventory_itemlocation.inventory_id ";
	$query .= "AND inventory_itemlocation.location_id = inventory_location.id AND category_id_id = '2' ";
	$query .= "AND inventory_inventory.manufacturer_id_id = inventory_manufacturer.id ";
//	$query .= "AND part_number LIKE '437420600%' ";
	$query .= "GROUP BY inventory_id ORDER BY part_number ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$part = $r['part'];

		if ($r['clei']) { $heci = $r['clei']; }
		else if (strlen($r['heci'])<>7 OR is_numeric($r['heci']) OR preg_match('/[^[:alnum:]]+/',$r['heci'])) { $heci = ''; }
		else { $heci .= 'VTL'; }//append fake ending to make the 7-digit a 10-digit string

		$partid = getPartId($part,$heci);
		$parts[$partid] = array('part'=>$part,'heci'=>$heci);

		if (! isset($results[$partid])) { $results[$partid] = 0; }

		$results[$partid] += $r['visible_qty'];
	}

	foreach ($results as $partid => $visible_qty) {
		$stk_qty = getQty($partid);
		if ($stk_qty<>$visible_qty) {
			echo '<strong>qty '.$visible_qty.'- (stock: '.$stk_qty.') &nbsp; '.$parts[$partid]['part'].' '.$parts[$partid]['heci'].'</strong><BR>';
		} else {
			echo 'qty '.$visible_qty.'- (stock: '.$stk_qty.') &nbsp; '.$parts[$partid]['part'].' '.$parts[$partid]['heci'].'<BR>';
		}

		$hidden_qty = false;
		if ($stk_qty<$visible_qty) {
			$visible_qty = $stk_qty;
		}

		// hide all qtys indefinitely
		if ($visible_qty==0 AND $stk_qty>0) {
			$hidden_qty = 0;
		} else if ($visible_qty>0 AND $stk_qty>$visible_qty) {
			$hidden_qty = ($stk_qty-$visible_qty);
		}

		$query = "REPLACE qtys (partid, qty, hidden_qty, visible_qty) ";
		$query .= "VALUES ('".res($partid)."','".res($stk_qty)."',";
		if ($hidden_qty===false) { $query .= "NULL,"; } else { $query .= "'".res($hidden_qty)."',"; }
		$query .= "'".res($visible_qty)."'); ";
echo $query.'<BR>';
//		$result = qdb($query) OR die(qe().'<BR>'.$query);
	}
?>
