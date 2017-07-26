<?php

//Main
    //Standard includes section
	include_once $_SERVER['ROOT_DIR']."/inc/packages.php";
	header('Content-Type: application/json');
    
    $action = grab('action');
    $package_no = grab('package_no');
    $order_number = grab('order');
    $order_type = grab('type');
    $name = grab('name');
    $id = grab('id');

    if($action == 'delete_package') {
    	// echo $package_id;
    	// exit;
    	echo json_encode(deletePackage($id));
    } else {
    	//exit;
    	echo json_encode(package_edit($action,$id,$order_number,$order_type,$name));
    }
?>