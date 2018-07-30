<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

	$ALERT = '';

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

	function checkOverlapping($data) {
		global $ALERT;

		$alert_str = '';

		$clockin = date("Y-m-d H:i:s", strtotime($data['clockin']));
		$clockout = '';

		if($data['clockout']){
			$clockout = date("Y-m-d H:i:s", strtotime($data['clockout']));
		}

		$userid = $data['userid'];

		// Check if the clockin or clockout falls within any of the perspective timesheet records
		$query = "SELECT * FROM timesheets ";
		$query .= "WHERE userid = ".res($userid)." AND ((clockin < ".fres($clockin)." AND clockout > ".fres($clockin).") ";
		// If clock out does not exist or does exists
		if($clockout){
			$alert_str = 'ERROR: Clockin and clockout times conflict with another record!';
			// If exists make sure the clockout also does not fall within another record for the user
			$query .= "OR (clockin < ".fres($clockout)." AND clockout > ".fres($clockout).")";
		} else {
			$alert_str = 'ERROR: Clockout cannot be empty if user has another open record!';
			// If does not exist make sure that the current user does not have a null clockout record
			$query .= "OR (clockout IS NULL)";
		}

		$query .= ");";

		$result = qedb($query);

		if(qnum($result)) {
			$ALERT = $alert_str;
			return true;
		}

		return false;

	}

	function editTimesheet($data) {
		global $ALERT;

		if(! empty($data)) {
			foreach($data as $key => $element) {
				if(! $element['clockin']) {
					$ALERT = "ERROR: Clockin is required!";
					return 0;
				}

				if(strtotime($element['clockin']) > strtotime($GLOBALS['now']) OR strtotime($element['clockout']) > strtotime($GLOBALS['now'])) {
					$ALERT = "ERROR: Record cannot be set to the future.";
					return 0;
				}

				if(strtotime($element['clockin']) > strtotime($element['clockout'])) {
					$ALERT = "ERROR: Please check and make sure the clockout is after the clockin.";
					return 0;
				}
		
				if(checkOverlapping($element)) {
					return 0;
				}

				$query = "UPDATE timesheets SET clockin = ".fres( $element['clockin'] ? date("Y-m-d H:i:s", strtotime($element['clockin'])) : '' ).", clockout = ".fres( $element['clockout'] ? date("Y-m-d H:i:s", strtotime($element['clockout'])) : '' )." WHERE id = ".res($key).";";
				// echo $query;
				qedb($query);
			}
		}
	}

	function addTimesheet($data) {
		global $ALERT;

		if(strtotime($data['clockin']) > strtotime($data['clockout'])) {
			$ALERT = "ERROR: Please check and make sure the clockout is after the clockin.";
			return 0;
		}

		if(strtotime($data['clockin']) > strtotime($GLOBALS['now']) OR strtotime($data['clockout']) > strtotime($GLOBALS['now'])) {
			$ALERT = "ERROR: Record cannot be set to the future.";
			return 0;
		}

		if(! empty($data) AND $data['clockin']) {

			if(checkOverlapping($data)) {
				// $ALERT = 'ERROR: Clockin and Clockout times conflict with another record!';
				return 0;
			}

			$user_rate = getUserRate($data['userid']);
			$query = "INSERT INTO timesheets (userid, clockin, clockout, taskid, task_label, rate) VALUES (".fres($data['userid']).", ".fres( $data['clockin'] ? date("Y-m-d H:i:s", strtotime($data['clockin'])) : date("Y-m-d H:i:s", strtotime($GLOBALS['now']))).", ".fres( $data['clockout'] ? date("Y-m-d H:i:s", strtotime($data['clockout'])) : '' ).", ".fres($data['taskid']).", ".fres($data['task_label']).", ".fres($user_rate).");";

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
			if(! empty($addTime) && $addTime['clockin']) {
				addTimesheet($addTime);
			} else {
				editTimesheet($data);
			}
		}
	}

	header('Location: /timesheet.php' . ($userid ? '?user=' . $userid : '') . ($payroll_num ? '&payroll=' . $payroll_num : '') . ($taskid ? '&taskid=' . $taskid : '') . ($ALERT ? '&ALERT=' . $ALERT : ''));

	exit;