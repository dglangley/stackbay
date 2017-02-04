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
		include_once $rootdir.'/inc/keywords.php';
		include_once $rootdir.'/inc/getRecords.php';
		include_once $rootdir.'/inc/getRep.php';
		include_once $rootdir.'/inc/form_handle.php';
		include_once $rootdir.'/inc/dropPop.php';
		include_once $rootdir.'/inc/locations.php';
		

    $inventory = grab('inventory');
	$mode = grab('mode');

	//COME BACK TO THIS!!!
	function getItemHistory($invid = 0) {
		$invid = prep($invid);
		$query  = "SELECT * FROM inventory_history WHERE invid = $invid;";
		$result = qdb($query);
		
		if(mysqli_num_rows($result) > 0){
    		foreach($result as $history){
                $record = array();
    			$record['date'] = date_format(date_create($history['date_changed']), 'm/d/Y');
    			$record['user'] = getRep($history['userid']);
    			$record['field'] = $history['field_changed'];
    			$record['history'] = $history['changed_from'];
    	        $output[] = $record;
    		}
		}
		
		return $output;
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
		$query  = "SELECT * FROM inventory_history WHERE invid = $invid ORDER BY date_changed DESC;";
		$history = qdb($query);
		
    	//Things to eventually consider: record splitting the history
    	
    	if(mysqli_num_rows($history)>0){
	    	//Loop through the history results
	    	foreach ($history as $r){
				switch ($r['field_changed']){
					case 'locationid':
						$string = "Moved from ".display_location($r['changed_from'])." to ".display_location($c['locationid'])." on ".format_date($r['date_changed'], 'm/d/Y');
						if($r['userid']){
							$string .= " by ".getContact($r['userid']);
						}
						$output[] = $string;
						//Update the current locationid to make the rest of the phrases make sense.
						$c['locationid'] = $r['changed_from'];
						//Break the loop
						continue 2;
					case 'serial_no':
						$string = "Serial number was changed from ".$r['changed_from']." to ".$c[$r['field_changed']]." on ".format_date($r['date_changed'], 'm/d/Y');
						if($r['userid']){
							$string .= " by ".getContact($r['userid']);
						}
						$output[] = $string;
						$c[$r['field_changed']] = $r['field_changed'];
						continue 2;
					case 'condition':
						$string = "Condition was updated from ".$r['changed_from']." to ".$c[$r['field_changed']]." on ".format_date($r['date_changed'], 'm/d/Y');
						if($r['userid']){
							$string .= " by ".getContact($r['userid']);
						}
						$output[] = $string;
						$c[$r['field_changed']] = $r['field_changed'];
						continue 2;
	
					case 'qty':
						if($r['changed_from'] > $c[$r['field_changed']]){
							$string = "Quantity decremented from ".$r['changed_from']." to ".$c[$r['field_changed']]." on ".format_date($r['date_changed'], 'm/d/Y');
							if($r['userid']){
								$string .= " by ".getContact($r['userid']);
							}
							$output[] = $string;
						}
						else{
							$string = "Quantity incremented from ".$r['changed_from']." to ".$c[$r['field_changed']]." on ".format_date($r['date_changed'], 'm/d/Y');
							if($r['userid']){
								$string .= " by ".getContact($r['userid']);
							}
							$output[] = $string;
						}
						$c[$r['field_changed']] = $r['field_changed'];
						continue 2;
					case 'partid':
						$string = "Part was changed from ".getPart($r['changed_from'])." to ".getPart($c[$r['field_changed']])." on ".format_date($r['date_changed'], 'm/d/Y');
						if($r['userid']){
							$string .= " by ".getContact($r['userid']);
						}
						$output[] = $string;
						$c[$r['field_changed']] = $r['field_changed'];
						continue 2;
					case 'last_purchase':
						$string = "Item was previously puchased from PO$".$r['changed_from']." to ".$c[$r['field_changed']]." on ".format_date($r['date_changed'], 'm/d/Y');
						if($r['userid']){
							$string .= " by ".getContact($r['userid']);
						}
						$output[] = $string;
						$c[$r['field_changed']] = $r['field_changed'];
						continue 2;
					case 'last_sale':
						$string = "Item was previously sold on SO#".$r['changed_from']." to ".$c[$r['field_changed']]." on ".format_date($r['date_changed'], 'm/d/Y');
						if($r['userid']){
							$string .= " by ".getContact($r['userid']);
						}
						$output[] = $string;
						$c[$r['field_changed']] = $r['field_changed'];
						continue 2;
					case 'last_sale':
						$string = "Item was previously sold on SO#".$r['changed_from']." to ".$c[$r['field_changed']]." on ".format_date($r['date_changed'], 'm/d/Y');
						if($r['userid']){
							$string .= " by ".getContact($r['userid']);
						}
						$output[] = $string;
						$c[$r['field_changed']] = $r['field_changed'];
						continue 2;
					case 'new':
						$string = "Item entered into system on ".format_date($r['date_changed'], 'm/d/Y');
						if($r['userid']){
							$string .= " by ".getContact($r['userid']);
						}
						$output[] = $string;
						continue 2;
				}
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