<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	function setInvoiceCharges($order_number, $invoice_number, $type) {
		// This function checks and sees if the charges on the order has been account for or not
		// If not then put them into the current invoice being generated

		$T = order_type($type);

		$offset_charge = array();

		// $query to get the sales_charges attached to this order
		// Get all similar memos and sum them together a the price and set it accordingly
		$query = "SELECT memo, SUM(price) as price FROM sales_charges WHERE so_number = ".fres($order_number)." GROUP BY memo;";
		$result = qedb($query);

		while($r = qrow($result)) {
			$offset_charge[$r['memo']] = $r['price'];
			// false for not accounted for and true for accounted for
			$sales_charge = false;

			// Get the invoice charges on everything for this order
			$query2 = "SELECT memo, SUM(price) as price FROM invoices i, invoice_charges ic WHERE order_number = ".res($order_number)." AND order_type = ".fres($T['type'])." AND i.invoice_no = ic.invoice_no GROUP BY memo;";
			if($r['memo'] == 'Freight' OR $r['memo'] == 'Sales Tax') {
				// Check the invoice to see if the freight or sales tax record has been accounted for
				$query2 = "SELECT * FROM invoices WHERE order_number = ".res($order_number)." AND order_type = ".fres($T['type']).";";
			}
			$result2 = qedb($query2);

			while($r2 = qrow($result2)) {

				// print_r($r);
				if($r['memo'] == 'Freight') {
					// check to see if any of the records pertain to the exact price amount
					if($r2['freight'] == $r['price']) {
						$sales_charge = true;
					}

					// offset is the total that should be charge minus what has been charged to get the left over
					if($r2['freight']) {
						$offset_charge[$r['memo']] -= $r2['freight'];
					}

				} else if($r['memo'] == 'Sales Tax') {
					// check to see if any of the records pertain to the exact price amount
					if($r2['sales_tax'] == $r['price']) {
						$sales_charge = true;
					}

					if($r2['sales_tax']) {
						$offset_charge[$r['memo']] -= $r2['sales_tax'];
					}
				} else {
					// the query should instead grab all the invoice_charges
					if($r2['memo'] == $r['memo'] AND $r2['price'] == $r['price']) {
						$sales_charge = true;
					}

					if($r2['price']) {
						$offset_charge[$r['memo']] -= $r2['price'];
					}
				}
			}

			// This adds in the sales charge depending what it is from the Memo
			if(! $sales_charge) {
				$query = '';

				if($r['memo'] == 'Freight') {
					$query3 = "UPDATE invoices SET ";
					$query3 .= " freight = " . fres($offset_charge[$r['memo']]);
					$query3 .= " WHERE invoice_no = ".res($invoice_number).";";
				} else if($r['memo'] == 'Sales Tax') {
					$query3 = "UPDATE invoices SET ";
					$query3 .= " sales_tax = " . fres($offset_charge[$r['memo']]);
					$query3 .= " WHERE invoice_no = ".res($invoice_number).";";
				} else {
					$query3 = "INSERT INTO invoice_charges (invoice_no, memo, qty, price) VALUES (".res($invoice_number).", ".fres($r['memo']).", 1, ".fres($offset_charge[$r['memo']]).");";
				}

				qedb($query3);
			}
		}
	}
