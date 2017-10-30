<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	function getItems($order_type,$order_number=0) {
		$T = order_type($order_type);
		$items = array();

		// no order number, we still need the fields from the $order_type table for new orders (see sidebar usage)
		if (! $order_number) {
			$query = "SHOW COLUMNS FROM ".$T['items'].";";
			$fields = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($fields)==0) {
				return false;
			}
			while ($r = mysqli_fetch_assoc($fields)) {
				$items[$r['Field']] = false;
			}
			return ($items);
		}

		$items = array();
		// get items information
		$query = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = '".res($order_number)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return false; }
		$items = mysqli_fetch_assoc($result);

		return ($items);
	}
?>
