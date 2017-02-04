<?php

//Prepare the page as a JSON type
header('Content-Type: application/json');

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
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';

	//This is a list of everything
	$partid = grab('partid');
	$condition = grab('condition');
	$serial = grab('serial');
	$savedSerial = grab('savedSerial');
	$po_number = grab('po_number');
	$place = grab('place');
	$instance = grab('instance');
	
	//items = ['partid', 'serial', 'qty', 'location', 'status', 'condition'];
	function savetoDatabase($partid, $condition, $serial, $po_number, $savedSerial, $place, $instance){
		$result = array();
		$locationid;
		$query;
		$result['partid'] = $partid;
		
		//Get the location ID based on the preset ones in the table
		if($instance != '') {
			$query = "SELECT id FROM locations WHERE place = '". res($place) ."' AND instance = '". res($instance) ."';";
		} else {
			$query = "SELECT id FROM locations WHERE place = '". res($place) ."' AND instance is NULL;";
		}
		$locationResult = qdb($query);
		if (mysqli_num_rows($locationResult)>0) {
			$locationResult = mysqli_fetch_assoc($locationResult);
			$locationid = $locationResult['id'];
		}
		
		$result['query'] = true;
		
		if($savedSerial == '') {
			$query = "SELECT * FROM inventory WHERE partid = '" . res($partid) . "' AND serial_no = '" . res($serial) . "';";
			$check = qdb($query);

			if($check->num_rows == 0) {
				
				//DEPRECATED To find this later on
				$query = "UPDATE purchase_items SET qty_received = qty_received + 1 WHERE po_number = ". res($po_number) ." AND partid = ". res($partid) .";";
				qdb($query);
				
				
				//Insert the item into the inventory
		 		$query  = "INSERT INTO inventory (serial_no, qty, partid, item_condition, status, locationid, last_purchase, last_sale, last_return, userid, date_created, id) VALUES 
		 		('". res($serial) ."', '1','". res($partid) ."', '". res($condition) ."', 'received', '". res($locationid) ."', '". res($po_number) ."', NULL, NULL, ".$GLOBALS['U']['id'].", ".$GLOBALS['now']." , NULL);";
				
				$result['query'] = qdb($query);
			} else {
				$result['query'] = false;
			}
		} else {
			$query = "UPDATE inventory SET serial_no = '". res($serial) ."', item_condition = '". res($condition) . "', locationid = '". res($locationid) ."' WHERE serial_no = '". res($savedSerial) ."' AND partid = '". res($partid) ."';";
			$result['query'] = qdb($query) or die(qe());
			$result['saved'] = $serial;
		}
		
		return $result;
	}
	
	$result = savetoDatabase($partid, $condition, $serial, $po_number, $savedSerial, $place, $instance);
	echo json_encode($result);
    exit;