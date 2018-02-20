<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	function getOrder($order_number,$order_type) {
		if (! $order_type) { die("getOrder(): Invalid order, or invalid order type"); }

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
		$query = "SELECT ".$T['orders'].".*, ".$T['datetime']." dt, ";
		if ($T['addressid']) { $query .= $T['addressid']." addressid "; } else { $query .= "'' addressid "; }
		$query .= "FROM ".$T['orders']." ";
		if ($order_type=='Build') { $query .= ", builds b "; }
		$query .= "WHERE ";
		if ($order_type=='Build') { $query .= "b.".$T['order']." = ".$T['orders'].".".$T['order']." AND b.id = "; }
		else { $query .= str_replace('meta',$T['orders'].'.',$T['order'])." = "; }
		$query .= "'".res($order_number)."' ";
		$query .= "; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) { return false; }
		$results = mysqli_fetch_assoc($result);

		// get items and add to subarray inside $results
		$co = array();
		$query = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = '".res($order_number)."' ";
		if ($order_type<>'purchase_request' AND $order_type<>'Demand' AND $order_type<>'Supply') {
			$query .= "ORDER BY IF(ref_1_label='".$T['item_label']."',0,1), IF(ref_2_label='".$T['item_label']."',0,1), line_number ASC, id ASC ";
		}
		$query .= "; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			// place internally-designated items in $co (change orders, or basically just an alternate array)
			// where they will later get inserted immediately below their corresponding id OR at the end
			if ($r['ref_1_label']==$T['item_label']) {
				$co[$r['ref_1']][] = $r;
			} else if ($r['ref_2_label']==$T['item_label']) {
				$co[$r['ref_2']][] = $r;
			} else {
				$items[$r['id']] = $r;
				if (isset($co[$r['id']])) {
					foreach ($co[$r['id']] as $k => $cor) {
						$items[$cor['id']] = $cor;
						unset($co[$r['id']][$k]);
					}
				}
			}
		}
		foreach ($co as $item_id => $next) {
			foreach ($next as $k => $cor) {
				$items[$cor['id']] = $cor;
				unset($co[$item_id][$k]);
			}
		}

		$results['items'] = $items;

		$charges = array();
		if ($T['charges']) {
			$query = "SELECT * FROM ".$T['charges']." WHERE ".$T['order']." = '".res($order_number)."'; ";
			$result = qedb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				$charges[$r['id']] = $r;
			}
		}

		$results['charges'] = $charges;

		return ($results);
	}
?>
