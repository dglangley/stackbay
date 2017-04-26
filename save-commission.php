<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';

	function updateCommission($rate,$userid) {
		if(!empty($rate)) {
			$query = "UPDATE users SET commission_rate = $rate WHERE id = userid;";
			qdb($query) OR die(qe().' '.$query);
		} 
	}
	
	//Variables used among all saves
	$submit_type = '';
	
	//Variables for commission
	$commisson = array();
	
	//Get submit type IMPORTANT
	if (isset($_REQUEST['submit'])) { $submit_type = $_REQUEST['submit']; }
	
	//Get base needed items (**this is a hack to make the first if statement work)
	//Commission
	if (isset($_REQUEST['commission'])) { $commission = $_REQUEST['commission']; }
	
	$msg = 'Success';
	if (!$commission) {
		$msg = 'Missing valid input data';
	//We are saving contacts here
	} else {
		foreach($commission as $userid => $rate) {
			updateCommission($rate, $userid);
		}
	}

	header('Location: /commission.php?update=true');
	exit;
