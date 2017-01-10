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
	
	function checkShipDate($so_number, $partid, $condition) {
		//Query to check the SO and make sure that the user can still add more items that still reflect the actual PO items
		//If 1 open slot and 2 users adding to that one slot, first come first serve.
		$query = "SELECT CASE WHEN ship_date  IS NULL THEN '1' ELSE '0' END AS check_SO FROM sales_items WHERE so_number = ". res($so_number) ." AND partid = ". res($partid) ." AND cond = '". res($condition) ."';";
		$incomplete = qdb($query);
		
		$incomplete = mysqli_fetch_assoc($incomplete);
		$incomplete = $incomplete['check_SO'];
		
		return $incomplete;
	}
	
	function checkNewItems($partid, $serial_no, $condition) {
		//Check if the item already exists in the database
		//Prevent duplicate entries or initiates a update on the item
		
		// Seperate Lot query from standard serialized queries
		// Lot matches condition and determines if a new lot record needs to be created or not
		if($serial_no == 'null' && $condition != '') {
			$query = "SELECT partid, serial_no FROM inventory WHERE serial_no IS NULL AND partid = ". res($partid) ." AND item_condition = '". res($condition) ."' AND qty > 0;";
		} else {
			$query = "SELECT partid, serial_no FROM inventory WHERE serial_no = '". res($serial_no) ."' AND partid = ". res($partid) ." AND qty > 0;";
		}
		$match = qdb($query);
		$exists = false;
		if (mysqli_num_rows($match)>0) {
			$exists = true;
		}
		
		return $exists;
	}
	
	function checkSOShipped($so_number, $partid, $condition) {
		
		//Check how many of the item is received and match to make sure not too many entries of the part ordered is inputed without tracking
		$query = "SELECT qty, qty_shipped, CASE WHEN qty = qty_shipped THEN '1' ELSE '0' END AS is_matching FROM sales_items WHERE so_number = ". res($so_number) ." AND partid = ". res($partid) ." AND cond = '". res($condition) ."';";
		$match = qdb($query);
		
		$match = mysqli_fetch_assoc($match);
		$matchQTY = $match['is_matching'];
		
		//If the qty and the qty received matches the PO then mark the PO as complete with the date that it was completed on
		if($matchQTY == 1) {
			$query = "UPDATE sales_items SET ship_date = CAST('". res(date("Y-m-d")) ."' AS DATE) WHERE so_number = ". res($so_number) ." AND partid = ". res($partid) ." AND cond = '". res($condition) ."';";
			qdb($query);
		}
	}
	
	//items = ['partid', 'Already saved serial','serial or array of serials', 'condition or array', 'lot', 'qty']
	function savetoDatabase($productItems, $so_number){
		$result = [];
		
		//This is splitting each product from mass of items
		$item_split = array_chunk($productItems, 6);
		
		foreach($item_split as $product) {
			//If serial is an array then parse thru everything in the partid as an array of items
			if($product[4] == 'false' && sizeof($product[2]) > 1) {
				$result['type'] = 'array';
				for($i = 0; $i < sizeof($product[2]); $i++) {
					//If the serial is not null then proceed, otherwise ignore the entry
					if($product[2][$i] != '') {
						//If there is an actual saved serial then initiate editing of the product
						//Else insert the new item
						
						//Check if the item exists already in the inventory
						$exists = checkNewItems(reset($product), $product[2][$i], $product[3]);
						$incomplete = checkShipDate($so_number, reset($product), $product[3]);
						
						if($product[1][$i]  == '') {
							//If the serial exists then proceed to outbound phase of the shipment
							if($incomplete == 1 && $exists) {
								//Increase the qty received by 1 for each item received
								$query = "UPDATE sales_items SET qty_shipped = qty_shipped + 1 WHERE so_number = ". res($so_number) ." AND partid = ". res(reset($product)) ." AND cond = '". res($product[3]) ."';";
								qdb($query);
		
								//Check the SO and - inventory or mark a ship date
								checkSOShipped($so_number, reset($product), $product[3]);
								
								//['partid', 'Already saved serial','serial or array of serials', 'condition or array', 'lot', 'qty']
								$query = "UPDATE inventory SET qty = qty - 1, status = 'outbound', last_sale = '". res($so_number) ."' WHERE partid='". res(reset($product)) ."' AND serial_no = '". res(reset($product[2])) ."' AND item_condition = '". res($product[3]) ."';";
								$result['query'] = qdb($query);
							}
						//This scenario is treated as the user has already registered the item, but decided to change the item they want to ship of same partid but different serial and on top of that they do not match
						} else if($exists && $product[1][$i] != $product[2][$i]) {
							//Restore the previous Serialized item back to in stock and received
							$query = "UPDATE inventory SET qty = qty + 1 WHERE serial_no = '" . res($product[1][$i]) . "' AND partid = ". res(reset($product)) ." AND item_condition = '". res($product[3]) ."';";
							qdb($query);
							
							$query = "UPDATE inventory SET qty = qty - 1 WHERE serial_no = '" . res($product[2][$i]) . "' AND partid = ". res(reset($product)) ." AND item_condition = '". res($product[3]) ."';";
							$result['query'] = qdb($query);
							
							//Error check and if the new item fails then default back to the original
							if(!$result['query']) {
								
							}
						}
						//Else everything matches so check other fields for changes
					}
				}
			//Single item update or insert	
			//Check if lot is false and that the serial is not empty
			} else if($product[4] == 'false' && reset($product[2]) != '') {
				$exists = checkNewItems(reset($product), reset($product[2]), $product[3]);
				$incomplete = checkShipDate($so_number, reset($product), $product[3]);
				
				//No items exists in the inventory so continue inserting of the item and there is still room to add more items
				if($incomplete == 1 && $exists) {
					
					//Increase the qty received by 1 for each item received
					$query = "UPDATE sales_items SET qty_shipped = qty_shipped + 1 WHERE so_number = ". res($so_number) ." AND partid = ". res(reset($product)) ." AND cond = '". res($product[3]) ."';";
					qdb($query);
					
					//Check if the order is complete by order number, partid and condition
					checkSOShipped($so_number, reset($product), $product[3]);
					
					//['partid', 'Already saved serial','serial or array of serials', 'condition or array', 'lot', 'qty']
			 		$query = "UPDATE inventory SET qty = qty - 1, status = 'outbound', last_sale = '". res($so_number) ."' WHERE partid='". res(reset($product)) ."' AND serial_no = '". res(reset($product[2])) ."' AND item_condition = '". res($product[3]) ."';";
					$result['query'] = qdb($query);
					
				//The item already exists (Any updates will be made to the actual product EG Serial edits)
				} else {
					$result['error'] = 'Warning: Serial# ' . reset($product[2]) . ' does not exists or is out of stock, all other items have been saved.';
				}
			//Handler if lot is checked
			} else if($product[4] == 'true') {
				$result['type'] = 'Lot Item Detected';
				
				//Check and make sure the lot being made does not exceed the amount of actual items ordered
				$curQty;
				$query = "SELECT qty_shipped FROM sales_items WHERE so_number = ". res($so_number) ." AND partid = ". res(reset($product)) ." AND cond = '". res($product[3]) ."';";
				$query = qdb($query);
				
				if (mysqli_num_rows($query)>0) {
					$count = mysqli_fetch_assoc($query);
					$curQty = $count['qty_shipped'];
				}
				
				//Add in the new lot that user is trying to add
				$curQty += $product[5];
				
				//Prevent user from adding more than the intended stock ordered on the SO
				$query = "SELECT qty, CASE WHEN qty < '" . res($curQty) . "' THEN '1' ELSE '0' END AS is_matching FROM sales_items WHERE so_number = ". res($so_number) ." AND partid = ". res(reset($product)) ." AND cond = '". res($product[3]) ."';";
				$match = qdb($query);
				
				$count = mysqli_fetch_assoc($match);
				$matchCond = $count['is_matching'];

				if($matchCond == '0') {
					
					$result['lot_received'] = $curQty;

					//Check if the item already exists in the inventory
					$exists = checkNewItems(reset($product), 'null', $product[3]);
					
					//If exists then update the lot with no serial else insert
					if($exists) {
						
						$query = "UPDATE sales_items SET qty_shipped = " . res($curQty) . " WHERE so_number = ". res($so_number) ." AND partid = ". res(reset($product)) ." AND cond = '". res($product[3]) ."';";
						qdb($query);
						
						//Quick check to see if the inventory has been fulfilled
						checkSOShipped($so_number, reset($product), $product[3]);
						
						//Check if the lot is not being compeletely sold then split and make a new record and deduct from that order for history tracking
						$query = "SELECT qty, CASE WHEN qty = '" . res($curQty) . "' THEN '1' ELSE '0' END AS is_matching FROM sales_items WHERE so_number = ". res($so_number) ." AND partid = ". res(reset($product)) ." AND cond = '". res($product[3]) ."';";
						$match = qdb($query);
						
						$count = mysqli_fetch_assoc($match);
						$leftovers = $count['is_matching'];
						
						//Quantity shipped matches the entire lot inventory, no splitting
						//Is Null prevents partial lot later entire lot from updating the split shipment
						if($leftovers == 1) {
							$query  = "UPDATE inventory SET qty = qty -  ". res($product[5]) .", status = 'outbound', last_sale = '". res($so_number) ."'  WHERE serial_no IS NULL AND partid = ". res(reset($product)) ." AND item_condition = '". res($product[3]) ."' AND last_sale IS NULL;";
							$result['query'] = qdb($query);
						//Quanity does not match split the order out and create a new row for the sold item
						} else {
							//This updates the main unsold items, hence IS NULL for the last sales order is a must
							$query  = "UPDATE inventory SET qty = qty -  ". res($product[5]) ."  WHERE serial_no IS NULL AND partid = ". res(reset($product)) ." AND item_condition = '". res($product[3]) ."' AND last_sale IS NULL;";
							$result['lot_split'] = qdb($query);

							$query  = "INSERT INTO inventory (serial_no, qty, partid, item_condition, status, locationid, last_purchase, last_sale, last_return, repid, date_created, id) VALUES (NULL, '". res($product[5]) ."','". res(reset($product)) ."', '". res($product[3]) ."', 'received', '', NULL, '". res($so_number) ."', NULL, '1', CAST('". res(date("Y-m-d")) ."' AS DATE), NULL);";
							$result['lot_split_add'] = qdb($query);
							$result['query'] = true;
							
							$id = qid();

							//Split was successful? set it up as compelete and outbound
							if($result['lot_split_add'] && $id != ''){
								$query  = "UPDATE inventory SET qty = 0, status = 'outbound'  WHERE id = " . res($id) . ";";
								$result['lot_split'] = qdb($query);
								$result['query'] = true;
							}
						}
					} else {
						$result['error'] = 'Warning: Lot does not exists or is out of stock, all other items have been saved.';
					}
				} else {
					$result['error'] = 'Lot exceeds the amount ordered.';
				}
			}
			//Else do not touch the item
		}
		
		return $result;
	}
	
	$result = savetoDatabase($productItems, $so_number);
	
	echo json_encode($result);
    exit;