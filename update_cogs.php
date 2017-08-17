<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInvoiceCOGS.php';

	$debug = 1;

/*
	$query = "SELECT sc.*, c.invoice_no FROM sales_cogs sc, commissions c ";
	$query .= "WHERE c.invoice_item_id IS NOT NULL AND c.cogsid = sc.id AND c.commission_rate IS NOT NULL; ";
*/

	$query = "SELECT invoice_no, order_type FROM invoices WHERE date_invoiced >= '2017-05-01 00:00:00' ORDER BY date_invoiced ASC; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$invoice_no = $r['invoice_no'];
		$order_type = $r['order_type'];

		setInvoiceCOGS($invoice_no,$order_type);
	}
?>
