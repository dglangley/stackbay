<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/saveFiles.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	function editExpense($expenses_list, $type){
		//$result = false;

		foreach ($expenses_list as $expense_id => $amount) {
			if($type != 'approve') {
				$amount = '';
			}

			$query = "INSERT INTO reimbursements (expense_id, datetime, amount, userid) VALUES (".fres($expense_id).",".fres($GLOBALS['now']).",".fres($amount).",".fres($GLOBALS['U']['id']).");";
			qdb($query) OR die(qe().' '.$query);
		}

		if(! empty($_FILES)) {
			$BUCKET = 'ventel.stackbay.com-receipts';
			// print '<pre>' . print_r($_FILES, true) . '</pre>';
			foreach($_FILES['files']['error'] as $expense_id => $error) {
				if(! $error) {

					$name = $_FILES['files']['name'][$expense_id];
					$temp_name = $_FILES['files']['tmp_name'][$expense_id];

					$file = array('name'=>str_replace($TEMP_DIR,'',$name),'tmp_name'=>$temp_name);
					$file_url = saveFile($file);

					$query = "UPDATE expenses SET file = ".fres($file_url)." WHERE id = ".res($expense_id).";";
					qdb($query) OR die(qe() . ' ' . $query);
				}
			}
		}
	}

	function addExpense($expenseDate, $description, $amount, $userid, $categoryid, $companyid=0, $reimbursement=0) {
		$query = "INSERT INTO expenses (expense_date, description, amount, file, userid, datetime, categoryid, companyid, units, reimbursement) ";
		$query .= "VALUES (".fres(date('Y-m-d', strtotime(str_replace('-', '/', $expenseDate)))).",".fres($description).",".fres($amount).",";
		$query .= fres($file).",".fres($userid).",".fres($GLOBALS['now']).", ".fres($categoryid).", ".fres($companyid).", 1, '".res($reimbursement)."');";
		qdb($query) OR die(qe() . ' ' . $query);

		// echo $query;

		$expense_id = qid();

		if(! empty($_FILES)) {
			$BUCKET = 'ventel.stackbay.com-receipts';
			//print '<pre>' . print_r($_FILES, true) . '</pre>';
			if(! $_FILES['files']['error']) {

				$name = $_FILES['files']['name'];
				$temp_name = $_FILES['files']['tmp_name'];

				$file = array('name'=>str_replace($TEMP_DIR,'',$name),'tmp_name'=>$temp_name);
				$file_url = saveFile($file);

				$query = "UPDATE expenses SET file = ".fres($file_url)." WHERE id = ".res($expense_id).";";

				qdb($query) OR die(qe() . ' ' . $query);
			}
		}
	}

	$type = 'approve';
	$expenses_list = array();

	// print '<pre>' . print_r($_REQUEST, true) . '</pre>';

	// These values are for the expenses add feature on the expenses page
	$expenseDate = '';
	$description = '';
	$categoryid = 0;
	$companyid = 0;
	$reimbursement = 0;
	$amount = 0;
	$userid = 0;

	if (isset($_REQUEST['expenses'])) { $expenses_list = $_REQUEST['expenses']; }
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }

	if (isset($_REQUEST['categoryid'])) { $categoryid = $_REQUEST['categoryid']; }
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	if (isset($_REQUEST['reimbursement'])) { $reimbursement = $_REQUEST['reimbursement']; }
	if (isset($_REQUEST['userid'])) { $userid = $_REQUEST['userid']; }

	if (isset($_REQUEST['expenseDate'])) { $expenseDate = $_REQUEST['expenseDate']; }
	if (isset($_REQUEST['description'])) { $description = $_REQUEST['description']; }
	if (isset($_REQUEST['amount'])) { $amount = $_REQUEST['amount']; }

	if($type == 'add_expense') {
		addExpense($expenseDate, $description, $amount, $userid, $categoryid, $companyid, $reimbursement);

		header('Location: /expenses.php?user='.$userid);
		exit;
	} else {
		editExpense($expenses_list, $type);
	}

	if($result) {
		header('Location: /expenses.php?edit=true');
	} else {
		header('Location: /expenses.php');
	}

	exit;
