<?php
	function getFreightAmount($order_number,$order_type) {
		$amount = 0;

		$query = "SELECT SUM(freight_amount) amount FROM packages ";
		$query .= "WHERE order_number = '".res($order_number)."' AND order_type = '".res($order_type)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$amount += $r['amount'];
		}

		return ($amount);
	}
?>
