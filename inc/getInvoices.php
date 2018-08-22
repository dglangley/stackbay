<?php
	function getInvoices($order_number,$order_type) {
		$invoices = array();

		$query = "SELECT *, date_invoiced datetime FROM invoices ";
		$query .= "WHERE order_number = '".res($order_number)."' AND order_type = '".res($order_type)."' AND status <> 'Void'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return ($invoices); }

		while ($r = mysqli_fetch_assoc($result)) {
			$invoices[] = $r;
		}
		return ($invoices);
	}
?>
