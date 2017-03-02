<?php
    $rootdir = $_SERVER['ROOT_DIR'];
    include_once $rootdir.'/inc/getRep.php';
    include_once $rootdir.'/inc/form_handle.php';
    
	//COME BACK TO THIS!!!
	function getItemHistory($invid = 0, $filter = '') {
		$invid = prep($invid);
		$query  = "SELECT * FROM inventory_history WHERE invid = $invid";
		$query .= ($filter == "exchange")? " AND field_changed LIKE '%_item_id'" : '' ; 
		$query .= ";";
		$result = qdb($query);
		
		if(mysqli_num_rows($result) > 0){
    		foreach($result as $history){
                $record = array();
    			$record['date'] = date_format(date_create($history['date_changed']), 'm/d/Y');
    			$record['user'] = getRep($history['userid']);
    			$record['field'] = $history['field_changed'];
    			$record['history'] = $history['changed_from'];
    			$record['value'] = $history['value'];
    	        $output[] = $record;
    		}
		}
		
		return $output;
	}
	

?>