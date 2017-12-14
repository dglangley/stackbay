<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	function getOrder($order_number,$order_type) {
		if (! $order_type) { die("Invalid order, or invalid order type"); }

		$T = order_type($order_type);

		$results = array();

		// no order number, we still need the fields from the $order_type table for new orders (see sidebar usage)
		if (! $order_number) {
			$query = "SHOW COLUMNS FROM ".$T['orders'].";";
			$fields = qedb($query);
			if (mysqli_num_rows($fields)==0) {
				return false;
			}
			while ($r = mysqli_fetch_assoc($fields)) {
				$results[$r['Field']] = false;
			}
			return ($results);
		}

		$items = array();

		// get order information
		$query = "SELECT *, ".$T['datetime']." dt, ";
		if ($T['addressid']) { $query .= $T['addressid']." addressid "; } else { $query .= "'' addressid "; }
		$query .= "FROM ".$T['orders']." WHERE ".$T['order']." = '".res($order_number)."'; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) { return false; }
		$results = mysqli_fetch_assoc($result);

		// get items and add to subarray inside $results
		$query = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = '".res($order_number)."' ";
		if ($order_type<>'purchase_request') { $query .= "ORDER BY line_number ASC, id ASC "; }
		$query .= "; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$items[$r['id']] = $r;
		}

		$results['items'] = $items;

		return ($results);
	}
?>
