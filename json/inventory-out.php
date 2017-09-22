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
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/item_history.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/getManf.php';
	include_once $rootdir.'/inc/getSerials.php';
	include_once $rootdir.'/inc/getCondition.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/filter.php';
	
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


	function getStatusStock($stock = '', $partid = 0) {
		$stockNumber;
		
		if($stock == 'pending') {
			$query  = "SELECT SUM(qty) FROM inventory WHERE partid =" . res($partid) . " AND (status = 'ordered');";
		} else if($stock == 'instock') {
			$query  = "SELECT SUM(qty) FROM inventory WHERE partid =" . res($partid) . " AND (status = 'received');";
		}
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$stockNumber = $result['SUM(qty)'];
		}
		
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
	
	$VENDORS = array();
	function getVendor($order_number) {
		global $VENDORS;
		$company;
		
		if (! $order_number) { return (''); }
		if (isset($VENDORS[$order_number])) { return ($VENDORS[$order_number]); }

		$order_number = prep($order_number);
		$query = "SELECT companyid FROM purchase_orders WHERE po_number = $order_number;";
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$company = $result['companyid'];
		}
		$VENDORS[$order_number] = $company;
		
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

	
	function search($search = ''){
		$f = array(
			"search" =>  $search,
			"start" => grab("start"),
			"end" => grab("end", format_date($GLOBALS['Today'],"n/j/Y")),
			"vendor" => grab("vendor"),
			"order" => grab("order")
			);
		$return = array();

		//$parts = array();
		$results = array();
		
		$place = grab("place");
		$location = grab("location");
		$f["locationid"] = ($place)? dropdown_processor($place,$location) : '';
		
		// $f['start'] = grab("start");
		// $f['end'] = grab("end");
/*david 3-2-17
		if ($f['conditionid'] == ""){
			$f['conditionid'] = '';
		}
*/	
		$filter_count = count(array_filter($f));
		if (! $f['search'] AND ! $filter_count) {
			return ("Please enter a search parameter");
		}

		/* conditions in order of specificity:
			-Search
			-Order
			-LocationID
			-Vendor
			-Date Range
			-Condition
			*/
		
		$query  = "SELECT i.*, p.*, i.id invid ";
		if ($f['order'] || $f['vendor']) { $query .= ", pi.po_number "; }
		if ($f['vendor']) { $query .= ", o.companyid "; }
		$query .= "FROM inventory i, parts p ";
		if ($f['order'] || $f['vendor']) { $query .= ", purchase_items pi "; }
		if ($f['vendor']) { $query .= ", purchase_orders o "; }
		$query .= "WHERE i.partid = p.id ";
		if ($f['search']) {
			$in = '';
			//Get all the parts from the search
			$initial = hecidb($f['search']);
			foreach($initial as $id =>$info){
				if ($in) { $in .= ', '; }
				$in .= "'$id'";
			}
			$in = trim($in);

			$serial_search = prep($f['search']);
			$query .= "AND (serial_no = $serial_search ";
			if ($in) { $query .= "OR i.partid IN (".$in.") "; }
			$query .= ") ";
		}
		if ($f['order'] || $f['vendor']) { $query .= "AND pi.id = i.purchase_item_id "; }
		if ($f['vendor']) {$query .= "AND o.po_number = pi.po_number"; }
		$query .= sFilter('i.locationid', $f['locationid']);
		// $query .= sFilter('i.conditionid',$f['conditionid']);
		$query .= sFilter('o.companyid', $f['vendor']);
		$query .= sFilter('pi.po_number',$f['order']);
		if ($f['start']<>'' OR format_date($f['end'],'Y-m-d')<>$GLOBALS['today']) {
			$query .= dFilter('i.date_created',$f['start'], $f['end']);
		}
		$query .= " ORDER BY i.locationid, i.date_created DESC, i.purchase_item_id, i.conditionid;";
		$result = qdb($query) or die(qe());
		if (mysqli_num_rows($result) > 0){
			$results = query_first($result);
		}

		return ($results);
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
			$key = ($row['locationid'] ? $row['locationid'] : '0').'.'.$po.'.'.getCondition($row['conditionid']).'.'.$date.'.'.$row['classification'];
			$partid = $row['partid'];

			if (! isset($r[$partid])) {
				$r[$partid] = array();
			}
			
			//Macro Level information on the key
			 if (!isset($r[$partid][$key])) {
				 $vendorid = getVendor($po);

				 $r[$partid][$key]['serials'] = array();
				 //$r[$partid][$key]['part_name'] =  $info['part']." ".$info['heci']; 
				 $r[$partid][$key]['part_name'] =  $row['part']." ".$row['heci']; 
				 $r[$partid][$key]['partid'] = $row['partid'];
				 $r[$partid][$key]['locationid'] = ($row['locationid'] ? $row['locationid'] : '0');
				 $r[$partid][$key]['location'] = display_location(($row['locationid'] ? $row['locationid'] : '0')) . ($row['bin'] ? ' Bin# ' . $row['bin'] : '');
				 $r[$partid][$key]['place'] = display_location($row['locationid'], 'place');
				 $r[$partid][$key]['instance'] = display_location($row['locationid'], 'instance');
				 $r[$partid][$key]['vendorid'] = $vendorid;
				 $r[$partid][$key]['vendor'] = getCompany($vendorid);
				 $r[$partid][$key]['conditionid'] = $row['conditionid'];
				 $r[$partid][$key]['notes'] = $row['notes'];
				 $r[$partid][$key]['unique'] = $row['invid'];
			 }
			 $r[$partid][$key]['qty'] += $row['qty'];
			 //Null Serial handler and serial grouping/append
			 $serial = 'null';
			 
			//if ($row['serial_no']){
				$r[$partid][$key]['serials'][] = $row['invid'].", ".($row['serial_no'] ? $row['serial_no'] : 'Component').", ".$row['qty'].", ".$row['status'].", ".$row['notes'];
			// }
			// else{
			// 	$r[$partid][$key]['null'][] = $serial;
			// }
		}
		return $r;
	}


$search = grab('search');
$f = grab('filters');


$return = (search($search));
//print_r($return); exit;
if (count($return) > 0){
	echo json_encode($return);
} else {
	echo json_encode('test');
}

// echo '<pre>' . print_r(get_defined_vars(), true) . '</pre>';
// print_r($return);
// exit;
?>
