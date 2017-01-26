<?php

	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	
	function getAddresses($addressid,$field = '') {
        
        $result = array();
        
        $result = qdb("Select * From addresses where id = '$addressid'");
        foreach ($result as $row){
            if($field){
                return $row[$field] . ' ' . $row['city'] . ' ' . $row['state'] . ', ' . $row['postal_code'];
            }
            else{
    		    return($row);
            }
        }
	}
?>
