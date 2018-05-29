<?php
	// Get the activities tied to this item_id and type
	function getActivities() {
		$notes = array();

		global $ORDER_DETAILS, $T;

		$query = "
			SELECT activity_log.id, userid techid, datetime, notes FROM activity_log WHERE item_id = '".res($ORDER_DETAILS['id'])."' AND item_id_label = '".res($T['item_label'])."'
			UNION
			SELECT '' as id, '' as techid, i.date_created as datetime, CONCAT('Component <b>', p.part, '</b> Received') FROM purchase_requests pr, purchase_items pi, parts p, inventory i WHERE pr.item_id = ".fres($ORDER_DETAILS['id'])." AND pr.item_id_label = ".fres($T['item_label'])." AND pr.po_number = pi.po_number AND pr.partid = pi.partid AND pi.qty <= pi.qty_received AND p.id = pi.partid AND i.purchase_item_id = pi.id
			UNION
			SELECT '' as id, '' as techid, pr.requested as datetime, CONCAT('Component <b>', p.part, '</b> Requested') FROM purchase_requests pr, parts p WHERE pr.item_id = ".fres($ORDER_DETAILS['id'])." AND pr.item_id_label = ".fres($T['item_label'])." AND pr.partid = p.id";

		// These are notes pertaining to repair items and having certain components received or the item scanned in for repair
		if($T['type'] == 'Repair') {
			$query .= "	
				UNION
				SELECT '' as id, '' as techid, i.date_created as datetime, CONCAT('Component Received ', `partid`, ' Qty: ', qty ) as notes FROM inventory i WHERE i.repair_item_id = ".fres($ORDER_DETAILS['id'])." AND serial_no IS NULL
				UNION
				SELECT '' as id, created_by as techid, created as datetime, CONCAT('".$T['type']." Order Created') as notes FROM repair_orders WHERE item_id = ".fres($ORDER_DETAILS[$T['id']])." AND item_id_label = 'repair_item_id'
				UNION
				SELECT '' as id, userid as techid, date_created as datetime, CONCAT('Received ".$T['type']." Serial: <b>', serial_no, '</b>') as notes FROM inventory WHERE id in (SELECT invid FROM inventory_history where field_changed = 'repair_item_id' and `value` = ".fres($ORDER_DETAILS['id']).") AND serial_no IS NOT NULL
				UNION
				SELECT '' as id, '' as techid, datetime as datetime, CONCAT('Tracking# ', IFNULL(tracking_no, 'N/A')) as notes FROM packages WHERE order_number = ".fres($ORDER_DETAILS[$T['order']])." AND order_type = 'Repair'
				UNION
				SELECT '' as id, '' as techid, datetime as datetime, CONCAT('<b>', part, '</b> pulled to Order') as notes FROM repair_components, inventory, parts WHERE item_id = ".fres($ORDER_DETAILS[$T['id']])." AND item_id_label = 'repair_item_id' AND inventory.id = repair_components.invid AND parts.id = inventory.partid
			";
		}

		$query .= "
			ORDER BY datetime DESC;
		";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$notes[] = $r;
		}

		return $notes;
	}