<?php
//Main
    //Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
    include_once $rootdir.'/inc/packages.php';
    
    header('Content-Type: application/json');

    $order_number = grab('order_number');
    $package_number = grab('package_number');
    
    $packageid = package_id($order_number, $package_number);
    $package_contents = package_contents($packageid);
    
    echo json_encode($package_contents);
?>