<?php

//Main
    //Standard includes section
	include_once $_SERVER['ROOT_DIR']."/inc/packages.php";
	header('Content-Type: application/json');
    
    $action = grab('action');
    $order_number = grab('order');
    $name = grab('name');

    echo json_encode(package_update($action,$order_number,$name));
?>