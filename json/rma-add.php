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
	$productid = $_REQUEST['rmaItems'];
	$rma_number = grab('rma_number');
	
	function getLocation($place, $instance) {
		$locationid;
		$query;
		
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
		
		return $locationid;
	}
	
	function savetoDatabase($productid, $rma_number){
		$result;
		$locationid;
		
		//Fall back if empty to prevent php fatal errors
		if(!empty($productid)) {
			foreach($productid as $product) {
				$locationid = getLocation($product['place'], $product['instance']);

				$query = "UPDATE inventory SET returns_item_id = ". res($product['id']) .", status = 'received', qty = qty + 1, locationid = '". res($locationid) ."' WHERE id = '". res($product['invid']) ."';";
				$result = qdb($query) or die(qe());
			}
		}
		
		return $result;
	}
	
	$result = savetoDatabase($productid, $rma_number);
	echo json_encode($result);
    exit;