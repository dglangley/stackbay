<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

$DEBUG = 1;

	// Get all the PO's
	$query = "SELECT po_number, status FROM purchase_orders WHERE status <> 'Void';";
	$result = qedb($query); 

	while($r = mysqli_fetch_assoc($result)) {
		//Quick Query to check if all the line items of a PO have been met in full
		$query2 = "SELECT po_number FROM purchase_items WHERE po_number = ".res($r['po_number'])." AND qty_received < qty;";
		$result2 = qedb($query2);

		echo $r['po_number'].' '.$r['status'].'<BR>';
		// If there are no results of the received qty being lower than the qty order than the order is complete
		if (mysqli_num_rows($result2) == 0) { $status = 'Complete'; } else { $status = 'Active'; }

		if ($status<>$r['status']) {
			$query3 = "UPDATE purchase_orders SET status = '".$status."' WHERE po_number = ".res($r['po_number']).";";
			qedb($query3);
		}
	}

	// Get all the SO's
	$query = "SELECT so_number, status FROM sales_orders WHERE status <> 'Void';";
	$result = qedb($query); 

	while($r = mysqli_fetch_assoc($result)) {
		//Quick Query to check if all the line items of a SO have been met in full
		$query2 = "SELECT so_number FROM sales_items WHERE so_number = ".res($r['so_number'])." AND qty_shipped < qty;";
		$result2 = qedb($query2);

		echo $r['so_number'].' '.$r['status'].'<BR>';
		// If there are no results of the shipped qty being lower than the qty order than the order is complete
		if (mysqli_num_rows($result2) == 0) { $status = 'Complete'; } else { $status = 'Active'; }

		if ($status<>$r['status']) {
			$query3 = "UPDATE sales_orders SET status = '".$status."' WHERE so_number = ".res($r['so_number']).";";
			qedb($query3);
		}
	}

	// Get all the RO's
	$query = "SELECT ro_number, status FROM repair_orders WHERE status <> 'Void';";
	$result = qedb($query); 

	while($r = mysqli_fetch_assoc($result)) {
		//Quick Query to check if all the line items of a RO have been met in full
		$query2 = "SELECT ro_number FROM repair_items WHERE ro_number = ".res($r['ro_number'])." AND repair_code_id IS NULL;";
		$result2 = qedb($query2);

		echo $r['ro_number'].' '.$r['status'].'<BR>';
		// If there is no repair line items in the order that does not have a null repair code
		if (mysqli_num_rows($result2) == 0) { $status = 'Complete'; } else { $status = 'Active'; }

		if ($status<>$r['status']) {
			$query3 = "UPDATE repair_orders SET status = '".$status."' WHERE ro_number = ".res($r['ro_number']).";";
			qedb($query3);
		}
	}

	// Get all the Service's
	$query = "SELECT so_number, status FROM service_orders WHERE status <> 'Void';";
	$result = qedb($query); 

	while($r = mysqli_fetch_assoc($result)) {
		//Quick Query to check if all the line items of a SO have been met in full
		$query2 = "SELECT so_number FROM service_items WHERE so_number = ".res($r['so_number'])." AND status_code IS NULL;";
		$result2 = qedb($query2);

		echo $r['so_number'].' '.$r['status'].'<BR>';
		// If there is no status line items in the order that does not have a null status code
		if (mysqli_num_rows($result2) == 0) { $status = 'Complete'; } else { $status = 'Active'; }

		if ($status<>$r['status']) {
			$query3 = "UPDATE service_orders SET status = '".$status."' WHERE so_number = ".res($r['so_number']).";";
			qedb($query3);
		}
	}

	// // Get all the Return's
	$query = "SELECT rma_number, status FROM returns WHERE status <> 'Void';";
	$result = qedb($query); 

	while($r = mysqli_fetch_assoc($result)) {
		//Quick Query to check if all the line items of a SO have been met in full
		$query2 = "SELECT rma_number FROM return_items, inventory_history ih WHERE rma_number = ".res($r['rma_number'])." AND ih.field_changed = 'returns_item_id' AND value = return_items.id AND ih.value IS NULL;";
		$result2 = qedb($query2);

		echo $r['rma_number'].' '.$r['status'].'<BR>';
		// If there is no status line items in the order that does not have a null status code
		if (mysqli_num_rows($result2) == 0) { $status = 'Complete'; } else { $status = 'Active'; }

		if ($status<>$r['status']) {
			$query3 = "UPDATE returns SET status = '".$status."' WHERE rma_number = ".res($r['rma_number']).";";
			qedb($query3);
		}
	}

	echo 'COMPLETED';
