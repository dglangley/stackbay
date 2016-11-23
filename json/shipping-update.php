<?php

    header('Content-Type: application/json');

//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	
	function updateData($so_number, $shipped_Items) {
		$result;
		
		foreach($shipped_Items as $partid) {
			$query = "UPDATE sales_items SET ship_date = CAST('". res(date("Y-m-d")) ."' AS DATE) WHERE so_number = ". res($so_number) ." AND partid = ". res($partid) .";";
			$result = qdb($query);
		}
		
		return $result;
	}
	
	$so_number = $_REQUEST['so_number'];
	$shipped_Items = $_REQUEST['partids'];
	
	$result = updateData($so_number, $shipped_Items);
	
    echo json_encode($shipped_Items);
    exit;