<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$DEBUG = 0;

	function editFinanceAccount($institution, $nickname, $digits, $typeid){

		// Make sure the record does not already exists before adding in the account
		$query = "SELECT * FROM finance_accounts WHERE bank = ".fres($institution)." AND nickname = ".fres($nickname)." AND account_number = ".fres($digits)." AND type_id = ".fres($typeid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) == 0) {
			$query2 = "INSERT finance_accounts (bank, nickname, account_number, type_id, status) VALUES (".fres($institution).", ".fres($nickname).", ".fres($digits).", ".fres($typeid).", 'Active');";
			qedb($query2);
		} else {
			// Exists already so just update the status accordingly
			$r = mysqli_fetch_assoc($result);
			$query2 = "UPDATE finance_accounts SET status = 'Active' WHERE id = ".fres($r['id']).";";
			qedb($query2);
		}
	}

	function deleteFinancialAccount($account_id) {
		$query = "UPDATE finance_accounts SET status = 'Void' WHERE id = ".fres($account_id).";";
		qedb($query);
	}

	
	if ($DEBUG) { print '<pre>' . print_r($_REQUEST, true). '</pre>'; }

	$institution = '';
	if (isset($_REQUEST['institution'])) { $institution = $_REQUEST['institution']; }

	$nickname = '';
	if (isset($_REQUEST['nickname'])) { $nickname = $_REQUEST['nickname']; }

	$digits = '';
	if (isset($_REQUEST['digits'])) { $digits = $_REQUEST['digits']; }

	$typeid = '';
	if (isset($_REQUEST['typeid'])) { $typeid = $_REQUEST['typeid']; }

	$deleteid = '';
	if (isset($_REQUEST['deleteid'])) { $deleteid = $_REQUEST['deleteid']; }

	if($deleteid) {
		deleteFinancialAccount($deleteid);
	}

	if($institution AND $typeid AND $nickname AND $digits) {
		editFinanceAccount($institution, $nickname, $digits, $typeid);
	}

	if ($DEBUG) { exit; }
	
	header('Location: /financial.php');

	exit;
