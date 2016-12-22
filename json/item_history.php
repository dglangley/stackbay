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
		
    $inventory = grab('inventory');

	function getItemHistory($invid = 0) {
		$query  = "SELECT * FROM inventory_history WHERE invid =" . res($invid) . ";";
		$result = qdb($query);
		
		if(mysqli_num_rows($result) > 0){
    		foreach($result as $history){
                $record = array();
    			$record['Date'] = date_format(date_create($history['date_changed']), 'm/d/Y');
    			$record['Rep'] = getRep($history['repid']);
    			$record['Field'] = $history['field_changed'];
    			$record['History'] = $history['changed_from'];
    	        $output[] = $record;
    		}
		}
		
		return $output;
	}
    
    if($inventory){
        $output = getItemHistory($inventory);
    }

    echo json_encode($output);
    exit;
?>