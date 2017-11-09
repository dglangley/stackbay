<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/saveFiles.php';

	function editExpense($expenses_list, $type){
		//$result = false;

		foreach ($expenses_list as $expense_id => $amount) {
			if($type != 'approve') {
				$amount = '';
			}

			$query = "INSERT INTO service_reimbursement (expense_id, datetime, amount, userid) VALUES (".fres($expense_id).",".fres($GLOBALS['now']).",".fres($amount).",".fres($GLOBALS['U']['id']).");";
			qdb($query) OR die(qe().' '.$query);
		}

		if(! empty($_FILES)) {
			// print '<pre>' . print_r($_FILES, true) . '</pre>';
			foreach($_FILES['files']['error'] as $expense_id => $error) {
				if(! $error) {

					$BUCKET = '';

					$name = $_FILES['files']['name'][$expense_id];
					$temp_name = $_FILES['files']['tmp_name'][$expense_id];

					$file = array('name'=>str_replace($TEMP_DIR,'',$name),'tmp_name'=>$temp_name);
					$file_url = saveFile($file);

					$query = "UPDATE service_expenses SET file = ".fres($file_url)." WHERE id = ".res($expense_id).";";
					qdb($query) OR die(qe() . ' ' . $query);
				}
			}
		}
	}

	$type = 'approve';
	$expenses_list = array();

	if (isset($_REQUEST['expenses'])) { $expenses_list = $_REQUEST['expenses']; }
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }

	editExpense($expenses_list, $type);

	if($result) {
		header('Location: /tasks.php?edit=true');
	} else {
		header('Location: /tasks.php');
	}

	exit;