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
	$productid = $_REQUEST['productItems'];
	$po_number = grab('po_number');
	
	function checkReceiveDate($po_number, $partid) {
		//Query to check the PO and make sure that the user can still add more items that still reflect the actual PO items
		//If 1 open slot and 2 users adding to that one slot, first come first serve.
		$query = "SELECT CASE WHEN receive_date  IS NULL THEN '1' ELSE '0' END AS check_PO FROM purchase_items WHERE po_number = ". res($po_number) ." AND partid = ". res($partid) .";";
		$incomplete = qdb($query);
		
		return $incomplete->num_rows;
	}
	
	function checkNewItems($partid, $serial_no, $condition = '', $locationid = '') {
		//Check if the item already exists in the database
		//Prevent duplicate entries or initiates a update on the item
		
		//Seperate Lot query from standard serialized queries
		//Lot matches condition and determines if a new lot record needs to be created or not
		if($serial_no == 'null' && $condition != '') {
			$query = "SELECT partid, serial_no FROM inventory WHERE serial_no IS NULL AND partid = ". res($partid) ." AND item_condition = '". res($condition) ."' AND locationid = '". res($locationid) ."';";
		} else {
			$query = "SELECT partid, serial_no FROM inventory WHERE serial_no = '". res($serial_no) ."' AND partid = ". res($partid) .";";
		}
		$match = qdb($query);
		$exists = false;
		if (mysqli_num_rows($match)>0) {
			$exists = true;
		}
		
		return $exists;
	}
	
	function checkPOReceived($po_number, $partid) {
		
		//Check how many of the item is received and match to make sure not too many entries of the part ordered is inputed without tracking
		$query = "SELECT qty, qty_received, CASE WHEN qty = qty_received THEN '1' ELSE '0' END AS is_matching FROM purchase_items WHERE po_number = ". res($po_number) ." AND partid = ". res($partid) .";";
		$match = qdb($query);
		
		if (mysqli_num_rows($match)>0) {
			$match = mysqli_fetch_assoc($match);
			$matchQTY = $match['is_matching'];
		}
		
		//If the qty and the qty received matches the PO then mark the PO as complete with the date that it was completed on
		if($matchQTY == 1) {
			$query = "UPDATE purchase_items SET receive_date = CAST('". res(date("Y-m-d")) ."' AS DATE) WHERE po_number = ". res($po_number) ." AND partid = ". res($partid) .";";
			qdb($query);
		}
	}
	
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
	
	//items = ['partid', 'Already saved serial','serial or array of serials', 'condition or array', 'lot', 'qty', 'part', 'instance']
	function savetoDatabase($productid, $po_number){
		$result = [];
		
		//This is splitting each product from mass of items
		$item_split = array_chunk($productid, 8);
		
		foreach($item_split as $product) {
			//If serial is an array then parse thru everything in the partid as an array of items
			if($product[4] == 'false' && sizeof($product[2]) > 1) {
				$result['type'] = 'array';
				for($i = 0; $i < sizeof($product[2]); $i++) {
					//If the serial is not null then proceed, otherwise ignore the entry
					if($product[2][$i] != '') {
						//If there is an actual saved serial then initiate editing of the product
						//Else insert the new item
						$locationid = getLocation($product[6][$i], $product[7][$i]);
						
						if($product[1][$i]  == '') {
							//Check if the item exists already in the inventory
							$exists = checkNewItems(reset($product), $product[2][$i]);
							
							$incomplete = checkReceiveDate($po_number, reset($product));
							
							//No items exists in the inventory so continue inserting of the item and there is still room to add more items
							if($incomplete == 1 && !$exists) {
								//Increase the qty received by 1 for each item received
								$query = "UPDATE purchase_items SET qty_received = qty_received + 1 WHERE po_number = ". res($po_number) ." AND partid = ". res(reset($product)) .";";
								qdb($query);
		
								//Check the PO and add inventory or mark a receive date
								checkPOReceived($po_number, reset($product));
								
								//['partid', 'Already saved serial','serial or array of serials', 'condition or array', 'lot', 'qty']
								$query  = "INSERT INTO inventory (partid, serial_no, qty, item_condition, status, locationid, last_purchase, last_sale, last_return, userid, date_created, id) VALUES ('". res(reset($product)) ."', '" . res($product[2][$i]) . "', '1', '". res($product[3][$i]) ."', 'received', '". res($locationid) ."', '". res($po_number) ."', NULL, NULL, '1', CAST('". res(date("Y-m-d")) ."' AS DATE), NULL);";
								$result['query'] = qdb($query);	
							} else {
								$result['error'] = 'Warning: Serial# ' . reset($product[2]) . ' already exists, item will be ignored, all other items have been saved.';
							}
						//Else if the 2 do not match (Saved Serial and New Serial) and the new serial does not exists yet
						} else if(!$exists) {
							$query  = "UPDATE inventory SET item_condition = '" . res($product[3][$i]) . "', serial_no = '" . res($product[2][$i]) . "', locationid = '". res($locationid) ."' WHERE serial_no = '" . res($product[1][$i]) . "' AND partid = ". res(reset($product)) .";";
							$result['query'] = qdb($query);
						}
						//Else everything matches so check other fields for changes
					}
				}
			//Single item update or insert	
			//Check if lot is false and that the serial is not empty
			} else if($product[4] == 'false' && reset($product[2]) != '') {
				
				$exists = checkNewItems(reset($product), reset($product[2]));
				$incomplete = checkReceiveDate($po_number, reset($product));
				$locationid = getLocation(reset($product[6]), reset($product[7]));
				
				$result['location'] = $locationid;
				
				//No items exists in the inventory so continue inserting of the item and there is still room to add more items
				if($incomplete == 1 && !$exists) {
					//Increase the qty received by 1 for each item received
					$query = "UPDATE purchase_items SET qty_received = qty_received + 1 WHERE po_number = ". res($po_number) ." AND partid = ". res(reset($product)) .";";
					qdb($query);
					
					checkPOReceived($po_number, reset($product));
					
					//['partid', 'Already saved serial','serial or array of serials', 'condition or array', 'lot', 'qty']
			 		$query  = "INSERT INTO inventory (serial_no, qty, partid, item_condition, status, locationid, last_purchase, last_sale, last_return, userid, date_created, id) VALUES ('". res(reset($product[2])) ."', '1','". res(reset($product)) ."', '". res(reset($product[3])) ."', 'received', '". res($locationid) ."', '". res($po_number) ."', NULL, NULL, '1', CAST('". res(date("Y-m-d")) ."' AS DATE), NULL);";
					$result['query'] = qdb($query);
					
					//Test echo of items
					//$result = 'Serial NEW ' . sizeof($product[2]);
				//The item already exists (Any updates will be made to the actual product EG Serial edits)
				} else {
					$result['error'] = 'Warning: Serial# ' . reset($product[2]) . ' already exists, item will be ignored, all other items have been saved.';
					//return $result;
				}
			//Handler if lot is checked
			} else if($product[4] == 'true') {
				$result['type'] = 'Lot Item Detected';
				$locationid = getLocation(reset($product[6]), reset($product[7]));
				
				//Check and make sure the lot being made does not exceed the amount of actual items ordered
				$curQty;
				$query = "SELECT qty_received FROM purchase_items WHERE po_number = ". res($po_number) ." AND partid = ". res(reset($product)) .";";
				$query = qdb($query);
				
				if (mysqli_num_rows($query)>0) {
					$count = mysqli_fetch_assoc($query);
					$curQty = $count['qty_received'];
				}
				
				//Add in the new lot that user is trying to add
				$curQty += $product[5];
				
				//Prevent user from adding more than the intended stock ordered on the PO
				$query = "SELECT qty, CASE WHEN qty < '" . res($curQty) . "' THEN '1' ELSE '0' END AS is_matching FROM purchase_items WHERE po_number = ". res($po_number) ." AND partid = ". res(reset($product)) .";";
				$match = qdb($query);
				
				if (mysqli_num_rows($match)>0) {
					$count = mysqli_fetch_assoc($match);
					$matchCond = $count['is_matching'];
				}
				
				if($matchCond == '0') {
					$query = "UPDATE purchase_items SET qty_received = " . res($curQty) . " WHERE po_number = ". res($po_number) ." AND partid = ". res(reset($product)) .";";
					qdb($query);
					
					$result['lot_received'] = $curQty;
					
					//Quick check to see if the inventory has been fulfilled
					checkPOReceived($po_number, reset($product));
					
					//Check if the item already exists in the inventory
					$exists = checkNewItems(reset($product), 'null', reset($product[3]), $locationid);
					
					//If exists then update the lot with no serial else insert
					if($exists) {
						$query  = "UPDATE inventory SET qty = qty +  ". res($product[5]) .", last_purchase = '". res($po_number) ."', locationid = '". res($locationid) ."' WHERE serial_no IS NULL AND partid = ". res(reset($product)) ." AND item_condition = '". res(reset($product[3])) ."';";
						$result['query'] = qdb($query);
					} else {
						$query  = "INSERT INTO inventory (partid, serial_no, qty, item_condition, status, locationid, last_purchase, last_sale, last_return, userid, date_created, id) VALUES ('". res(reset($product)) ."', NULL, '" . res($product[5]) . "', '". res(reset($product[3])) ."', 'received', '". res($locationid) ."', '". res($po_number) ."', NULL, NULL, '1', CAST('". res(date("Y-m-d")) ."' AS DATE), NULL);";
						$result['query'] = qdb($query);	
					}
				} else {
					$result['error'] = 'Lot exceeds the amount ordered.';
				}
			}
			//Else do not touch the item
		}
		
		return $result;
	}
	
	$result = savetoDatabase($productid, $po_number);
	echo json_encode($result);
    exit;