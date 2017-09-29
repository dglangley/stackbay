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
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/setInventory.php';
	include_once $rootdir.'/inc/locations.php';

	
	//Function Declarations
	function updateToDatabase($id,$action) {
		global $now;
		$query = '';
		
		//Grab all the line item parts from the inventory submission
		$serial = strtoupper(trim($_REQUEST['serial_no']));
		$place = $_REQUEST['place'];
		$instance = $_REQUEST['instance'];
		$conditionid = $_REQUEST['conditionid'];
		$status = $_REQUEST['status'];
		$invid = $_REQUEST['invid'];
		$partid = $_REQUEST['partid'];
		$notes = grab('notes');
		
		//Handle the repair toggle
		if($action == 'repair' and $status == 'in repair'){
			$status = "received";
		} else if($status){
			$status = "in repair";
		}
		if ($action == "scrap"){
			$status = "scrapped";
		}
		$locationid = dropdown_processor($place, $instance);

		if($action != 'repair') {
			//Some reason, this was still updating with no id
		    if($id) {
				$I = array('id'=>$id,'serial_no'=>$serial,'locationid'=>$locationid,'conditionid'=>$conditionid,'status'=>$status,'notes'=>$notes);
				setInventory($I);
		    } else {
		    	return 'Failed to Update';
		    }
			$result = qdb($query) or die(qe()."$query");
		} else {
			//We are going to generate a new Repair Order for this
			//if(grab('repair_trigger')){
			$ro_number;
			$insert = "INSERT INTO repair_orders (created, created_by, sales_rep_id, companyid, contactid, cust_ref, bill_to_id, ship_to_id, freight_carrier_id, freight_services_id, freight_account_id, termsid, public_notes, private_notes, repair_code_id, status) VALUES (
				".prep($now).",
				".prep($U['id']).",
				NULL,
				'25',
				NULL,
				NULL,
				NULL,
				NULL,
				NULL,
				NULL,
				NULL,
				".prep('15').",
				NULL,
				NULL,
				NULL,
				'Active'
				);";
			//echo $insert . '<br><br>';
			qdb($insert);
			$ro_number = qid();

			//$result = $insert;

			$insert = "INSERT INTO repair_items (partid, ro_number, line_number, qty, price, due_date, invid, warrantyid, notes) VALUES (
				".prep($partid).",
				".prep($ro_number).",
				'1',
				".prep('1').",
				".prep('0.00').",
				NULL,
				".prep($invid).",";

			$insert .=	prep('14').",
				NULL
				);";

				$result = $insert;

			//echo $insert;
			qdb($insert);
			$repair_item_id = qid();

			$update = "UPDATE inventory SET repair_item_id = ".prep($repair_item_id).", status = 'in repair' WHERE id = ".prep($invid).";";
			qdb($update);

				//header("Location: /order_form.php?ps=repair&on=" . $ro_number);
			//}
			$result = $ro_number;
		}
		// echo $query;exit;
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
	
	if($action == 'delete') {
		$result = deleteToDatabase($id);
	} else {
		$result = updateToDatabase($id,$action);
	}
	
    echo json_encode($result);
    exit;
