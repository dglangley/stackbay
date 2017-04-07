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
    
    $status;
    
    if($number && $type){
        $query = "SELECT status FROM ".$o['order']." WHERE ".$o['id']." = $number;";
        $result = qdb($query);
        
        if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$status = $result['status'];
		}
    }
    
    //echo $status; die;
    $status = ($status == 'Void' ? 'Active' : 'Void');
    
    if($number && $type){
        $update = "UPDATE ".$o['order']." SET `status` = '$status' WHERE ".$o['id']." = $number;";
        $result = qdb($update);
    }
    
    echo json_encode($number+" | "+$type);
    exit;
?>