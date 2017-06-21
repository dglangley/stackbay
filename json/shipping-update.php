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
	include_once $rootdir.'/inc/setCogs.php';
	include_once $rootdir.'/inc/getCost.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/invoice.php';

	//This is a list of everything
	$productItems = $_REQUEST['items'];
	$so_number = grab('so_number');
	

	//items = ['partid', 'Already saved serial','serial or array of serials', 'conditionid or array', 'lot', 'qty']
	function savetoDatabase($productItems, $so_number, $date){
		$result = [];
		
		//This is splitting each product from mass of items
		$item_split = array_chunk($productItems,7);
		
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
					// added by david 6-21-17 to set profits and cogs on each unit being shipped; this section should be for billable sales only!
					while ($r = mysqli_fetch_assoc($result)) {
						$inventoryid = $r['serialid'];
						$query2 = "SELECT si.id FROM inventory i, sales_items si ";
						$query2 .= "WHERE si.so_number = '".res($so_number)."' AND i.id = '".res($inventoryid)."' AND si.id = i.sales_item_id; ";
						$result2 = qdb($query2);// OR die(qe().'<BR>'.$query2);
						while ($r2 = mysqli_fetch_assoc($result2)) {
							$cogs = getCost($partid);//get existing cost at this point in time
							$profitid = setCogs($inventoryid, $r2['id'], $cogs);
						}
					}
				}
				
				$result['timestamp'] = $date;
				$result['on'] = $so_number;
				
				//Invoice Creation based off shipping
		}
		
		create_invoice($so_number, $date, "Sale");
		
		return $result;
	}
	
	$result = savetoDatabase($productItems, $so_number, $now);
	
	echo json_encode($result);
    exit;
