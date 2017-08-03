<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getLocation.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';

	$query = "SELECT part, heci, SUM(qty) qty, locationid FROM inventory i, parts p ";
	$query .= "WHERE locationid = 1 AND qty > 0 AND serial_no LIKE 'VTL%' AND i.partid = p.id ";
	$query .= "GROUP BY i.partid; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
//	echo mysqli_num_rows($result).'<BR>';
	while ($r = mysqli_fetch_assoc($result)) {
		$p = explode(' ',$r['part']);
		$part = $p[0];
		$heci = $r['heci'];
		echo '<strong>'.$part.' '.$heci.'</strong><BR/>';

		if ($heci) {
			$query2 = "SELECT id FROM inventory_inventory WHERE clei = '".$heci."'AND quantity_stock > 0 ; ";
		} else {
			$query2 = "SELECT id FROM inventory_inventory WHERE part_number = '".$part."'AND quantity_stock > 0 ; ";
		}
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
		if (mysqli_num_rows($result2)==0) {
			if ($heci) {
				$query2 = "SELECT id FROM inventory_inventory WHERE part_number = '".$part."' AND quantity_stock > 0; ";
				$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
			}
			if (mysqli_num_rows($result2)==0) {
				echo 'COULD NOT FIND IN BDB!<BR>';
				continue;
			}
		}
		$r2 = mysqli_fetch_assoc($result2);
		$inventory_id = $r2['id'];
		echo 'Qty '.$r['qty'].' in '.getLocation($r['locationid']).'; &nbsp; ';

		$query2 = "SELECT *, COUNT(id) qty FROM inventory_itemlocation il WHERE inventory_id = '".$inventory_id."' AND serial = '000' GROUP BY location_id; ";
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
		if (mysqli_num_rows($result2)==0) {
		}
		while ($r2 = mysqli_fetch_assoc($result2)) {
			echo ' &nbsp; Brians qty '.$r2['qty'].' in '.getLocation($r2['location_id']).' ';
		}
		echo '<BR>';
	}
?>
