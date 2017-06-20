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
        if($type != 'Repair') {
            $query = "SELECT status FROM ".$o['order']." WHERE ".$o['id']." = $number;";
        } else {
           $query = "SELECT repaircodeid as status FROM ".$o['order']." WHERE ".$o['id']." = $number;"; 
        }
        $result = qdb($query);
        
        if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$status = $result['status'];
		}
    }
    
    //echo $status; die;
    if($type == 'Repair') {
        $status = ($status == '18' ? '' : '18');
        $update = "UPDATE ".$o['order']." SET `repaircodeid` = '$status' WHERE ".$o['id']." = $number;";
        $result = qdb($update);
    } else if($number && $type){
        $status = ($status == 'Void' ? 'Active' : 'Void');
        $update = "UPDATE ".$o['order']." SET `status` = '$status' WHERE ".$o['id']." = $number;";
        $result = qdb($update);
    }
    
    echo json_encode($number+" | "+$type);
    exit;
?>