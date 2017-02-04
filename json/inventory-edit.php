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
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/locations.php';

	
	//, qty = $qty, item_condition = $condition, status = $status
	function updateToDatabase($serial, $locationid, $qty, $condition, $status, $id) {
		$query;
		$serial = prep($serial);
		$locationid = prep($locationid);
		$qty = prep($qty);
		$condition = prep($condition);
		$status = prep($status);
		$id = prep($id);
		
	    if($id && $id != "" && $locationid != null) {
		    $query  = "UPDATE inventory SET serial_no = $serial, locationid = $locationid, qty = $qty, item_condition = $condition, status = $status WHERE id = $id;";
	    } else if($id && $id != "" && $locationid == null) {	  
	    	$query  = "UPDATE inventory SET serial_no = $serial, qty = $qty, item_condition = $condition, status = $status WHERE id = $id;";
	    } else {
	    	return 'Failed to Update';
	    }
	    // echo($query);exit;
	    // else {
	    //     $query  = "INSERT INTO inventory (serial_no, qty, partid, item_condition, status, locationid, last_purchase, last_sale, last_return, userid, date_created, id) VALUES ('". res($serial) ."', '". res($qty) ."', '". res($partid) ."', '". res($condition) ."', '". res($status) ."', '". res($locationid) ."', NULL, NULL, NULL, '1', '". res($date) ."', NULL);";
	    // }
	    
		$result = qdb($query);
		
		return $result;
	}
	
	function deleteToDatabase($id) {
		$id = prep($id);
		$query = "DELETE FROM inventory WHERE id = $id;";
		$result = qdb($query);
		
		return $result;
	}
	
	function getLocationID($place, $instance) {
		$location;
		
		if($instance != '') {
			$query = "SELECT id FROM locations WHERE place ='".res($place)."' AND instance ='".res($instance)."';";
		} else {
			$query = "SELECT id FROM locations WHERE place ='".res($place)."' AND instance is NULL";
		}
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$location = $result['id'];
		}
		
		return $location;
	}

    $id = $_REQUEST['id'];
	$serial = $_REQUEST['serial_no'];
	$place = $_REQUEST['place'];
	$instance = $_REQUEST['instance'];
	$qty = $_REQUEST['qty'];
	$condition = $_REQUEST['condition'];
	$status = $_REQUEST['status'];
	
	// echo("id: $id | serial: $serial | place: $place | instance: $instance | qty: $qty | condition: $condition | status: $status");
	// exit;
	$delete = $_REQUEST['delete'];
	
	$result;
	
	if($delete == '') {
		$result = updateToDatabase($serial, dropdown_processor($place, $instance), $qty, $condition, $status, $id);
	} else {
		$result = deleteToDatabase($id);
	}
	
    echo json_encode($result);
    exit;