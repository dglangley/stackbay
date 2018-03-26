<?php
	function getOrderNumber($item_id, $table = 'repair_items', $field = 'ro_number') {
		$order_number = 0;
		$ln = 0;

		if (! $item_id) { return (''); }

		$query = "SELECT ";
		if ($table=='service_quote_outsourced') { $query .= "'' "; }
		$query .= "line_number, $field AS order_number FROM $table WHERE id = ".res($item_id).";";
		$result = qdb($query) OR die(qe().' '.$query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$ln = $r['line_number'];
			$order_number = $r['order_number'];
		}

		return $order_number.($ln ? '-'.$ln : '');//.'-'.$ln;
	}
?>
