<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getUnitFreight.php';

	function calcRepairCost($ro_number,$repair_item_id=0,$inventoryid=0,$include_freight=false) {
		if (! $ro_number) { return (0); }

		$cost = 0;

		/***** COMPONENTS COST *****/
		$query = "SELECT rc.qty, pi.price, i.id inventoryid FROM repair_components rc, inventory i, purchase_items pi ";
		//$query .= "WHERE rc.ro_number = '".res($ro_number)."' ";
		$query .= "WHERE rc.invid = i.id ";
		if ($repair_item_id) { $query .= "AND item_id = '".res($repair_item_id)."' AND item_id_label = 'repair_item_id' "; }
		$query .= "AND i.purchase_item_id = pi.id; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
//			$ext = $r['qty']*$r['price'];
			$price = 0;
			$query2 = "SELECT actual FROM inventory_costs WHERE inventoryid = '".$r['inventoryid']."'; ";
			$result2 = qedb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$price = $r2['actual']/$r['qty'];
			} else {
//				echo $ro_number.' = '.$r['qty'].'*'.$r['price'].':'.$query2.'<BR>';
			}
			$ext = $r['qty']*$price;

			$cost += $ext;
		}

		/***** GET ALL 3RD PARTY REPAIRS AND REPLACEMENT-FREIGHT COSTS *****/
		$query = "SELECT ri.id FROM repair_items ri ";
		$query .= "WHERE ri.ro_number = '".res($ro_number)."' ";// AND ro.ro_number = ri.ro_number; ";
		if ($repair_item_id) { $query .= "AND id = '".res($repair_item_id)."' "; }
		$query .= "; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {

			$query2 = "SELECT os_number FROM outsourced_orders os ";
			$query2 .= "WHERE order_number = '".res($ro_number)."' AND order_type = 'Repair'; ";
			$result2 = qedb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$os = $r2['os_number'];

				$query3 = "SELECT qty, price FROM outsourced_items oi WHERE oi.os_number = '".res($os)."' ";
				if ($repair_item_id) { $query3 .= "AND ref_2 = '".res($repair_item_id)."' AND ref_2_label = 'repair_item_id' "; }
				$query3 .= "; ";
				$result3 = qedb($query3);
				while ($r3 = mysqli_fetch_assoc($result3)) {
					$cost += ($r3['qty']*$r3['price']);
				}
			}

			// in addition to below freight cost method, also try to get freight cost from packages
			// associated with outbound SO, rather than just Repair association / legacy method
			if ($include_freight) {
				$query2 = "SELECT so_number FROM sales_items ";
				$query2 .= "WHERE ((ref_1 = '".$r['id']."' AND ref_1_label = 'repair_item_id') OR (ref_2 = '".$r['id']."' AND ref_2_label = 'repair_item_id')) ";
				$query2 .= "GROUP BY so_number; ";
				$result2 = qedb($query2);
				while ($r2 = mysqli_fetch_assoc($result2)) {
					$freight_cost = getUnitFreight($inventoryid,$r2['so_number'],'Sale');
					if ($freight_cost>0) { $cost += $freight_cost; }
				}
			}
		}

		// get freight cost per unit based on the package's freight that it was in, divided by #pcs inside
		if ($include_freight) {
			$freight_cost = getUnitFreight($inventoryid,$ro_number,'Repair');
			if ($freight_cost>0) { $cost += $freight_cost; }
		}

		return ($cost);
	}
?>
