<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setPayment.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/companyMap.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/mapJob.php';

	$debug = 2;

	function setBillItem($bill_no, $partid, $memo, $qty, $amount, $item_id, $item_id_label, $warranty, $line_number) {
		$debug = $GLOBALS['debug'];

		$memo = utf8_encode(trim($memo));

		$query2 = "REPLACE bill_items (bill_no, partid, memo, qty, amount, item_id, item_id_label, warranty, line_number) ";
		$query2 .= "VALUES ('".res($bill_no)."',".fres($partid).",".fres($memo).",";
		$query2 .= fres(round($qty)).",'".res($amount)."',".fres($item_id).",".fres($item_id_label).",";
		$query2 .= fres($warranty).",".fres($line_number)."); ";
		if ($debug) { echo $query2.'<BR>'; }
		if ($debug<>1) { $result2 = qdb($query2) OR die(qe().'<BR>'.$query2); }
		$bill_item_id = qid();

		return ($bill_item_id);
	}

	$query = "SELECT bill_item_id FROM maps_bill; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$query2 = "SELECT bill_no FROM bill_items WHERE id = '".$r['bill_item_id']."'; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);

			$query3 = "DELETE FROM bills WHERE bill_no = '".$r2['bill_no']."'; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);

			$query3 = "SELECT paymentid FROM payment_details WHERE ref_number = '".$r2['bill_no']."' AND ref_type = 'bill'; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			while ($r3 = mysqli_fetch_assoc($result3)) {
				$query4 = "DELETE FROM payments WHERE id = '".$r3['paymentid']."'; ";
				$result4 = qdb($query4) OR die(qe().'<BR>'.$query4);
			}
			$query3 = "DELETE FROM payment_details WHERE ref_number = '".$r2['bill_no']."' AND ref_type = 'bill'; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
		}
		$query2 = "DELETE FROM bill_items WHERE id = '".$r['bill_item_id']."'; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
	}

	$query = "TRUNCATE maps_bill; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);

	$query = "SELECT b.*, c.name company_name FROM services_bill b ";
	$query .= "LEFT JOIN services_company c ON c.id = b.vendor_id ";
	$query .= "WHERE b.date >= '2016-01-01' ";//AND 1 = 2; ";// AND b.voided = '0'; ";
	$query .= "ORDER BY b.id ASC; ";// LIMIT 0,50; ";
	echo $query.'<BR>';
	$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$bill_no = $r['id'];
		$invoice_no = $r['ref_no'];
		$date_created = $r['date'];
		$due_date = $r['due_date'];
		$notes = trim($r['memo']);
		$companyid = companyMap($r['vendor_id'],$r['company_name']);
		$status = 'Completed';
		if ($r['voided']) { $status = 'Voided'; }

		$query2 = "REPLACE bills (bill_no, invoice_no, date_created, due_date, po_number, companyid, notes, status) ";
		$query2 .= "VALUES ('".res($bill_no)."',".fres($invoice_no).",".fres($date_created).",".fres($due_date).",";
		$query2 .= "NULL,'".res($companyid)."',".fres($notes).",'".res($status)."'); ";
		if ($debug) { echo $query2.'<BR>'; }
		if ($debug<>1) { $result2 = qdb($query2) OR die(qe().'<BR>'.$query2); }

		$rows = array();
		$query2 = "SELECT * FROM services_billli WHERE bill_id = '".res($bill_no)."'; ";
		$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$order_number = 0;
			$order_type = 'Purchase';
			if ($r2['jmpo_id']) {
				$order_number = $r2['jmpo_id'];
			} else if (! $order_number AND $r2['po_id']) {//services_purchaseorder
				$order_number = $r2['po_id']+1000;
			}

			$amount = $r2['qty']*$r2['amount'];

			$bill_item_id = setBillItem($bill_no, false, $r2['memo'], $r2['qty'], $r2['amount'], false, false, false, false);

			$query3 = "REPLACE maps_bill (BDB_billli_id, bill_item_id) VALUES ('".$r2['id']."', '".$bill_item_id."'); ";
			if ($debug) { echo $query3.'<BR>'; }
			if ($debug<>1) { $result3 = qdb($query3) OR die(qe().'<BR>'.$query3); }

			if (! isset($rows[$order_number])) { $rows[$order_number] = 0; }
			$rows[$order_number] += $amount;
		}
		$items = array();
		foreach ($rows as $order_number => $amount) {
			$items[] = array(
				'order_number' => $order_number,
				'order_type' => $order_type,
				'ref_number' => $bill_no,
				'ref_type' => 'bill',
				'amount' => $amount,
			);
		}

		if ($r['paid']) {
			setPayment($companyid, $date_created, 'Other', 'Bill'.$bill_no, $r['amount'], 'Imported', false, false, $items);
		}
	}
	echo '<BR><BR>';

	function setInvoice($invoice_no,$companyid,$date_invoiced,$order_number,$order_type,$shipmentid,$freight,$notes,$status) {
		$debug = $GLOBALS['debug'];

		$query2 = "REPLACE invoices (invoice_no, companyid, date_invoiced, order_number, order_type, shipmentid, freight, public_notes, status) ";
		$query2 .= "VALUES ('".res($invoice_no)."',".fres($companyid).",".fres($date_invoiced).",";
		$query2 .= fres($order_number).",'".res($order_type)."',".fres($shipmentid).",".fres($freight).",".fres($notes).",".fres($status)."); ";
		if ($debug) { echo $query2.'<BR>'; }
		if ($debug<>1) { $result2 = qdb($query2) OR die(qe().'<BR>'.$query2); }

		return true;
	}

	$query = "SELECT invoice_item_id FROM maps_invoice; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$query2 = "SELECT invoice_no FROM invoice_items WHERE id = '".$r['invoice_item_id']."'; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);

			$query3 = "DELETE FROM invoices WHERE invoice_no = '".$r2['invoice_no']."'; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);

			$query3 = "SELECT paymentid FROM payment_details WHERE ref_number = '".$r2['invoice_no']."' AND ref_type = 'invoice'; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			while ($r3 = mysqli_fetch_assoc($result3)) {
				$query4 = "DELETE FROM payments WHERE id = '".$r3['paymentid']."'; ";
				$result4 = qdb($query4) OR die(qe().'<BR>'.$query4);
			}
			$query3 = "DELETE FROM payment_details WHERE ref_number = '".$r2['invoice_no']."' AND ref_type = 'invoice'; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
		}
		$query2 = "DELETE FROM invoice_items WHERE id = '".$r['invoice_item_id']."'; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
	}

	$query = "TRUNCATE maps_invoice; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);

	function setInvoiceItem($invoice_no, $item_id, $memo, $qty, $amount, $line_number, $task_id, $task_label, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $warranty) {
		$debug = $GLOBALS['debug'];

		$memo = utf8_encode(trim($memo));

		$query2 = "REPLACE invoice_items (invoice_no, item_id, item_label, memo, qty, amount, line_number, ";
		$query2 .= "taskid, task_label, ref_1, ref_1_label, ref_2, ref_2_label, warranty) ";
		$query2 .= "VALUES ('".res($invoice_no)."',".fres($item_id).",NULL,".fres($memo).",";
		$query2 .= "'".res(round($qty))."','".res($amount)."',".fres($line_number).",";
		$query2 .= fres($task_id).",".fres($task_label).",";
		$query2 .= fres($ref_1).",".fres($ref_1_label).",";
		$query2 .= fres($ref_2).",".fres($ref_2_label).",";
		$query2 .= fres($warranty)."); ";
		if ($debug) { echo $query2.'<BR>'; }
		if ($debug<>1) { $result2 = qdb($query2) OR die(qe().'<BR>'.$query2); }
		$invoice_item_id = qid();

		return ($invoice_item_id);
	}

	$query = "SELECT i.*, c.name company_name FROM services_invoice i ";
	$query .= "LEFT JOIN services_company c ON c.id = i.customer_id ";
	$query .= "WHERE i.date >= '2016-01-01' ";// AND i.voided = '0'; ";
	$query .= "ORDER BY i.id ASC; ";// LIMIT 0,50; ";
	echo $query.'<BR>';
	$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$invoice_no = $r['id']-10000;//translation
		$date_invoiced = $r['date'];
		$due_date = $r['due_date'];
		$notes = trim($r['memo']);
		$companyid = companyMap($r['customer_id'],$r['company_name']);
		$order_number = '';
		$order_type = 'Service';
		$status = 'Completed';
		if ($r['voided']) { $status = 'Void'; }

		setInvoice($invoice_no,$companyid,$date_invoiced,$order_number,$order_type,false,false,$notes,$status);

		$sum_amount = 0;
		$query2 = "SELECT * FROM services_invoiceli WHERE invoice_id = '".res($r['id'])."'; ";
		$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {

			$service_item_id = 0;
			$ref_1_label = '';
			$query3 = "SELECT * FROM services_jobpoli li, services_jobpo po ";
			$query3 .= "WHERE invoiceli_id = '".$r2['id']."' AND li.po_id = po.id; ";
			$result3 = qdb($query3,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query3);
			if (mysqli_num_rows($result3)>0) {
				$r3 = mysqli_fetch_assoc($result3);
				$service_item_id = mapJob($r3['job_id']);
				$ref_1_label = 'service_item_id';
			}

			$invoice_item_id = setInvoiceItem($invoice_no, false, $r2['memo'], $r2['quantity'], $r2['amount'], false, $service_item_id, $ref_1_label, false, false, false, false, false);

			$query3 = "REPLACE maps_invoice (BDB_invoiceli_id, invoice_item_id) VALUES ('".$r2['id']."', '".$invoice_item_id."'); ";
			if ($debug) { echo $query3.'<BR>'; }
			if ($debug<>1) { $result3 = qdb($query3) OR die(qe().'<BR>'.$query3); }

			$sum_amount += $r2['quantity']*$r2['amount'];
		}
		$items = array(
			0 => array(
				'order_number' => false,
				'order_type' => false,
				'ref_number' => $invoice_no,
				'ref_type' => 'invoice',
				'amount' => $sum_amount,
			)
		);

		if ($r['paid']) {
			setPayment($companyid, $date_created, 'Other', 'Invoice'.$invoice_no, $r['amount'], 'Imported', false, false, $items);
		}
	}
?>
