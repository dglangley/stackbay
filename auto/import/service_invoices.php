<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setPayment.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/companyMap.php';

	$debug = 1;

	$query = "SELECT b.*, c.name company_name FROM services_bill b ";
	$query .= "LEFT JOIN services_company c ON c.id = b.vendor_id ";
	$query .= "WHERE b.date >= '2016-01-01'; ";// AND b.voided = '0'; ";
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
		$query2 .= fres($order_number).",'".res($companyid)."',".fres($notes).",'".res($status)."'); ";
		if ($debug) { echo $query2.'<BR>'; }
		else { $result2 = qdb($query2) OR die(qe().'<BR>'.$query2); }

		$items = array();
		$query2 = "SELECT * FROM services_billli WHERE bill_id = '".res($bill_no)."'; ";
		$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$order_number = $r2['jmpo_id'];
			$order_type = 'Purchase';
			if (! $order_number) {//services_purchaseorder
				$order_number = $r2['po_id']+1000;
				$order_type = 'Purchase';
			}

			$items[] = array(
				'order_number' => $order_number,
				'order_type' => $order_type,
				'ref_number' => '',
				'ref_type' => '',
				'amount' => $r2['amount'],
			);
		}

		if ($r['paid']) {
			setPayment($companyid, $date_created, 'Other', 'Bill'.$bill_no, $r['amount'], 'Imported', false, false, $items);
		}
	}

	$query = "SELECT i.*, c.name company_name FROM services_invoice i ";
	$query .= "LEFT JOIN services_company c ON c.id = i.customer_id ";
	$query .= "WHERE i.date >= '2016-01-01'; ";// AND i.voided = '0'; ";
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

		$query2 = "REPLACE invoices (invoice_no, companyid, date_invoiced, order_number, order_type, shipmentid, freight, public_notes, status) ";
		$query2 .= "VALUES ('".res($invoice_no)."',".fres($companyid).",".fres($date_invoiced).",";
		$query2 .= fres($order_number).",'".res($order_type)."',NULL,NULL,".fres($notes).",'Completed'); ";
		if ($debug) { echo $query2.'<BR>'; }
		else { $result2 = qdb($query2) OR die(qe().'<BR>'.$query2); }

		$items = array();
		$query2 = "SELECT * FROM services_invoiceli WHERE invoice_id = '".res($invoice_no)."'; ";
		$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$order_number = $r2['jobpo_id'];
			$order_type = 'Purchase';
			if (! $order_number) {//services_purchaseorder
				$order_number = $r2['po_id']+1000;
				$order_type = 'Purchase';
			}

			$items[] = array(
				'order_number' => $order_number,
				'order_type' => $order_type,
				'ref_number' => '',
				'memo' => $r2['memo'],
				'amount' => $r2['amount'],
				'qty' => $r2['quantity'],
			);
		}

		if ($r['paid']) {
			setPayment($companyid, $date_created, 'Other', 'Invoice'.$invoice_no, $r['amount'], 'Imported', false, false, $items);
		}
	}
?>
