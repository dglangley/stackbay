<?php

//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/order_parameters.php';
	
    $number = grab('number');
    $type = grab('type');
    $o = o_params($type);
    $result = false;
    
    if($number && $type){
        $update = "UPDATE ".$o['order']." SET `status` = 'Void' WHERE ".$o['id']." = $number;";
        $result = qdb($update);
    }
    
    echo json_encode($number+" | "+$type);
    exit;
?>