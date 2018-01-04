<?php
	function getInvoice($invoice_no) {
		$total = 0;
		$query = "SELECT freight FROM invoices WHERE invoice_no = '".res($invoice_no)."'; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) {
			return false;
		}
		$r = mysqli_fetch_assoc($result);
		$total += $r['freight'];

		$query = "SELECT * FROM invoice_items WHERE invoice_no = '".res($invoice_no)."'; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$total += ($r['qty']*$r['amount']);
		}

		return ($total);
	}
?>
