<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getUnitFreight.php';

	function calcRepairCost($ro_number,$repair_item_id=0,$inventoryid=0,$include_freight=false) {
		if (! $ro_number) { return (0); }

		$cost = 0;

		/***** COMPONENTS COST *****/
		$query = "SELECT rc.qty, pi.price FROM repair_components rc, inventory i, purchase_items pi ";
		$query .= "WHERE rc.ro_number = '".res($ro_number)."' AND rc.invid = i.id ";
		$query .= "AND i.purchase_item_id = pi.id; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
//			$ext = $r['qty']*$r['price'];
			$price = 0;
			$query2 = "SELECT actual FROM inventory_costs WHERE inventoryid = '".$r['inventoryid']."'; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$price = $r2['actual']/$r['qty'];
			}
			$ext = $r['qty']*$price;

			$cost += $ext;
		}

		/***** GET ALL 3RD PARTY REPAIRS AND REPLACEMENT-FREIGHT COSTS *****/
		$query = "SELECT ri.id FROM repair_items ri ";
		$query .= "WHERE ri.ro_number = '".res($ro_number)."'; ";// AND ro.ro_number = ri.ro_number; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {

			$query2 = "SELECT ss_number FROM service_orders so ";
			$query2 .= "WHERE order_number = '".res($ro_number)."' AND order_type = 'Repair'; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$so = $r2['ss_number'];

				$query3 = "SELECT qty, price FROM service_items si WHERE si.ss_number = $so; ";
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
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
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
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
