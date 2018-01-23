<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
//	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
exit;

	$DEBUG = 1;

	$po_number = 0;
	$bdb_po = 0;
	$query = "SELECT BDB_poid, po_number, purchase_item_id FROM maps_PO m, purchase_items pi ";
	$query .= "WHERE purchase_item_id = pi.id ";
	$query .= "ORDER BY BDB_poid ASC, po_number ASC ";
	$query .= "; ";
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

		$query2 = "SELECT amount, paymentid FROM payment_details WHERE order_number = '".$r['po_number']."' AND order_type = 'Purchase'; ";
		$result2 = qedb($query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$amount = $r2['amount'];

			$query3 = "SELECT SUM(amount) amount FROM payment_details WHERE order_number = '".$po_number."' AND order_type = 'Purchase' AND paymentid = '".$r2['paymentid']."'; ";
			$result3 = qedb($query3);
			if (mysqli_num_rows($result3)>0) {
				$r3 = mysqli_fetch_assoc($result3);
				$amount += $r3['amount'];

				$query3 = "UPDATE payment_details SET amount = '".$amount."' WHERE order_number = '".$po_number."' AND order_type = 'Purchase' AND paymentid = '".$r2['paymentid']."'; ";
				$result3 = qedb($query3);

				$query3 = "DELETE FROM payment_details WHERE order_number = '".$r['po_number']."' AND order_type = 'Purchase' AND paymentid = '".$r2['paymentid']."'; ";
				$result3 = qedb($query3);
			} else {
				$query3 = "UPDATE payment_details SET order_number = '".$po_number."' WHERE order_number = '".$r['po_number']."' AND order_type = 'Purchase' AND paymentid = '".$r2['paymentid']."'; ";
				$result3 = qedb($query3);
			}
		}

		$query2 = "UPDATE purchase_credits SET po_number = '".$po_number."' WHERE po_number = '".$r['po_number']."'; ";
		$result2 = qedb($query2);

		$query2 = "UPDATE purchase_requests SET po_number = '".$po_number."' WHERE po_number = '".$r['po_number']."'; ";
		$result2 = qedb($query2);

		$query2 = "DELETE FROM purchase_orders WHERE po_number = '".$r['po_number']."'; ";
		$result2 = qedb($query2);

		echo '<BR>';
	}
?>
