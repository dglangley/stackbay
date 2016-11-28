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
	$productid = $_REQUEST['productid'];
	
	//items = ['partid', 'serial or array of serials', 'qty', 'location or array', 'status or array', 'condition or array'];
	function savetoDatabase($productid){
		$result;
		
		//This is splitting each product from mass of items
		$item_split = array_chunk($productid, 6);
		
		foreach($item_split as $product) {
			
			//If the serial is array then we will serialize each item with custom inputs
			if(is_array ($product[1])) {
				for($i = 0; $i < count($product[1]); $i++) {
					$query  = "INSERT INTO inventory (serial_no, qty, partid, item_condition, status, locationid, last_purchase, last_sale, last_return, repid, date_created, id) VALUES ('". res($product[1][$i]) ."', '1', '". res($product[0]) ."', '". res($product[5][$i]) ."', '". res($product[4][$i]) ."', '". res($product[3][$i]) ."', NULL, NULL, NULL, '1', CAST('". res(date("Y-m-d")) ."' AS DATE), NULL);";
					$result = qdb($query) OR die(qe());
				}
				//$result = $product[1].length;
			} else {
				//items = ['partid', 'serial or array of serials', 'qty', 'location or array', 'status or array', 'condition or array'];
				$query  = "INSERT INTO inventory (serial_no, qty, partid, item_condition, status, locationid, last_purchase, last_sale, last_return, repid, date_created, id) VALUES ('". res($product[1]) ."', '". res($product[2]) ."', '". res($product[0]) ."', '". res($product[5]) ."', '". res($product[4]) ."', '". res($product[3]) ."', NULL, NULL, NULL, '1', CAST('". res(date("Y-m-d")) ."' AS DATE), NULL);";
				$result = qdb($query) OR die(qe());
			}
		}
		
		return $result;
	}
	
	$result = savetoDatabase($productid);
	echo json_encode($result);
    exit;