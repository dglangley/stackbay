<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/saveFiles.php';

	$DEBUG = 0;
	$ALERT = '';

	function insertExpense($taskid, $T, $date, $userid, $categoryid, $companyid, $accountid, $units, $amount, $notes, $reimb, $mileage) {
		// print_r($_REQUEST);
		// print_r($_FILES); die();

		if($categoryid == 91) {
			// 91 == gas so grab the mileage_rate and calculate the amount

			$amount = $mileage * $units;
		}

		$query = "INSERT INTO expenses (item_id, item_id_label, companyid, expense_date, description, categoryid, units, amount, financeid, userid, datetime, reimbursement) ";

		$query .= "VALUES (".res($taskid).", ".fres($T['item_label']).", ".fres($companyid).", ".fres(format_date($date, 'Y-m-d')).", ".fres($notes).", ".fres($categoryid).", ".fres(($units ? : 1)).", ".fres($amount).", ".fres($accountid).", ".fres($userid).", ".fres($GLOBALS['now']).", ".fres(($reimb) ? 1 : 0).");";

		// echo $query; die();
		qedb($query);

		$expenseid = qid();

		if(! empty($_FILES)) {
			if(! $_FILES['files']['error']) {

				$BUCKET = 'ventel.stackbay.com-receipts';

				$name = $_FILES['files']['name'];
				$temp_name = $_FILES['files']['tmp_name'];
				$file = array('name'=>str_replace($TEMP_DIR,'',$name),'tmp_name'=>$temp_name);
				$file_url = saveFile($file);

				$query = "UPDATE expenses SET file=".fres($file_url)." WHERE id=".res($expenseid).";";
				qedb($query);
			}
		}
	}

	function updateMileage($mileage, $taskid, $T) {
		$query = "UPDATE ".$T['items']." SET mileage_rate = ".fres($mileage)." WHERE id = ".res($taskid).";";
		qedb($query);
	}

	function deleteExpense($expense_id) {
		global $USER_ROLES, $ALERT; 


		$userid = 0;

		// First check the user id of the selected expense id
		$query = "SELECT userid FROM expenses WHERE id = ".res($expense_id).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$userid = $r['userid'];
		}

		// Check if the expense has already been reimbursed using the reimbursement table
		$query = "SELECT * FROM reimbursements WHERE expense_id = ".res($expense_id).";";
		$result = qedb($query);

		// If the expense has already been reimbursed then do not allow the user to delete the expense and warn them of their wrong doings
		if(mysqli_num_rows($result) > 0) {
			$ALERT = "Can not delete expense! Expense has already been reimbursed.";

			return false;
		}

		if($userid == $GLBOALS['U']['id'] OR array_intersect($USER_ROLES,array(1,4))) {
			$query = "DELETE FROM expenses WHERE id = ".fres($expense_id).";";
			qedb($query);
		} else {
			$ALERT = "Warning. You are trying to delete another user's expense. Permission denied!";
		}
	}

	// Order details
	$taskid = '';
	if (isset($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }
	$type = '';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }
	$order_number = '';
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }

	// Form submit values
	$date = '';
	if (isset($_REQUEST['date'])) { $date = trim($_REQUEST['date']); }
	$userid = '';
	if (isset($_REQUEST['userid'])) { $userid = trim($_REQUEST['userid']); }
	$units = 1;
	if (isset($_REQUEST['units'])) { $units = trim($_REQUEST['units']); }
	$categoryid = '';
	if (isset($_REQUEST['categoryid'])) { $categoryid = trim($_REQUEST['categoryid']); }
	$companyid = '';
	if (isset($_REQUEST['companyid'])) { $companyid = trim($_REQUEST['companyid']); }
	$accountid = '';
	if (isset($_REQUEST['accountid'])) { $accountid = trim($_REQUEST['accountid']); }
	$amount = 0;
	if (isset($_REQUEST['amount'])) { $amount = trim($_REQUEST['amount']); }
	$notes = '';
	if (isset($_REQUEST['notes'])) { $notes = trim($_REQUEST['notes']); }
	$reimb = '';
	if (isset($_REQUEST['reimb'])) { $reimb = trim($_REQUEST['reimb']); }
	$delete = '';
	if (isset($_REQUEST['delete'])) { $delete = trim($_REQUEST['delete']); }

	$mileage = '';
	if (isset($_REQUEST['mileage'])) { $mileage = trim($_REQUEST['mileage']); }

	$T = order_type($type);

	if($delete) {
		deleteExpense($delete);
	} else {
		insertExpense($taskid, $T, $date, $userid, $categoryid, $companyid, $accountid, $units, $amount, $notes, $reimb, $mileage);

		if($mileage) {
			updateMileage($mileage, $taskid, $T);
		}
	}
	
	// Responsive Testing
	$responsive = false;
	if (isset($_REQUEST['responsive'])) { $responsive = trim($_REQUEST['responsive']); }

	$link = '/serviceNEW.php';

	if($responsive) {
		$link = '/responsive_task.php';
	} 

	header('Location: '.$link.'?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=expenses' . ($ALERT?'&ALERT='.$ALERT:''));
	// header('Location: /serviceNEW.php?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=expenses' . ($ALERT?'&ALERT='.$ALERT:''));

	exit;
