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
	$productItems = $_REQUEST['items'];
	$so_number = grab('so_number');
	
	$date = date("Y-m-d h:i:sa");
	
	//items = ['partid', 'Already saved serial','serial or array of serials', 'condition or array', 'lot', 'qty']
	function savetoDatabase($productItems, $so_number, $date){
		$result = [];
		
		//This is splitting each product from mass of items
		$item_split = array_chunk($productItems, 7);
		
		foreach($item_split as $product) {
			//This query updates and saves the box as closed only if there are no errors in the order
				foreach($product[6] as $box) {
					$check;
					//Check and only ship boxes that have something placed into them
					$query = "SELECT * FROM package_contents WHERE packageid = '".res($box)."';";
					$data = qdb($query);
					
					if (mysqli_num_rows($data)>0) {
						$query = "UPDATE packages SET datetime ='".res($date)."' WHERE id = '".res($box)."' AND datetime is NULL;";
						$check = qdb($query);
					}
				}
				
				$result['timestamp'] = $date;
				$result['on'] = $so_number;
		}
		
		return $result;
	}
	
	$result = savetoDatabase($productItems, $so_number, $date);
	
	echo json_encode($result);
    exit;