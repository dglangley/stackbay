<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getManf.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSys.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setPart.php';

	$query = "SELECT category_id_id, part_number part, heci, clei, short_description description, ";
	$query .= "inventory_manufacturer.name manf, system_name system, inventory_id, ";
	$query .= "COUNT(inventory_itemlocation.id) AS qty, previous_export_qty ";
	$query .= "FROM inventory_itemlocation, inventory_location, inventory_inventory, inventory_manufacturer ";
	$query .= "WHERE no_sales = '0' AND inventory_inventory.id = inventory_itemlocation.inventory_id ";
	$query .= "AND inventory_itemlocation.location_id = inventory_location.id AND category_id_id = '2' ";
	$query .= "AND inventory_inventory.manufacturer_id_id = inventory_manufacturer.id ";
	$query .= "GROUP BY inventory_id ORDER BY part_number ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$partarr = explode(' ',$r['part']);
		$part = trim($partarr[0]);
		$heci = $r['clei'];
		$heci7 = $r['heci'];
		if (is_numeric($heci) OR preg_match('/[^[:alnum:]]+/',$heci)) {
			$heci = '';
		} else if (strlen($heci7)==7 AND ! $heci) {
			$heci = $heci7;
		}
		if (strlen($heci)==7) { $heci = $heci7.'VTL'; }

		$partid = getPartId($part,$heci);

		if ($partid) { continue; }

		if (! $partid) {
			$manf = strtoupper(trim($r['manf']));
			$manfid = getManf($manf);
			if (! $manfid) {
				$query2 = "SELECT id FROM manfs WHERE name LIKE '".res($manf)."%'; ";
				$result2 = qdb($query2) OR die(qe().' '.$query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$manfid = $r2['id'];
				}
			}
			$descr = strtoupper(trim($r['description']));
			$sysid = 0;
			$system = strtoupper(trim($r['system']));
			if ($system) {
				$sysid = getSys($system);

				$query2 = "SELECT id FROM systems WHERE system LIKE '".res($sys)."%'; ";
				$result2 = qdb($query2) OR die(qe().' '.$query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$sysid = $r2['id'];
				} else {
					$descr = trim($system.' '.$descr);
				}
			}
			$descr = str_replace('*',' ',$descr);

			$partid = setPart(array('part'=>$part,'heci'=>$heci,'manfid'=>$manfid,'sysid'=>$sysid,'descr'=>$descr));
		}

		// build BB, PS, TE and other inventories here; see /var/www/venteldb/inventory/util.py and search for "BrokerBin", etc
	}
?>
