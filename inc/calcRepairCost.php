<?php
	function calcRepairCost($ro_number,$repair_item_id=0,$inventoryid=0) {
		if (! $ro_number) { return (0); }

		$cost = 0;
		$query = "SELECT ri.id FROM repair_orders ro, repair_items ri ";
		$query .= "WHERE ro.ro_number = '".res($ro_number)."' AND ro.ro_number = ri.ro_number; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$query2 = "SELECT rc.qty, pi.price FROM repair_components rc, inventory i, purchase_items pi ";
			$query2 .= "WHERE rc.ro_number = '".res($ro_number)."' AND rc.invid = i.id ";
			$query2 .= "AND i.purchase_item_id = pi.id; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$ext = $r2['qty']*$r2['price'];
				$cost += $ext;
			}

			// get freight cost per unit based on the package's freight that it was in, divided by #pcs inside
			$freight_cost = getUnitFreight($inventoryid,$ro_number,'Repair');
			if ($freight_cost>0) { $cost += $freight_cost; }

			// also try to get freight cost from packages associated with outbound SO, rather than just Repair (legacy method)
			$query2 = "SELECT so_number FROM sales_items ";
			$query2 .= "WHERE ((ref_1 = '".$r['id']."' AND ref_1_label = 'repair_item_id') OR (ref_2 = '".$r['id']."' AND ref_2_label = 'repair_item_id')) ";
			$query2 .= "GROUP BY so_number; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$freight_cost = getUnitFreight($inventoryid,$r2['so_number'],'Sale');
				if ($freight_cost>0) { $cost += $freight_cost; }
			}
		}

		return ($cost);
	}
?>
