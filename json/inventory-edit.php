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

	
	//Function Declarations
	function updateToDatabase($id,$action) {
		$query = '';
		
		//Grab all the line item parts from the inventory submission
		$serial = $_REQUEST['serial_no'];
		$place = $_REQUEST['place'];
		$instance = $_REQUEST['instance'];
		$conditionid = $_REQUEST['conditionid'];
		$status = $_REQUEST['status'];
		$notes = grab('notes');
		
		//Handle the repair toggle
		if($action == 'repair' and $status == 'in repair'){
			$status = "shelved";
		} else if($status){
			$status = "in repair";
		}
		
		//Prep Query information
		$serial = prep($serial);
		$locationid = prep($locationid);
		$qty = prep($qty);
		$conditionid = prep($conditionid);
		$status = prep($status);
		$notes = prep($notes);
		$id = prep($id);
		
		//Some reason, this was still updating with no id
	    if($id) {
		    $query  = "UPDATE inventory SET serial_no = $serial, locationid = $locationid, conditionid = $conditionid, notes = $notes, ";
			if($status){
				$query .= " ,status = $status";
			}
			if($locationid){
				$query .= " ,locationid = $locationid";
			}
		    $query .= " WHERE id = $id;";
	    } else {
	    	return 'Failed to Update';
	    }
		$result = qdb($query);
		
		return $result;
	}
	function deleteToDatabase($id) {
		$id = prep($id);
		$query = "DELETE FROM inventory WHERE id = $id;";
		$result = qdb($query);
		
		return $result;
	}
	

//=========================== Main ===========================	
	$result = '';
	$id = grab('id');
	$action = grab('action');
	
	if(action == 'delete') {
		$result = deleteToDatabase($id);
	} else {
		// $result = updateToDatabase($serial, dropdown_processor($place, $instance), $qty, $conditionid, $status, $id, $notes);
		$result = updateToDatabase($id,$action);
	}
	
    echo json_encode($result);
    exit;
