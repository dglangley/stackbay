<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	// Get all the PO's
	$query = "SELECT po_number FROM purchase_orders AND status <> 'Void';";
	$result = qedb($query); 

	while($r = mysqli_fetch_assoc($result)) {
		//Quick Query to check if all the line items of a PO have been met in full
		$query2 = "SELECT po_number FROM purchase_items WHERE po_number = ".res($r['po_number'])." AND qty_received < qty;";
		$result2 = qedb($query2);

		if (mysqli_num_rows($result2) == 0) {
			// If there are no results of the received qty being lower than the qty order than the order is complete
			$query3 = "UPDATE purchase_orders SET status = 'Complete' WHERE po_number = ".res($r['po_number']).";";
			qedb($query3);
		} else {
			$query3 = "UPDATE purchase_orders SET status = 'Active' WHERE po_number = ".res($r['po_number']).";";
			qedb($query3);
		}
	}

	// Get all the SO's
	$query = "SELECT so_number FROM sales_orders AND status <> 'Void';";
	$result = qedb($query); 

	while($r = mysqli_fetch_assoc($result)) {
		//Quick Query to check if all the line items of a PO have been met in full
		$query2 = "SELECT so_number FROM sales_items WHERE so_number = ".res($r['so_number'])." AND qty_shipped < qty;";
		$result2 = qedb($query2);

		if (mysqli_num_rows($result2) == 0) {
			// If there are no results of the shipped qty being lower than the qty order than the order is complete
			$query3 = "UPDATE sales_orders SET status = 'Complete' WHERE so_number = ".res($r['so_number']).";";
			qedb($query3);
		} else {
			$query3 = "UPDATE sales_orders SET status = 'Active' WHERE so_number = ".res($r['so_number']).";";
			qedb($query3);
		}
	}

	// Get all the RO's
	$query = "SELECT ro_number FROM repair_orders AND status <> 'Void';";
	$result = qedb($query); 

	while($r = mysqli_fetch_assoc($result)) {
		//Quick Query to check if all the line items of a PO have been met in full
		$query2 = "SELECT ro_number FROM repair_items WHERE ro_number = ".res($r['ro_number'])." AND repair_code_id IS NULL;";
		$result2 = qedb($query2);

		if (mysqli_num_rows($result2) == 0) {
			// If there is no repair line items in the order that does not have a null repair code
			$query3 = "UPDATE repair_orders SET status = 'Complete' WHERE ro_number = ".res($r['ro_number']).";";
			qedb($query3);
		} else {
			$query3 = "UPDATE repair_orders SET status = 'Active' WHERE ro_number = ".res($r['ro_number']).";";
			qedb($query3);
		}
	}

	// Get all the Service's
	$query = "SELECT so_number FROM service_orders AND status <> 'Void';";
	$result = qedb($query); 

	while($r = mysqli_fetch_assoc($result)) {
		//Quick Query to check if all the line items of a PO have been met in full
		$query2 = "SELECT so_number FROM service_items WHERE so_number = ".res($r['so_number'])." AND status_code IS NULL;";
		$result2 = qedb($query2);

		if (mysqli_num_rows($result2) == 0) {
			// If there is no status line items in the order that does not have a null status code
			$query3 = "UPDATE service_orders SET status = 'Complete' WHERE so_number = ".res($r['so_number']).";";
			qedb($query3);
		} else {
			$query3 = "UPDATE service_orders SET status = 'Active' WHERE so_number = ".res($r['so_number']).";";
			qedb($query3);
		}
	}

	echo 'COMPLETED';