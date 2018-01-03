<?php
	function getCredits($order_number,$order_type) {
		$credit = 0;

		$query = "SELECT * FROM credits WHERE order_number = '".res($order_number)."' AND order_type = '".res($order_type)."'; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$query2 = "SELECT * FROM credit_items WHERE cid = '".$r['id']."'; ";
			$result2 = qedb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$credit += $r2['qty']*$r2['amount'];
			}
		}

		return ($credit);
	}
?>
