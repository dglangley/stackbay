<?php

//Main
    //Standard includes section
	include_once $_SERVER['ROOT_DIR']."/inc/packages.php";
	header('Content-Type: application/json');
    
    $action = grab('action');
    $order_number = grab('order');
    $order_type = grab('type');
    $name = grab('name');
    $id = grab('id');
    echo json_encode(package_edit($action,$id,$order_number,$order_type,$name));
?>