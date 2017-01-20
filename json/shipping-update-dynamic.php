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
	$serial = grab('serial');
	$condition = grab('condition');
	$so_number = grab('so_number');
	$package = grab('package_no');
	
	//items = ['partid', 'serial or array of serials', 'qty', 'location or array', 'status or array', 'condition or array'];
	function savetoDatabase($partid, $serial, $condition, $so_number, $package){
		$result = array();
		$exists = false;
		$qty = 0;
		
		//Assume the product is found and everything works until further notice
		$result['query'] = true;
		
		//If the serial is declared then search for it, otherwise query for a lot item.
		if($serial != '') {
			
			//Where there exists a serial number, get all the values of the serial which are still in stock
			$query = "SELECT * FROM inventory WHERE partid = '". res($partid) ."' AND serial_no = '". res($serial) ."' AND qty > 0;";
			$check = qdb($query);
			
			//If the serial exists then run the single serial query... (This is assuming that the serial law is well maintained and not the same item has the same serial number)
			if($check->num_rows == 1) {
				$check = mysqli_fetch_assoc($check);
					 
				//Check to make sure that the conditions match, else trigger an error (Future dev: trigger a warning to override the order specified condition)
				if($check['item_condition'] == $condition) {
					
					//Set the qty shipped for values on a sales order.
					$query = "UPDATE sales_items SET qty_shipped = qty_shipped + 1 WHERE partid='". res($partid) ."' AND so_number = '". res($so_number) ."';";
					qdb($query);
					
					$query = "UPDATE inventory SET qty = qty - 1, status = 'outbound', last_sale = '". res($so_number) ."' WHERE partid='". res($partid) ."' AND serial_no = '". res($serial) ."';";
					$result['query'] = qdb($query);
					
					//Check to see if the aty received and the qty ordered is matching or not
					$query = "SELECT qty, qty_shipped, CASE WHEN qty = qty_shipped THEN '1' ELSE '0' END AS is_matching FROM sales_items WHERE so_number = ". res($so_number) ." AND partid = ". res($partid) .";";
					$match = qdb($query) or die(qe());
					
					//Pair the package to the line item of the inventory number we changed.
					$inventory_id = $check['id'];
					$package_query = "INSERT INTO package_contents VALUES ('$package','$inventory_id');";
					$result['package'] = qdb($package_query) OR die(qe());
					
					if (mysqli_num_rows($match)>0) {
						$match = mysqli_fetch_assoc($match);
						$result['qty_match'] = $match['is_matching'];
					}
					
					//If the qty matches then the order is compelete. Mark it as received with the date received
					if($result['qty_match'] == 1) {
						$query = "UPDATE sales_items SET ship_date = CAST('". res(date("Y-m-d")) ."' AS DATE) WHERE so_number = ". res($so_number) ." AND partid = ". res($partid) .";";
						qdb($query);
					}
				} else {
					$result['query'] = false;
					$result['error'] = "Item Scanned Condition is: '". $check['item_condition'] ."' does not match with the specified sales order condition: '$condition'.";
				}
			} else {
				$result['query'] = false;
				$result['error'] = "Item does not exist or is out of stock for current serial.";
			}
		//Lot item query here
		} else {
			$query = "SELECT qty FROM inventory WHERE partid = '". res($partid) ."' AND serial_no = IS NULL;";
			$check = qdb($query);
			
			if (mysqli_num_rows($check)>0) {
				$check = mysqli_fetch_assoc($check);
				$qty = $check['qty'];
				$exists = true;
			}
			
			//Handle lot item and check and make sure that the qty shipped out does not exceed the amount ordered from the SO
			if($exists && $qty >= 0) {	
				$query = ";";
			} else {
				$result['query'] = false;
				$result['error'] = "Lot Item does not exist or is out of stock.";
			}
		}
		
		return $result;
	}
	
	$result = savetoDatabase($partid, $serial, $condition, $so_number,$package);
	echo json_encode($result);
    exit;