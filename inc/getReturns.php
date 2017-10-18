<?php
	function getReturns($order_number, $order_type){
		$returns = array();

		$query = "SELECT *, ri.id return_item_id FROM returns r, return_items ri ";
		$query .= "WHERE r.order_number = '".res($order_number)."' AND r.order_type = '".res($order_type)."' AND r.rma_number = ri.rma_number; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$r['receive_date'] = '';

			$query2 = "SELECT * FROM inventory_history h ";
			$query2 .= "WHERE invid = '".$r['inventoryid']."' AND h.field_changed = 'returns_item_id' AND h.value = '".$r['return_item_id']."' ";
			$query2 .= "GROUP BY invid; ";
			$result2 = qdb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$r['receive_date'] = $r2['date_changed'];
			}
			$returns[] = $r;
		}

		return ($returns);
	}
?>
