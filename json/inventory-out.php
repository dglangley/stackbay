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
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/locations.php';
	
	// Get Pages function determines how many pages the inventory should output.
	function getPages($show_num = '5') {
		//Find out what page number we are on.
		global $page;
		
		//Set a null counter for the number of rows
		$rows = 0;
		
		//Static query which gets all the parts from the inventory screen
		$query  = "SELECT COUNT(*) as rows FROM (SELECT DISTINCT  `partid` FROM  `inventory`) AS t1;";
		$result = mysqli_fetch_assoc(qdb($query));
		$rows = $result['rows'];
		$pages = ceil($rows / $show_num);
		
		for($i = 1; $i <= $pages; $i++) {
			//echo $page;
			echo '<li class="' .($page == $i || ($page == '' && $i == 1) ? 'active':''). '"><a href="?page=' .$i. '">'.$i.'</a></li>';
		}
	}
	
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
	
	
	function getVendor($order_number) {
		$company;
		
		$order_number = prep($order_number);
		$query = "SELECT * FROM purchase_orders WHERE po_number = $order_number;";
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
		$query  = "SELECT SUM(qty) FROM inventory WHERE partid =" . res($partid) . " AND item_condition = $stock ";
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
	function search($search = ''){
		$return = array();
		
		if ($search){
			$in = '(';
			//Get all the parts from the search
			$initial = hecidb($search);
			foreach($initial as $id =>$info){
				$in .= "'$id', ";
			}
			$in = trim($in, ", ");
			$in .= ")";
			$query  = "SELECT DISTINCT partid FROM inventory";
			$query .= " WHERE partid IN $in;";
			$result = qdb($query);
			
			if(mysqli_num_rows($result)>0){
				//Loop through the results
				foreach($result as $inv){
					$parts[$inv['partid']] = $initial[$inv['partid']];
				}
			}
			$parts = array();
			$search = prep("%".$search."%");
			$query  = "SELECT DISTINCT `partid` FROM inventory where serial_no LIKE $search";
			$result = qdb($query);
			foreach ($result as $part){
		    	$p = hecidb($part['partid'],'id');
		    	foreach($p as $id => $info){
		    		if(!isset($parts[$id])){
		        		$parts[$id] = $info;
		    		}
		    	}
			}
			
			
		}

		else{
		    $parts = array();
			$query  = "SELECT DISTINCT partid FROM inventory WHERE `qty` > 0;";
			$result = qdb($query);
			foreach ($result as $part){
		    	$p = hecidb($part['partid'],'id');
		    	foreach($p as $id => $info){
		        	$parts[$id] = $info;
		    	}
			}
		}

		if(isset($parts)){
			foreach($parts as $id => $info){
				$return[$info['part']] = query_first($id);
			}
		}
		return ($return);
	}
	
	function sFilter($field, $value){
		if ($value){
			$value = prep($value);
			$string = " AND `$field` = $value ";
		}
		else{
			$string = '';
		}
		return $string;
	}
	
	function dFilter($field, $start, $end = ''){
		if ($start and $end){
	   		$start = prep(format_date($start, 'Y-m-d'));
	   		$end = prep(format_date($end, 'Y-m-d'));
	   		$string = " AND $field between CAST($start AS DATE) and CAST($end AS DATE) ";
		}
		else if($start){
			$string = " AND $field between CAST($start AS DATE) ";
		}
		else{
			$string = '';
		}
		return $string;
	}
	
	function query_first($partid, $filters = array()){
		//Query to grab rows first
		$r = array();
		
		
		
		//Filter Grabber
		$location = $filters['filter_value'];
		$condition = $filters['condition'];
		$purchase_order = $filters['po'];
		$earliest = $filters['earliest'];
		$latest = $filters['latest'];
		
		
		$partid = prep($partid);
		$query = "SELECT `partid`, SUM(`qty`) sumqty, `locationid`, `item_condition`, `last_purchase`, `status`, `last_sale`, `date_created`, `id` invid ";
		$query .= "FROM inventory ";
		$query .= "WHERE `partid` = $partid AND `qty` > 0 ";
		$query .= sFilter('locationid', $location);
		$query .= sFilter('item_condition',$condition);
		$query .= sFilter('last_purchase',$purchase_order);
		$query .= dFilter('date_created',$earliest, $latest);
		$query .= "GROUP BY partid, locationid, last_purchase, item_condition, date_created;";
		// $query .= "ORDER BY sumqty;";
		
		$rows = qdb($query);
		foreach ($rows as $row){
			 $r['partid'] = $row['partid'];
			 $r['qty' ] = $row['sumqty'];
			 $r['locationid'] = $row['locationid'];
			 $r['location'] = display_location($row['locationid']);
			 $r['place'] = display_location($row['locationid'], 'place');
			 $r['instance'] = display_location($row['locationid'], 'instance');
			 $r['condition'] = $row['item_condition'];
			 $r['last_purchase'] = $row['last_purchase'];
			 $r['status'] = $row['status'];
			 $r['last_sale'] = $row['last_sale'];
			 $r['date_created'] =  format_date($row['date_created'],"m/d/Y");
			 $r['vendorid'] = getVendor($row['last_purchase']);
			 $r['vendor'] = getCompany($r['vendorid']);
			 $r['unique'] = $row['invid'];
			 //$r['id'] = $row['id'];
			 
			
			//Query to grab serials second
			$q_serial = "Select * FROM inventory, inventory_history ";
			$q_serial .= " WHERE ";
			$q_serial .= " inventory.id = inventory_history.invid AND ";
			$q_serial .= "partid = ".prep($row['partid'])." AND  ";
			$q_serial .= "locationid = ".prep($row['locationid'])." AND  ";
			$q_serial .= "last_purchase = ".prep($row['last_purchase'])." AND  ";
			$q_serial .= "item_condition = ".prep($row['item_condition'])." AND  ";
			$q_serial .= "date_created = ".prep($row['date_created'])." ORDER BY date_changed DESC;";
			$s = array();
			$serials = qdb($q_serial);
			foreach($serials as $serial){
				if($serial['serial_no']){
					$s[$serial['serial_no']][] = $serial;
				}
				else{
					$s['null'][] = $serial;
				}
			}
			$r['serials'] = $s;
			$o[] = $r;
		}
		return $o;
	}
	// $parts_arr = array();


$search = grab('search');
$serial = grab('serial');
$filters = grab('filters');

$return = (search($search));

// print_r($return);
echo json_encode($return);
?>