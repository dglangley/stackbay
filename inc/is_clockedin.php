<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRep.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrderNumber.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';

	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function is_clockedin($userid, $taskid=0, $task_label='') {
		$clock = false;

		// Grab the last clock in for the user where the clockout time has not been set yet and limit it by 1 (In totality there will always only be 1 record open per userid)
		$query = "SELECT * FROM timesheets WHERE userid  = ".res($userid)." ";
//		if ($taskid AND $task_label) { $query .= "AND taskid = '".res($taskid)."' AND task_label = '".res($task_label)."' "; }
		$query .= "AND clockout IS NULL ";
		$query .= "ORDER BY id DESC; ";// LIMIT 1;";
		$result = qdb($query) OR die(qe() . ' ' . $query);
		if (mysqli_num_rows($result)==0) {
			return ($clock);
		} else if (mysqli_num_rows($result)>1) {
			die("You are clocked into more than one job at a time. Please see a manager immediately!");
		}
		while ($r = mysqli_fetch_assoc($result)) {
			if (($r['taskid']==$taskid AND $r['task_label']==$task_label) OR (! $taskid AND ! $task_label)) { $clock = $r; }
		}

		return ($clock);
	}

	// This checks if a user that is hourly has been clocked in on any job for more than 5 hours at a time
	function is_idle() {
		$idle = false;

		$query = "SELECT * FROM timesheets WHERE ";
		$query .= "clockout IS NULL ";
		$query .= "ORDER BY id DESC; ";// LIMIT 1;";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		$http_host = 'http';
		if ($GLOBALS['DEV_ENV']) {
			$http_host .= '://'.$_SERVER["HTTP_HOST"];
		} else {
			$http_host .= 's://'.$_SERVER["HTTP_HOST"];
		}

		while($r = mysqli_fetch_assoc($result)) {
			$userid = $r['userid'];

			$ts1 = strtotime($r['clockin']);
			$ts2 = strtotime($GLOBALS['now']);
			$hours = abs($ts1 - $ts2) / 3600; // 3600 = seconds to minutes to hours

			$T = order_type($r['task_label']);
			$order_number = getOrderNumber($r['taskid'], $T['items'], $T['order']);
			$title = $T['abbrev'].'# '.$order_number;
			$link = '/service.php?order_type='.$T['type'].'&taskid='.$r['taskid'];//order_number=' . $order_number;

			if($hours > 5) {

				$idle = true;

				// Don't alert all users... just alert the user that is doing the bad
				if($userid == $GLOBALS['U']['id']) {
					$ALERTS[] = "Warning: You have exceeded 5 hours of clock in time.";
				}

				// If this is the case then let us notify the managers (David & Scott currently set)
				if($result && ! $DEV_ENV) {
					$email_body_html = getRep($userid)." has been a bad boy and exceeded the 5 hour clockin rule. <BR><BR> ".
						"Currently clocked: " .round($hours,2). " hours on <a target='_blank' href='".$http_host.$link."'>".$title."</a>";
					$email_subject = ' Timesheet Warning for ' . getRep($userid);

					// $recipients[] = 'david@ven-tel.com';
					$email_name = "timesheet_email";
					$recipients = getSubEmail($email_name);

					// $bcc = 'dev@ven-tel.com';

					$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
					if ($send_success) {
					    // echo json_encode(array('message'=>'Success'));
					} else {
					    die($GLOBALS['SEND_ERR']);
					}
				}
			}
		}

		return $idle;
	}

/*
	function getOrderLn($field, $table, $item_id) {
		$order_number = 0;

		$query = "SELECT $field as order_number FROM $table WHERE ";
		$query .= "id = ".res($item_id)." ";
		$query .= "ORDER BY id DESC; ";// LIMIT 1;";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if (mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);

			$order_number = $r['order_number'] . ($r['line_number'] ? '-'.$r['line_number'] : '-1');
		}

		return $order_number;
	}
*/
