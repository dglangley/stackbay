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
	$conditionid = grab('conditionid');
	$serial = strtoupper(grab('serial'));
	$savedSerial = strtoupper(grab('savedSerial'));
	$po_number = grab('po_number');
	$item_id = grab('item_id');
	$place = grab('place');
	$instance = grab('instance');
	
	//items = ['partid', 'serial', 'qty', 'location', 'status', 'conditionid'];
	function savetoDatabase($partid, $conditionid, $serial, $po_number, $item_id, $savedSerial, $place, $instance){
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
		$query = "SELECT * FROM inventory WHERE partid = '" . res($partid) . "' AND serial_no = '" . res($serial) . "';";
		$check = qdb($query);
		if($check->num_rows > 0) {
			$result['query'] = false;
		} else {//== 0
			if($savedSerial == '') {// no record for this item previously, so creating a new record

				//DEPRECATED To find this later on
				$query = "UPDATE purchase_items SET qty_received = qty_received + 1 WHERE id = '".res($item_id)."'; ";//po_number = ". res($po_number) ." AND partid = ". res($partid) .";";
				qdb($query);
				
				//Insert the item into the inventory
		 		//$query  = "INSERT INTO inventory (serial_no, qty, partid, conditionid, status, locationid, purchase_item_id, sales_item_id, returns_item_id, userid, date_created, id) VALUES ('". res($serial) ."', '1','". res($partid) ."', '". res($conditionid) ."', 'received', '". res($locationid) ."', '". res($po_number) ."', NULL, NULL, ".$GLOBALS['U']['id'].", '".$GLOBALS['now']."' , NULL);";
		 		$query  = "INSERT INTO inventory (serial_no, qty, partid, conditionid, status, locationid, purchase_item_id, sales_item_id, returns_item_id, userid, date_created, id) VALUES ('". res($serial) ."', '1','". res($partid) ."', '". res($conditionid) ."', 'received', '". res($locationid) ."', '". res($item_id) ."', NULL, NULL, ".$GLOBALS['U']['id'].", '".$GLOBALS['now']."' , NULL);";
				
				//$result['test'] = $query;
				$result['query'] = qdb($query) or die(qe());
				//$result['query'] = $query;
			} else {
				$query = "UPDATE inventory SET serial_no = '". res($serial) ."', conditionid = '". res($conditionid) . "', locationid = '". res($locationid) ."', purchase_item_id = '".res($item_id)."' ";
				$query .= "WHERE serial_no = '". res($savedSerial) ."' AND partid = '". res($partid) ."';";
				$result['query'] = qdb($query) or die(qe());
				$result['saved'] = $serial;
			}
		}
		
		return $result;
	}
	
	$result = savetoDatabase($partid, $conditionid, $serial, $po_number, $item_id, $savedSerial, $place, $instance);
	echo json_encode($result);
    exit;
