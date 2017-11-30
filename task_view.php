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
	include_once $_SERVER["ROOT_DIR"] . '/inc/file_zipper.php';

	// List of subcategories
	$documentation = true;
	$details = true;
	$labor = true;
	$activity = true;
	$materials = true;
	$expenses = true;
	$closeout = true;
	$outside = true;

	$new = false;

	// Dynamic Variables Used
	$documentation_data = array();
	$activity_data = array();
	$materials_data = array();
	$expenses_data = array();
	$closeout_data = array();
	$labor_data = array();
	$repair_codes = array();
	$ticketStatus = '';

	$class = 'service';

	$materials_total = 0.00;
	$labor_total = 0.00;
	$expenses_total = 0.00;
	$outside_services_total = 0.00;
	$total_amount = 0.00;

	$item_id = 0;
	$item_details = array();
	$component_data = array();
	$outsourced = array();


	// These variables are soley used for ICO & CCO
	$OG_item_id = 0;
	$OG_component_data = array();
	$OG_outsourced = array();

	//print '<pre>' . print_r($ORDER, true) . '</pre>';

	// Disable the modules you want on the page here
	// if($type == 'installation') {

	// } else 
	if(strtolower($type) == 'service') {
		if(! empty($task_number)) {
			$item_id = getItemID($order_number, $task_number, 'service_items', 'so_number');
			$item_details = getItemDetails($item_id, 'service_items', 'id');
			$component_data = getMaterials($order_number, $item_id, $type, 'service_item_id');
			$outsourced = getOutsourced($item_id, $type);

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
				$labor_total += $cost['cost'];
			}
		} else {
			$new = true;
		}

		$total_amount = $materials_total + $labor_total + $expenses_total + $outside_services_total;

	} else if(strtolower($type) == 'repair') {
		if($ORDER['repair_code_id']) {
			$ticketStatus = getRepairCode($ORDER['repair_code_id']);
		}

		$query = "SELECT * FROM repair_codes;";

		$result = qdb($query) or die(qe() . ' ' . $query);

		while ($row = $result->fetch_assoc()) {
			$repair_codes[] = $row;
		}

		// Diable Modules for Repair
		$item_id = getItemID($order_number, $task_number, 'repair_items', 'ro_number');

		$documentation = false;
		$closeout = false;
		$expenses = false;
		$outside = false;

		$item_details = getItemDetails($item_id, 'repair_items', 'id');

		$activity_data = grabActivities($order_number, $item_id, $type);
		$component_data = getMaterials($order_number, $item_id, $type);
		getLaborTime($item_id, $type);

		foreach ($labor_data as $cost) {
			$labor_total += $cost['cost'];
		}

		$total_amount = $materials_total + $labor_total + $expenses_total + $outside_services_total;

	} else if($quote){

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

			print_r($component_data);

			$new = false;
		}

		$labor_total = $item_details['labor_hours'] * $item_details['labor_rate'];
		$expenses_total = $item_details['expenses'];
		$total_amount = $materials_total + $labor_total + $expenses_total + $outside_services_total;
	}

	// Get the current users access
	// Big or if the user is management then give them access no matter what assignment they possess
	// if((! accessControl($GLOBALS['U']['id'], $item_id) && ! $quote) && ! in_array("4", $USER_ROLES)){
	// 	echo "<script type='text/javascript'>alert('You do not have access to this task. Please contact management to get access.');</script>";
	// 	echo "<script>location.href='/';</script>";
	// }

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
				SELECT userid techid, datetime, notes FROM activity_log WHERE item_id = '".res($item_id)."' AND item_id_label = '".res($label)."'
				UNION
				SELECT '' as techid, i.date_created as datetime, CONCAT('Component Received ', `partid`, ' Qty: ', qty ) as notes FROM inventory i WHERE i.repair_item_id = ".prep($repair_item_id)." AND serial_no IS NULL
				UNION
				SELECT created_by as techid, created as datetime, CONCAT('".ucwords($type)." Order Created') as notes FROM repair_orders WHERE ro_number = ".prep($ro_number)."
				UNION
				SELECT userid as techid, date_created as datetime, CONCAT('Received ".ucwords($type)." Serial: <b>', serial_no, '</b>') as notes FROM inventory WHERE id in (SELECT invid FROM inventory_history where field_changed = 'repair_item_id' and `value` = ".prep($repair_item_id).") AND serial_no IS NOT NULL
				UNION
				SELECT '' as techid, datetime as datetime, CONCAT('Tracking# ', IFNULL(tracking_no, 'N/A')) as notes FROM packages WHERE order_number = ".prep($ro_number)." AND order_type = 'Repair'
				UNION
				SELECT '' as techid, datetime as datetime, CONCAT('<b>', part, '</b> pulled to Order') as notes FROM repair_components, inventory, parts WHERE ro_number = ".prep($ro_number)." AND inventory.id = repair_components.invid AND parts.id = inventory.partid
				UNION
				SELECT '' as techid, i.date_created as datetime, CONCAT('Component <b>', p.part, '</b> Received') FROM purchase_requests pr, purchase_items pi, parts p, inventory i WHERE pr.ro_number = ".prep($ro_number)." AND pr.po_number = pi.po_number AND pr.partid = pi.partid AND pi.qty <= pi.qty_received AND p.id = pi.partid AND i.purchase_item_id = pi.id
				UNION
				SELECT '' as techid, pr.requested as datetime, CONCAT('Component <b>', p.part, '</b> Requested') FROM purchase_requests pr, parts p WHERE pr.ro_number = ".prep($ro_number)."  AND pr.partid = p.id
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
		
		if($type == 'repair' OR strtolower($type) == 'service') {

			$query = "SELECT *, SUM(qty) as totalOrdered FROM purchase_requests WHERE item_id = ". prep($item_id) ." AND item_id_label = '".res($field)."' GROUP BY partid, po_number ORDER BY requested DESC;";
			$result = qdb($query) OR die(qe());
					
			while ($row = mysqli_fetch_assoc($result)) {

				// print_r($row);
				$qty = 0;
				$po_number = $row['po_number'];

				// Check to see what has been received and sum it into the total Ordered
				$query = "SELECT *, SUM(i.qty) as totalReceived FROM repair_components c, inventory i ";
				if ($po_number) { $query .= "LEFT JOIN purchase_items pi ON pi.id = i.purchase_item_id "; }
				$query .= "WHERE c.item_id = '".res($item_id)."' AND c.invid = i.id ";
				$query .= "AND i.partid = ".prep($row['partid'])." ";
				if ($po_number) { $query .= "AND pi.po_number = '".res($po_number)."' "; }
				$query .= "; ";

				$result2 = qdb($query) OR die(qe().' '.$query);

				if (mysqli_num_rows($result2)>0) {
					$row2 = mysqli_fetch_assoc($result2);
					$qty = ($row2['totalReceived'] ? $row2['totalReceived'] : 0);
				}

				$row['totalReceived'] = $qty;

				// This piece grabs more information on the component requested such as status, price and how many ordered if PO is active (AKA created)
				$total = 0;
				
				// Grab actual available quantity for the requested component
				$row['available'] = getAvailable($row['partid'], $item_id);
				$row['pulled'] = getPulled($row['partid'], $item_id);

				$row['total'] = $total;

				$purchase_requests[] = $row;
			}

			//print_r($purchase_requests);

			if($type == 'repair') {
				// Also grab elements that were fulfilled by the in stock
				$query = "SELECT *, SUM(i.qty) as totalReceived FROM repair_components c, inventory i ";
				$query .= "WHERE c.ro_number = '".res($order_number)."' AND c.invid = i.id ";
				$query .= "GROUP BY i.partid; ";

				//echo $query;
				$result = qdb($query) OR die(qe()); 

				while ($row = $result->fetch_assoc()) {
					if(!in_array_r($row['partid'] , $purchase_requests)) {
						$purchase_requests[] = $row;
					}
				}
			}
		} else if($type == 'service_quote') {
			$query = "SELECT * FROM service_quote_materials WHERE $field = ".res($item_id).";";
			$result = qdb($query) OR die(qe().' '.$query);

			//echo $query;

			while($row = mysqli_fetch_assoc($result)) {
				$purchase_requests[] = $row;
				$materials_total += $row['quote'];
			}
		} 
		//else if(strtolower($type) == 'service') {
			// $query = "SELECT * FROM service_materials WHERE $field = ".res($item_id).";";
			// $result = qdb($query) OR die(qe().' '.$query);

			// while($row = mysqli_fetch_assoc($result)) {
			// 	$purchase_requests[] = $row;
			// 	$materials_total += $row['quote'];
			// }
		//}

		return $purchase_requests;
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

		if($type == 'repair') {
			$task_label = 'repair_item_id';
		} else {
			$task_label = 'service_item_id';
		}


		$query = "SELECT * FROM timesheets WHERE taskid = ".res($item_id)." AND task_label = '".res(strtolower($task_label))."' AND clockin IS NOT NULL AND clockout IS NOT NULL;";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		while($r = mysqli_fetch_assoc($result)){
			$totalSeconds_data[$r['userid']] += strtotime($r['clockout']) - strtotime($r['clockin']);
		}

		// Also pull assigned users and set them to 0 hours worked
		$query = "SELECT * FROM service_assignments WHERE item_id = ".res($item_id)." AND item_id_label = ".fres($task_label).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		while($r = mysqli_fetch_assoc($result)){
			$totalSeconds_data[$r['userid']] += 0;
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
    			$query = "REPLACE INTO service_assignments (item_id, item_id_label, userid) VALUES (".fres($item_id).", ".fres(($type == 'repair' ? 'repair_item_id' : 'service_item_id')).",".fres($r['userid']).")";
    			qdb($query) OR die(qe() .' ' . $query);

    			$totalSeconds_data[$r['userid']] += 0;
    		}
		}

		// From the data given
		foreach($totalSeconds_data as $userid => $labor_seconds) {
			$data = array();
			$status = '';
			$hours_worked = ($labor_seconds / 3600);
			//$totalSeconds += $labor_seconds;
			$rate = 0;

			$data['laborSeconds'] = $labor_seconds;

			// Get the users hourly rate
			$query = "SELECT hourly_rate FROM users WHERE id=".res($userid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if (mysqli_num_rows($result)) {
				$result = mysqli_fetch_assoc($result);
				$rate = $result['hourly_rate'];
			}

			// Check if the user is currently allowed on this job or not
			$query = "SELECT * FROM service_assignments WHERE item_id = ".res($item_id)." AND item_id_label = ".fres($task_label)." AND userid = ".res($userid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if (mysqli_num_rows($result)) {
				$status = 'active';
			}

			$data['status'] = $status;

			$cost = round($rate * $hours_worked, 2);
			$data['cost'] = $cost;

			$labor_data[$userid] = $data;
		}
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

	function accessControl($userid, $item_id){
		global $quote;
		// Guilty until proven innocent
		$access = false;

		if(! $quote) {
			$query = "SELECT * FROM service_assignments WHERE service_item_id = ".res($item_id)." AND userid = ".res($userid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);


			if(mysqli_num_rows($result)) {
				$access = true;
			}
		}

		return $access;
	}

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

	if(strtolower($type) == "service" AND ($class == "service" OR $class == "FFR")) {
		if($quote) {
			$pageTitle .= "Service Quote for Order# ".$order_number.($task_number ? '-'.$task_number : '').$special;
		} else if($new) {
			$pageTitle = "New Service for Order# ".$order_number.$special;
		} else {
			$pageTitle = "Service# ".$order_number."-".$task_number.$special;
		}
	} else if(strtolower($type) == "repair" OR $class == "repair") {
		if($quote) {
			$pageTitle .= "Repair Quote for Order# ".$order_number.($task_number ? '-'.$task_number : '').$special;
		} else {
			if(! $task_number) {
				$task_number = 1;
			}
			$pageTitle = "Repair# ".$order_number."-".$task_number.$special;
		}
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
			}

			#pad-wrapper {
				padding-bottom: 230px; /*just larger than footer height*/
			}
			#sticky-footer {
				position: fixed;
				bottom: 0;
				margin-left:-19px;
				margin-bottom:-19px;
				height: 220px;
				border-bottom:1px solid white;
			}
		</style>
	</head>
	
	<body class="sub-nav" data-order-type="<?=($quote ? 'quote' : $type)?>" data-order-number="<?=$order_number?>" data-taskid="<?=$item_id;?>" data-techid="<?=$GLOBALS['U']['id'];?>">
		<div id="loader" class="loader text-muted" style="display: none;">
			<div>
				<i class="fa fa-refresh fa-5x fa-spin"></i><br>
				<h1 id="loader-message">Please wait while your search result is populating...</h1>
			</div>
		</div>
		<div class="container-fluid data-load full-height">
			<?php include 'inc/navbar.php'; include 'modal/package.php'; include '/modal/image.php';?>
			<div class="row table-header full-screen" id = "order_header">
				<div class="col-md-4">
					<!-- Configure Repair Table Header Options -->
					<?php if(! $quote AND ! $new AND $type == 'repair') { ?>
						<?php if(! $task_edit) { ?>
							<a href="/service.php?order_type=<?=$type;?>&order_number=<?=$order_number_details;?>&edit=true" class="btn btn-default btn-sm toggle-edit"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
						<?php } else { ?>
							<div class="col-md-6" style="padding-top: 10px;">
								<?php if(! empty($repair_codes)) { ?>
									<select style="margin-top: 10px;" id="repair_code_select" class="form-control input-sm select2" name="repair_code_id">
										<option selected="" value="null">- Select Status -</option>
										<?php 
											foreach($repair_codes as $code):
												echo "<option value='".$code['id']."' ".($ORDER['repair_code_id'] == $code['id'] ? 'selected' : '').">".$code['description']."\t".$code['code']."</option>";
											endforeach;
										?>
									</select>
								<?php } ?>
							</div>
						<?php } ?>

						<?php if(! $task_edit) { ?>
							<a href="/repair_add.php?on=<?=($build ? $build . '&build=true' : $order_number)?>" class="btn btn-default btn-sm text-warning">
								<i class="fa fa-qrcode"></i> Receive
							</a>
						<?php } ?>
							<form action="tasks_log.php" style="display: inline-block;">
								<input type="hidden" name="item_id" value="<?=$item_id;?>">
								<button class="btn btn-sm btn-default btn-flat info" type="submit" name="type" value="test_out" title="Mark as Tested" data-toggle="tooltip" data-placement="bottom">
									<i class="fa fa-terminal"></i>
								</button>
							</form>
					<!-- Configure Service Header Options -->
					<?php } else { ?>
						<?php if(! $task_edit) { ?>
							<a href="/service.php?order_type=<?=$type;?>&order_number=<?=$order_number_details;?>&edit=true" class="btn btn-default btn-sm toggle-edit"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
						<?php } else { ?>
						<?php } ?>
					<?php } ?>
				</div>
				<div class="col-sm-4 text-center" style="padding-top: 5px;">
					<h2>
						<?=$pageTitle;?>
					</h2>
				</div>
				<div class="col-sm-4">
					<div class="col-md-4">
						
					</div>
					<div class="col-md-8">
						<?php if(! $quote){ ?>
							<div class="col-md-9" style="padding-top: 10px;">
								<select name="task" class="form-control repair-task-selector task_selection pull-right" data-noreset="true">
									<option><?=ucwords($type) . '# '.$order_number_details;?> - <?=getCompany($ORDER['companyid']);?></option>
								</select>
							</div>

							<div class="col-md-3 remove-pad">
								<?php if($task_edit) { ?>
									<a href="#" class="btn btn-success toggle-save"><i class="fa fa-floppy-o" aria-hidden="true"></i> Save</a>
								<?php } else if(! $ticketStatus) { ?>
									<button class="btn btn-success btn-sm btn-update" data-toggle="modal" data-target="#modal-complete">
										<i class="fa fa-save"></i> Complete
									</button>
								<?php } ?>
							</div>
						<?php } else { ?>

							<?php if(empty($task_number)) { ?>
								<a href="#" class="btn btn-success btn-sm quote_order pull-right" data-type="quote"><i class="fa fa-pencil" aria-hidden="true"></i> Quote</a>
							<?php } else { ?>
								<a href="#" class="btn btn-success btn-sm save_quote_order pull-right" data-type="save"><i class="fa fa-floppy-o" aria-hidden="true"></i> Save</a>
							<?php } ?>
						<?php } ?>
					</div>
				</div>
			</div>

			<form id="save_form" action="/task_edit.php" method="post">
				<input type="hidden" name="<?=($quote ? 'quote' : 'service');?>_item_id" value="<?=$item_id;?>">
				<input type="hidden" name="order" value="<?=$order_number;?>">
				<input type="hidden" name="line_number" value="<?=$task_number;?>">
				<input type="hidden" name="type" value="<?=$type;?>">
				<div class="row" style="height: 100%; margin: 0;">
					
					<?php include 'sidebar.php'; ?>

					<div id="pad-wrapper" >

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

						<?php if(in_array("4", $USER_ROLES)){ ?>
							<br>
							<!-- Cost Dash for Management People Only -->

							<div id="main-stats">
					            <div class="row stats-row">
					                <div class="col-md-3 col-sm-3 stat">
					                    <div class="data">
					                        <span class="number text-brown"><?=format_price($total_amount);?></span>
											<span class="info">Quote</span>
					                    </div>
					                </div>
					                <div class="col-md-3 col-sm-3 stat">
					                    <div class="data">
					                        <span class="number text-black">$0.00</span>
											<span class="info">Cost</span>
					                    </div>
					                </div>
					                <div class="col-md-3 col-sm-3 stat">
					                    <div class="data">
					                        <span class="number text-black">$0.00</span>
											<span class="info">Commission</span>
					                    </div>
					                </div>
					                <div class="col-md-3 col-sm-3 stat last">
					                    <div class="data">
					                        <span class="number text-success">$0.00</span>
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
					        	echo '<li class="'.(($tab == 'activity' OR ($activity && empty($tab))) ? 'active' : '').'"><a href="#activity" data-toggle="tab"><i class="fa fa-folder-open-o"></i> Activity</a></li>';
				        	} 
				        	if($details) { 
					        	echo '<li class="'.(($tab == 'details' OR (! $activity && empty($tab))) ? 'active' : '').'"><a href="#details" data-toggle="tab"><i class="fa fa-list"></i> Details</a></li>';
					        } 
				        	if($documentation) { 
					        	echo '<li class="'.($tab == 'documentation' ? 'active' : '').'"><a href="#documentation" data-toggle="tab"><i class="fa fa-file-pdf-o"></i> Documentation</a></li>';
					        } 
					        if($labor) {
								echo '<li class="'.($tab == 'labor' ? 'active' : '').'"><a href="#labor" data-toggle="tab"><i class="fa fa-users"></i> Labor <span class="labor_cost">'.((in_array("4", $USER_ROLES)) ?'&nbsp; '.format_price($labor_total).'':'').'</span></a></li>';
							} 
							if($materials) { 
								echo '<li class="'.($tab == 'materials' ? 'active' : '').'"><a href="#materials" data-toggle="tab"><i class="fa fa-microchip" aria-hidden="true"></i> Materials &nbsp; <span class="materials_cost"><!--'.format_price($materials_total).'--></span></a></li>';
							} 
							if($expenses) {
								echo '<li class="'.($tab == 'expenses' ? 'active' : '').'"><a href="#expenses" data-toggle="tab"><i class="fa fa-credit-card"></i> Expenses &nbsp; <span class="expenses_cost">'.format_price($expenses_total).'</span></a></li>';
							} 
							if($outside) {
								echo '<li class="'.($tab == 'outside' ? 'active' : '').'"><a href="#outside" data-toggle="tab"><i class="fa fa-suitcase"></i> Outside Services &nbsp; <span class="outside_cost">'.format_price($outside_services_total).'</span></a></li>';
							} ?>
							<?php if(in_array("4", $USER_ROLES)){ ?>
								<li class="pull-right"><a href="#"><strong><i class="fa fa-shopping-cart"></i> Total &nbsp; <span class="total_cost"><?=format_price($total_amount);?></span></strong></a></li>
							<?php } ?>
						</ul>

						<div class="tab-content">

							<!-- Activity pane -->
							<?php if($activity) { ?>
								<div class="tab-pane <?=(($tab == 'activity' OR ($activity && empty($tab))) ? 'active' : '');?>" id="activity">
									<section>
										<div class="row list table-first">
											<div class="col-md-2">Date/Time</div>
											<div class="col-md-4">Tech</div>
											<div class="col-md-6">Activity</div>
										</div>

										<div class="col-md-12" style="margin: 10px 0;">
											<div class="input-group">
												<input type="text" name="notes" class="form-control input-sm" placeholder="Notes...">
												<span class="input-group-btn">
													<button class="btn btn-sm btn-primary" type="submit" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Save Entry"><i class="fa fa-save"></i></button>
												</span>
											</div>
										</div>

										<?php
											if($activity_data) {
											foreach($activity_data as $activity_row):
										?>
											<hr>
											<div class="row list">
												<div class="col-md-2"><?=format_date($activity_row['datetime'], 'n/j/y, h:i a');?></div>
												<div class="col-md-4"><?=getContact($activity_row['techid'], 'userid');?></div>
												<div class="col-md-6"><?=$activity_row['notes'];?></div>
											</div>
										<?php endforeach; } ?>
									</section>
								</div><!-- Activity pane -->
							<?php } ?>

							<!-- Details pane -->
							<?php if($details) { ?>
								<div class="tab-pane <?=(($tab == 'details' OR (! $activity && empty($tab))) ? 'active' : '');?>" id="details">
									<section>
										<div class="row list table-first">
											<div class="col-md-3"><?=($type == 'repair' ? 'Description' : 'Site Address')?></div>
											<div class="col-md-2"><?=($type == 'repair' ? 'Serial(s)' : '')?></div>
											<div class="col-md-2"><?=($type == 'repair' ? 'RMA#' : '')?></div>
											<div class="col-md-5">Notes</div>
										</div>
										<hr>
										<?php if(! $quote && $type == 'repair') { ?>
											<div class="row list">
												<div class="col-md-3"><?=trim(partDescription($item_details['partid'], true));?></div>
												<div class="col-md-4">
													<?php foreach(getDetails($item_id) as $serial) {
														echo $serial;
													} ?>
												</div>
												<div class="col-md-5"><?=$item_details['notes'];?></div>
											</div>
										<?php } else if (! $quote && $type == 'Service' && $item_details['item_label']=='addressid') { ?>
											<div class="row list">
												<div class="col-md-7"><?=format_address($item_details['item_id'], '<br/>', true, '', $ORDER['companyid']);?></div>
												<div class="col-md-5"><?=$item_details['notes'];?></div>
											</div>
										<?php } ?>

										<?php if($quote OR $new) { ?>
											<div class="row list">
												<div class="col-md-5 part-container">
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
												</div>
												<!-- <div class="col-md-3">
													
												</div> -->
											</div>
										<?php } ?>
									</section>
								</div><!-- Details pane -->
							<?php } ?>

							<?php if($documentation) { ?>
								<!-- Documentation pane -->
								<div class="tab-pane <?=($tab == 'documentation' ? 'active' : '');?>" id="documentation">
									<section>
										<table class="table table-striped">
											<thead class="table-first">
												<th class="col-md-3">Date/Time</th>
												<th class="col-md-3">User</th>
												<th class="col-md-3">Notes</th>
												<th class="col-md-3 text-right">Action</th>
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
					                            		<select name="techid" class="form-control input-xs tech-selector required"></select>
				                            		</td>
													<td><input class="form-control input-sm" type="text" name="expense[notes]"></td>
													<td style="cursor: pointer;">
														<button class="btn btn-success btn-sm pull-right" name="documentation" value="true">
												        	<i class="fa fa-plus"></i>	
												        </button>

												        <a href="#" class="pull-right" style="margin-right: 15px; margin-top: 7px;"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>
												       <!--  <div class="remove_expense pull-right">
															<i class="fa fa-trash fa-4" aria-hidden="true"></i>
														</div> -->
													</td>
												</tr>
											</tbody>
										</table>
									</section>

									<?php if($closeout) { ?>
										<!-- <br>
										<section>
											<div class="row">
												<div class="col-sm-12">
													<h4>Closeout</h4>
												</div>
											</div>
										</section> -->
									<?php } ?>
								</div><!-- Documentation pane -->
							<?php } ?>
							
							<?php if($labor) { ?>
								<!-- Labor pane -->
								<div class="tab-pane <?=($tab == 'labor' ? 'active' : '');?>" id="labor">
									<?php if($task_edit){ ?>
										<div class="row labor_edit">

											<div class="col-md-12">
												<div class="input-group pull-left" style="margin-bottom: 10px; margin-right: 15px; max-width: 200px">
						  							<!-- <span class="input-group-addon">$</span> -->
													<input class="form-control input-sm labor_hours" name="labor_hours" type="text" placeholder="Hours" value="<?=$item_details['labor_hours'];?>">
												</div>
												<div class="input-group" style="margin-bottom: 10px; max-width: 200px">
						  							<span class="input-group-addon">$</span>
													<input class="form-control input-sm labor_rate" name="labor_rate"  type="text" placeholder="Rate" value="<?=number_format((float)$item_details['labor_rate'], 2, '.', '');?>">
												</div>
											</div>
										</div>
									<?php } ?>

				                    <table class="table table-hover table-condensed">
				                        <thead class="no-border">
				                            <tr>
				                                <th class="col-md-4">
				                                    Employee
				                                </th>
				                                <th class="col-md-4">
				                                    Total Hours Logged
				                                </th>
				                                <?php if(in_array("4", $USER_ROLES)){ ?>
					                                <th class="col-md-2 text-right">
					                                    Cost
					                                </th>
				                                <?php } ?>
				                                <th class="col-md-2 text-center">
													<div data-toggle="tooltip" data-placement="left" title="" data-original-title="Tech Complete?"><i class="fa fa-id-badge"></i></div>
				                                </th>
				                               <!--  <th class="col-md-1 text-center">
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
															<?=getUser($user);?>
						                                </td>
						                                <td>
															<?=toTime($data['laborSeconds']);?><br> &nbsp; <span class="info"><?=timeToStr(toTime($data['laborSeconds']));?></span>
						                                </td>
						                                <td class="text-right">
						                                	<?php if(in_array("4", $USER_ROLES)){ ?>
																<?=format_price($data['cost']);?>
															<?php } ?>
						                                </td>
						                                <td class="text-center">
						                                	<?php if(in_array("4", $USER_ROLES) && $data['status']){ ?>
							                                	<button type="submit" class="btn btn-primary btn-sm pull-right" name="tech_status" value="<?=$user;?>">
														        	<i class="fa fa-trash" aria-hidden="true"></i>
														        </button>
													        <?php } else { ?>
													        	<i title="In Active" class="fa fa-user-times pull-right" style="color:#d9534f; margin-top: 10px; margin-right: 10px;"></i>
													       	<?php } ?>
						                                </td>
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
					                            		<button type="submit" class="btn btn-success btn-sm add_tech" <?=(($quote && empty($task_number)) ? 'disabled' : '');?>>
												        	<i class="fa fa-plus"></i>	
												        </button>
												    </td>
					                            	<td></td>
					                            	<td></td>
					                            </tr>
				                            <?php } ?>
				                            <!-- row -->
				                            <?php if(in_array("4", $USER_ROLES)){ ?>
					                            <tr class="first">
					                                <td colspan="1">
														<div class="progress progress-lg">
															<div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">0%</div>
														</div>
					                                </td>
					                                <td>
														$0.00 profit of <span class="labor_cost"><?=format_price($labor_total);?></span> quoted Labor
					                                </td>
					                                <td>
														<strong><?=toTime($totalSeconds);?> &nbsp; </strong>
					                                </td>
					                                <td class="text-right">
					                                    <strong><?=format_price($labor_total)?></strong>
					                                </td>
					                            </tr>
				                            <?php } ?>
										</tbody>
									</table>
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

										<table class="table table-striped">
											<thead class="table-first">
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
														<th class="col-md-1">Requested</th>
														<th class="col-md-2">SOURCE</th>
														<th class="col-md-1">Available</th>
														<th class="col-md-1">Pulled</th>
														<th class="col-md-2 text-right">Price Per Unit</th>
														<th class="col-md-2 text-right">EXT Price</th>
													<?php } ?>
												</tr>
											</thead>

											<tbody <?=($quote ? 'id="quote_body"' : '');?>>
												<?php 
													$total = 0; 

													foreach($component_data as $row){ 
													$price = 0;
													$ext = 0;

													if($row['po_number'] && $type == 'repair') {
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
														$materials_total += $ext;
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
													<td class="text-right" <?=($quote ? 'colspan="2"' : '');?>><strong><?=($quote ? 'Quote' : '');?> Total:</strong> <span class="materials_cost"><?=format_price($materials_total);?></span></td>
												</tr>
											</tbody>
										</table>

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

										<table class="table table-striped">
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
					                            		<select name="techid" class="form-control input-xs contact-selector required"></select>
				                            		</td>
													<td><input class="form-control input-sm" type="text" name="expense[notes]"></td>
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
										</table>
									</section>
								</div><!-- Expenses pane -->
							<?php } ?>

							<?php if($outside) { ?>
								<!-- Outside Services pane -->
								<div class="tab-pane <?=($tab == 'outside' ? 'active' : '');?>" id="outside">
				                    <table class="table table-hover table-condensed">
				                        <thead class="no-border">
				                            <tr>
				                                <th class="col-md-3">
				                                    Vendor
				                                </th>
				                                <th class="col-md-6">
				                                    Description
				                                </th>
				                                <th class="col-md-2">
				                                    Amount
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
													<td><input class="form-control input-sm" type="text" name="outsourced[<?=$line_number;?>][description]" value="<?=$list['description'];?>"></td>
													<td>
														<div class="input-group">													
															<span class="input-group-addon">										                
																<i class="fa fa-usd" aria-hidden="true"></i>										            
															</span>										            
															<input class="form-control input-sm part_amount" type="text" name="outsourced[<?=$line_number;?>][amount]" placeholder="0.00" value="<?=$list['amount'];?>">
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
														<input class="form-control input-sm part_amount" type="text" name="outsourced[<?=$line_number;?>][amount]" placeholder="0.00" value="">
													</div>
												</td>
												<td class="os_action">
													<button class="btn btn-success btn-sm pull-right os_expense_add">
											        	<i class="fa fa-plus"></i>	
											        </button>
												</td>
											</tr>
				                            <tr class="">
				                                <td colspan="3">
				                                </td>
				                                <td class="text-right">
				                                    <strong><span class="outside_cost"><?=format_price($outside_services_total);?></span></strong>
				                                </td>
				                            </tr>
										</tbody>
									</table>
								</div><!-- Outside Services pane -->
							<?php } ?>
						</div>
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
