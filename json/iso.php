<?php

//Prepare the page as a JSON type
header('Content-Type: application/json');

$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';

	//This is a list of everything
	$part_no = grab('part_no');
	$heci = grab('heci');
	$damage = grab('damage');
	
	$invid = $_REQUEST['invid'];
	$comments = $_REQUEST['comments'];
	
	$special_req = grab('special_req');
	$contact_info = grab('contact_info');
	$transit_time = grab('transit_time');
	
	$so_number = grab('so_number');
	$type = grab('type');

	function savePart($part_no, $heci, $damage, $so_number, $invid, $comments) {
		$damaged = ($damage == 'true' ? 'yes' : 'no');
		
			
		//Using for loop to parse through matching elements of 2 arrays instead of foreach
		for($i = 0; $i < count($invid); $i++) {
			$query = "UPDATE inventory SET notes = '".res($comments[$i])."' WHERE id = '".res($invid[$i])."';";
			qdb($query);
		}
		
		$query = "REPLACE INTO iso (part, heci, cosmetic, component, so_number) VALUES ('".res($part_no)."', '".res($heci)."', '".res($damaged)."', '".res($damaged)."', '".res($so_number)."');";
		$result = qdb($query);
		
		return $result;
	}
	
	function saveReq($special_req, $contact_info, $transit_time, $so_number, $invid, $comments) {
		//Using for loop to parse through matching elements of 2 arrays instead of foreach
		for($i = 0; $i < count($invid); $i++) {
			$query = "UPDATE inventory SET notes = '".res($comments[$i])."' WHERE id = '".res($invid[$i])."';";
			qdb($query);
		}
		
		$query = "UPDATE iso SET special_req = '".res($special_req)."', shipping_info = '".res($contact_info)."', transit_time = '".res($transit_time)."' WHERE so_number = ".res($so_number).";";
		$result = qdb($query);
		
		return $result;
	}
	
	
	if($type == 'part')
		$result = savePart($part_no, $heci, $damage, $so_number, $invid, $comments);
	else
		$result = saveReq($special_req, $contact_info, $transit_time, $so_number, $invid, $comments);
		
	echo json_encode($result);
    exit;
