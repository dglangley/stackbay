<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	// This function gets the current order and type
	// traverses backwards up the ref labels until amount/price on a order list is > 0
	// If it falls into a hole where the there is no more leads and there exists no invoice then just return false
	function getInvoiceNumber($inventoryid, $item_id, $order_type) {
		$invoice_number = 0;
		$order_number = 0;
		$T = order_type($order_type);

		// Get Order Number from item_id
		$query = "SELECT ".$T['order']." as order_number FROM ".$T['items']." WHERE id = ".res($item_id).";";
		$result = qedb($query);

		if(qnum($result)) {
			$r = qrow($result);

			$order_number = $r['order_number'];
		}

		$query = "SELECT i.invoice_no FROM package_contents pc, packages p, invoice_shipments s, invoice_items i ";
		$query .= "WHERE p.id = pc.packageid AND p.order_type = ".fres($T['type'])." AND p.order_number = ".res($order_number)." ";
		$query .= "AND pc.serialid = ".res($inventoryid)." AND s.packageid = p.id AND s.invoice_item_id = i.id;";
		$result = qedb($query);

		if(qnum($result)) {
			$r = qrow($result);

			$invoice_number = $r['invoice_no'];
		}

		return $invoice_number;
	}