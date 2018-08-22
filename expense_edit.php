<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/saveFiles.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';

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

	function addExpense($expenseDate, $description, $amount, $userid, $categoryid, $companyid=0, $reimbursement=0, $financeid = 0) {
		global $TEMP_DIR, $FILE_ERR;

		$query = "INSERT INTO expenses (expense_date, description, amount, file, userid, datetime, categoryid, companyid, units, reimbursement, financeid) ";
		$query .= "VALUES (".fres(date('Y-m-d', strtotime(str_replace('-', '/', $expenseDate)))).",".fres($description).",".fres($amount).",";
		$query .= fres($file).",".fres($userid).",".fres($GLOBALS['now']).", ".fres($categoryid).", ".fres($companyid).", 1, '".res($reimbursement)."', ".fres($financeid).");";
		qdb($query) OR die(qe() . ' ' . $query);

		// echo $query;

		$expense_id = qid();

		if(! empty($_FILES)) {
			$BUCKET = 'ventel.stackbay.com-receipts';
			// print '<pre>' . print_r($_FILES, true) . '</pre>';

			$fileCount = count($_FILES['files']['name']);

			if($fileCount > 1) {
				$files = $_FILES['files'];

				// Multiple Files Detected
				$file = 'Expense_Receipts.zip';
				//echo $file; die();
				$zip = new ZipArchive();
				if ($zip->open($file, ZipArchive::CREATE) !== TRUE) {
				    die ("Could not open archive");
				}

				foreach ($_FILES['files']['name'] as $f => $name) {   
					$fileName = $_FILES['files']['name'][$f];
					$fileContent = file_get_contents($_FILES["files"]["tmp_name"][$f]);
					$fileType = $_FILES['files']['type'][$f];
					$fileSize = $_FILES['files']['size'][$f];
			        $fileExt = pathinfo($_FILES['files']['name'][$f], PATHINFO_EXTENSION);;

					$zip->addFromString($fileName, $fileContent);
				}

				// closes the archive
				$zip->close();

				$files = array('name'=>$file,'tmp_name'=>$file);

				$file_url = saveFile($files);

				if($FILE_ERR) {
					die($FILE_ERR);
				}

				$query = "UPDATE expenses SET file = ".fres($file_url)." WHERE id = ".res($expense_id).";";

				qdb($query) OR die(qe() . ' ' . $query);

				if(file_exists($file)){
				    unlink($file);
				} else {
					die('failed to create zip');
				}
			} 

			if(! $_FILES['files']['error'][0] AND $fileCount == 1) {

				$name = $_FILES['files']['name'][0];
				$temp_name = $_FILES['files']['tmp_name'][0];

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
	$companyid = setCompany();
	$reimbursement = 0;
	$financeid;
	$amount = 0;
	$userid = 0;

	if (isset($_REQUEST['expenses'])) { $expenses_list = $_REQUEST['expenses']; }
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }

	if (isset($_REQUEST['categoryid'])) { $categoryid = $_REQUEST['categoryid']; }
	if (isset($_REQUEST['reimbursement'])) { $reimbursement = $_REQUEST['reimbursement']; }
	if (isset($_REQUEST['financeid'])) { $financeid = $_REQUEST['financeid']; }
	if (isset($_REQUEST['userid'])) { $userid = $_REQUEST['userid']; }

	if (isset($_REQUEST['expenseDate'])) { $expenseDate = $_REQUEST['expenseDate']; }
	if (isset($_REQUEST['description'])) { $description = $_REQUEST['description']; }
	if (isset($_REQUEST['amount'])) { $amount = $_REQUEST['amount']; }

	if($type == 'add_expense') {
		addExpense($expenseDate, $description, $amount, $userid, $categoryid, $companyid, $reimbursement, $financeid);

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
