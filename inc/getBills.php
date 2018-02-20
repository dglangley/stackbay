<?php
	function getBills($order_number,$order_type) {
		$bills = array();

		$query = "SELECT *, date_created datetime FROM bills ";
		$query .= "WHERE order_number = '".res($order_number)."' AND order_type = '".res($order_type)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return ($bills); }

		while ($r = mysqli_fetch_assoc($result)) {
			$bills[] = $r;
		}
		return ($bills);
	}
?>
