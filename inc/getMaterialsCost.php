<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getInventoryCost.php';

	$MATERIALS = array();
	function getMaterialsCost($item_id,$item_label='service_item_id') {
		global $MATERIALS;

		if (! $item_id) { return false; }
		if (isset($MATERIALS[$item_id.$item_label])) { return ($MATERIALS[$item_id.$item_label]); }

		$MATERIALS[$item_id.$item_label] = array(
			'cost' => 0,
			'items' => array(),
		);

		$mat_cost = 0;
		if ($item_label=='service_item_id') {
			$query = "SELECT i.id, i.partid ";
			$query .= "FROM service_materials m, inventory i ";
			$query .= "WHERE m.service_item_id = '".res($item_id)."' AND m.inventoryid = i.id; ";
		} else if ($item_label=='repair_item_id') {
			$query = "SELECT i.id, i.partid ";
			$query .= "FROM repair_components rc, inventory i ";
			$query .= "WHERE rc.item_id = '".res($item_id)."' AND rc.item_id_label = '".res($item_label)."' AND rc.invid = i.id; ";
		}
		$result = qedb($query);
		while ($r = qrow($result)) {
			$cost = getInventoryCost($r['id']);
			$mat_cost += $cost;

			$MATERIALS[$item_id.$item_label]['items'][] = array('inventoryid'=>$r['id'], 'partid'=>$r['partid'],'cost'=>$cost);
		}

		// check ICO
		if ($item_label=='service_item_id') {
			$query = "SELECT i.id, i.partid FROM service_materials m, inventory i, service_items si ";
			$query .= "WHERE m.service_item_id = si.id AND si.ref_2 = '".res($item_id)."' AND si.ref_2_label = '".$item_label."' ";
			$query .= "AND (si.amount IS NULL OR si.amount = '0.00') AND m.inventoryid = i.id ";
			$query .= "GROUP BY i.id; ";
			$result = qedb($query);
			while ($r = qrow($result)) {
				$cost = getInventoryCost($r['id']);
				$mat_cost += $cost;

				$MATERIALS[$item_id.$item_label]['items'][] = array('inventoryid'=>$r['id'], 'partid'=>$r['partid'],'cost'=>$cost);
			}
		}

		$MATERIALS[$item_id.$item_label]['cost'] = $mat_cost;

		return ($MATERIALS[$item_id.$item_label]);
	}
?>
