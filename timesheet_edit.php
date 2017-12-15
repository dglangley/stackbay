<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

	function getUserRate($userid) {
		$rate = 0;

		$query = "SELECT * FROM users WHERE id = ".res($userid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$rate = $r['hourly_rate'];
		}

		return $rate;
	}

	function editTimesheet($data) {
		if(! empty($data)) {
			foreach($data as $key => $element) {
				$query = "UPDATE timesheets SET clockin = ".fres( $element['clockin'] ? date("Y-m-d H:i:s", strtotime($element['clockin'])) : '' ).", clockout = ".fres( $element['clockout'] ? date("Y-m-d H:i:s", strtotime($element['clockout'])) : '' )." WHERE id = ".res($key).";";
				// echo $query;
				qedb($query);
			}
		}
	}

	function addTimesheet($data) {
		if(! empty($data) AND $data['clockin']) {
			$user_rate = getUserRate($data['userid']);
			$query = "INSERT INTO timesheets (userid, clockin, clockout, taskid, task_label, rate) VALUES (".fres($data['userid']).", ".fres( $data['clockin'] ? date("Y-m-d H:i:s", strtotime($data['clockin'])) : '' ).", ".fres( $data['clockout'] ? date("Y-m-d H:i:s", strtotime($data['clockout'])) : '' ).", ".fres($data['taskid']).", ".fres($data['task_label']).", ".fres($user_rate).");";
			// echo $query;
			qedb($query);
		}
	}

	function deleteTimesheet($id) {
		$query = "DELETE FROM timesheets WHERE id = ".res($id).";";
		qdb($query) OR die(qe() . ' ' . $query);
	}

	function payRollApproval($payroll_array) {
		foreach($payroll_array as $key => $amount) {
			$query = "INSERT INTO timesheet_approvals (timesheetid, paid_date, amount, userid) VALUES (".fres($key).", ".fres($GLOBALS['now']).", ".fres($amount).", ".fres($GLOBALS['U']['id']).");";

			qdb($query) OR die(qe() . ' ' . $query);
		}
	}

	// print '<pre>' . print_r($_REQUEST, true). '</pre>';

	$data = array();
	$addTime = array();
	$id = 0;
	$userid = 0;
	$payroll = 0;
	$payroll_num = 0;
	$taskid = 0;

	$payroll_array = array();

	$type = '';

	if (isset($_REQUEST['addTime'])) { $addTime = $_REQUEST['addTime']; }
	if (isset($_REQUEST['data'])) { $data = $_REQUEST['data']; }
	if (isset($_REQUEST['taskid'])) { $taskid = $_REQUEST['taskid']; }
	if (isset($_REQUEST['delete'])) { $delete = $_REQUEST['delete']; }
	if (isset($_REQUEST['userid'])) { $userid = $_REQUEST['userid']; }
	if (isset($_REQUEST['payroll'])) { $payroll_array = $_REQUEST['payroll']; }
	if (isset($_REQUEST['payroll_num'])) { $payroll_num = $_REQUEST['payroll_num']; }
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }

	// A second check to make sure that the user actually has the correct credentials to edit any time items
	if(in_array("4", $USER_ROLES)) {
		if(! empty($delete)) {
			deleteTimesheet($delete);
		} else if($type == 'payroll') {
			payRollApproval($payroll_array);
		} else {
			editTimesheet($data);
			if(! empty($addTime) && $addTime['clockin']) {
				addTimesheet($addTime);
			}
		}
	}

	header('Location: /timesheet.php' . ($userid ? '?user=' . $userid : '') . ($payroll_num ? '&payroll=' . $payroll_num : '') . ($taskid ? '&taskid=' . $taskid : ''));

	exit;