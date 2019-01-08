<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/saveFiles.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getFinancialAccounts.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCategory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getItemOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';

	$DEBUG = 0;
	if ($DEBUG) { print "<pre>".print_r($_REQUEST,true)."</pre>"; }

	$taskid = 0;
	$userid = 0;
	$status = '';
	if (isset($_REQUEST['userid'])) { $userid = $_REQUEST['userid']; }
	if (isset($_REQUEST['taskid'])) { $taskid = $_REQUEST['taskid']; }
	if (isset($_REQUEST['status'])) { $status = $_REQUEST['status']; }

	$params = '';
	if ($userid) { $params .= 'userid='.$userid; }
	if ($taskid) {
		if ($params) { $params .= '&'; }
		$params .= 'taskid='.$taskid;
	}
	if ($status) {
		if ($params) { $params .= '&'; }
		$params .= 'status='.$status;
	}
	if (! empty($_REQUEST['filter_companyid'])) {
		if ($params) { $params .= '&'; }
		$params .= 'filter_companyid='.$_REQUEST['filter_companyid'];
	}

	$admin = false;
	if ($U['admin'] OR $U['manager'] OR $U['accounting']) { $admin = true; }

/*
	if (! $admin) {
		if ($params) { $params = '?'.$params; }

		header('Location: expenses.php'.$params);
		exit;
	}
*/

	function saveExpenses($E, $type){

		if ($type=='edit') {
			if (empty($E['reimbursement'])) { $E['reimbursement'] = 0; }

			// get expense data so we can prepare a notice for relevant users that info has been updated
			$changes = trackChanges($E);

			$query = "UPDATE expenses SET item_id = ".fres($E['item_id']).", item_id_label = ".fres($E['item_id_label']);
			$query .= ", companyid = ".fres($E['companyid'])." ";
			$query .= ", expense_date = '".res(format_date($E['expense_date'],'Y-m-d'))."' ";
			$query .= ", description = ".fres($E['description'])." ";
			$query .= ", categoryid = ".fres($E['categoryid'])." ";
			$query .= ", units = '".res($E['units'])."', amount = '".res($E['amount'])."' ";
			$query .= ", financeid = ".fres($E['financeid'])." ";
			$query .= ", reimbursement = '".res($E['reimbursement'])."' ";
			$query .= "WHERE id = '".$E['id']."'; ";
			$result = qedb($query);

			if ($changes) {
				if ($DEV_ENV) {
					$emails = array('david@ven-tel.com','David Langley');
				} else {
					$emails = getSubEmail('expense_edit');
				}

				$sbj = 'Expense '.$E['id'].' has been updated';
				$email_html = $changes;//"Expense ".$E['id']." has been updated:<br><br>".$changes;
				if ($GLOBALS['DEBUG']) {
					echo $sbj.'<BR><BR>'.$email_html.'<BR><BR>';
				} else {
					$send_success = send_gmail($email_html,$sbj,$emails);
					if ($send_success) {
//						die('Success');
					} else {
//					    $ERR = $GLOBALS['SEND_ERR'];
					}
				}
			}
		} else {
			foreach ($E as $id => $amount) {
				if($type != 'approve') { $amount = ''; }

				$query = "INSERT INTO reimbursements (expense_id, datetime, amount, userid) ";
				$query .= "VALUES (".fres($id).",".fres($GLOBALS['now']).",".fres($amount).",".fres($GLOBALS['U']['id']).");";
				$result = qedb($query);
			}
		}

/*
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
					qedb($query);
				}
			}
		}
*/
	}

	function trackChanges($E) {
		$id = $E['id'];
		if (! $id) { return (''); }

		$changes = '';
		$query = "SELECT * FROM expenses WHERE id = '".res($id)."'; ";
		$result = qedb($query);
		if (qnum($result)==0) { return (''); }

		$r = qrow($result);
		if ($r['item_id']<>$E['item_id'] OR $r['item_id_label']<>$E['item_id_label']) {
			if ($changes) { $changes .=', and '; }
			if (! $r['item_id'] OR ! $r['item_id_label']) {
				$changes .= 'added task '.getItemOrder($E['item_id'],$E['item_id_label'],true);
			} else if (! $E['item_id'] OR ! $E['item_id_label']) {
				$changes .= 'removed task '.getItemOrder($r['item_id'],$r['item_id_label'],true);
			} else {
				$changes .= 'changed task from '.getItemOrder($r['item_id'],$r['item_id_label'],true).' to '.getItemOrder($E['item_id'], $E['item_id_label'], true);
			}
		}
		if ($r['companyid']<>$E['companyid']) {
			if ($changes) { $changes .=', and '; }
			if (! $r['companyid']) {
				$changes .= 'added vendor '.getCompany($E['companyid']);
			} else if (! $E['companyid']) {
				$changes .= 'removed vendor '.getCompany($r['companyid']);
			} else {
				$changes .= 'changed vendor from '.getCompany($r['companyid']).' to '.getCompany($E['companyid']);
			}
		}
		if ($r['expense_date']<>format_date($E['expense_date'],'Y-m-d')) {
			if ($changes) { $changes .=', and '; }
			if (! $r['expense_date']) {
				$changes .= 'added the expense date '.format_date($E['expense_date']);
			} else if (! $E['expense_date']) {
				$changes .= 'removed the expense date '.format_date($r['expense_date']);
			} else {
				$changes .= 'changed the expense date from '.format_date($r['expense_date']).' to '.format_date($E['expense_date']);
			}
		}
		if ($r['description']<>$E['description']) {
			if ($changes) { $changes .=', and '; }
			if (! $r['description']) {
				$changes .= 'added notes "'.$E['description'].'"';
			} else if (! $E['description']) {
				$changes .= 'removed notes "'.$r['description'].'"';
			} else {
				$changes .= 'changed notes from "'.$r['description'].'" to "'.$E['description'].'"';
			}
		}
		if ($r['categoryid']<>$E['categoryid']) {
			if ($changes) { $changes .=', and '; }
			if (! $r['categoryid']) {
				$changes .= 'added expense category '.getCategory($E['categoryid']);
			} else if (! $E['categoryid']) {
				$changes .= 'removed expense category '.getCategory($r['categoryid']);
			} else {
				$changes .= 'changed expense category from '.getCategory($r['categoryid']).' to '.getCategory($E['categoryid']);
			}
		}
		if (($r['units']*$r['amount'])<>($E['units']*$E['amount'])) {
			if ($changes) { $changes .=', and '; }
			$changes .= 'changed amount from $'.number_format(($r['units']*$r['amount']),2,'.',',').' to '.number_format(($E['units']*$E['amount']),2,'.',',');
		}
		if ($r['financeid']<>$E['financeid']) {
			if ($changes) { $changes .=', and '; }
			if (! $r['financeid']) {
				$changes .= 'added account '.getFinanceName($E['financeid']);
			} else if (! $E['financeid']) {
				$changes .= 'removed account '.getFinanceName($r['financeid']);
			} else {
				$changes .= 'changed account from '.getFinanceName($r['financeid']).' to '.getFinanceName($E['financeid']);
			}
		}
		if ($r['reimbursement']<>$E['reimbursement']) {
			if ($changes) { $changes .=', and '; }
			$changes .= 'changed reimbursement from '.($r['reimbursement'] ? 'YES' : 'NO').' to '.($E['reimbursement'] ? 'YES' : 'NO');
		}

		if ($changes) {
			$changes = $GLOBALS['U']['name'].' '.$changes;
		}

		return ($changes);
	}

	function addExpense($expense_date, $description, $amount, $userid, $categoryid, $companyid=0, $reimbursement=0, $financeid = 0) {
		global $TEMP_DIR, $FILE_ERR;

		$query = "INSERT INTO expenses (expense_date, description, amount, file, userid, datetime, categoryid, companyid, units, reimbursement, financeid) ";
		$query .= "VALUES (".fres(date('Y-m-d', strtotime(str_replace('-', '/', $expense_date)))).",".fres($description).",".fres($amount).",";
		$query .= fres($file).",".fres($userid).",".fres($GLOBALS['now']).", ".fres($categoryid).", ".fres($companyid).", 1, '".res($reimbursement)."', ".fres($financeid).");";
		qedb($query);

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
				qedb($query);

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
				qedb($query);
			}
		}
	}

	$type = 'approve';
	$expenses = array();

	// These values are for the expenses add feature on the expenses page
	$expense_date = '';
	$expense_userid = 0;
	$description = '';
	$categoryid = 0;
	$companyid = setCompany();
	$reimbursement = 0;
	$financeid;
	$amount = 0;
	$id = 0;

	if (isset($_REQUEST['reimbursement'])) { $expenses = $_REQUEST['reimbursement']; }
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }

	if (isset($_REQUEST['categoryid'])) { $categoryid = $_REQUEST['categoryid']; }
	if (isset($_REQUEST['reimbursement'])) { $reimbursement = $_REQUEST['reimbursement']; }
	if (isset($_REQUEST['financeid'])) { $financeid = $_REQUEST['financeid']; }

	if (isset($_REQUEST['expense_date'])) { $expense_date = $_REQUEST['expense_date']; }
	if (isset($_REQUEST['description'])) { $description = $_REQUEST['description']; }
	if (isset($_REQUEST['amount'])) { $amount = $_REQUEST['amount']; }
	if (isset($_REQUEST['id'])) { $id = $_REQUEST['id']; }
	if (isset($_REQUEST['expense_userid'])) { $expense_userid = $_REQUEST['expense_userid']; }

	if (! $expense_userid) { $expense_userid = $userid; }

	if($type == 'add') {
		addExpense($expense_date, $description, $amount, $expense_userid, $categoryid, $companyid, $reimbursement, $financeid);

		if ($DEBUG) { exit; }

		if ($params) { $params = '?'.$params; }
		header('Location: /expenses.php'.$params);
		exit;
	} else if ($admin) {
		if ($type=='delete' AND $id) {
			$query = "UPDATE expenses SET status = 'Void' WHERE id = '".res($id)."'; ";
			$result = qedb($query);
		} else {
			if ($type=='edit') {
				$expenses = array(
					'item_id' => (! empty($_REQUEST['item_id']) ? $_REQUEST['item_id'] : ''),
					'item_id_label' => (! empty($_REQUEST['item_id_label']) ? $_REQUEST['item_id_label'] : ''),
					'companyid' => (! empty($_REQUEST['companyid']) ? $_REQUEST['companyid'] : ''),
					'expense_date' => (! empty($_REQUEST['expense_date']) ? $_REQUEST['expense_date'] : ''),
					'description' => (! empty($_REQUEST['description']) ? $_REQUEST['description'] : ''),
					'categoryid' => (! empty($_REQUEST['categoryid']) ? $_REQUEST['categoryid'] : ''),
					'units' => (! empty($_REQUEST['units']) ? $_REQUEST['units'] : ''),
					'amount' => (! empty($_REQUEST['amount']) ? $_REQUEST['amount'] : ''),
					'financeid' => (! empty($_REQUEST['financeid']) ? $_REQUEST['financeid'] : ''),
					'reimbursement' => (! empty($_REQUEST['reimbursement']) ? $_REQUEST['reimbursement'] : ''),
					'id' => (! empty($_REQUEST['expenseid']) ? $_REQUEST['expenseid'] : ''),
				);
			}
			saveExpenses($expenses, $type);
		}
	}

	if ($DEBUG) { exit; }

/*
	if ($result) {
		if ($params) { $params = '&'.$params; }
		header('Location: /expenses.php?edit=true'.$params);
	} else {
*/
		if ($params) { $params = '?'.$params; }
		header('Location: /expenses.php'.$params);
//	}

	exit;
