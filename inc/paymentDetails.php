<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	function getPaymentDetails($data) {
		$bills = array();
		$invoices = array();
		$credits = array();

		$orderedResults = array();

		foreach ($data as $row) {
            // Charges handler for bill or invoice
            $charges = 0;

            // Get information per order number selected
            // If paying a purchase then look into bills
            if($row['type'] == 'Purchase' OR $row['type']=='Outsourced') {
                $query = "SELECT 'Bill' as ref_type, SUM(qty * amount) as total_amount, sales_tax, freight, i.bill_no FROM bills i, bill_items t ";
				$query .= "WHERE i.bill_no = t.bill_no AND i.order_number = '".res($row['order_number'])."' AND i.order_type = '".res($row['type'])."' GROUP BY i.bill_no;";

                $result = qdb($query) OR die(qe().' '.$query);

                if(mysqli_num_rows($result) > 0){
                    while ($rows = mysqli_fetch_assoc($result)) {
                        //$T = order_type('Bill');

                        $query2 = "SELECT * FROM bill_charges WHERE bill_no = ".fres($rows['bill_no'])."; ";
                        $result2 = qedb($query2);

                        while ($r2 = mysqli_fetch_assoc($result2)) {
                            $charges += $r2['qty']*$r2['price'];
                        }

                    	$rows['order_type'] = $row['type'];
                        $rows['total_amount'] += $rows['sales_tax'] + $rows['freight'] + $charges;
                        
                        $orderedResults[$row['type'] .'.'.$row['order_number']][] = $rows;
                    }
                } else {
                    $query = '';
                	// If no bill found then allow user to just pay for the order itself
                    if($row['type']=='Purchase') {
                    	$query = "SELECT *, i.price as amount, 'Purchase' as order_type, i.po_number as invoice_no, 'Purchase' as ref_type, SUM(qty * price) as total_amount FROM purchase_items i WHERE i.po_number = '".res($row['order_number'])."';";
                    } else if($row['type']=='Outsourced') {
                        $query .= "SELECT *, i.price as amount, 'Service' as order_type, i.os_number as invoice_no, 'Service' as ref_type, SUM(qty * price) as total_amount FROM outsourced_items i WHERE i.os_number = '".res($row['order_number'])."';";
                    }

                    $result = qdb($query) OR die(qe().' '.$query);

	                while ($rows = mysqli_fetch_assoc($result)) {
                        $orderedResults[$row['type'] .'.'.$row['order_number']][] = $rows;
                    }
                }

            // If paying a sales then look into invoice and credits
            } else if($row['type'] == 'Sale' OR $row['type']=='Repair' OR $row['type']=='Service') {
                $cust_ref = '';

                if(($row['type'] == 'Sale' OR $row['type']=='Repair')) {
                    $query = "SELECT * FROM credits i, credit_items t WHERE i.id = t.cid AND i.order_number = '".res($row['order_number'])."' AND i.order_type = '".$row['type']."'; ";
                    $result = qedb($query);

                    $rows = array();

                    if(qnum($result)) {

                        while ($r = qrow($result)) {
                            $rows['total_amount'] += $r['amount']; 
                            $rows['invoice_no'] = $r['cid']; 
                        }

                        $rows['ref_type'] = 'Credit';
                        $rows['cust_ref'] = '';
                        $rows['order_type'] = $row['type'];

                        $orderedResults[$row['type'].'.'.$row['order_number']][] = $rows;
                    }
                }

                $query = "SELECT 'Invoice' as ref_type, SUM(qty * amount) as total_amount, sales_tax, freight, i.invoice_no FROM invoices i, invoice_items t WHERE i.invoice_no = t.invoice_no AND i.order_number = '".res($row['order_number'])."' AND i.order_type = '".$row['type']."' GROUP BY i.invoice_no;";
                $result = qdb($query) OR die(qe ().' '.$query);

                if(mysqli_num_rows($result) > 0){

	                while ($rows = mysqli_fetch_assoc($result)) {

                        $query2 = "SELECT * FROM invoice_charges WHERE invoice_no = ".fres($rows['invoice_no'])."; ";
                        $result2 = qedb($query2);

                        while ($r2 = mysqli_fetch_assoc($result2)) {
                            $charges += $r2['qty']*$r2['price'];
                        }

                        $T = order_type($row['type']);
                    
                        // Also query for the cust_ref
                        $query3 = "SELECT cust_ref FROM ".$T['orders']." WHERE ".$T['order']."=".res($row['order_number']).";";
                        $result3 = qedb($query3);

                        if(qnum($result3)) {
                            $r3 = qrow($result3);
                            $cust_ref = $r3['cust_ref'];

                            $rows['cust_ref'] = $cust_ref;
                        }

                        $rows['order_type'] = $row['type'];
                        $rows['total_amount'] += $rows['sales_tax'] + $rows['freight'] + $charges;
	                    $orderedResults[$row['type'] .'.'.$row['order_number']][] = $rows;
	                }
	            } else {
	            	// No invoice found...
	            	$query = "";

	            	if($row['type']=='Repair') {
	            		$query .= "SELECT *, i.price as amount, 'Repair' as order_type, i.ro_number as invoice_no, 'Repair' as ref_type FROM repair_items i WHERE i.ro_number = '".res($row['order_number'])."';";
	            	} else if($row['type']=='Sale') {
	            		$query .= "SELECT *, i.price as amount, 'Sale' as order_type, i.so_number as invoice_no, 'Sale' as ref_type FROM sales_items i WHERE i.so_number = '".res($row['order_number'])."';";
	            	} else {
                        $query .= "SELECT '1' as qty, SUM(i.amount) as amount, 'Service' as order_type, i.so_number as invoice_no, 'Service' as ref_type FROM service_items i WHERE i.so_number = '".res($row['order_number'])."';";
                    }

	                $result = qdb($query) OR die(qe().' '.$query);

	                while ($rows = mysqli_fetch_assoc($result)) {
                        $orderedResults[$row['type'] .'.'.$row['order_number']][] = $rows;
                    }
	            }
            }
        }

		return ($orderedResults);
	}

    // This function grabs the information of the payment based on the passed in paymentid
	function updatePayment($paymentid) {
		$orderedResults = array();

		$query = "SELECT *, p.amount as total FROM payments p, payment_details d WHERE p.id = ".res($paymentid)." AND d.paymentid = p.id;";
		$result = qdb($query) OR die(qe().' '.$query);

		while ($rows = mysqli_fetch_assoc($result)) {
        	$rows['type'] = $rows['order_type'];

        	// Need a better way to pull the original invoice amount (Invoice total based on Invoice Items) without having to run all these queries per order
        	if($rows['type'] == 'Purchase') {
                $query = "SELECT * FROM bills i, bill_items t WHERE i.bill_no = t.bill_no AND i.po_number = '".res($rows['order_number'])."' ";
				if ($rows['ref_number'] AND $rows['ref_type']=='Bill') {
					$query .= "AND i.bill_no = '".$rows['ref_number']."' ";
				}
				$query .= "; ";

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

            } else if($rows['type'] == 'Sale' OR $rows['type']=='Repair' OR $rows['type']=='Service') {
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
                	} else if($rows['type']=='Sale') {
                		$query .= "SELECT * FROM sales_items i WHERE i.so_number = '".res($rows['order_number'])."';";
                	} else {
                        $query .= "SELECT *, amount as price FROM service_items i WHERE i.so_number = '".res($rows['order_number'])."';";
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
