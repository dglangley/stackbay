<?php

//=============================================================================
//========================= Inventory Edit Submit Template ====================
//=============================================================================
// The order table submit will work with the values of the individual lines of|
// the submitted pages. It will handle each of the rows, and return the       |
// success message upon its completion. This will allow the page to refresh   |
// upon completion of the page.                                               |
//                                                                            | 
// Last update: Aaron Morefield - October 18th, 2016                          |
//=============================================================================	

    header('Content-Type: application/json');

//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	
	//, qty = $qty, item_condition = $condition, status = $status
	function updateToDatabase($serial, $date, $locationid, $qty, $condition, $status, $cost, $partid, $id) {
		$query;
		
	    if($id && $id != "") {
		    $query  = "UPDATE inventory SET serial_no = ". res($serial) .", date_created = '". res($date) ."', locationid = ". res($locationid) .", qty = ". res($qty) .", item_condition = '". res($condition) ."', status = '". res($status) ."' WHERE id = ". res($id) .";";
	    } else {
	        $query  = "INSERT INTO inventory (serial_no, qty, partid, item_condition, status, locationid, last_purchase, last_sale, last_return, repid, date_created, id) VALUES ('". res($serial) ."', '". res($qty) ."', '". res($partid) ."', '". res($condition) ."', '". res($status) ."', '". res($locationid) ."', NULL, NULL, NULL, '1', '". res($date) ."', NULL);";
	    }
	    
	    // INSERT INTO vmmdb.inventory (serial_no, qty, partid, item_condition, status, locationid, last_purchase, last_sale, last_return, repid, date_created, id) VALUES ('111', '1', '1', 'new', 'ordered', '1', NULL, NULL, NULL, '1', '2016-11-08 00:00:00', NULL);
	    
		$result = qdb($query);
		
		return $result;
	}
	
	function getStock($stock = '', $partid = 0) {
		$stockNumber = 0;
		
		//echo $stock . $partid;
		
		
		$query  = "SELECT SUM(qty) FROM inventory WHERE partid = ". res($partid) ." AND item_condition = '". res($stock) ."';";
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

    $id = $_REQUEST['id'];
	$serial = $_REQUEST['serial_no'];
	$date = $_REQUEST['date_created'];
	$location = $_REQUEST['locationid'];
	$qty = $_REQUEST['qty'];
	$condition = $_REQUEST['condition'];
	$status = $_REQUEST['status'];
	$cost = $_REQUEST['cost'];
	$partid = $_REQUEST['partid'];
	
	$result = updateToDatabase($serial, date_format(date_create($date),"Y/m/d H:i:s"), $location, $qty, $condition, $status, $cost, $partid, $id);
	
    echo json_encode(array("result" => $result, "new_stock" =>  getStock('new', $partid), "used_stock" =>  getStock('used', $partid), "refurb_stock" =>  getStock('refurbished', $partid)));
    exit;