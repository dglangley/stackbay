<?php
$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/split_inventory.php';
	include_once $rootdir.'/inc/setInventory.php';

	if (! $DEBUG) {
		//Prepare the page as a JSON type
		header('Content-Type: application/json');
	}

	function getPartClassification($partid) {
		$class = '';

		$query = "SELECT classification FROM parts WHERE id = ".fres($partid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$class = $r['classification'];
		}

		return $class;
	}

	//This is a list of everything
	$partid = grab('partid');
	$serial = strtoupper(grab('serial'));
	$conditionid = grab('conditionid');
	$item_id = grab('item_id');
	$so_number = grab('so_number');
	$package = grab('package_no');
	
	//items = ['partid', 'serial or array of serials', 'qty', 'location or array', 'status or array', 'conditionid or array'];
	function savetoDatabase($partid, $serial, $conditionid, $so_number, $package, $item_id){
		$result = array();
		$exists = false;
		$qty = 0;
		
		//Assume the product is found and everything works until further notice
		$result['query'] = true;
		
		//If the serial is declared then search for it, otherwise query for a lot item.
		if($serial != '') {

			$classification = getPartClassification($partid);

			$query = '';

			if($classification == 'component' Or $classification == 'material') {
				// make sure repair_item_id is null to avoid accidentally pulling designated parts
				$query = "SELECT * FROM inventory WHERE partid = '". res($partid) ."' AND repair_item_id IS NULL AND (status = 'received') LIMIT 0,1;";
			} else {
				//Where there exists a serial number, get serial which is still in stock
				$query = "SELECT * FROM inventory WHERE partid = '". res($partid) ."' AND serial_no = '". res($serial) ."' AND (status = 'received' OR status = 'in repair') LIMIT 0,1;";
			}
			$check = qedb($query);

			if (mysqli_num_rows($check)==0) {
				$result['query'] = false;
				$result['error'] = "Item does not exist or is out of stock for current serial.";
			}
			
			//If the serial exists then run the single serial query... (This is assuming that the serial law is well maintained and not the same item has the same serial number)
			while ($row = mysqli_fetch_assoc($check)) {
				$inventoryid = $row['id'];
					 
				//Check to make sure that the conditions match, else trigger an error (Future dev: trigger a warning to override the order specified condition)
//dgl 2-28-17 at this point we don't care about mismatching condition
//				if($row['conditionid'] == $conditionid) {
					
					$query = '';

					if($classification == 'component' OR $classification == 'material') {
						//Set the qty shipped to the qty spoof into the $serial variable
						$query = "UPDATE sales_items SET qty_shipped = ".fres($serial)." WHERE id = '".$item_id."'; ";
					} else {
						//Set the qty shipped for values on a sales order.
						$query = "UPDATE sales_items SET qty_shipped = qty_shipped + 1 WHERE id = '".$item_id."'; ";
					}

					//partid='". res($partid) ."' AND so_number = '". res($so_number) ."';";
					qedb($query);

					if($classification == 'component' OR $classification == 'material') {
						// Split component feature from materials handler (Pull)
						split_inventory($inventoryid, $serial);	
					}

					$I = array('id'=>$inventoryid,'status'=>'manifest','sales_item_id'=>$item_id);
					setInventory($I);

					//Check to see if the qty received and the qty ordered is matching or not
					$query = "SELECT qty, qty_shipped, CASE WHEN qty = qty_shipped THEN '1' ELSE '0' END AS is_matching FROM sales_items WHERE id = '".$item_id."'; ";//so_number = ". res($so_number) ." AND partid = ". res($partid) .";";
					$match = qedb($query);
					
					//Pair the package to the line item of the inventory number we changed.
					$package_query = "INSERT INTO package_contents (packageid, serialid) VALUES ('$package','$inventoryid');";
					$result['package'] = qedb($package_query);
					
					if (mysqli_num_rows($match)>0) {
						$match = mysqli_fetch_assoc($match);

						//If the qty matches then the order is compelete. Mark it as received with the date received
						if ($match['is_matching']) {
					
							$query = "UPDATE sales_items SET ship_date = CAST('". res(date("Y-m-d")) ."' AS DATE) WHERE id = '".$item_id."'; ";//so_number = ". res($so_number) ." AND partid = ". res($partid) .";";
							qedb($query);
						}
					}
					
					$result['invid'] = $inventoryid;
//dgl 2-28-17 at this point we don't care about mismatching condition
//				} else {
//					$result['query'] = false;
//					$result['error'] = "Item Scanned Condition is: '". $row['condition'] ."' does not match with the specified sales order condition: '$conditionid'.";
//				}
			}
		//Lot item query here
		} else {
			// $query = "SELECT qty FROM inventory WHERE partid = '". res($partid) ."' AND serial_no = IS NULL;";
			// $check = qedb($query);
			
			// if (mysqli_num_rows($check)>0) {
			// 	$row = mysqli_fetch_assoc($check);
			// 	$qty = $row['qty'];
			// 	$exists = true;
			// }
			
			// //Handle lot item and check and make sure that the qty shipped out does not exceed the amount ordered from the SO
			// if($exists && $qty >= 0) {	
			// 	$query = ";";
			// } else {
			// 	$result['query'] = false;
			// 	$result['error'] = "Component Item does not exist or is out of stock.";
			// }
		}
		
		return $result;
	}
	
	$result = savetoDatabase($partid, $serial, $conditionid, $so_number, $package, $item_id);
	echo json_encode($result);
    exit;
