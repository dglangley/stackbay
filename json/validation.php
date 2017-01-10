<?php
	header('Content-Type: application/json');
	$rootdir = $_SERVER['ROOT_DIR'];
		
	//include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/form_handle.php';
	
	//Please insert here what types of special fields you would like to validate (Should match the special functions created to validate each)
	$checks = array('email', 'phone', 'url');
	
	$data_array = array();
	$valid_array = array();
	
	//Function to check valid email
	function check_email($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}
	
	//Function to check valid phone number
	function check_phone($phone) {
		$isPhoneNum = false;
		
		//eliminate every char except 0-9
		$phone_No = preg_replace("/[^0-9]/", '', $phone);
		
		//eliminate leading 1 if its there
		if (strlen($phone_No) == 11) $phone_No = preg_replace("/^1/", '',$phone_No);
		
		//if we have 10 digits left is phone number possibly
		if (strlen($phone_No) == 10) $isPhoneNum = true;
		
		return $isPhoneNum;
	}
	
	function check_url($url) {
		return filter_var($website_url, FILTER_SANITIZE_URL);
	}
	
	//Run through all the data and determine by search which validation check to take
	function filter_keys($data_array) {
		global $checks;
		
		$valid_array = array();
		
		foreach($data_array as $key => $input) {
			$valid_check = true;
			foreach($checks as $keyword) {
		        if (strpos($key, $keyword) !== false){
		        	$function = 'check_' . $keyword;
		        	$valid_check = ($function($input) != false ? true : " " . ucwords($keyword) . " is invalid");
		        } 
		    }
		    $valid_array[$key] = $valid_check;
		}
		
		return $valid_array;
	}
	
    //Grab all post data passed into validation and pass it as a key value associative array
	foreach ($_POST as $key => $value) {
	    $data_array[$key] = $value;
	}
	
	$valid_array = filter_keys($data_array);
    
    echo json_encode($valid_array);
    exit;
?>