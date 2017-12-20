<?php
	$MATERIALS = array();
	function getMaterials($item_id,$item_label='service_item_id') {
		global $MATERIALS;

		if (! $item_id) { return false; }
		if (isset($MATERIALS[$item_id.$item_label])) { return ($MATERIALS[$item_id.$item_label]); }

		$materials = array();
		$query = "SELECT i.id inventoryid, actual cost FROM purchase_items pi, inventory i ";
		$query .= "LEFT JOIN inventory_costs c ON c.inventoryid = i.id ";
		$query .= "WHERE pi.ref_1 = '".res($item_id)."' AND pi.ref_1_label = '".res($item_label)."' ";
		$query .= "AND i.purchase_item_id = pi.id; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$materials[] = array('cost'=>$r['cost']);
		}

		$MATERIALS[$item_id.$item_label] = $materials;
		return ($materials);
	}
?>
