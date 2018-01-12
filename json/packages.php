<?php
    //Standard includes section
	include_once $_SERVER['ROOT_DIR']."/inc/dbconnect.php";
	include_once $_SERVER['ROOT_DIR']."/inc/packages.php";

	$DEBUG = 0;

	if (! $DEBUG) {
		header('Content-Type: application/json');
	}

    $action = grab('action');
    $package_no = grab('package_no');
    $order_number = grab('order');
    $order_type = grab('type');
	if ($order_type=='undefined') { $order_type = ''; }
    $name = grab('name');
    $id = grab('id');

    if($action == 'delete_package') {
    	echo json_encode(deletePackage($id));
    } else {
    	echo json_encode(package_edit($action,$id,$order_number,$order_type,$name));
    }
?>
