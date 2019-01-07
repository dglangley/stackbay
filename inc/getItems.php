<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	function getItems($order_type,$order_number=0) {
		$T = order_type($order_type);
		$items = array();

		// no order number, we still need the fields from the $order_type table for new orders (see sidebar usage)
		if (! $order_number) {
			$query = "SHOW COLUMNS FROM ".$T['items'].";";
			$fields = qedb($query);
			if (qnum($fields)==0) {
				return false;
			}
			while ($r = qrow($fields)) {
				$items[$r['Field']] = false;
			}
			return ($items);
		}

		$items = array();
		// get items information
		$query = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = '".res($order_number)."'; ";
		$result = qedb($query);
		if (qnum($result)==0) { return false; }
		$items = qrow($result);

		return ($items);
	}
?>
