<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	function getPaymentDetails($data) {
		$bills = array();
		$invoices = array();
		$credits = array();

		$orderedResults = array();

		foreach ($data as $row) {
            // Get information per order number selected
            // If paying a purchase then look into bills
            if($row['type'] == 'Purchase') {
                $query = "SELECT *, 'Bill' as ref_type, SUM(qty * amount) as total_amount FROM bills i, bill_items t WHERE i.bill_no = t.bill_no AND i.po_number = '".res($row['order_number'])."' GROUP BY i.bill_no;";

                $result = qdb($query) OR die(qe().' '.$query);

                if(mysqli_num_rows($result) > 0){
                    while ($rows = mysqli_fetch_assoc($result)) {
                    	$rows['order_type'] = $row['type'];
                        $orderedResults[$row['type'] .'.'.$row['order_number']][] = $rows;
                    }
                } else {
                	// If no bill found then allow user to just pay for the order itself
                	$query = "SELECT *, i.price as amount, 'Purchase' as order_type, i.po_number as invoice_no, 'Purchase' as ref_type, SUM(qty * price) as total_amount FROM purchase_items i WHERE i.po_number = '".res($row['order_number'])."';";
	                $result = qdb($query) OR die(qe().' '.$query);

	                while ($rows = mysqli_fetch_assoc($result)) {
                        $orderedResults[$row['type'] .'.'.$row['order_number']][] = $rows;
                    }
                }

            // If paying a sales then look into invoice and credits
            } else if($row['type'] == 'Sale' OR $row['type']=='Repair') {
                $query = "SELECT *, 'Invoice' as ref_type, SUM(qty * amount) as total_amount FROM invoices i, invoice_items t WHERE i.invoice_no = t.invoice_no AND i.order_number = '".res($row['order_number'])."' AND i.order_type = '".$row['type']."' GROUP BY i.invoice_no;";
                $result = qdb($query) OR die(qe ().' '.$query);

                if(mysqli_num_rows($result) > 0){
	                while ($rows = mysqli_fetch_assoc($result)) {
	                    $orderedResults[$row['type'] .'.'.$row['order_number']][] = $rows;
	                }
	            } else {
	            	// No invoice found...
	            	$query = "";

	            	if($row['type']=='Repair') {
	            		$query .= "SELECT *, i.price as amount, 'Repair' as order_type, i.ro_number as invoice_no, 'Repair' as ref_type FROM repair_items i WHERE i.ro_number = '".res($row['order_number'])."';";
	            	} else {
	            		$query .= "SELECT *, i.price as amount, 'Sale' as order_type, i.so_number as invoice_no, 'Sale' as ref_type FROM sales_items i WHERE i.so_number = '".res($row['order_number'])."';";
	            	}
	                $result = qdb($query) OR die(qe().' '.$query);

	                while ($rows = mysqli_fetch_assoc($result)) {
                        $orderedResults[$row['type'] .'.'.$row['order_number']][] = $rows;
                    }
	            }

                if($row['type'] == 'Sale') {
	                $query = "SELECT * FROM sales_credits i, sales_credit_items t WHERE i.id = t.cid AND i.order_num = '".res($row['order_number'])."' AND i.order_type = '".$row['type']."'; ";
	                $result = qdb($query) OR die(qe().' '.$query);

	                while ($rows = mysqli_fetch_assoc($result)) {
	                	$rows['type'] = 'Credit';
	                    $orderedResults[$row['type'] .'.'.$row['order_number']][] = $rows;
	                }
	            }
            }
        }

		return ($orderedResults);
	}

	function updatePayment($paymentid) {
		$orderedResults = array();

		$query = "SELECT *, p.amount as total FROM payments p, payment_details d WHERE p.id = ".res($paymentid)." AND d.paymentid = p.id;";
		$result = qdb($query) OR die(qe().' '.$query);

		while ($rows = mysqli_fetch_assoc($result)) {
        	$rows['type'] = $rows['order_type'];

        	// Need a better way to pull the original invoice amount (Invoice total based on Invoice Items) without having to run all these queries per order
        	if($rows['type'] == 'Purchase') {
                $query = "SELECT * FROM bills i, bill_items t WHERE i.bill_no = t.bill_no AND i.po_number = '".res($rows['order_number'])."';";

                $inv_result = qdb($query) OR die(qe().' '.$query);
                $inv_total = 0;

                while ($inv_rows = mysqli_fetch_assoc($inv_result)) {
            		$inv_total += $inv_rows['amount'] * $inv_rows['qty'];
                }

                // If no amount found due to no bills
                if(! $inv_total) {
                	$query = "SELECT * FROM purchase_items i WHERE i.po_number = '".res($rows['order_number'])."';";
	                $inv_result = qdb($query) OR die(qe().' '.$query);

	                while ($inv_rows = mysqli_fetch_assoc($inv_result)) {
                    	$inv_total += $inv_rows['price'] * $inv_rows['qty'];
                    }

                    $rows['ref_number'] = $rows['order_number'];
                    $rows['ref_type'] = $rows['type'];
                }

                $rows['invoice_amount'] = $inv_total;

            } else if($rows['type'] == 'Sale' OR $rows['type']=='Repair') {
                $query = "SELECT * FROM invoices i, invoice_items t WHERE i.invoice_no = t.invoice_no AND i.order_number = '".res($rows['order_number'])."' AND i.order_type = '".$rows['type']."';";
                $inv_result = qdb($query) OR die(qe ().' '.$query);

                $inv_total = 0;

                while ($inv_rows = mysqli_fetch_assoc($inv_result)) {
                	$inv_total += $inv_rows['amount'] * $inv_rows['qty'];
                }

                // If no amount found due to no invoice
                if(! $inv_total) {
                	$query = '';
                	if($rows['type']=='Repair') {
                		$query .= "SELECT * FROM repair_items i WHERE i.ro_number = '".res($rows['order_number'])."';";
                	} else {
                		$query .= "SELECT * FROM sales_items i WHERE i.so_number = '".res($rows['order_number'])."';";
                	}
	                $inv_result = qdb($query) OR die(qe().' '.$query);

	                while ($inv_rows = mysqli_fetch_assoc($inv_result)) {
                    	$inv_total += $inv_rows['price'] * $inv_rows['qty'];
                    }

                    $rows['ref_number'] = $rows['order_number'];
                    $rows['ref_type'] = $rows['type'];
                }

                $rows['invoice_amount'] = $inv_total;
            }

            $orderedResults[$rows['type'] .'.'.$rows['order_number']][] = $rows;
        }

		return ($orderedResults);
	}
?>
