<?php
	$MATERIALS = array();
	function getMaterialsCost($item_id,$item_label='service_item_id') {
		global $MATERIALS;

		if (! $item_id) { return false; }
		if (isset($MATERIALS[$item_id.$item_label])) { return ($MATERIALS[$item_id.$item_label]); }

		$materials = array();
		$query = "SELECT i.id inventoryid, actual cost, pi.partid FROM purchase_items pi, inventory i ";
		$query .= "LEFT JOIN inventory_costs c ON c.inventoryid = i.id ";
		$query .= "WHERE pi.ref_1 = '".res($item_id)."' AND pi.ref_1_label = '".res($item_label)."' ";
		$query .= "AND i.purchase_item_id = pi.id; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$charge = $r['cost'];
			$query2 = "SELECT qty, charge FROM service_bom ";
			$query2 .= "WHERE item_id = '".res($item_id)."' AND item_id_label = '".$item_label."' AND partid = '".$r['partid']."'; ";
			$result2 = qedb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				//$charge = $r2['qty']*$r2['charge'];
				$charge = $r2['charge'];
			}

			$materials[] = array('cost'=>$r['cost'],'charge'=>$charge);
		}

		$MATERIALS[$item_id.$item_label] = $materials;
		return ($materials);
	}
?>
