<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInvoiceCOGS.php';

exit;
	$debug = 1;

	$query = "SELECT invoice_no, order_type FROM invoices ORDER BY invoice_no DESC LIMIT 0,100; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		setInvoiceCOGS($r['invoice_no'],$r['order_type']);
	}
?>
