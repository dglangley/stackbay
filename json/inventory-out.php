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
	
	function getVendor($order_type,$order_number) {
		$company;
		
		//Determine which order type to look for in this system
		$order_table = $order_type == 'po' ? 'purchase_items' : 'sales_items';
		$selector = $order_type == 'po' ? 'po_number' : 'so_number';
		
		//Order by the lastest order and limit the order # to 3 at a time
		$query = "SELECT ". res($selector) ." FROM " . res($order_table) . " WHERE partid = ". res($partid) ." LIMIT 1;";
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$po = $result[$selector];
		}
		
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
	
	function getStock($stock = '', $partid = 0) {
		$stockNumber;
		
		$query  = "SELECT SUM(qty) FROM inventory WHERE partid =" . res($partid) . " AND item_condition = '" . res($stock) . "';";
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
	
	function inventory_arr_out($id,$info){
		//Pass in a HECIDB Result
			$p = array();
			$s = array();
			$c = array();
		
		//Grab any filtered amounts
			
			
			$i++;
			// Macro portion of the part container
			$p['id'] = $id;
			
			//Eventually, there will need to be a photo-get Parameter passed here as well, but for now it will not be passed.
			
			$p['system'] = $info['Sys'];
			$p['part'] = $info['part'];
			$p['Manf'] = $info["Manf"];
			$p['po_history'] = getOrder('po', $id);
			$p['so_history'] = getOrder('so', $id);
			
			$p['company'] = getOrder('so', $id);
			
			$p['conditions'] = array();
			
			$p['conditions']['new'] = getStock('new', $id);
			$p['conditions']['used'] = getStock('used', $id);
			$p['conditions']['refurbished'] = getStock('refurbished', $id);
			
			
			$p['in_stock'] = getStatusStock('instock',$id);
			$p['pending'] = getStatusStock('pending', $id);
			$p['price_avg'] = getAvgPrice($id);
			$p['serials'] = array();
			
			$serials = getPartSerials($id);
			$element = 0; $page_no = 1; 
			foreach($serials as $serial){
				(($element % 5) == 0 && $element != 0 ? $page_no++ : ''); 
				$element++; 
				$s['page'] = $page_no;
				$s['id'] = $serial['id'];
				$s['serial_no'] = $serial['serial_no'];
				$s['date'] = date_format(date_create($serial['date_created']), 'm/d/Y');
				
				$p['date'] = date_format(date_create($serial['date_created']), 'm/d/Y');
				
				$s['location'] = display_location($serial['locationid']);
				$s['qty'] = $serial['qty'];
				$s['condition'] = $serial['item_condition'];
				$s['status'] = $serial['status'];
				$s['history'] = array();
				foreach(getItemHistory($serial['id']) as $history){
					$h['date'] = date_format(date_create($history['date_changed']), 'm/d/Y');
					$h['repid'] = getRep($history['repid']);
					$h['field_changed'] = $history['field_changed'];
					$h['changed_from'] = $history['changed_from'];
					$s['history'][] = $h;
				}
				$p['serials'][] = $s;
			}
		return($p);
		}

	
	function search($search = '',$serial=''){
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
			
			
		}
		else if($serial){
			$parts = array();
			$serial = prep("%".$serial."%");
			$query  = "SELECT DISTINCT `partid` FROM inventory where serial_no LIKE $serial";
			$result = qdb($query);
			foreach ($result as $part){
		    	$p = hecidb($part['partid'],'id');
		    	foreach($p as $id => $info){
		        	$parts[$id] = $info;
		    	}
			}
		}
		else{
		    $parts = array();
			$query  = "SELECT DISTINCT partid FROM inventory;";
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
				$return[] = inventory_arr_out($id, $info);
			}
		}
		return ($return);
	}
	
	
	// $parts_arr = array();


$search = grab('search');
$serial = grab('serial');


$return = (search($search,$serial));
// print_r($return);
echo json_encode($return);
?>