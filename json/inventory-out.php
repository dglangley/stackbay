<?php 
    header('Content-Type: application/json');

//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getPartId.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/getManf.php';
	include_once $rootdir.'/inc/getSerials.php';
	include_once $rootdir.'/inc/getCondition.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/locations.php';
	
	// Get Pages function determines how many pages the inventory should output.
	// function getPages($show_num = '5') {
	// 	//Find out what page number we are on.
	// 	global $page;
		
	// 	//Set a null counter for the number of rows
	// 	$rows = 0;
		
	// 	//Static query which gets all the parts from the inventory screen
	// 	$query  = "SELECT COUNT(*) as rows FROM (SELECT DISTINCT  `partid` FROM  `inventory`) AS t1;";
	// 	$result = mysqli_fetch_assoc(qdb($query));
	// 	$rows = $result['rows'];
	// 	$pages = ceil($rows / $show_num);
		
	// 	for($i = 1; $i <= $pages; $i++) {
	// 		//echo $page;
	// 		echo '<li class="' .($page == $i || ($page == '' && $i == 1) ? 'active':''). '"><a href="?page=' .$i. '">'.$i.'</a></li>';
	// 	}
	// }
	
	function getPartLocation($partid){
		$partid = prep($partid);
		$select = "SELECT DISTINCT locationid FROM inventory where partid = $partid";
		$result = qdb($select);
		$locations = array();
		foreach ($result as $row){
			$locations[$row['locationid']] = array();
		}
		
		return $locations;
	}

	function getItemHistory($invid = 0) {
		$partHistory_array = array(); 
		
		$query  = "SELECT * FROM inventory_history WHERE invid =" . res($invid) . ";";
		$result = qdb($query);
		
		while ($row = $result->fetch_assoc()) {
			$partHistory_array[] = $row;
		}
		
		return $partHistory_array;
	}
	
	function getStatusStock($stock = '', $partid = 0) {
		$stockNumber;
		
		if($stock == 'pending') {
			$query  = "SELECT SUM(qty) FROM inventory WHERE partid =" . res($partid) . " AND (status = 'ordered');";
		} else if($stock == 'instock') {
			$query  = "SELECT SUM(qty) FROM inventory WHERE partid =" . res($partid) . " AND (status = 'received' OR status = 'shelved');";
		}
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$stockNumber = $result['SUM(qty)'];
		}
		
		// while ($row = $result->fetch_assoc()) {
		// 	$stockNumber= $row['serial_no'];
		// }
		if(!$stockNumber) {
			$stockNumber = 0;
		}
		
		return $stockNumber;
	}

	$POs = array();
	function getPO($itemid) {
		global $POs;
		$po;

		if (! $itemid) { return $po; }
		if (isset($POs[$itemid])) { return ($POs[$itemid]); }

		$itemid = prep($itemid);
		$query = "SELECT po_number FROM purchase_items WHERE id = $itemid;";
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$po = $result['po_number'];
		}

		$POs[$itemid] = $po;
		
		return $po;
	}
	
	function getVendor($order_number) {
		$company;
		
		$order_number = prep($order_number);
		$query = "SELECT companyid FROM purchase_orders WHERE po_number = $order_number;";
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$company = $result['companyid'];
		}
		
		return $company;
	}
	
	function getAllVendors($order_type,$order_number) {
		$company;
		
		//Determine which order type to look for in this system
		$order_table = ($order_type == 'po') ? 'purchase_items' : 'sales_items';
		$selector = ($order_type == 'po') ? 'po_number' : 'so_number';
		
		//Order by the lastest order and limit the order # to 3 at a time
		$query = "SELECT ". res($selector) ." FROM " . res($order_table) . " WHERE partid = ". res($partid) ." LIMIT 1;";
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$po = $result[$selector];
		}
		//Take 
		$order_number = prep($order_number);
		$query = "SELECT * FROM purchase_orders WHERE po_number = '".res($po)."';";
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$company = $result['companyid'];
		}
		
		return $company;
	}
	
	//Get the past Purchase/Sales Order for each part in the inventory
	function getOrder($order_type,$partid = 0) {
		$order_array = array();
		$order_message = "";
		
		//Determine which order type to look for in this system
		$item_table = $order_type == 'po' ? 'purchase_items' : 'sales_items';
		$order_table = $order_type == 'po' ? 'purchase_orders' : 'sales_orders';
		$selector = $order_type == 'po' ? 'po_number' : 'so_number';
		
		
		
		//Order by the lastest order and limit the order # to 3 at a time
		$query = "SELECT DISTINCT $item_table.$selector, companyid FROM $item_table, $order_table WHERE partid = ". res($partid) ." AND $order_table.$selector = $item_table.$selector ORDER BY $selector;";
		$result = qdb($query) or die(qe());
		
		$order_out = array();
		if(mysqli_num_rows($result) > 0){
			foreach ($result as $row) {
				$order_out[] = array(
					"number" => $row[$selector],
					"vendor" => getCompany($row['companyid'])
					);
			}	
		}
		
		return $order_out;
	}

	function getAvgPrice($partid) {
		$order_array = array();
		$avg = 0;
		$counter = 0;
		$partid = prep($partid);
		
		$query = "SELECT AVG(price) average FROM sales_items WHERE partid = $partid group by `partid`;";
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result) > 0){
			$row = mysqli_fetch_assoc($result);
			return (format_price($row['average']));
		}
		else{
			return "Never Sold";
		}
	}
	
	function getStock($stock = '', $partid = 0,$location='') {
		$stockNumber;
		$stock = prep($stock);
		$query  = "SELECT SUM(qty) FROM inventory WHERE partid =" . res($partid) . " AND conditionid = $stock ";
		if ($location){
			$location = prep($location);
			$query .= " AND locationid = $location";
		}
		$query .= ";";
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$stockNumber = $result['SUM(qty)'];
		}
		
		// while ($row = $result->fetch_assoc()) {
		// 	$stockNumber= $row['serial_no'];
		// }
		if(!$stockNumber) {
			$stockNumber = '0';
		}

		return $stockNumber;
	}

	//This function flattens the ordered array into a single outputtable collumn
	function iteratable($ordered){
		$output = array();
		foreach($ordered as $part){
			$r = array();
			$r['id'] = $part['id'];
			foreach($part['locations'] as $locid => $loc_info){
				$r['location'] = $locid;
				$r['location_display'] = $loc_info['display'];
				
			}
			
			
		}
	}
	function sFilter($field, $value){
		if ($value){
			$value = prep($value);
			$string = " AND $field = $value ";
		}
		else{
			$string = '';
		}
		return $string;
	}
	
	function dFilter($field, $start = '', $end = ''){
		if ($start and $end){
	   		$start = prep(format_date($start, 'Y-m-d'));
	   		$end = prep(format_date($end, 'Y-m-d'));
	   		$string = " AND $field between CAST($start AS DATE) and CAST($end AS DATE) ";
		}
		else if($start){
			$start = prep(format_date($start, 'Y-m-d'));
			$string = " AND CAST($field AS DATE) >= CAST($start AS DATE) ";
		}
		else if($end){
			$end = prep(format_date($end, 'Y-m-d'));
			$string = " AND CAST($field AS DATE) <= CAST($end AS DATE) ";
		}
		else{
			$string = '';
		}
		return $string;
	}
	
	function search($search = ''){
		$return = array();
		$parts = array();
		$result1 = array();
		$result2 = array();
		
		$place = grab("place");
		$location = grab("location");
		$locationid = ($place)? dropdown_processor($place,$location) : '';
		
		$start = grab("start");
		$end = grab("end");
		$conditionid = grab("conditionid");
/*david 3-2-17
		if ($conditionid == ""){
			$conditionid = '';
		}
*/
		$vendor = grab("vendor");
		$order = grab("order");
		if ($search || $locationid || $conditionid || $order || ($start && $end) || $vendor){

			$in = '';
			//Get all the parts from the search
			$initial = hecidb($search);
			if ($initial){
				foreach($initial as $id =>$info){
					$in .= "'$id', ";
				}
				$in = trim($in, ", ");
			}

			$query  = "SELECT i.*, p.*, i.id invid ";
			if ($order || $vendor) { $query .= ", pi.po_number "; }
			if ($vendor) { $query .= ", o.companyid "; }
			$query .= "FROM inventory i, parts p ";
			if ($order || $vendor) { $query .= ", purchase_items pi "; }
			if ($vendor) { $query .= ", purchase_orders o "; }
			$query .= "WHERE i.partid = p.id AND i.qty > 0 ";
			if ($in) { $query .= "AND i.partid IN (".$in.") "; }
			if ($order || $vendor) { $query .= "AND pi.id = i.purchase_item_id "; }
			if ($vendor) {$query .= "AND o.po_number = pi.po_number"; }
			$query .= sFilter('i.locationid', $locationid);
			$query .= sFilter('i.conditionid',$conditionid);
			$query .= sFilter('o.companyid', $vendor);
			$query .= sFilter('pi.po_number',$order);
			$query .= dFilter('i.date_created',$start, $end);
			$query .= " ORDER BY i.locationid, i.purchase_item_id, i.conditionid, i.date_created;";
			// echo $query; exit;
			$result = qdb($query);
			// echo ($query); exit;
			if (mysqli_num_rows($result) > 0){
				$result1 = query_first($result);
			} 
			
/*
				if(mysqli_num_rows($result)>0){
					//Loop through the results
					foreach($result as $inv){
						$parts[$inv['partid']] = $initial[$inv['partid']];
					}
				}
*/
			
			//This portion searches by serial number and appends the values of all the partids by serial
 
			if ($search) {
				$search = prep($search);
					$query  = "SELECT i.*, p.*, i.id invid ";
					if ($order || $vendor) { $query .= ", pi.po_number "; }
					if ($vendor) { $query .= ", o.companyid "; }
					$query .= "FROM inventory i, parts p ";
					if ($order || $vendor) { $query .= ", purchase_items pi "; }
					if ($vendor) { $query .= ", purchase_orders o "; }
					$query .= "WHERE serial_no = $search AND i.partid = p.id AND i.qty > 0 ";
					if ($in) { $query .= "AND i.partid IN (".$in.") "; }
					if ($order || $vendor) { $query .= "AND pi.id = i.purchase_item_id "; }
					if ($vendor) {$query .= "AND o.po_number = pi.po_number"; }
					$query .= sFilter('i.locationid', $locationid);
					$query .= sFilter('i.conditionid',$conditionid);
					$query .= sFilter('o.companyid', $vendor);
					$query .= sFilter('pi.po_number',$order);
					$query .= dFilter('i.date_created',$start, $end);
					$query .= " ORDER BY i.locationid, i.purchase_item_id, i.conditionid, i.date_created;";
					$result = qdb($query);
					if (mysqli_num_rows($result) > 0){
						$result2 = query_first($result);
					}
			}
/*
			if (mysqli_num_rows($result) > 0){
				foreach ($result as $part){
			    	$p = hecidb($part['partid'],'id');
			    	foreach($p as $id => $info){
			    		if(!isset($parts[$id])){
			        		$parts[$id] = $info;
			    		}
			    	}
				}
			}
		}

			$query  = "SELECT DISTINCT i.partid FROM inventory i ";
			if ($order) { $query .= ", purchase_items pi "; }
			$query .= "WHERE i.qty > 0 ";
			if ($order) { $query .= "pi.id = i.purchase_item_id "; }
			$query .= sFilter('i.locationid', $locationid);
			$query .= sFilter('i.conditionid',$conditionid);
			if ($order) { $query .= sFilter('pi.po_number',$order); }
			$query .= dFilter('i.date_created',$start, $end);
			$query .= ";";
			$result = qdb($query);
			foreach ($result as $part){
		    	$p = hecidb($part['partid'],'id');
		    	foreach($p as $id => $info){
		        	$parts[$id] = $info;
		    	}
			}
*/
		}else{
			$return = "Please enter a search parameter";
		}

/*
		foreach($parts as $id => $info){
			$return[$id] = query_first($id,$info);
		}

		return ($return);
*/
		
		return (array_merge($result1,$result2));
	}
	
	function query_first($rows) {//$partid,$info){
		//Query to grab rows first
		$r = array();

		//Filter Grabber
		
/*
		$partid = prep($partid);
		$query = "SELECT `serial_no`,`partid`, `qty`, `locationid`, `conditionid`, `purchase_item_id`, `status`, `sales_item_id`, `date_created`, `id` invid ";
		$query .= "FROM inventory ";
		$query .= "WHERE `partid` = $partid AND `qty` > 0 ";
		$query .= " ORDER BY locationid, purchase_item_id, conditionid, date_created;";
		// $query .= "ORDER BY sumqty;";
		
		
		$rows = qdb($query);
*/

		foreach ($rows as $row){
			// print_r($row);
			$date = format_date($row['date_created'],"m/d/Y");
			$po = getPO($row['purchase_item_id']);
			$key = $row['locationid'].'+'.$po.'+'.getCondition($row['conditionid']).'+'.$date;
			$partid = $row['partid'];

			if (! isset($r[$partid])) {
				$r[$partid] = array();
			}
			
			//Macro Level information on the key
			 if (!isset($r[$partid][$key])) {
				 $r[$partid][$key]['serials'] = array();
				 //$r[$partid][$key]['part_name'] =  $info['part']." ".$info['heci']; 
				 $r[$partid][$key]['part_name'] =  $row['part']." ".$row['heci']; 
				 $r[$partid][$key]['qty'] += $row['qty'];
				 $r[$partid][$key]['partid'] = $row['partid'];
				 $r[$partid][$key]['locationid'] = $row['locationid'];
				 $r[$partid][$key]['location'] = display_location($row['locationid']);
				 $r[$partid][$key]['place'] = display_location($row['locationid'], 'place');
				 $r[$partid][$key]['instance'] = display_location($row['locationid'], 'instance');
				 $r[$partid][$key]['vendorid'] = getVendor($po);
				 $r[$partid][$key]['vendor'] = getCompany($r[$partid][$key]['vendorid']);
				 $r[$partid][$key]['conditionid'] = $row['conditionid'];
				 $r[$partid][$key]['notes'] = $row['notes'];
				 $r[$partid][$key]['unique'] = $row['invid'];
			 }
			 //Null Serial handler and serial grouping/append
			 $serial = 'null';
			 
			if ($row['serial_no']){
				$r[$partid][$key]['serials'][] = $row['invid'].", ".$row['serial_no'].", ".$row['qty'].", ".$row['status'];
			}
			else{
				$r[$partid][$key]['null'][] = $serial;
			}
		}
		return $r;
	}


$search = grab('search');
$filters = grab('filters');


$return = (search($search));
// print_r($return); exit;
if (count($return) > 0){
	echo json_encode($return);
} else {
	echo json_encode('test');
}

// echo '<pre>' . print_r(get_defined_vars(), true) . '</pre>';
// print_r($return);
// exit;
?>
