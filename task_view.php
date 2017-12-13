<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/format_address.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/dictionary.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getAddresses.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getUser.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getRepairCode.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getClass.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getItemOrder.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/file_zipper.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/is_clockedin.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/order_type.php';

	function getInventoryCost($inventoryid) {

		// calculate inventory cost
		$cost = 0;
		$query3 = "SELECT actual FROM inventory_costs WHERE inventoryid = '".res($inventoryid)."'; ";
		$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
		if (mysqli_num_rows($result3)>0) {
			$r3 = mysqli_fetch_assoc($result3);
			$cost = $r3['actual'];//*$row['pulled'];
		}
		return ($cost);

	}

	// List of subcategories
	$documentation = true;
	$details = true;
	$labor = true;
	$activity = true;
	$materials = true;
	$expenses = true;
	$closeout = true;
	$outside = true;
	$images = true;

	$new = false;

	// Dynamic Variables Used
	$documentation_data = array();
	$activity_data = array();
	$materials_data = array();
	$expenses_data = array();
	$closeout_data = array();
	$labor_data = array();
	$service_codes = array();
	$ticketStatus = '';

	$materials_total = 0;
	$labor_total = 0;
	$labor_cost = 0;
	$expenses_total = 0;
	$outside_services_total = 0;
	$total_amount = 0;

	$item_id = 0;
	$item_id_label = '';
	$item_details = array();
	$component_data = array();
	$outsourced = array();


	// These variables are soley used for ICO & CCO
	$OG_item_id = 0;
	$OG_component_data = array();
	$OG_outsourced = array();

	if (isset($type) AND trim($type)) { $type = ucfirst($type); }
	if (! isset($T)) { $T = order_type($type); }

	//print '<pre>' . print_r($ORDER, true) . '</pre>';

	if($quote){

		// Create the option for the user to create a quote or create an order
		$activity = false;
		$documentation = false;
		$details = true;
		$task_edit = true; 
		$expenses = false;

		if(! empty($task_number)) {
			$item_id = getItemID($order_number, $task_number, 'service_quote_items', 'quoteid');
			$item_details = getItemDetails($item_id, 'service_quote_items', 'id');
			$component_data = getMaterials($order_number, $item_id, 'service_quote', 'quote_item_id');
			$outsourced = getOutsourced($item_id, $type);

			//print_r($component_data);

			$new = false;
		} else {
			$new = true;
		}

		$labor_cost = $item_details['labor_hours'] * $item_details['labor_rate'];
		//$labor_total = $labor_cost;
		$expenses_total = $item_details['expenses'];
		$total_amount = $materials_total + $labor_cost + $expenses_total + $outside_services_total;
	} else if(strtolower($type) == 'service') {
		$item_id_label = 'service_item_id';

		if(! empty($task_number)) {
			$item_id = getItemID($order_number, $task_number, 'service_items', 'so_number');
			$item_details = getItemDetails($item_id, 'service_items', 'id');

			if($item_details['status_code']) {
				$ticketStatus = getRepairCode($item_details['status_code'], 'service');
			}

			$component_data = getMaterials($order_number, $item_id, $type, 'service_item_id');
			$outsourced = getOutsourced($item_id, $type);
			$documentation_data = getDocumentation($item_id, 'service_item_id');

			// print_r($documentation_data);

			$activity_data = grabActivities($order_number, $item_id, $type);

			if($item_details['ref_1_label'] == 'ICO') {
				$OG_item_id = $item_details['ref_1'];
				$ico = true;
			}

			if($item_details['ref_2_label'] == "ICO") {
				$OG_item_id = $item_details['ref_2'];
				$ico = true;
			}

			if($item_details['ref_1_label'] == 'CCO') {
				$OG_item_id = $item_details['ref_1'];
				$cco = true;
			}

			if($item_details['ref_2_label'] == 'CCO') {
				$OG_item_id = $item_details['ref_2'];
				$cco = true;
			}

			// Get Merge Data 
			if(($ico OR $cco) AND $OG_item_id) {
				$OG_component_data = getMaterials($order_number, $OG_item_id, $type, 'service_item_id');
				$OG_outsourced = getOutsourced($OG_item_id, $type);
			}

			getLaborTime($item_id, $type);

			foreach ($labor_data as $cost) {
				$labor_cost += $cost['cost'];
			}
		} else {
			$new = true;
		}

		if ($item_details['quote_item_id']) {
			$query = "SELECT * FROM service_quote_items WHERE id = '".res($item_details['quote_item_id'])."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($result)>0) {
				$quote_r = mysqli_fetch_assoc($result);
				$labor_total = $quote_r['labor_hours'] * $quote_r['labor_rate'];
			}
		}
		$total_amount = $materials_total + $labor_cost + $expenses_total + $outside_services_total;

	} else if(strtolower($type) == 'repair') {
		$item_id_label = 'repair_item_id';

		// Diable Modules for Repair
		$item_id = getItemID($order_number, $task_number, 'repair_items', 'ro_number');

		$documentation = false;
		$closeout = false;
		$expenses = false;
		$outside = false;

		$item_details = getItemDetails($item_id, 'repair_items', 'id');

		if($item_details['repair_code_id']) {
			$ticketStatus = getRepairCode($item_details['repair_code_id'], 'repair');
		}

		$query = "SELECT * FROM repair_codes;";

		$result = qdb($query) or die(qe() . ' ' . $query);

		while ($row = $result->fetch_assoc()) {
			$service_codes[] = $row;
		}

		$activity_data = grabActivities($order_number, $item_id, $type);
		$component_data = getMaterials($order_number, $item_id, $type);
		getLaborTime($item_id, $type);

		foreach ($labor_data as $cost) {
			$labor_cost += $cost['cost'];
		}

		$total_amount = $materials_total + $labor_cost + $expenses_total + $outside_services_total;

	}

	function partDescription($partid, $desc = true){
		$r = reset(hecidb($partid, 'id'));
		$parts = explode(' ',$r['part']);

	    $display = "<span class = 'descr-label'>".$parts[0]." &nbsp; ".$r['heci']."</span>";
	    if($desc)
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf']).' &nbsp; '.dictionary($r['system']).
				'</span> <span class="description-label">'.dictionary(substr($r['description'],0,30)).'</span></div>';

	    return $display;
	}

	function getDetails($itemid) {
		$serials = array();

		$query = "SELECT serial_no FROM inventory WHERE repair_item_id = ".res($itemid).";";
		$result = qdb($query) OR die(qe());

		while ($row = $result->fetch_assoc()) {
			$serials[] = $row['serial_no'];
		}

		//print_r($serials);

		return $serials;
	}

	// Get the details of the current item_id (repair_item_id, service_item_id etc)
	function getItemDetails($item_id, $table, $field) {
		$data = array();

		$query = "SELECT * FROM $table WHERE $field = ".res($item_id).";";
		$result = qdb($query) OR die(qe().' '.$query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$data = $r;
		}

		return $data;
	}

	function getItemID($order_number, $line_number, $table = 'repair_items', $field = 'ro_number'){
		$item_id = 0;

		$query = "SELECT id FROM $table WHERE $field = ".res($order_number).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if(mysqli_num_rows($result) == 1){
			$r = mysqli_fetch_assoc($result);
			$item_id = $r['id'];
		} else if(mysqli_num_rows($result) > 1) {
			$query = "SELECT id FROM $table WHERE line_number = ".res($line_number)." AND $field = ".res($order_number).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if(mysqli_num_rows($result)){
				$r = mysqli_fetch_assoc($result);
				$item_id = $r['id'];
			}
		} else {
			die("Missing item!");
		}

		return $item_id;
	}

	function grabActivities($ro_number, $item_id, $type = 'Repair'){
		$activities = array();
		$invid = '';
		$label = '';
		if ($type=='Repair') { $label = 'repair_item_id'; }
		else if ($type=='Service') { $label = 'service_item_id'; }

		$query = "
				SELECT activity_log.id, userid techid, datetime, notes FROM activity_log WHERE item_id = '".res($item_id)."' AND item_id_label = '".res($label)."'
				UNION
				SELECT '' as id, '' as techid, i.date_created as datetime, CONCAT('Component Received ', `partid`, ' Qty: ', qty ) as notes FROM inventory i WHERE i.repair_item_id = ".prep($repair_item_id)." AND serial_no IS NULL
				UNION
				SELECT '' as id, created_by as techid, created as datetime, CONCAT('".ucwords($type)." Order Created') as notes FROM repair_orders WHERE ro_number = ".prep($ro_number)."
				UNION
				SELECT '' as id, userid as techid, date_created as datetime, CONCAT('Received ".ucwords($type)." Serial: <b>', serial_no, '</b>') as notes FROM inventory WHERE id in (SELECT invid FROM inventory_history where field_changed = 'repair_item_id' and `value` = ".prep($repair_item_id).") AND serial_no IS NOT NULL
				UNION
				SELECT '' as id, '' as techid, datetime as datetime, CONCAT('Tracking# ', IFNULL(tracking_no, 'N/A')) as notes FROM packages WHERE order_number = ".prep($ro_number)." AND order_type = 'Repair'
				UNION
				SELECT '' as id, '' as techid, datetime as datetime, CONCAT('<b>', part, '</b> pulled to Order') as notes FROM repair_components, inventory, parts WHERE ro_number = ".prep($ro_number)." AND inventory.id = repair_components.invid AND parts.id = inventory.partid
				UNION
				SELECT '' as id, '' as techid, i.date_created as datetime, CONCAT('Component <b>', p.part, '</b> Received') FROM purchase_requests pr, purchase_items pi, parts p, inventory i WHERE pr.ro_number = ".prep($ro_number)." AND pr.po_number = pi.po_number AND pr.partid = pi.partid AND pi.qty <= pi.qty_received AND p.id = pi.partid AND i.purchase_item_id = pi.id
				UNION
				SELECT '' as id, '' as techid, pr.requested as datetime, CONCAT('Component <b>', p.part, '</b> Requested') FROM purchase_requests pr, parts p WHERE pr.ro_number = ".prep($ro_number)."  AND pr.partid = p.id
				ORDER BY datetime DESC;";

		$result = qdb($query) OR die(qe());
		foreach($result as $row){
			$activities[] = $row;
		}

		// Aaron's way to check if an item is marked for tested or not
		$query = "SELECT invid FROM inventory_history where field_changed = 'repair_item_id' and `value` = ".prep($repair_item_id)." LIMIT 1;";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$invid = $result['invid'];
		} else {
			$query = "SELECT id FROM inventory where `repair_item_id` = ".prep($repair_item_id)." LIMIT 1;";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if (mysqli_num_rows($result)) {
				$result = mysqli_fetch_assoc($result);
				$invid = $result['invid'];
			}
		}
		
		if($invid){
			$invhis = "
			SELECT DISTINCT * 
			FROM inventory_history ih, activity_log l 
			WHERE field_changed = 'status' 
			AND l.datetime = ih.date_changed 
			AND l.notes is null
			AND invid = ".prep($invid)."
			order by date_changed asc;
			";
			$history = qdb($invhis) or die(qe()." | $invhis");
			foreach($history as $h){
				$status = '';
				if($h['value'] == "in repair" && ($h['changed_from'] == "shelved" || $h['changed_from'] == "manifest")){
					$status = 'Checked In';	
				} else if($h['changed_from'] == "in repair" && ($h['value'] == "shelved" || $h['value'] == "manifest")){
					$status = 'Checked Out';	
				} else if($h['changed_from'] == "testing" && ($h['value'] == "shelved" || $h['value'] == "manifest")){				
					$status = 'Out of Test Lab';	
				} else if($h['value'] == "testing" && ($h['changed_from'] == "shelved" || $h['changed_from'] == "manifest")){				
					$status = 'In Test Lab';	
				}
				foreach($activities as $count => $row){
					if(!$row['notes']){
						$activities[$count]['notes'] = $status;
					}
				}
			}
		}
		return $activities;
	}

	function in_array_r($item , $array){
	    return preg_match('/"'.$item.'"/i' , json_encode($array));
	}

	function getMaterials($order_number, $item_id, $type = 'repair', $field = 'repair_item_id') {
		global $materials_total;

		$purchase_requests = array();
		
		if(strtolower($type) == 'repair' OR strtolower($type) == 'service') {

			$query = "SELECT r.partid, r.po_number, i.id purchase_item_id, SUM(r.qty) as totalOrdered FROM purchase_requests r ";
			$query .= "LEFT JOIN purchase_items i ON r.po_number = i.po_number ";
			$query .= "WHERE r.item_id = ". prep($item_id) ." AND r.item_id_label = '".res($field)."' ";//GROUP BY partid, po_number ORDER BY requested DESC;";
			$query .= "GROUP BY partid, po_number ";
			$query .= "ORDER BY requested DESC; ";
			$result = qdb($query) OR die(qe());

			while ($row = mysqli_fetch_assoc($result)) {

				$query2 = "SELECT * FROM inventory WHERE purchase_item_id = '".$row['purchase_item_id']."'; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)>0) { continue; }

				$row['po_number'] = '';
				$row['totalReceived'] = 0;
				$row['available'] = 0;
				$row['pulled'] = 0;

				$purchase_requests[] = $row;
			}

				// Check to see what has been received and sum it into the total Ordered
				if ($type=='Repair') {
//					$query = "SELECT *, i.id inventoryid, SUM(i.qty) as totalReceived FROM repair_components c, inventory i ";
					$query = "SELECT *, i.id inventoryid, i.qty as totalReceived FROM repair_components c, inventory i ";
//					if ($po_number) { $query .= "LEFT JOIN purchase_items pi ON pi.id = i.purchase_item_id "; }
					$query .= "WHERE c.item_id = '".res($item_id)."' AND c.invid = i.id ";
//					$query .= "AND i.partid = ".prep($row['partid'])." ";
//					if ($po_number) { $query .= "AND pi.po_number = '".res($po_number)."' "; }
					$query .= "; ";
				} else if ($type=='Service') {
//					$query = "SELECT *, i.id inventoryid, SUM(i.qty) as totalReceived FROM service_materials m, inventory i ";
					$query = "SELECT *, i.id inventoryid, i.qty as totalReceived FROM service_materials m, inventory i ";
					if ($po_number) { $query .= "LEFT JOIN purchase_items pi ON pi.id = i.purchase_item_id "; }
					$query .= "WHERE m.service_item_id = '".res($item_id)."' AND m.inventoryid = i.id ";
//					$query .= "AND i.partid = ".prep($row['partid'])." ";
//					if ($po_number) { $query .= "AND pi.po_number = '".res($po_number)."' "; }
					$query .= "; ";
				}
				$result2 = qdb($query) OR die(qe().' '.$query);
				while ($row2 = mysqli_fetch_assoc($result2)) {
					$row = array('partid'=>$row2['partid']);

					$inventoryid = $row2['inventoryid'];
					$qty = ($row2['totalReceived'] ? $row2['totalReceived'] : 0);

					$row['po_number'] = '';
					$row['totalOrdered'] = 0;
					$query3 = "SELECT SUM(r.qty) qty, r.po_number FROM purchase_requests r, purchase_items i ";
					$query3 .= "WHERE item_id = '".res($item_id)."' AND item_id_label = '".res($field)."' ";
					$query3 .= "AND r.partid = '".$row2['partid']."' AND r.po_number = i.po_number AND i.id = '".$row2['purchase_item_id']."'; ";
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					if (mysqli_num_rows($result3)>0) {
						$r3 = mysqli_fetch_assoc($result3);
						if ($r3['qty']>0) {
							$row['totalOrdered'] = $r3['qty'];
							$row['po_number'] = $r3['po_number'];
						}
					}

					$row['totalReceived'] = $qty;
				
					// Grab actual available quantity for the requested component
					$row['available'] = getAvailable($row['partid'], $item_id);
					$row['pulled'] = getPulled($row['partid'], $item_id);

					$cost = getInventoryCost($inventoryid);
					$materials_total += $cost;

//					$row['total'] = $total;

					$purchase_requests[] = $row;
				}
//			}

			//print_r($purchase_requests);

/*
			if(strtolower($type) == 'repair') {
				// Also grab elements that were fulfilled by the in stock
				$query = "SELECT *, SUM(i.qty) as totalReceived FROM repair_components c, inventory i ";
				$query .= "WHERE c.ro_number = '".res($order_number)."' AND c.invid = i.id ";
				$query .= "GROUP BY i.partid; ";
			} else {
				// Also grab elements that were fulfilled by the in stock
				$query = "SELECT *, SUM(i.qty) as totalReceived FROM service_materials c, inventory i ";
				$query .= "WHERE c.service_item_id = '".res($item_id)."' AND c.inventoryid = i.id ";
				$query .= "GROUP BY i.partid; ";
			}
			$result = qdb($query) OR die(qe()); 

			while ($row = $result->fetch_assoc()) {
				if(!in_array_r($row['partid'] , $purchase_requests)) {
					$cost = getInventoryCost($row['inventoryid']);
					$materials_total += $cost;

					$purchase_requests[] = $row;
				}
			}
*/
		} else if($type == 'service_quote') {
			$query = "SELECT * FROM service_quote_materials WHERE $field = ".res($item_id).";";
			$result = qdb($query) OR die(qe().' '.$query);

			//echo $query;

			while($row = mysqli_fetch_assoc($result)) {
				$purchase_requests[] = $row;
				$materials_total += $row['quote'];
			}
		} 

		return $purchase_requests;
	}

	function getDocumentation($item_id, $item_label) {
		$documents = array();

		$query = "SELECT * FROM service_docs WHERE item_id = ".res($item_id)." AND item_label = ".fres($item_label)." ORDER BY datetime DESC;";
		$result = qdb($query) OR die(qe().'<BR>'.$query);

		while($r = mysqli_fetch_assoc($result)) {
			$documents[] = $r;
		}

		return $documents;
	}

	function getAvailable($partid,$itemid=0) {
		$qty = 0;

		$query = "SELECT SUM(i.qty) as sum, i.id FROM purchase_items pi, inventory i WHERE pi.ref_1_label = 'repair_item_id' AND pi.ref_1='".res($itemid)."' AND pi.id = i.purchase_item_id AND i.partid = '".res($partid)."' AND i.qty > 0 AND (status = 'shelved' OR status = 'received')
                UNION
           SELECT SUM(i.qty) as sum, i.id FROM purchase_items pi, inventory i WHERE i.partid = '".res($partid)."' AND i.purchase_item_id = pi.id AND (pi.ref_1_label <> 'repair_item_id' OR pi.ref_1_label IS NULL) AND i.qty > 0 AND (status = 'shelved' OR status = 'received');";

		$result = qdb($query) OR die(qe());
				
		 while ($row = $result->fetch_assoc()) {
			$qty += ($row['sum'] ? $row['sum'] : 0);
		}

		return $qty;
	}

	function getPulled($partid, $item_id) {
		$qty = 0;
		
		$query = "SELECT SUM(r.qty) as sum FROM repair_components r, inventory i WHERE r.item_id = ". prep($item_id) ." AND r.invid = i.id AND i.partid = ".prep($partid).";";
		$result = qdb($query) OR die(qe());
				
		if (mysqli_num_rows($result)>0) {
			$results = mysqli_fetch_assoc($result);
			$qty = ($results['sum'] ? $results['sum'] : 0);
		}

		return $qty;
	}

	function getOutsourced($item_id, $type) {
		global $outside_services_total, $quote;
		$outsourced = array();

		if($quote) {
			$query = "SELECT * FROM service_quote_outsourced WHERE quote_item_id = ".res($item_id).";";
			$result = qdb($query) OR die(qe().' '.$query);

			while($r = mysqli_fetch_assoc($result)){
				$outsourced[] = $r;
				$outside_services_total += $r['amount'];
			}
		} else {
			$query = "SELECT * FROM outsourced_orders WHERE os_number = ".res($item_id).";";
			$result = qdb($query) OR die(qe().' '.$query);

			while($r = mysqli_fetch_assoc($result)){
				$outsourced[] = $r;
				$outside_services_total += $r['amount'];
			}
		}

		return $outsourced;
	}

	// Creating an array for the current task based on total time spent per unique userid on the task
	function getLaborTime($item_id, $type){
		global $labor_data;
		$totalSeconds = 0; 
		$totalSeconds_data = array();

		if(strtolower($type) == 'repair') {
			$task_label = 'repair_item_id';
		} else {
			$task_label = 'service_item_id';
		}


		$query = "SELECT * FROM timesheets WHERE taskid = ".res($item_id)." AND task_label = '".res(strtolower($task_label))."' AND clockin IS NOT NULL AND clockout IS NOT NULL;";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		while($r = mysqli_fetch_assoc($result)){
			$totalSeconds_data[$r['userid']][$r['rate']] += strtotime($r['clockout']) - strtotime($r['clockin']);
		}

		// Also pull assigned users and set them to 0 hours worked
		$query = "SELECT * FROM service_assignments WHERE item_id = ".res($item_id)." AND item_id_label = ".fres($task_label).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);
		while($r = mysqli_fetch_assoc($result)){
			$totalSeconds_data[$r['userid']][0] += 0;
		}

		// If no user is assigned to this task and ONLY single-user class then auto add them into the system as the assigned person
		if(empty($totalSeconds_data)) {
			// Check by type of work if there is only 1 user in this class
			$query = "SELECT u.id as userid, c.name 
    			FROM service_classes sc, user_classes uc, users u, contacts c 
    			WHERE LOWER(sc.class_name) = '".res(strtolower($type))."' AND uc.classid = sc.id AND u.id = uc.userid AND c.id = u.contactid;";
    		$result = qdb($query) OR die(qe() . ' ' . $query);

    		if(mysqli_num_rows($result) == 1) {
    			$r = mysqli_fetch_assoc($result);
    			//print_r($r);
    			$query = "REPLACE INTO service_assignments (item_id, item_id_label, userid) VALUES (".fres($item_id).", ".fres((strtolower($type) == 'repair' ? 'repair_item_id' : 'service_item_id')).",".fres($r['userid']).")";
    			qdb($query) OR die(qe() .' ' . $query);

    			$totalSeconds_data[$r['userid']][0] += 0;
    		}
		}

		// From the data given
		foreach($totalSeconds_data as $userid => $labor_row) {
			foreach ($labor_row as $rate => $labor_seconds) {
				$data = array();
				$status = '';
				$hours_worked = ($labor_seconds / 3600);
				//$totalSeconds += $labor_seconds;

				$data['laborSeconds'] = $labor_seconds;
				$data['start_datetime'] = '';
				$data['end_datetime'] = '';

/*
			// Get the users hourly rate
			$query = "SELECT hourly_rate FROM users WHERE id=".res($userid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if (mysqli_num_rows($result)) {
				$r = mysqli_fetch_assoc($result);
				$rate = $r['hourly_rate'];
			}
*/

				// Check if the user is currently allowed on this job or not
				$query = "SELECT * FROM service_assignments WHERE item_id = ".res($item_id)." AND item_id_label = ".fres($task_label)." AND userid = ".res($userid).";";
				$result = qdb($query) OR die(qe() . ' ' . $query);

				if (mysqli_num_rows($result)) {
					$r = mysqli_fetch_assoc($result);

					$status = 'active';
					$data['start_datetime'] = $r['start_datetime'];
					$data['end_datetime'] = $r['end_datetime'];
				}

				$data['status'] = $status;

				$cost = round($rate * $hours_worked, 2);
				$data['cost'] = $cost;

				if (isset($labor_data[$userid])) {
					$labor_data[$userid]['laborSeconds'] += $data['laborSeconds'];
					$labor_data[$userid]['cost'] += $data['cost'];
					$labor_data[$userid]['status'] = $data['status'];
				} else {
					$labor_data[$userid] = $data;
				}
			}
		}
	}

	function checkNotification($activityid) {
		$available = true;

		$query = "SELECT * FROM messages WHERE ref_1_label = 'activityid' AND ref_1 = ".res($activityid).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if (mysqli_num_rows($result)) {
			$available = false;
		}

		return $available;
	}

	function toTime($secs) {
		// given $secs seconds, what is the time g:i:s format?
		$hours = floor($secs/3600);

		// what are the remainder of seconds after taking out hours above?
		$secs -= ($hours*3600);

		$mins = floor($secs/60);

		$secs -= ($mins*60);

		return (str_pad($hours,2,0,STR_PAD_LEFT).':'.str_pad($mins,2,0,STR_PAD_LEFT).':'.str_pad($secs,2,0,STR_PAD_LEFT));
	}

	function timeToStr($time) {
		$t = explode(':',$time);
		$hours = $t[0];
		$mins = $t[1];
		if (! $mins) { $mins = 0; }
		$secs = $t[2];
		if (! $secs) { $secs = 0; }

		//$days = floor($hours/24);
		//$hours -= ($days*24);

		$str = '';
		//if ($days>0) { $str .= $days.'d, '; }
		if ($hours>0 OR $str) { $str .= (int)$hours.'h, '; }
		if ($mins>0 OR $str) { $str .= (int)$mins.'m, '; }
		if ($secs>0 OR $str) { $str .= (int)$secs.'s'; }

		return ($str);
	}

	function accessControl($userid, $item_id, $label){
		global $quote;
		// Guilty until proven innocent
		$access = false;

		if(! $quote) {
			$query = "SELECT * FROM service_assignments WHERE item_id = ".res($item_id)." AND item_id_label = ".fres($label)." AND userid = ".res($userid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);


			if(mysqli_num_rows($result)) {
				$access = true;
			}
		}

		return $access;
	}

	$class = '';
	if ($item_details['task_name']) { $class = $item_details['task_name']; }
	else { $class = getClass($ORDER['classid']); }

	$pageTitle = '';

	if($new) {
		$pageTitle = 'New ';
	}

	$special = '';

	if($ico) {
		$special = " (ICO)";
	}

	if($cco) {
		$special = " (CCO)";
	}
//	if ($item_details['task_name']) { $special .= ' '.$item_details['task_name']; }

	//if(strtolower($type) == "service" AND ($class == "service" OR $class == "FFR")) {
	if(strtolower($type) == "service" OR $quote) {
		if($quote) {
			if ($order_number) {
				$pageTitle .= $class." Quote ".$order_number.($task_number ? '-'.$task_number : '').$special;
			} else {
				$pageTitle .= " Quote";
			}
		} else if($new) {
			$pageTitle .= "for Order# ".$order_number.$special;
		} else {
			if ($item_details['task_name']) {
				$pageTitle = $item_details['task_name'].' '.$order_number."-".$task_number.$special;
			} else {
				$pageTitle = getClass($ORDER['classid']).' '.$order_number."-".$task_number.$special;
			}
		}
	} else if(strtolower($type) == "repair" OR $class == "repair") {
		if($quote) {
			$pageTitle .= "Repair Quote for Order# ".$order_number.($task_number ? '-'.$task_number : '').$special;
		} else {
			if(! $task_number) {
				$task_number = 1;
			}
			$pageTitle = 'Repair '.$order_number."-".$task_number.$special;
		}
	}

	$start_datetime = '';
	$end_datetime = '';

	$clock = false;
	if ($U['hourly_rate']) {
		$clock = is_clockedin($U['id'], $item_id, $item_id_label);
		if ($clock===false) {
			$clock = is_clockedin($U['id']);
			$view_mode = true;
		}
	}

	$manager_access = array_intersect($USER_ROLES,array(1,4));
	$assigned = false;
	if ($item_id AND $item_id_label) {
		$query = "SELECT * FROM service_assignments WHERE item_id = '".res($item_id)."' AND item_id_label = '".res($item_id_label)."' AND userid = '".res($userid)."';";
		$result = qdb($query) OR die(qe() . ' ' . $query);
		if (mysqli_num_rows($result)) { $assigned = true; }
	}

?>

<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $_SERVER["ROOT_DIR"].'/inc/scripts.php';
			include_once $_SERVER["ROOT_DIR"].'/modal/materials_request.php';
			include_once $_SERVER["ROOT_DIR"].'/modal/results.php';
			include_once $_SERVER['ROOT_DIR']. '/modal/service_image.php';

			if(! $quote AND ! $new) {
				include_once $_SERVER["ROOT_DIR"].'/modal/lici.php';
				include_once $_SERVER["ROOT_DIR"].'/modal/service_complete.php';
			}
		?>

		<title>
			<?=$pageTitle;?>
		</title>

		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />

		<style type="text/css">

			.list {
				padding: 5px;
			}

			.list-pad span {
				margin-top: 5px;
				display: block;
			}

			.select2 {
				margin-bottom: 10px;
				width: 100% !important;
			}

			.row-title {
				padding: 5px 0;
				background-color: #fff;
			}

			.row-title-pad {
				padding: 10px 0;
			}

			hr {
				margin: 0;
			}

			.table-first {
				font-weight: bold;
				text-transform: uppercase;
			}

			section {
				margin-bottom: 15px;
			}

			.companyid, .company_address, .company_contact {
				overflow: hidden;
			}

			.sidebar .select2 {
			    width: 100% !important;
			    overflow: hidden;
			}

			.alert-success {
			    background-color: #dff0d8 !important;
			    border-color: #d6e9c6 !important;
			    color: #468847 !important;
			}

			#main-stats {
				height: 55px;
			}

			.table td {
			     vertical-align: top !important; 
			}

			.market-table {
			    max-height: 82px;
			    min-height: 82px;
			    margin-bottom: 0;
			    overflow: hidden;
			    position: relative;
			}

			.market-table:hover {
			    max-height: 82px;
			    min-height: 82px;
			    margin-bottom: 0;
			    overflow: visible;
			    position: initial;
			}

			.found_parts_quote td {
				max-height: 100px;
				height: 100px;
				overflow: hidden;
			}

			.market-table .bg-availability:hover .market-results, .market-table .bg-demand:hover .market-results {
				max-width: 260px;
				max-height: 300px;
			}

			.market-price {
				display: inline;
			}

			.market-table .market-results {
				width: 260px;
			}

			.ticket_status_danger {
				color: #a94442;
			}
			.ticket_status_success {
				color: #3c763d;
			}
			.ticket_status_warning {
				color: #8a6d3b;
			}

			.nav-tabs.nav-tabs-ar + .tab-content {
				overflow: visible;
			}

			.part_listing .add_button {
				display: none;
			}

			.labor_user.inactive {
				opacity: 0.5;
			}

			.bx-wrapper .bx-viewport {
				box-shadow: none;
				border: 0;
				left: 0;
			}

			.dropImage > i {
			    text-align: center;
			    display: block;
			    font-size: 40px;
			    vertical-align: middle;
			    height: 200px;
			    line-height: 200px;
			    color: #C9C9C9;
			}

			.imageDrop:hover {
			    text-decoration: none;
			}

			.imageDrop:hover i {
			    color: #999;
			}

			.bx-wrapper {
				width: 100%;
				max-width: 100% !important;
				min-height: 200px;
			}

			.bx-wrapper .bx-viewport {
				height: 200px !important;
				background: transparent;
			}

			#bxslider-pager li {
				width: 200px !important;
			}

			.upload{
			    display: none !important;
			}
			#pad-wrapper {
				margin-top:64px;
			}

			<?php 
				if($view_mode AND ! $manager_access){
					echo '
						#pad-wrapper input, #pad-wrapper .select2, #pad-wrapper button, #pad-wrapper .upload_link, #pad-wrapper .input-group {
							display: none !important;
						}

						.table-header .toggle-edit, .table-header .btn-update {
							display: none !important;
						}
					';
				}
			?>
		</style>
	</head>
	
	<body class="sub-nav" data-order-type="<?=($quote ? 'quote' : $type)?>" data-order-number="<?=$order_number?>" data-taskid="<?=$item_id;?>" data-techid="<?=$GLOBALS['U']['id'];?>">
		<div id="loader" class="loader text-muted" style="display: none;">
			<div>
				<i class="fa fa-refresh fa-5x fa-spin"></i><br>
				<h1 id="loader-message">Please wait while your search result is populating...</h1>
			</div>
		</div>
		<?php include 'inc/navbar.php'; include 'modal/package.php'; include '/modal/image.php';?>



			<div class="table table-header table-filter">
				<div class="col-md-2">
					<?php if ($type=='Repair' AND ! $task_edit AND ! $view_mode) { ?>
						<span class="pull-right">
							<form action="tasks_log.php" style="display: inline-block;">
								<input type="hidden" name="item_id" value="<?=$item_id;?>">
								<button class="btn btn-sm btn-default btn-flat info" type="submit" name="type" value="test_out" title="Mark as Tested" data-toggle="tooltip" data-placement="bottom">
									<i class="fa fa-terminal"></i>
								</button>
							</form>
						</span>
					<?php } ?>
					<?php if ($manager_access AND ((! $quote AND ! $new AND $type=='Repair'))) { ?>
						<a href="/service.php?order_type=<?=$type;?>&order_number=<?=$order_number_details;?>&edit=true" class="btn btn-default btn-sm toggle-edit"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
					<?php } ?>
					<?php if(! $task_edit AND $type=='Repair') { ?>
						<a href="/repair_add.php?on=<?=($build ? $build . '&build=true' : $order_number)?>" class="btn btn-default btn-sm text-warning">
							<i class="fa fa-qrcode"></i> Receive
						</a>
					<?php } ?>
					<?php if ($quote) { ?>
						<a target="_blank" href="/docs/SQ<?=$item_id;?>.pdf" class="btn btn-default btn-sm" title="View PDF" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-file-pdf-o"></i></a>
					<?php } ?>
				</div>
				<div class="col-md-2">
					<?php if (! $quote AND ! $new AND $type == 'Repair' AND ! empty($service_codes) AND $edit) { ?>
									<select id="repair_code_select" class="form-control input-sm select2" name="repair_code_id">
										<option selected="" value="null">- Select Status -</option>
										<?php 
											foreach($service_codes as $code):
												echo "<option value='".$code['id']."' ".($item_details['repair_code_id'] == $code['id'] ? 'selected' : '').">".$code['description']."\t".$code['code']."</option>";
											endforeach;
										?>
									</select>
					<?php } ?>
				</div>
				<div class="col-md-4 text-center">
					<h2 class="minimal"><?=$pageTitle;?></h2>
				</div>
				<div class="col-md-1">
				</div>
				<div class="col-md-2">
						<?php if(! $quote){ ?>
								<?php if(strtolower($type) == 'repair'){ ?>
									<select name="task" class="form-control repair-task-selector task_selection pull-right" data-noreset="true">
										<option selected><?=$order_number_details;?>-<?=$item_details['line_number'];?> <?=getCompany($ORDER['companyid']);?></option>
									</select>
								<?php } else { ?>
									<select name="task" class="form-control service-task-selector task_selection pull-right" data-noreset="true">
										<option selected><?= $item_details['task_name'].' '.$order_number_details; ?></option>
									</select>
								<?php  } ?>
						<?php } ?>
				</div>
				<div class="col-md-1 text-right">
					<?php if ($quote) { ?>
						<?php if(empty($task_number)) { ?>
							<a href="#" class="btn btn-success btn-md quote_order pull-right" data-type="quote"><i class="fa fa-floppy-o" aria-hidden="true"></i> Save</a>
						<?php } else { ?>
							<a href="#" class="btn btn-success btn-md save_quote_order pull-right" data-type="save"><i class="fa fa-floppy-o" aria-hidden="true"></i> Save</a>
						<?php } ?>
					<?php } else if($task_edit) { ?>
						<a href="#" class="btn btn-success toggle-save"><i class="fa fa-floppy-o" aria-hidden="true"></i> Save</a>
					<?php } else if(! $ticketStatus) { ?>
						<button class="btn btn-success btn-sm btn-update" data-toggle="modal" data-target="#modal-complete">
							<i class="fa fa-save"></i> Complete
						</button>
					<?php } else if($manager_access AND strtolower($type) == 'service') { ?>
						<button class="btn btn-success btn-sm btn-update" data-toggle="modal" data-target="#modal-complete">
							<i class="fa fa-save"></i> Change Status
						</button>
					<?php } ?>
				</div>
			</div>

		<div class="container-fluid data-load full-height" style="margin-top:48px">
			<form id="save_form" action="/task_edit.php" method="post" enctype="multipart/form-data">
				<input type="hidden" name="<?=($quote ? 'quote' : 'service');?>_item_id" value="<?=$item_id;?>">
				<input type="hidden" name="order" value="<?=$order_number;?>">
				<input type="hidden" name="line_number" value="<?=$task_number;?>">
				<input type="hidden" name="type" value="<?=$type;?>">
				<div class="row" style="height: 100%; margin: 0;">
					
					<?php include 'sidebar.php'; ?>

						<div class="hidden-md hidden-lg table-responsive">
							<table class="table table-condensed">
								<tr>
									<td class="col-xs-4">
<?php
	if ($clock['taskid']==$item_id) {
		$rp_cls = 'default btn-clock';
		$rp_title = 'Switch to Regular Pay';
		$tt_cls = 'default btn-clock';
		$tt_title = 'Switch to Travel Time';
		if ($clock['rate']==11) {
			$tt_cls = 'warning';
			$tt_title = 'Clocked In';
		} else {
			$rp_cls = 'primary';
			$rp_title = 'Clocked In';
		}
		echo '
			<button class="btn btn-'.$rp_cls.'" type="button" data-type="clock" data-clock="in" data-toggle="tooltip" data-placement="bottom" title="'.$rp_title.'"><i class="fa fa-briefcase"></i></button>
			<button class="btn btn-'.$tt_cls.'" type="button" data-type="travel" data-clock="in" data-toggle="tooltip" data-placement="bottom" title="'.$tt_title.'"><i class="fa fa-car"></i></button>
		';
	} else if ($clock['taskid']) {
		echo '
			<a class="btn btn-default" href="service.php?order_type=Service&order_number='.getItemOrder($clock['taskid'], $clock['task_label']).'" data-toggle="tooltip" data-placement="bottom" title="Clocked In"><i class="fa fa-clock-o"></i> '.getItemOrder($clock['taskid'], $clock['task_label'], true).'</a>
		';
	} else {
		echo '
			<button class="btn btn-danger" type="button" data-toggle="tooltip" data-placement="bottom" title="Not Clocked In"><i class="fa fa-close"></i></button>
		';
	}
?>
									</td>
									<td class="col-xs-4 text-center">
										<h3><?=$pageTitle;?></h3>
									</td>
									<td class="col-xs-8">
										<select name="task2" class="form-control service-task-selector task_selection" data-noreset="true">
											<option selected><?= $item_details['task_name'].' '.$order_number_details; ?></option>
										</select>
									</td>
								</tr>
							</table>
						</div>
					<div id="pad-wrapper" >

					<?php if (! $assigned AND $U['hourly_rate'] AND in_array("8", $USER_ROLES)) { ?>
						<div class="alert alert-default" style="padding:5px; margin:0px">
							<h3 class="text-center text-warning">You are not assigned to this task</h3>
						</div>
					<?php } ?>

						<?php
							if ($ticketStatus) {
								echo '
							
									<div class="alert alert-default" style="padding:5px; margin:0px">
										<h3 class="text-center">
											<span class="ticket_status_'.(strpos(strtolower($ticketStatus), 'unrepairable') !== false || strpos(strtolower($ticketStatus), 'voided') !== false || strpos(strtolower($ticketStatus), 'canceled') !== false ? 'danger' : (strpos(strtolower($ticketStatus), 'trouble') ? 'warning' : 'success')).'">' .ucwords($ticketStatus) . '</span>
										</h3>
									</div>
							
								';
							}
						?>

						<?php
							$profit = false;
							$charge = false;
						?>
						<?php if(in_array("4", $USER_ROLES)){ ?>
							<br>
							<!-- Cost Dash for Management People Only -->
							<?php
								$charge = $item_details['qty']*$item_details[$T['amount']];
								$profit = $charge-$total_amount;
							?>

							<div id="main-stats">
					            <div class="row stats-row">
					                <div class="col-md-4 col-sm-4 stat">
					                    <div class="data" style="min-height: 35px;">
					                    	<?php if(! $quote) { ?>
					                        	<span class="number text-brown"><?=format_price($charge);?></span>
					                        	<span class="info">Charge</span>
					                        <?php } else { ?>
					                        		<div class="input-group pull-left" style="margin-left: 25px; max-width: 200px;">													
														<span class="input-group-addon">										                
															<i class="fa fa-usd" aria-hidden="true"></i>										            
														</span>										            
														<input class="form-control input-sm" type="text" name="amount" placeholder="0.00" value="<?=$charge?>">
													</div>
													<!-- <input class="form-control input-sm pull-left" type="text" style="margin-left: 25px; max-width: 150px;" name="amount" placeholder="0.00" value="<?=$charge;?>"> -->
											
					                        	<!-- <span class="info" style="margin-top: 15px; float: right;">Charge</span> -->
					                        <?php } ?>
					                    </div>
					                </div>
					                <div class="col-md-4 col-sm-4 stat">
					                    <div class="data">
					                        <span class="number text-black"><?=format_price($total_amount);?></span>
											<span class="info">Cost</span>
					                    </div>
					                </div>
<!--
					                <div class="col-md-3 col-sm-3 stat">
					                    <div class="data">
					                        <span class="number text-black">$0.00</span>
											<span class="info">Commission</span>
					                    </div>
					                </div>
-->
					                <div class="col-md-4 col-sm-4 stat last">
					                    <div class="data">
					                        <span class="number text-success"><?=format_price($profit);?></span>
											<span class="info">Profit</span>
					                    </div>
					                </div>
					            </div>
					        </div>

				        <?php } ?>

				        <br>

				        <!-- Begin all the tabs in the page -->
				        <ul class="nav nav-tabs nav-tabs-ar">
				        	<?php if($activity) {
					        	echo '<li class="'.(($tab == 'activity' OR ($activity && empty($tab))) ? 'active' : '').'"><a href="#activity" data-toggle="tab"><span class="hidden-xs hidden-sm"><i class="fa fa-folder-open-o fa-lg"></i> Activity</span><span class="hidden-md hidden-lg"><i class="fa fa-folder-open-o fa-2x"></i></span></a></li>';
				        	} 
				        	if($details) { 
					        	echo '<li class="'.(($tab == 'details' OR (! $activity && empty($tab))) ? 'active' : '').'"><a href="#details" data-toggle="tab"><span class="hidden-xs hidden-sm"><i class="fa fa-list fa-lg"></i> Details</span><span class="hidden-md hidden-lg"><i class="fa fa-list fa-2x"></i></span></a></li>';
					        } 
				        	if($documentation) { 
					        	echo '<li class="'.($tab == 'documentation' ? 'active' : '').'"><a href="#documentation" data-toggle="tab"><span class="hidden-xs hidden-sm"><i class="fa fa-file-pdf-o fa-lg"></i> Documentation</span><span class="hidden-md hidden-lg"><i class="fa fa-file-pdf-o fa-2x"></i></span></a></li>';
					        } 
					        if($labor) {
								echo '<li class="'.($tab == 'labor' ? 'active' : '').'"><a href="#labor" data-toggle="tab"><span class="hidden-xs hidden-sm"><i class="fa fa-users fa-lg"></i> Labor <span class="labor_cost">'.((in_array("4", $USER_ROLES)) ?'&nbsp; '.format_price($labor_cost).'':'').'</span></span><span class="hidden-md hidden-lg"><i class="fa fa-users fa-2x"></i></span></a></li>';
							} 
							if($materials) { 
								echo '<li class="'.($tab == 'materials' ? 'active' : '').'"><a href="#materials" data-toggle="tab"><span class="hidden-xs hidden-sm"><i class="fa fa-microchip fa-lg"></i> Materials <span class="materials_cost">'.((in_array("4", $USER_ROLES)) ?'&nbsp; '.format_price($materials_total).'':'').'</span></span><span class="hidden-md hidden-lg"><i class="fa fa-microchip fa-2x"></i></span></a></li>';
							} 
							if($expenses) {
								echo '<li class="'.($tab == 'expenses' ? 'active' : '').'"><a href="#expenses" data-toggle="tab"><span class="hidden-xs hidden-sm"><i class="fa fa-credit-card fa-lg"></i> Expenses <span class="expenses_cost">'.((in_array("4", $USER_ROLES)) ?'&nbsp; '.format_price($expenses_total).'':'').'</span></span><span class="hidden-md hidden-lg"><i class="fa fa-credit-card fa-2x"></i></span></a></li>';
							} 
							if($outside) {
								echo '<li class="'.($tab == 'outside' ? 'active' : '').'"><a href="#outside" data-toggle="tab"><span class="hidden-xs hidden-sm"><i class="fa fa-suitcase fa-lg"></i> Outside Services <span class="outside_cost">'.((in_array("4", $USER_ROLES)) ?'&nbsp; '.format_price($outside_services_total).'':'').'</span></span><span class="hidden-md hidden-lg"><i class="fa fa-suitcase fa-2x"></i></span></a></li>';
							}
							if($images) {
								echo '<li class="'.($tab == 'images' ? 'active' : '').'"><a href="#images" data-toggle="tab"><span class="hidden-xs hidden-sm"><i class="fa fa-file-image-o fa-lg" aria-hidden="true"></i> Images</span><span class="hidden-md hidden-lg"><i class="fa fa-file-image-o fa-2x"></i></span></a></li>';
							} ?>
							<?php if(in_array("4", $USER_ROLES)){ ?>
								<li class="pull-right"><a href="#"><strong><i class="fa fa-shopping-cart"></i> Total &nbsp; <span class="total_cost"><?=format_price($total_amount);?></span></strong></a></li>
							<?php } ?>
						</ul>

						<div class="tab-content">

							<!-- Activity pane -->
							<?php if($activity) { ?>
								<div class="tab-pane <?=(($tab == 'activity' OR ($activity && empty($tab))) ? 'active' : '');?>" id="activity">
									<div class="table-responsive"><table class="table table-condensed ">
										<tr>
											<td class="col-sm-12">
												<div class="input-group">
													<input type="text" name="notes" class="form-control input-sm" placeholder="Notes...">
													<span class="input-group-btn">
														<button class="btn btn-sm btn-primary" type="submit" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Save Entry"><i class="fa fa-save"></i></button>
													</span>
												</div>
											</td>
										</tr>
									</table></div>

									<div class="table-responsive"><table class="table table-condensed table-striped table-hover">
										<thead>
										<tr>
											<th class="col-sm-2">Date/Time</th>
											<th class="col-sm-4"><span class="line"></span> Tech</th>
											<th class="col-sm-5"><span class="line"></span> Activity</th>
											<th class="col-sm-1"><span class="line"></span> Notify</th>
										</tr>
										</thead>

										<?php
											foreach($activity_data as $activity_row):
										?>
										<tr>
											<td class="col-sm-2"><?=format_date($activity_row['datetime'], 'n/j/y g:ia');?></td>
											<td class="col-sm-4">
												<?php
													$contact = getContact($activity_row['techid'], 'userid');
													$names = explode(' ',$contact);
													echo '<span class="hidden-md hidden-lg">'.ucfirst(substr($names[0],0,1)).' '.$names[1].'</span><span class="hidden-sm hidden-xs">'.$contact.'</span>';
												?>
											</td>
											<td class="col-sm-5">
												<?=$activity_row['notes'];?>
											</td>
											<td class="col-sm-1">
												<?php if($activity_row['id']) { 
													if(checkNotification($activity_row['id'])) {
													 echo '<a href="javascript:void(0);" class="pull-right forward_activity" data-activityid="'.$activity_row['id'].'"><i class="fa fa-envelope-o" aria-hidden="true"></i></a>';
													}
												} ?>
											</td>
										</tr>
										<?php endforeach; ?>
									</table></div>
								</div><!-- Activity pane -->
							<?php } ?>

							<!-- Details pane -->
							<?php if($details) { ?>
								<div class="tab-pane <?=(($tab == 'details' OR (! $activity && empty($tab))) ? 'active' : '');?>" id="details">
									<div class="table-responsive"><table class="table table-condensed table-striped table-hover">
										<thead>
										<tr>
											<th class="col-sm-3"><?=(strtolower($type) == 'repair' ? 'Description' : 'Site')?> &nbsp;</th>
											<?php if (strtolower($type)=='repair') { ?>
											<th class="col-sm-2"><span class="line"></span> Serial(s)</th>
											<th class="col-sm-2"><span class="line"></span> RMA#</th>
											<th class="col-sm-2"><span class="line"></span> Refs</th>
											<?php } ?>
											<th class="col-sm-3"><span class="line"></span> Notes</th>
										</tr>
										</thead>
										<?php if(! $quote && strtolower($type) == 'repair') { ?>
											<tr>
												<td><?=trim(partDescription($item_details['partid'], true));?></td>
												<td>
													<?php foreach(getDetails($item_id) as $serial) {
														echo $serial;
													} ?>
												</td>
												<td>
												</td>
												<td>
													<?=$item_details['ref_1_label'].' '.$item_details['ref_1'].'<BR>';?>
													<?=$item_details['ref_2_label'].' '.$item_details['ref_2'].'<BR>';?>
												</td>
												<td><?=str_replace(chr(10),'<BR>',$item_details['notes']);?></td>
											</tr>
										<?php } else if (! $quote && $type == 'Service' && $item_details['item_label']=='addressid') { ?>
											<tr>
												<td><?=format_address($item_details['item_id'], '<br/>', true, '', $ORDER['companyid']);?></td>
												<td>
													<?=$item_details['description'];?>		
												</td>
											</tr>
										<?php } ?>

										<?php if($quote OR $new) { ?>
											<tr>
												<td class="part-container">
													<?php
														include_once $_SERVER["ROOT_DIR"].'/inc/buildDescrCol.php';
														include_once $_SERVER["ROOT_DIR"].'/inc/setInputSearch.php';
														include_once $_SERVER["ROOT_DIR"].'/inc/getItems.php';
														include_once $_SERVER["ROOT_DIR"].'/inc/detectDefaultType.php';
														$EDIT = true;

														$P = array();

														$P['name'] = format_address($item_details['item_id'],', ',true,'');
														$P['id'] = $item_details['item_id'];

														$id = false;
														$items = getItems($T['item_label']);
														$def_type = detectDefaultType($items);
													?>
													
													<?= buildDescrCol($P,$id,$def_type,$items); ?>
													<?= setInputSearch($def_type); ?>
												</td>
												<td><textarea class="form-control" name="description" rows="3" placeholder="Scope"><?=$item_details['description']?></textarea></td>
											</tr>
										<?php } ?>
									</table></div>
								</div><!-- Details pane -->
							<?php } ?>

							<?php if($documentation) { ?>
								<!-- Documentation pane -->
								<div class="tab-pane <?=($tab == 'documentation' ? 'active' : '');?>" id="documentation">
									<div class="table-responsive"><table class="table table-condensed table-striped table-hover">
										<thead>
											<tr>
												<th>Date/Time</th>
												<th><span class="line"></span> Notes</th>
												<th><span class="line"></span> Type</th>
												<th><span class="line"></span> File</th>
												<th><span class="line"></span> Action</th>
											</tr>
										</thead>

											<tbody>
												<?php foreach($documentation_data as $document) { ?>
													<tr>
														<td><?=format_date($document['datetime']);?></td>
														<td><?=$document['notes'];?></td>
														<td><?=$document['type'];?></td>
														<td>
															<span class="file_name" style="<?=$document['filename'] ? 'margin-right: 5px;' : '';?>"><a href="<?=str_replace($TEMP_DIR,'uploads/',$document['filename']);?>"><?=substr($document['filename'], strrpos($document['filename'], '/') + 1);?></a></span>
														</td>
														<td><input type="checkbox" name="copZip[<?=$document['id'];?>]" class="pull-right"></td>
													</tr>
												<?php } ?>
												<tr>
													<td></td>
													<!-- <td class="datetime">																			
														<div class="form-group" style="margin-bottom: 0; width: 100%;">												
															<div class="input-group datepicker-date date datetime-picker" style="min-width: 100%; width: 100%;" data-format="MM/DD/YYYY">										            
																<input type="text" name="documentation[date]" class="form-control input-sm" value="">										            
																<span class="input-group-addon">										                
																	<span class="fa fa-calendar"></span>										            
																</span>										        
															</div>											
														</div>																		
													</td> -->
													<td><input class="form-control input-sm" type="text" name="documentation[notes]"></td>
													<td>
														<select class="form-control input-sm select2" name="documentation[type]">
															<option value="">- Select Type -</option>
															<option value="MOP">MOP</option>
															<option value="SOW">SOW</option>
															<option value="COP">COP</option>
														</select>
													</td>
													<td class="file_container">
														<input type="file" class="upload" name="files" accept="image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,*.htm,*.html,*.xml" value="">
														<a href="#" class="upload_link btn btn-default btn-sm">
															<i class="fa fa-folder-open-o" aria-hidden="true"></i> Browse...
														</a>
													</td>
													<td style="cursor: pointer;">
														<button class="btn btn-primary btn-sm pull-right" type="submit">
												        	<i class="fa fa-upload"></i>	
												        </button>

												      <!--   <a href="#" class="pull-right" style="margin-right: 15px; margin-top: 7px;"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a> -->
													</td>
												</tr>
											</tbody>
										</table></div>
										<button class="btn btn-success btn-sm pull-right" type="submit">
								        	Generate Closeout
								        </button>
								        <br>
									</section>

									<?php if($closeout) { ?>

									<?php } ?>
								</div><!-- Documentation pane -->
							<?php } ?>
							
							<?php if($labor) { ?>
								<!-- Labor pane -->
								<div class="tab-pane <?=($tab == 'labor' ? 'active' : '');?>" id="labor">
									<?php if($task_edit){ ?>
										<div class="table-responsive"><table class="table table-condensed table-striped table-hover">
											<tr>
											<td class="col-md-12">
												<div class="input-group pull-left" style="margin-bottom: 10px; margin-right: 15px; max-width: 200px">
						  							<!-- <span class="input-group-addon">$</span> -->
													<input class="form-control input-sm labor_hours" name="labor_hours" type="text" placeholder="Hours" value="<?=$item_details['labor_hours'];?>">
												</div>
												<div class="input-group" style="margin-bottom: 10px; max-width: 200px">
						  							<span class="input-group-addon">$</span>
													<input class="form-control input-sm labor_rate" name="labor_rate"  type="text" placeholder="Rate" value="<?=number_format((float)$item_details['labor_rate'], 2, '.', '');?>">
												</div>
											</td>
											</tr>
										</table></div>
									<?php } ?>

									<div class="table-responsive"><table class="table table-condensed table-striped table-hover">
				                        <thead class="no-border">
				                            <tr>
				                                <th class="col-sm-4">Tech</th>
				                                <th class="col-sm-2"><span class="line"></span> Start</th>
				                                <th class="col-sm-2"><span class="line"></span> End</th>
				                                <th class="col-sm-2"><span class="line"></span> Labor Time</th>
				                                <th class="col-sm-2 text-right">
				                                	<?php if(in_array("4", $USER_ROLES)){ ?>
					                                    <span class="line"></span> Cost
				                                	<?php } ?>
					                            </th>
				                                <!-- <th class="col-sm-2 text-center">
													<div data-toggle="tooltip" data-placement="left" title="" data-original-title="Tech Complete?"><i class="fa fa-id-badge"></i></div>
				                                </th>
				                                <th class="col-sm-1 text-center">
													<div data-toggle="tooltip" data-placement="left" title="" data-original-title="Admin Complete?"><i class="fa fa-briefcase"></i></div>
				                                </th> -->
				                            </tr>
				                        </thead>
				                        <tbody>
				                        	<?php 
				                        		$totalSeconds = 0;
				                        		if(! $quote && ! $new):
				                        		foreach($labor_data as $user => $data) { 
													//$cost = round($rate * $hours_worked, 2);
													$totalSeconds += $data['laborSeconds'];
				                        	?>
						                        	<tr class="labor_user valign-top <?=(! $data['status'] ? 'inactive' : '');?>">
						                                <td>
															<a href="timesheet.php?user=<?=$user;?>&taskid=<?=$item_id;?>"><?=getUser($user);?></a>
						                                </td>
						                                <td>
															<?= format_date($data['start_datetime'],'D, M j, Y g:ia'); ?>
						                                </td>
						                                <td>
															<?= format_date($data['end_datetime'],'D, M j, Y g:ia'); ?>
						                                </td>
						                                <td>
															<?=toTime($data['laborSeconds']);?><br> &nbsp; <span class="info"><?=timeToStr(toTime($data['laborSeconds']));?></span>
						                                </td>
						                                <td class="text-right">
						                                	<?php if(in_array("4", $USER_ROLES)){ ?>
																<?=format_price($data['cost']);?>
															<?php } ?>
						                                </td>
<!--
						                                <td class="text-center">
						                                	<?php if(in_array("4", $USER_ROLES) && $data['status']){ ?>
							                                	<button type="submit" class="btn btn-primary btn-sm pull-right" name="tech_status" value="<?=$user;?>">
														        	<i class="fa fa-trash" aria-hidden="true"></i>
														        </button>
													        <?php } else { ?>
													        	<i title="In Active" class="fa fa-user-times pull-right" style="color:#d9534f; margin-top: 10px; margin-right: 10px;"></i>
													       	<?php } ?>
						                                </td>
-->
						                            </tr>
				                            <?php 
				                        		} 
				                        		endif;
				                        	?>

				                            <?php if(in_array("4", $USER_ROLES)){ ?>
					                            <tr>
					                            	<td>
					                            		<select name="techid" class="form-control input-xs tech-selector required"></select>
				                            		</td>
					                            	<td>
								                <div class="input-group datepicker-datetime date datetime-picker" data-hposition="right">
					   		    			         <input type="text" name="start_datetime" id="start-date" class="form-control input-sm" value="<?php echo $start_datetime; ?>" />
					           		       			 <span class="input-group-addon">
							       		                 <span class="fa fa-calendar"></span>
					       					         </span>
												</div>
													</td>
					                            	<td>
								                <div class="input-group datepicker-datetime date datetime-picker" data-hposition="right">
					   		    			         <input type="text" name="end_datetime" id="end-date" class="form-control input-sm" value="<?php echo $end_datetime; ?>" />
					           		       			 <span class="input-group-addon">
							       		                 <span class="fa fa-calendar"></span>
					       					         </span>
												</div>
													</td>
					                            	<td>
												    </td>
					                            	<td class="text-right">
					                            		<button type="submit" class="btn btn-success btn-sm add_tech" <?=(($quote && empty($task_number)) ? 'disabled' : '');?>>
												        	<i class="fa fa-plus"></i>	
												        </button>
													</td>
					                            </tr>
				                            <?php } ?>
				                            <!-- row -->
				                            <?php if(in_array("4", $USER_ROLES) AND $labor_total>0){ ?>
											<?php
												$labor_profit = ($labor_total-$labor_cost);
												$labor_progress = 100*round(($labor_cost/$labor_total),2);

												$progress_bg = '';
												if ($labor_progress>=100) { $progress_bg = 'bg-danger'; }
												else if ($labor_progress<50) { $progress_bg = 'bg-success'; }
											?>
					                            <tr class="first">
					                                <td>
														<div class="progress progress-lg">
															<div class="progress-bar <?=$progress_bg;?>" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: <?=$labor_progress;?>%"><?=$labor_progress;?>%</div>
														</div>
<!--
														<?=format_price($profit);?> profit of <span class="labor_cost"><?=format_price($labor_total);?></span> quoted Labor
-->
														<?=format_price($labor_cost);?> labor used from <span class="labor_cost"><?=format_price($labor_total);?></span> quoted labor
					                                </td>
					                                <td>
														<strong><?=toTime($totalSeconds);?> &nbsp; </strong>
					                                </td>
					                                <td class="text-right">
					                                    <strong><?=format_price($labor_cost)?></strong>
					                                </td>
					                            </tr>
				                            <?php } ?>
										</tbody>
									</table></div>
								</div><!-- Labor pane -->
							<?php } ?>

							<!-- Materials pane -->
							<?php if($materials) { ?>
								<div class="tab-pane <?=($tab == 'materials' ? 'active' : '');?>" id="materials">
									<section>
										<div class="row">
											<div class="col-sm-6">
									
											</div>
											<div class="col-sm-6">
												<?php if(! $quote AND ! $new) { ?>
													<button style="margin-bottom: 10px;" data-toggle="modal" type="button" data-target="#modal-component" class="btn btn-success btn-sm pull-right modal_request">
											        	<i class="fa fa-plus"></i>	
											        </button>
										        <?php } ?>
									        </div>
										</div>

										<div class="table-responsive"><table class="table table-condensed table-striped table-hover">
											<thead>
												<tr>
													<?php if($quote OR $new){ ?>
														<th class="col-md-3">Material</th>
														<th class="col-md-2">Amount</th>
														<th class="col-md-1">Supply</th>
														<th class="col-md-3">Leadtime</th>
														<th>Profit %</th>
														<th>Quote</th>
														<th></th>
													<?php } else { ?>
														<th class="col-md-3">Material</th>
														<th class="col-md-1"><span class="hidden-md hidden-lg">Reqd</span><span class="hidden-xs hidden-sm">Requested</span></th>
														<th class="col-md-2">Source</th>
														<th class="col-md-1"><span class="hidden-md hidden-lg">Avail</span><span class="hidden-xs hidden-sm">Available</span></th>
														<th class="col-md-1">Pulled</th>
														<th class="col-md-2 text-right"><span class="hidden-md hidden-lg">Per</span><span class="hidden-xs hidden-sm">Unit Price</span></th>
														<th class="col-md-2 text-right"><span class="hidden-md hidden-lg">Ext</span><span class="hidden-xs hidden-sm">Ext Price</span></th>
													<?php } ?>
												</tr>
											</thead>

											<tbody <?=($quote ? 'id="quote_body"' : '');?>>
												<?php 
													$total = 0; 

													foreach($component_data as $row){ 
													$price = 0;
													$ext = 0;

													if($row['po_number'] && strtolower($type) == 'repair') {
														$query = "SELECT rc.qty, (c.actual/i.qty) price, po.status ";
														$query .= "FROM repair_components rc, inventory_history h, purchase_items pi, purchase_orders po, purchase_requests pr, inventory i ";
														$query .= "LEFT JOIN inventory_costs c ON i.id = c.inventoryid ";
														$query .= "WHERE po.po_number = ".prep($row['po_number'])." AND pr.partid = ".prep($row['partid'])." ";
														$query .= "AND po.po_number = pi.po_number AND po.po_number = pr.po_number AND pr.partid = pi.partid AND pr.ro_number = $order_number ";
														$query .= "AND rc.ro_number = pr.ro_number ";
														$query .= "AND h.value = pi.id AND h.field_changed = 'purchase_item_id' AND h.invid = i.id AND i.id = rc.invid ";
														$query .= "GROUP BY i.id; ";
														$result = qdb($query) OR die(qe().'<BR>'.$query);

														if (mysqli_num_rows($result)>0) {
															$query_row = mysqli_fetch_assoc($result);
															$status = $query_row['status'];
															$price = $query_row['price'];
															if($status == 'Active') {
																$ordered = $query_row['qty'];
															}
														}
														$ext = ($price * $ordered);
														// dl 12-11-17 because of addition in getMaterials()
														//$materials_total += $ext;
													}
												?>
													<?php if(! $quote AND ! $new) { ?>
														<tr class="list">
															<td>
																<span class="descr-label part_description" data-request="<?=$row['totalOrdered'];?>"><?=trim(partDescription($row['partid'], true));?></span>
															</td>
															<td><?=$row['totalOrdered'];?></td>
															<td>
																<?php
																	if($row['po_number']) {
																		echo $row['po_number'].' <a href="/PO'.$row['po_number'].'"><i class="fa fa-arrow-right"></i></a>';
																	} else if ($row['status'] == 'Void') {
																		echo "<span style='color: red;'>Canceled</span>";
																	} else if(($row['totalOrdered'] - $row['pulled'] > 0)) {
																		echo "<span style='color: #8a6d3b;'>Pending</span> <a target='_blank' href='/purchase_requests.php'><i class='fa fa-arrow-right'></i></a>";
																	} else if(($row['totalOrdered'] - $row['pulled'] <= 0)) {
																		echo "<span style='color: #3c763d;'>Pulled from Stock</span>";
																	} else if($row['status'] == 'Void') {
																		echo "<span style='color: #a94442;'>Canceled</span>";
																	}
																?>
															</td>
															<td><?=$row['available'];?></td>
															<td>
																<?=$row['totalReceived'];?> 
																<?php
																	if(($row['totalOrdered'] - $row['totalReceived']) > 0 && $row['available']) {
																		echo '&emsp;<a href="#" class="btn btn-default btn-sm text-info pull_part" data-type="'.$_REQUEST['type'].'" data-itemid="'.$item_id.'" data-partid="'.$row['partid'].'"><i class="fa fa-download" aria-hidden="true"></i> Pull</a>';
																	}
																?>
															</td>
															<td class="text-right"><?=format_price($price)?></td>
															<td class="text-right"><?=format_price($ext)?></td>
														</tr>
													<?php } else { ?>
														<tr class="part_listing found_parts_quote" style="overflow:hidden;" data-quoteid="<?=$row['id'];?>">
															<td>
																<div class="remove-pad col-md-1">
																	<div class="product-img">
																		<img class="img" src="/img/parts/ER-B.jpg" alt="pic" data-part="ER-B">
																	</div>										
																</div>
																<div class="col-md-11">
																	<span class="descr-label part_description" data-request="<?=$row['qty'];?>"><?=trim(partDescription($row['partid'], true));?></span>
																</div>
															</td>
															<td>										
																<div class="col-md-4 remove-pad" style="padding-right: 5px;">											
																	<input class="form-control input-sm part_qty" type="text" name="qty" data-partid="<?=$row['partid'];?>" placeholder="QTY" value="<?=$row['qty'];?>">										
																</div>										
																<div class="col-md-8 remove-pad">											
																	<div class="form-group" style="margin-bottom: 0;">												
																		<div class="input-group">													
																			<span class="input-group-addon">										                
																				<i class="fa fa-usd" aria-hidden="true"></i>										            
																			</span>										            
																			<input class="form-control input-sm part_amount" type="text" name="amount" placeholder="0.00" value="<?=number_format((float)$row['amount'], 2, '.', '');?>">										        
																		</div>											
																	</div>										
																</div>									
															</td>
															<td style="background: #FFF;">
																<div class="table market-table" data-partids="<?=$row['partid'];?>">
																	<div class="bg-availability">
																		<a href="javascript:void(0);" class="market-title modal-results" data-target="marketModal" data-title="Supply Results" data-type="supply">
																			Supply <i class="fa fa-window-restore"></i>
																		</a>
																		<a href="javascript:void(0);" class="market-download" data-toggle="tooltip" data-placement="top" title="" data-original-title="force re-download">
																			<i class="fa fa-download"></i>
																		</a>
																		<div class="market-results" id="<?=$row['partid'];?>" data-ln="0" data-type="supply">
																		</div>
																	</div>
																</div>
															</td>
															<td class="datetime">										
																<div class="col-md-2 remove-pad">											
																	<input class="form-control input-sm date_number" type="text" name="leadtime" data-partid="<?=$row['partid'];?>" data-stock="2" placeholder="#" value="<?=$row['leadtime'];?>">										
																</div>										
																<div class="col-md-4">											
																	<select class="form-control input-sm date_span">												
																		<option value="days" <?=($row['leadtime_span'] == 'Days' ? 'selected' : '');?>>Days</option>												
																		<option value="weeks" <?=($row['leadtime_span'] == 'Weeks' ? 'selected' : '');?>>Weeks</option>												
																		<option value="months" <?=($row['leadtime_span'] == 'Months' ? 'selected' : '');?>>Months</option>											
																	</select>										
																</div>										
																<div class="col-md-6 remove-pad">											
																	<div class="form-group" style="margin-bottom: 0; width: 100%;">												
																		<div class="input-group datepicker-date date datetime-picker" style="min-width: 100%; width: 100%;" data-format="MM/DD/YYYY">										            
																			<input type="text" name="delivery_date" class="form-control input-sm delivery_date" value="">										            
																			<span class="input-group-addon">										                
																				<span class="fa fa-calendar"></span>										            
																			</span>										        
																		</div>											
																	</div>										
																</div>									
															</td>
															<td>
																<div class="form-group" style="margin-bottom: 0;">										
																	<div class="input-group">								            
																		<input type="text" class="form-control input-sm part_perc" value="<?=number_format((float)$row['profit_pct'], 2, '.', '');?>" placeholder="0">								            
																		<span class="input-group-addon">								                
																			<i class="fa fa-percent" aria-hidden="true"></i>								            
																		</span>								        
																	</div>									
																</div>
															</td>
															<td>
																<div class="form-group" style="margin-bottom: 0;">										
																	<div class="input-group">											
																		<span class="input-group-addon">								                
																			<i class="fa fa-usd" aria-hidden="true"></i>								           
																		</span>								            
																		<input type="text" placeholder="0.00" class="form-control input-sm quote_amount" value="<?=number_format((float)$row['quote'], 2, '.', '');?>">								        
																	</div>									
																</div>
															</td>
															<td class="remove_part" style="cursor: pointer;"><i class="fa fa-trash fa-4" aria-hidden="true"></i></td>
														</tr>
													<?php } ?>
												<?php } ?>

												<tr id='quote_input'>
													<?php if($quote OR $new) { ?>
														<td colspan="5">
															<div class='input-group' style="width: 100%;">
			                                                    <input type='text' class='form-control input-sm' id='partSearch' autocomplete="off" placeholder='SEARCH FOR MATERIAL...'>
			                                                    <span class='input-group-btn'>
			                                                        <button class='btn btn-sm btn-primary li_search_button'><i class='fa fa-search'></i></button>              
			                                                    </span>
			                                                </div>
			                                            </td>
			                                            <!-- <td colspan="2"></td> -->
		                                            <?php } else { ?>
		                                            	<td colspan="6"></td>
		                                            <?php } ?>
													<td class="text-right" <?=($quote ? 'colspan="2"' : '');?>>
														<strong><?=($quote ? 'Quote' : '');?>
														<?=(in_array("4", $USER_ROLES) ? 'Total:</strong> <span class="materials_cost">'.format_price($materials_total).'</span>' : '</strong>');?>
													</td>
												</tr>
											</tbody>
										</table></div>

									</section>
								</div><!-- Materials pane -->
							<?php } ?>

							<!-- Expenses pane -->
							<?php if($expenses) { ?>
								<div class="tab-pane <?=($tab == 'expenses' ? 'active' : '');?>" id="expenses">
									<section>
										<div class="row">
											<div class="col-sm-6">
												<?php if($task_edit) { ?>
													<div class="input-group" style="margin-bottom: 10px; max-width: 250px">
							  							<span class="input-group-addon"><i class="fa fa-car" aria-hidden="true"></i></span>
														<input class="form-control input-sm" class="mileage_rate" name="expenses" type="text" placeholder="Price" value="<?=number_format((float)$item_details['expenses'], 2, '.', '');?>">
													</div>
												<?php } else { ?>
													<b>Mileage Rate</b>: <span class="mileage_rate"><?=format_price($item_details['mileage_rate']);?></span>
												<?php } ?>
											</div>
											<div class="col-sm-6">
												
									        </div>
										</div>

										<div class="table-responsive"><table class="table table-striped">
											<thead class="table-first">
												<th class="col-md-2">Date/Time</th>
												<th class="col-md-2">User</th>
												<th class="col-md-5">Notes</th>
												<th class="col-md-2">Amount</th>
												<th class="col-md-1">Action</th>
											</thead>

											<tbody>
												<tr>
													<td class="datetime">																			
														<div class="form-group" style="margin-bottom: 0; width: 100%;">												
															<div class="input-group datepicker-date date datetime-picker" style="min-width: 100%; width: 100%;" data-format="MM/DD/YYYY">										            
																<input type="text" name="expense[date]" class="form-control input-sm" value="">										            
																<span class="input-group-addon">										                
																	<span class="fa fa-calendar"></span>										            
																</span>										        
															</div>											
														</div>																		
													</td>
													<td>
					                            		<select name="expense[techid]" class="form-control input-xs user-selector required"></select>
				                            		</td>
													<td><input class="form-control input-sm" type="text" name="expense[description]"></td>
													<td>
														<div class="input-group">													
															<span class="input-group-addon">										                
																<i class="fa fa-usd" aria-hidden="true"></i>										            
															</span>										            
															<input class="form-control input-sm part_amount" type="text" name="expense[amount]" placeholder="0.00" value="">
														</div>
													</td>
													<td style="cursor: pointer;">
														<button class="btn btn-success btn-sm btn-status pull-right" type="submit">
												        	<i class="fa fa-plus"></i>	
												        </button>
													</td>
												</tr>
											</tbody>
										</table></div>
									</section>
								</div><!-- Expenses pane -->
							<?php } ?>

							<?php if($outside) { ?>
								<!-- Outside Services pane -->
								<div class="tab-pane <?=($tab == 'outside' ? 'active' : '');?>" id="outside">
				                    <div class="table-responsive"><table class="table table-hover table-condensed">
				                        <thead class="no-border">
				                            <tr>
				                                <th class="col-md-2">
				                                    Vendor
				                                </th>
				                                <th class="col-md-3">
				                                    Description
				                                </th>
				                                <th class="col-md-2">
				                                    Amount
				                                </th>
				                                <th class="col-md-2">
				                                    Profit %
				                                </th>
				                                <th class="col-md-2">
				                                    Total
				                                </th>
				                                 <th class="col-md-1 text-right">
				                                    Action
				                                </th>
				                            </tr>
				                        </thead>
				                        <tbody id="os_table">
				                        	<?php $line_number = 1; foreach($outsourced as $list) { ?>
				                        		<tr class="outsourced_row" data-line="<?=$line_number;?>">
				                            		<td>
				                            			<input type="hidden" name="outsourced[<?=$line_number;?>][quoteid]" value="<?=$list['id'];?>">
					                            		<select name="outsourced[<?=$line_number;?>][companyid]" class="form-control input-xs company-selector required">
					                            			<option value="<?=$list['companyid'];?>"><?=getCompany($list['companyid']);?></option>
					                            		</select>
				                            		</td>
													<td>
														<input class="form-control input-sm" type="text" name="outsourced[<?=$line_number;?>][description]" value="<?=$list['description'];?>">
													</td>
													<td>
														<div class="input-group">													
															<span class="input-group-addon">										                
																<i class="fa fa-usd" aria-hidden="true"></i>										            
															</span>										            
															<input class="form-control input-sm os_amount" type="text" name="" placeholder="0.00" value="<?=$list['amount'];?>">
														</div>
													</td>
													<td>
														<div class="input-group">											  
															<input class="form-control input-sm os_amount_profit" type="text" name="" placeholder="0" value="">
															<span class="input-group-addon">										                
																<i class="fa fa-percent" aria-hidden="true"></i>										            
															</span>	
														</div>
													</td>
													<td>
														<div class="input-group">													
															<span class="input-group-addon">										                
																<i class="fa fa-usd" aria-hidden="true"></i>										            
															</span>										            
															<input class="form-control input-sm os_amount_total" type="text" name="outsourced[<?=$line_number;?>][amount]" placeholder="0.00" value="<?=$list['amount'];?>">
														</div>
													</td>
													<td class="os_action">
														<i class="fa fa-trash fa-4 remove_outsourced pull-right" style="cursor: pointer; margin-top: 10px;" aria-hidden="true"></i>
													</td>
												</tr>
					                   
				                        	<?php $line_number ++; } ?>
				                        	<tr class="outsourced_row" data-line="<?=$line_number;?>">
			                            		<td class="select2_os">
				                            		<select name="outsourced[<?=$line_number;?>][companyid]" class="form-control input-xs company-selector required"></select>
			                            		</td>
												<td><input class="form-control input-sm" type="text" name="outsourced[<?=$line_number;?>][description]"></td>
												<td>
													<div class="input-group">													
														<span class="input-group-addon">										                
															<i class="fa fa-usd" aria-hidden="true"></i>										            
														</span>										            
														<input class="form-control input-sm os_amount" type="text" name="" placeholder="0.00" value="">
													</div>
												</td>
												<td>
													<div class="input-group">																		
														<input class="form-control input-sm os_amount_profit" type="text" name="" placeholder="0" value="">
														<span class="input-group-addon">										                
															<i class="fa fa-percent" aria-hidden="true"></i>										            
														</span>		
													</div>
												</td>
												<td>
													<div class="input-group">													
														<span class="input-group-addon">										                
															<i class="fa fa-usd" aria-hidden="true"></i>										            
														</span>										            
														<input class="form-control input-sm os_amount_total" type="text" name="outsourced[<?=$line_number;?>][amount]" placeholder="0.00" value="">
													</div>
												</td>
												<td class="os_action">
													<button class="btn btn-success btn-sm pull-right os_expense_add">
											        	<i class="fa fa-plus"></i>	
											        </button>
												</td>
											</tr>
				                            <tr class="">
				                                <td colspan="5">
				                                </td>
				                                <td class="text-right">
				                                    <strong><span class="outside_cost"><?=format_price($outside_services_total);?></span></strong>
				                                </td>
				                            </tr>
										</tbody>
									</table></div>
								</div><!-- Outside Services pane -->
							<?php } ?>

							<?php if($images) { ?>
								<div class="tab-pane <?=(($tab == 'images') ? 'active' : '');?>" id="images">
									<section>
										<div id="sticky-footer">
											<ul id="bxslider-pager">
												<li data-slideIndex="0">
													<a data-toggle="modal" href="#image-modal" class="imageDrop">
														<div class="dropImage" style="width: 200px; height: 200px; background: #E9E9E9;">
															<i class="fa fa-plus-circle" aria-hidden="true"></i>
														</div>
													</a>
												</li>
												<li data-slideIndex="1"><a href=""><img src="/img/sliderDemo/ff0099.png"></a></li>
												<li data-slideIndex="2"><a href=""><img src="/img/sliderDemo/ff0000.png"></a></li>
												<li data-slideIndex="3"><a href=""><img src="/img/sliderDemo/fff000.png"></a></li>
												<li data-slideIndex="4"><a href=""><img src="/img/sliderDemo/fff000.png"></a></li>
												<li data-slideIndex="5"><a href=""><img src="/img/sliderDemo/fff000.png"></a></li>
												<li data-slideIndex="6"><a href=""><img src="/img/sliderDemo/fff000.png"></a></li>
												<li data-slideIndex="7"><a href=""><img src="/img/sliderDemo/fff000.png"></a></li>
												<li data-slideIndex="8"><a href=""><img src="/img/sliderDemo/fff000.png"></a></li>
												<li data-slideIndex="9"><a href=""><img src="/img/sliderDemo/fff000.png"></a></li>
												<li data-slideIndex="10"><a href=""><img src="/img/sliderDemo/fff000.png"></a></li>
												<li data-slideIndex="11"><a href=""><img src="/img/sliderDemo/fff000.png"></a></li>
												<li data-slideIndex="12"><a href=""><img src="/img/sliderDemo/fff000.png"></a></li>
												<li data-slideIndex="13"><a href=""><img src="/img/sliderDemo/fff000.png"></a></li>
												<li data-slideIndex="14"><a href=""><img src="/img/sliderDemo/fff000.png"></a></li>
											</ul>
										</div>
									</section>
								</div><!-- Images pane -->
							<?php } ?>
						</div>
					</div><!-- pad-wrapper -->
				</div><!-- row -->
			</div> 
		</form>

		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script type="text/javascript" src="js/part_search.js"></script>
		<?php if(! $quote AND ! $new) { ?>
			<script type="text/javascript" src="js/lici.js"></script>
		<?php } ?>
		<script type="text/javascript" src="js/task.js"></script>
		<script type="text/javascript" src="js/imageCarousel.js"></script>
		<script src="js/imageSlider_services.js"></script>

		<script src="js/addresses.js?id=<?php echo $V; ?>"></script>
		<script src="js/item_search.js?id=<?php echo $V; ?>"></script>
	</body>
</html>
