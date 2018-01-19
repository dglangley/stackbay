<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
//	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';

	$DEBUG = 2;

	$po_number = 0;
	$bdb_po = 0;
	$query = "SELECT BDB_poid, po_number, purchase_item_id FROM maps_PO m, purchase_items pi ";
	$query .= "WHERE purchase_item_id = pi.id; ";
	$result = qedb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		if ($r['BDB_poid']<>$bdb_po) {
			$bdb_po = $r['BDB_poid'];
			$po_number = $r['po_number'];
		}

		echo $r['BDB_poid'].' = '.$r['purchase_item_id'].' '.$r['po_number'].'<BR>';

		if ($po_number==$r['po_number']) {
			echo '<BR>';
			continue;
		}

		$query2 = "UPDATE purchase_items SET po_number = '".$po_number."' WHERE id = '".$r['purchase_item_id']."'; ";
		$result2 = qedb($query2);

		$query2 = "UPDATE bills SET po_number = '".$po_number."' WHERE po_number = '".$r['po_number']."'; ";
		$result2 = qedb($query2);

		$query2 = "UPDATE credits SET order_number = '".$po_number."' WHERE order_number = '".$r['po_number']."' AND order_type = 'Purchase'; ";
		$result2 = qedb($query2);

		$query2 = "UPDATE packages SET order_number = '".$po_number."' WHERE order_number = '".$r['po_number']."' AND order_type = 'Purchase'; ";
		$result2 = qedb($query2);

		$query2 = "UPDATE payment_details SET order_number = '".$po_number."' WHERE order_number = '".$r['po_number']."' AND order_type = 'Purchase'; ";
		$result2 = qedb($query2);

		$query2 = "UPDATE purchase_credits SET po_number = '".$po_number."' WHERE po_number = '".$r['po_number']."'; ";
		$result2 = qedb($query2);

		$query2 = "UPDATE purchase_requests SET po_number = '".$po_number."' WHERE po_number = '".$r['po_number']."'; ";
		$result2 = qedb($query2);

		$query2 = "DELETE FROM purchase_orders WHERE po_number = '".$r['po_number']."'; ";
		$result2 = qedb($query2);

		echo '<BR>';
	}
?>
