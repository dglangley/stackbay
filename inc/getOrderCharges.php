<?php
	// include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	// Get the total order cost
	function getOrderCharges($order_number, $T) {
		$charges = 0;

		// Query to sum up the item level qty and amount attached to the order
		// Service Orders uses service_bom
		$query = "SELECT * FROM ".$T['items']." i WHERE i.".$T['order']." = ".res($order_number).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$charges += $r[$T['amount']] * $r[$T['qty']];

			// If it is a service item then also add in the service bom charges
			if($T['type'] == 'Service') {
				$query2 = "SELECT * FROM service_bom WHERE item_id_label = '".$T['item_label']."' AND item_id = ".$r['id'].";";
				$result2 = qedb($query2);

				while($r2 = mysqli_fetch_assoc($result2)) {
					$charges += $r['charge'];
				}
			}
		}

		// Also Check charges made against the order
		if ($T['charges']) {
			$query = "SELECT * FROM ".$T['charges']." WHERE ".$T['order']." = ".res($order_number)."; ";
			$result = qedb($query);

			while ($r = mysqli_fetch_assoc($result)) {
				$charges += $r['qty']*$r['price'];
			}

		}

		return $charges;
	}

	// Get all payments  credits made against this order
	function getOrderCredits($order_number, $T) {
		$credits = 0;

		// Check the credits found per line item to see if any exists
		$query = "SELECT * FROM ".$T['items']." i WHERE i.".$T['order']." = ".res($order_number).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$credits += getCredit($T['type'], $r['id'], $T['item_label']);
		}

		// Check and add up all the payments made on this order
		$query = "SELECT * FROM payment_details WHERE order_type = '".$T['type']."' AND order_number = ".res($order_number).";";
		$result = qedb($query);

		while ($r = mysqli_fetch_assoc($result)) {
			$credits += $r['amount'];
		}

		return $credits;
	}

	function getCredit($order_type, $item_id, $item_label) {
		$credit_total = 0;
		
		if ($order_type=='Sale' OR $order_type=='Repair') {
			$query = "SELECT SUM(qty*amount) total FROM credit_items WHERE item_id = ".fres($item_id)." AND item_id_label = '".res($item_label)."'; ";
			$result = qedb($query);

			while ($r = mysqli_fetch_assoc($result)) {
				$credit_total += $r['total'];
			}

		} else if($order_type == 'Purchase') {
            // david's purchase credits hack for now; updated 7-21-17 now that we have purchase_credits, we need to adopt above method (under 'sales')
			// but we first need to implement a mechanism that generates credits from the RTV process...
            $query = "SELECT p.price, (s.qty*p.price) total FROM purchase_items p, sales_items s ";
			$query .= "WHERE po_number = ".fres($order['order_num'])." AND s.ref_1 = p.id AND s.ref_1_label = '".$item_label."'; ";
            $result = qedb($query);

            while ($r = mysqli_fetch_assoc($result)) {
                $credit_total += $r['total'];
            }

			$query = "SELECT SUM(qty*amount) total FROM purchase_credit_items WHERE purchase_item_id = ".fres($item_id)."; ";
			$result = qedb($query);

			while ($r = mysqli_fetch_assoc($result)) {
				$credit_total += $r['total'];
			}
        }

        return $credit_total;
	}
?>
