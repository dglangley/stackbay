<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$DEBUG = 0;

	function editFinanceAccount($institution, $typeid){

		// Make sure the record does not already exists before adding in the account
		$query = "SELECT * FROM finance_accounts WHERE name = ".fres($institution)." AND type_id = ".fres($typeid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) == 0) {
			$query2 = "INSERT finance_accounts (name, type_id) VALUES (".fres($institution).", ".fres($typeid).");";
			qedb($query2);
		}
	}

	
	if ($DEBUG) { print '<pre>' . print_r($_REQUEST, true). '</pre>'; }

	$institution = '';
	if (isset($_REQUEST['institution'])) { $institution = $_REQUEST['institution']; }

	$typeid = '';
	if (isset($_REQUEST['typeid'])) { $typeid = $_REQUEST['typeid']; }

	editFinanceAccount($institution, $typeid);

	if ($DEBUG) { exit; }
	
	header('Location: /financial.php');

	exit;
