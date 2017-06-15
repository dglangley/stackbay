<?php
    
	header('Content-Type: application/json');
	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
		include_once $rootdir.'/inc/dbconnect.php';
		include_once $rootdir.'/inc/format_date.php';
		include_once $rootdir.'/inc/format_price.php';
		include_once $rootdir.'/inc/getCompany.php';
		include_once $rootdir.'/inc/getWarranty.php';
		include_once $rootdir.'/inc/getPart.php';
		include_once $rootdir.'/inc/pipe.php';
		include_once $rootdir.'/inc/getRep.php';
		include_once $rootdir.'/inc/keywords.php';
		include_once $rootdir.'/inc/getRecords.php';
		include_once $rootdir.'/inc/getRep.php';
		include_once $rootdir.'/inc/form_handle.php';
		include_once $rootdir.'/inc/dropPop.php';
		include_once $rootdir.'/inc/locations.php';
		include_once $rootdir.'/inc/order_parameters.php';
		

    $inventory = grab('inventory');
	$mode = grab('mode');

	function getOrderNumber($line_number, $order_type){
		$o = o_params($order_type);
		$select = "SELECT ".$o['id']." FROM ".$o['item']." where id = ".prep($line_number);
		$old = qdb($select) or die(qe()." $select");
		$result = mysqli_fetch_assoc($old);
		return($result[$o['id']]);
	}

    function display_history($invid){
    	//Initialize variables
    	$output = array();
    	//Take in the inventory id
		$invid = prep($invid);
		// Get current values
		$c_query = "SELECT * FROM inventory WHERE id = $invid;";
		$c = qdb($c_query);
		if (mysqli_num_rows($c) > 0){
			$c = mysqli_fetch_assoc($c);
		}
		//Get associated history items
		$query  = "SELECT * FROM inventory_history WHERE invid = $invid ORDER BY date_changed ASC;";
		$history = qdb($query);
		
    	//Things to eventually consider: record splitting the history
    	
    	if(mysqli_num_rows($history)>0){
			$i = 0;
	    	//Loop through the history results
	    	foreach ($history as $r){
				$string = '';
				switch ($r['field_changed']){
					case 'locationid':
						if($r['changed_from']){
							$string = "Moved from ".display_location($r['changed_from'])." to ".display_location($r['value']);
						} else {
							$string = "Part placed in ".display_location($r['value']);
						}
						//Update the current locationid to make the rest of the phrases make sense.
						//Break the loop
						break;

					case 'serial_no':
						$string = "Serial number changed: ".$r['changed_from']." to ".$r['value'];
						break;

					case 'conditionid':
						$string = "Condition: ".getCondition($r['changed_from'])." to ".($r['value']? getCondition($r['value']) : " unknown ");
						break;
	
					case 'qty':
						continue(2);
						$string = "Quantity ";
						if($r['changed_from'] > $c[$r['field_changed']]){ $string .= "decremented "; }
						else { $string .= "incremented "; }
						$string .= ": ".$r['changed_from']." to ".$r['value'];
						break;

					case 'partid':
						$string = "Part RM'ed: ".getPart($r['changed_from'])." to ".getPart($r['value']);
				
						break;
					case 'status':
						$string = "Status Changed: ".$r['changed_from']." to ".$r['value'];
						break;
					case 'purchase_item_id':
					case 'sales_item_id':
					case 'returns_item_id':
						// $order_type = strtoupper(substr($r['field_changed'],0,1))."O";
						$o = o_params($r['field_changed']);
						/*
						$event = 'purchased';
						if ($r['field_changed']=='returns_item_id') {
							$order_type = 'RMA';
							$event = 'returned';
						} else if ($r['field_changed']=='sales_item_id') {
							$event = 'sold';
						}*/
						$string = "Item ".$o['event']." on ".strtoupper($o['short'])."#".getOrderNumber($r['value'],$r['field_changed']);
						break;

					case 'new':
						continue(2);
						$string = "Item entered into system ";
						break;
					default:
						continue(2);
						break;

				}
				// if($r['date_changed']){
				// 	$string .= " on <strong>".format_date($r['date_changed'], 'n/d/y')."</strong>";
				// }
				if($r['userid']){
					$string .= " by ".getRep($r['userid']);
				}
				$output[(++$i).'. <strong>'.format_date($r['date_changed'],'D n/d/y g:ia').'</strong>'] = ucwords($string);
	    	}
    	}
    	else{
    		$output[] = "No History Record found for this part";
    	}
    	return $output;
    }
    
    if($inventory && $mode == 'get'){
        $output = getItemHistory($inventory);
    }
    else if($inventory && $mode == 'display'){
    	$output = display_history($inventory);
    }
    
    echo json_encode($output);
    exit;
?>
