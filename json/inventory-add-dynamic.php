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
	
	//items = ['partid', 'serial or array of serials', 'qty', 'location or array', 'status or array', 'condition or array'];
	function savetoDatabase($partid, $condition, $serial, $po_number, $savedSerial){
		$result = array();
		
		$result['query'] = true;
		
		if($savedSerial == '') {
			$query = "SELECT * FROM inventory WHERE partid = '" . res($partid) . "' AND serial_no = '" . res($serial) . "';";
			$check = qdb($query);
			
			if($check->num_rows == 0) {
				
				$query = "UPDATE purchase_items SET qty_received = qty_received + 1 WHERE po_number = ". res($po_number) ." AND partid = ". res($partid) .";";
				qdb($query);
				
				//Check to see if the aty received and the qty ordered is matching or not
				$query = "SELECT qty, qty_received, CASE WHEN qty = qty_received THEN '1' ELSE '0' END AS is_matching FROM purchase_items WHERE po_number = ". res($po_number) ." AND partid = ". res($partid) .";";
				$match = qdb($query);
				
				if (mysqli_num_rows($match)>0) {
					$match = mysqli_fetch_assoc($match);
					$result['qty_match'] = $match['is_matching'];
				}
	
				//If the qty matches then the order is compelete. Mark it as received with the date received
				if($result['qty_match'] == 1) {
					$query = "UPDATE purchase_items SET receive_date = CAST('". res(date("Y-m-d")) ."' AS DATE) WHERE po_number = ". res($po_number) ." AND partid = ". res($partid) .";";
					qdb($query);
				}
				
				//Insert the item into the inventory
		 		$query  = "INSERT INTO inventory (serial_no, qty, partid, item_condition, status, locationid, last_purchase, last_sale, last_return, repid, date_created, id) VALUES ('". res($serial) ."', '1','". res($partid) ."', '". res($condition) ."', 'received', '', '". res($po_number) ."', NULL, NULL, '1', CAST('". res(date("Y-m-d")) ."' AS DATE), NULL);";
				$result['query'] = qdb($query);
			} else {
				$result['query'] = false;
			}
		} else {
			$query = "UPDATE inventory SET serial_no = '". res($serial) ."' WHERE serial_no = '". res($savedSerial) ."' AND partid = '". res($partid) ."';";
			$result['query'] = qdb($query) or die(qe());
			$result['saved'] = true;
		}
		
		return $result;
	}
	
	$result = savetoDatabase($partid, $condition, $serial, $po_number, $savedSerial);
	echo json_encode($result);
    exit;