<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';

	$debug = 1;

	$i = 0;
	$query = "SELECT * FROM purchase_orders o WHERE o.created >= '2017-05-01 00:00:00' AND po_number >= 510101; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$po_number = $r['po_number'];

		$query2 = "SELECT p.id partid, i.id, part, heci FROM purchase_items pi, inventory i, parts p ";
		$query2 .= "WHERE po_number = $po_number AND pi.id = i.purchase_item_id AND i.partid = p.id; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$partid = $r2['partid'];
			$inventoryid = $r2['id'];
			$part = $r2['part'].' '.$r2['heci'];

			$query3 = "SELECT * FROM inventory_costs WHERE inventoryid = $inventoryid; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			if (mysqli_num_rows($result3)>0) { continue; }

			$avg_cost = getCost($partid);
			echo '<strong>'.$po_number.' '.$part.'<BR>Average Cost: '.$avg_cost.'</strong><BR>';

			setCost($inventoryid);
			echo "<BR>";
$i++;
if ($i>=2) { exit; }
		}
	}
?>
