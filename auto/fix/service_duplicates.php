<?php
exit;
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$DEBUG = 3;

	$query = "SELECT pi.* FROM purchase_items pi ";
	$query .= "LEFT JOIN maps_PO ON pi.id = purchase_item_id ";
	$query .= "WHERE purchase_item_id IS NULL AND ref_1_label = 'service_item_id' AND receive_date = '2018-01-14'; ";

	$query = "SELECT * FROM inventory WHERE date_created = '2018-01-14 18:48:31' AND notes = 'Services Import'; ";
	$result = qedb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$inventoryid = $r['id'];
		$purchase_item_id = $r['purchase_item_id'];

		$query3 = "DELETE FROM inventory WHERE id = '".$inventoryid."'; ";
		$result3 = qedb($query3);

		$query3 = "DELETE FROM inventory_costs WHERE inventoryid = '".$inventoryid."'; ";
		$result3 = qedb($query3);

		$query3 = "DELETE FROM service_materials WHERE inventoryid = '".$inventoryid."'; ";
		$result3 = qedb($query3);

		$query2 = "SELECT * FROM purchase_items WHERE id = '".$purchase_item_id."'; ";
		$result2 = qedb($query2);
		if (mysqli_num_rows($result2)==0) { die("Could not find purchase id ".$purchase_item_id); }
		$r2 = mysqli_fetch_assoc($result2);
		$po_number = $r2['po_number'];

		// inventory
/*
		$query2 = "SELECT * FROM inventory WHERE purchase_item_id = '".$r['id']."' AND notes = 'Services Import' AND date_created LIKE '2018-01-14 18:4%'; ";
		$result2 = qedb($query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$inventoryid = $r2['id'];

			$query3 = "DELETE FROM inventory WHERE id = '".$inventoryid."'; ";
			$result3 = qedb($query3);

			$query3 = "DELETE FROM inventory_costs WHERE inventoryid = '".$inventoryid."'; ";
			$result3 = qedb($query3);

			$query3 = "DELETE FROM service_materials WHERE inventoryid = '".$inventoryid."'; ";
			$result3 = qedb($query3);
		}
*/

		// purchase requests
		$query2 = "SELECT * FROM purchase_requests WHERE po_number = '".$po_number."' AND notes = 'Services Import' ";
		$query2 .= "AND item_id = '".$r['ref_1']."' AND item_id_label = '".$r['ref_1_label']."'; ";
		$result2 = qedb($query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$query3 = "DELETE FROM purchase_requests WHERE id = '".$r2['id']."'; ";
			$result3 = qedb($query3);
		}

		$query2 = "DELETE FROM purchase_items WHERE po_number = '".$po_number."'; ";
		$result2 = qedb($query2);

		$query2 = "DELETE FROM purchase_orders WHERE po_number = '".$po_number."'; ";
		$result2 = qedb($query2);

		if ($DEBUG) { echo '<BR>'; }
	}
?>
