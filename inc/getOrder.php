<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	function getOrder($order_number,$order_type) {
		$T = order_type($order_type);

		$results = array();
		// get order information
		$query = "SELECT * FROM ".$T['orders']." WHERE ".$T['order']." = '".res($order_number)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return ($results); }
		$results = mysqli_fetch_assoc($result);

		// get items and add to subarray inside $results
		$items = array();
		$query = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = '".res($order_number)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$items[$r['id']] = $r;
		}

		$results['items'] = $items;

		return ($results);
	}
?>
