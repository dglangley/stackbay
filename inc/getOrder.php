<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	function getOrder($order_number,$order_type) {
		$T = order_type($order_type);

		$results = array();

		// no order number, we still need the fields from the $order_type table for new orders (see sidebar usage)
		if (! $order_number) {
			$query = "SHOW COLUMNS FROM ".$T['orders'].";";
			$fields = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($fields)==0) {
				return false;
			}
			while ($r = mysqli_fetch_assoc($fields)) {
				$results[$r['Field']] = false;
			}
			return ($results);
		}

		$items = array();
/*
		$alt = array();
		// accommodate invoice lookup by which we get order number and type below
		if ($order_type=='Invoice') {
			$query = "SELECT * FROM ".$T['orders']." WHERE ".$T['order']." = '".res($order_number)."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($result)==0) {
				return false;
			}
			$results = mysqli_fetch_assoc($result);

			$query = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = '".res($order_number)."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($result)==0) {
				return false;
			}
			while ($r = mysqli_fetch_assoc($result)) {
				$items[] = $r;
			}

			// redeclare to get from orders table
			$order_number = $results['order_number'];
			$order_type = $results['order_type'];

			$T = order_type($order_type);
		}
*/

		// get order information
		$query = "SELECT *, ".$T['datetime']." dt, ";
		if ($T['addressid']) { $query .= $T['addressid']." addressid "; } else { $query .= "'' addressid "; }
		$query .= "FROM ".$T['orders']." WHERE ".$T['order']." = '".res($order_number)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return false; }
		$results = mysqli_fetch_assoc($result);
//		$results['bill_to_id'] = $results['addressid'];

/*
		foreach ($alt as $k => $v) {
			$results[$k] = $v;
		}
*/

		// get items and add to subarray inside $results
//		if (count($alt)==0) {
			$query = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = '".res($order_number)."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			while ($r = mysqli_fetch_assoc($result)) {
				$items[$r['id']] = $r;
			}
//		}

		$results['items'] = $items;

		return ($results);
	}
?>
