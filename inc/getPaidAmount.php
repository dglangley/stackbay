<?php
	function getPaidAmount($invoice_no=0) {
		$paid_amt = 0;
		if (! $invoice_no) { return ($paid_amt); }
		$query2 = "SELECT SUM(amount) amount FROM payment_details ";
		$query2 .= "WHERE (order_number = '".$invoice_no."' AND order_type = 'Invoice') ";
		$query2 .= "OR (ref_number = '".$invoice_no."' AND ref_type = 'invoice'); ";
		$result2 = qedb($query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$paid_amt += $r2['amount'];
		}
		return ($paid_amt);
	}
?>
