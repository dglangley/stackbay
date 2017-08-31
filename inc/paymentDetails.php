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
                $query = "SELECT * FROM bills i, bill_items t WHERE i.bill_no = t.bill_no AND i.po_number = '".res($row['order_number'])."';";

                $result = qdb($query) OR die(qe().' '.$query);

                if(mysqli_num_rows($result) > 0){
                    foreach ($result as $rows) {
                    	$rows['type'] = 'Bill';
                        $orderedResults[$row['order_number']][] = $rows;
                    }
                }

            // If paying a sales then look into invoice and credits
            } else if($row['type'] == 'Sale' OR $row['type']=='Repair') {
                $query = "SELECT * FROM invoices i, invoice_items t WHERE i.invoice_no = t.invoice_no AND i.order_number = '".res($row['order_number'])."' AND i.order_type = '".$row['type']."';";
                $result = qdb($query) OR die(qe ().' '.$query);

                while ($rows = mysqli_fetch_assoc($result)) {
                	$rows['type'] = 'Invoice';
                    $orderedResults[$row['order_number']][] = $rows;
                }

                if($row['type'] == 'Sale') {
	                $query = "SELECT * FROM sales_credits i, sales_credit_items t WHERE i.id = t.cid AND i.order_num = '".res($row['order_number'])."' AND i.order_type = '".$row['type']."'; ";
	                $result = qdb($query) OR die(qe().' '.$query);

	                while ($rows = mysqli_fetch_assoc($result)) {
	                	$rows['type'] = 'Credit';
	                    $orderedResults[$row['order_number']][] = $rows;
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

                $result = qdb($query) OR die(qe().' '.$query);
                $inv_total = 0;

                while ($inv_rows = mysqli_fetch_assoc($inv_result)) {
            		$inv_total += $inv_row['amount'] * $inv_row['qty'];
                }

                $rows['invoice_amount'] = $inv_total;

            } else if($rows['type'] == 'Sale' OR $rows['type']=='Repair') {
                $query = "SELECT * FROM invoices i, invoice_items t WHERE i.invoice_no = t.invoice_no AND i.order_number = '".res($rows['order_number'])."' AND i.order_type = '".$rows['type']."';";
                $inv_result = qdb($query) OR die(qe ().' '.$query);

                $inv_total = 0;

                while ($inv_rows = mysqli_fetch_assoc($inv_result)) {
                	$inv_total += $inv_rows['amount'] * $inv_rows['qty'];
                }

                $rows['invoice_amount'] = $inv_total;
            }

            $orderedResults[$rows['order_number']][] = $rows;
        }

		return ($orderedResults);
	}
?>
