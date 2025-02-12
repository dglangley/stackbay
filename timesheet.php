<?php	
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';

	// If true then the user has privileges to edit/view other people's timesheets
	$user_editor = false;
	$user_access = false;
	$time_pass = false;
	if ($U['admin'] OR $U['editor']) {
		$user_editor = true;
		$user_access = true;
		if (isset($_COOKIE['time_pass'])) { $time_pass = $_COOKIE['time_pass']; }
	} else if ($U['manager'] OR ($U['accounting'] AND $userid<>$U['id'])) {
		$user_access = true;
		if (isset($_COOKIE['time_pass'])) { $time_pass = $_COOKIE['time_pass']; }
	}

	$userid = 0;
	if (isset($_REQUEST['userid']) AND $user_access) { $userid = $_REQUEST['userid']; }
	$taskid = 0;
	if (isset($_REQUEST['taskid'])) { $taskid = $_REQUEST['taskid']; }
	$task_label = 'service_item_id';//default for now but I prob want to change this imminently (12-17-18)
	if (isset($_REQUEST['task_label'])) { $task_label = $_REQUEST['task_label']; }
	$payroll_num =  '';
	if (isset($_REQUEST['payroll_num'])) { $payroll_num = $_REQUEST['payroll_num']; }
	$tsid = 0;
	if (isset($_REQUEST['tsid']) AND is_numeric($_REQUEST['tsid']) AND $_REQUEST['tsid']>0) { $tsid = trim($_REQUEST['tsid']); }

	$password = '';
	$loginErr = '';
	if (isset($_POST['password']) AND ! $timepass) {
		include_once $_SERVER["ROOT_DIR"].'/inc/user_access.php';
		include_once $_SERVER["ROOT_DIR"].'/inc/user_login.php';

		// spoof the username from the user login
		$_POST["username"] = $U['username'];

		// create login object
		$venLog = new venLogin;

		// login with password
		$venLog->loginMember();

		// check for errors
		if($venLog->getError()) {
			$loginErr =  $venLog->getError();

			include 'timesheet_login.php';
			exit;
		}
	} else if (! $time_pass AND ! $SUPER_USER) {
		include 'timesheet_login.php';
		exit;
	}

	// allow admin to continue for up to an hour with one password verification
	if ($user_editor) {
		setcookie('time_pass',1,time()+3600);
	} else if ($user_access) {
		// for regular access like accounting, just 5 min access
		setcookie('time_pass',1,time()+300);
	}

	include_once $_SERVER['ROOT_DIR'].'/inc/getUser.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUsers.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getReimbursements.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_task.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getTimesheet.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/payroll.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/order_type.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUserClasses.php';

	function getTaskNum($item_id, $item_id_label) {
		$order_number = 0;
		$table = '';

		if($item_id_label == 'repair_item_id') {
			$table = 'repair_items';
			$field2 = "''";
			$field = 'ro_number';
		} else {
			$table = 'service_items';
			$field2 = 'task_name';
			$field = 'so_number';
		}

		$query = "SELECT $field order_number, line_number, $field2 task_name FROM $table WHERE id = ".res($item_id).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$order_number = $r['order_number'];
			if ($r['line_number']) { $order_number .= '-'.$r['line_number']; }
			if ($r['task_name']) { $order_number .= ' '.$r['task_name']; }
		}

		//return $type . $service_number;
		return $order_number;
	}

	$TASKS = array();
	function getUniqueTask($userid=0,$taskid=0,$task_label='') {
		global $TASKS;

		$key = $userid.'.'.$taskid.'.'.$task_label;
		if (isset($TASKS[$key])) { return ($TASKS[$key]); }

		$unique_id = array();
		$now = $GLOBALS['now'];

		$query = "SELECT DISTINCT item_id taskid, item_id_label task_label FROM service_assignments ";
		$query .= "WHERE (start_datetime IS NULL OR start_datetime <= '".$now."') ";
		$query .= "AND (end_datetime IS NULL OR end_datetime >= '".$now."') ";
		if ($userid) { $query .= "AND userid = '".res($userid)."' "; }
		$query .= "; ";
		$result = qedb($query);
		while($r = qrow($result)) {
			$T = order_type($r['task_label']);
			$query2 = "SELECT * FROM ".$T['items']." i ";
			$query2 .= "WHERE id = '".$r['taskid']."' AND ".$T['status_code']." IS NULL; ";//active, not closed
			$result2 = qedb($query2);
			if (qnum($result2)>0) {
				$unique_id[$r['taskid'].'.'.$r['task_label']] = $r;
			}
		}

		if ($taskid AND $task_label) {
			$unique_id[$taskid.'.'.$task_label] = array('taskid'=>$taskid,'task_label'=>$task_label);
		}

		$TASKS[$key] = $unique_id;

		return $unique_id;
	}

	function checkPayrollStatus($ids) {
		$ids = array_map(function($a) use($mysqli) {
			return is_string($a) ? "'".res($a)."'" : $a;
		}, $ids);

		// DL 11-29-17 creating too many quote marks
		//$ids = join("','",$ids); 
		$ids = join(",",$ids); 

		$query = "SELECT * FROM timesheet_approvals WHERE timesheetid IN ($ids);";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if(mysqli_num_rows($result)) {
			return true;
		}

		return false;
	}

	// edit mode defines what controls are available to the user
	$edit = false;
	if ($tsid) { $edit = true; }

	$create = false;
	if (isset($_REQUEST['create']) AND $_REQUEST['create']) { $create = true; $edit = true; }

	// Timesheet variable flags
	$curdate = ''; 

	// These are running totals
	$total_time = 0;
	$total_pay = 0;

	$total_reg_seconds = 0;
	$total_reg_pay = 0;

	$total_travel_seconds = 0;
	$total_travel_pay = 0;

	$total_reg_ot_seconds = 0;
	$total_reg_ot_pay = 0;

	$total_travel_ot_seconds = 0;
	$total_travel_ot_pay = 0;

	$total_reg_dt_seconds = 0;
	$total_reg_dt_pay = 0;

	$total_travel_dt_seconds = 0;
	$total_travel_dt_pay = 0;

	$total_all_seconds = 0;
	$total_all_pay = 0;

	// Set the pay period start date & time
	$pay_period = '';

	if (! $user_access) {
		if (! $userid) { $userid = $U['id']; }
		// user should not see other users (if ! $user_access) and should NEVER be in edit mode
		if ($userid != $U['id'] OR $edit) {
			header('Location: /timesheet.php?userid=' . $U['id'] . ($payroll_num ? '&payroll_num=' . $payroll_num : ''));
			exit;
		}
	}

	$wages_access = false;
	if ($U['admin'] OR $U['manager'] OR ! $userid OR $userid==$U['id']) {
		$wages_access = true;
/*
	} else if ($userid AND $U['accounting']) {
		$USER_CLASSES = getUserClasses($U['id']);
		$EMPL_CLASSES = getUserClasses($U['id']);
		foreach ($EMPL_CLASSES as $cl) {
			if (isset($USER_CLASSES[$cl])) { $wages_access = true; }
		}
*/
	}

	// Create a new object for payroll dates
	$payroll = new Payroll;

	$date_range = false;//set for debugging and overriding dates

	if (! $date_range) {
		// Set the payroll hours period (AKA 2 weeks = 336 hours)
		$payroll->setHours(336);

		// A small demo of what the payroll class can do for you
		// print_r( $payroll->getCurrentPeriodStart() );
		// print_r( $payroll->getCurrentPeriodEnd() );
		// print_r( $payroll->getPreviousPeriodStart(1) );
		// print_r( $payroll->getPreviousPeriodEnd(1) );
		// print_r( $payroll->getPreviousPeriodStart(2) );
		// print_r( $payroll->getPreviousPeriodEnd(2) );

		$currentPayroll = $payroll->getCurrentPeriodStart();
		$currentPayrollEnd = $payroll->getCurrentPeriodEnd();

		$payroll_start = $currentPayroll->format('Y-m-d H:i:s');
		$payroll_end = $currentPayrollEnd->format('Y-m-d H:i:s');
	}

	if($payroll_num ) {
		$start;
		$end;
		//$currentPayroll = $payroll->getCurrentPeriodStart();
		if($payroll_num == 'current') {
			$start = $currentPayroll;
			$end = $currentPayrollEnd;
		} else {
			$start = $payroll->getPreviousPeriodStart($payroll_num);
			$end = $payroll->getPreviousPeriodEnd($payroll_num);
		}

		$startDate = $start->format('Y-m-d H:i:s');
		$endDate = $end->format('Y-m-d H:i:s');

		if ($userid) {
			$timesheet_data = $payroll->getTimesheets($userid, false, $startDate, $endDate, $taskid, $task_label);
		} else {
			$timesheet_data = $payroll->getTimesheets($GLOBALS['U']['id'], $user_editor, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $taskid, $task_label);
		}
	} else {

		$startDate = $payroll_start;
		$endDate = $payroll_end;

		if ($taskid) {
			if ($userid) {
				$timesheet_data = $payroll->getTimesheets($userid, false, false, false, $taskid, $task_label);
			} else {
				$timesheet_data = $payroll->getTimesheets($GLOBALS['U']['id'], false, false, false, $taskid, $task_label);
			}
		} else if ($userid) {
			$timesheet_data = $payroll->getTimesheets($userid, false, $payroll_start, $payroll_end, $taskid, $task_label);
		} else {
			if ($date_range) {
				$payroll_start = '2017-01-01 00:00:00';
				$payroll_end = '2017-08-31 00:00:00';

				$timesheet_data = $payroll->getTimesheets($GLOBALS['U']['id'], true, $payroll_start, $payroll_end, $taskid, $task_label);
			} else {
				$timesheet_data = $payroll->getTimesheets($GLOBALS['U']['id'], $user_editor, $payroll_start, $payroll_end, $taskid, $task_label);
			}
		}
	}

	$reimbursements = 0;
	if ($userid) {
		$reimbursements = getReimbursements($userid,$startDate,$endDate);
	}

	$timesheet_ids = array();

	$timesheet_users = array();
	foreach($timesheet_data as $list) {
		$timesheet_ids[] = $list['id'];
		$timesheet_users[$list['userid']] = true;
	}

	// get each user's timesheet as applicable for this view, so we can reference it below
	$userTimesheets = array();
	foreach ($timesheet_users as $ts_userid => $bool) {
		$userTimesheets[$ts_userid] = getTimesheet($ts_userid);
	}

	$new_data = array();
	foreach($timesheet_data as $item) { 
		// creating two time shifts out of one, if the user is clocked in past midnight
		if (! isset($_REQUEST['old'])) {
			
			$clockin_date = substr($item['clockin'],0,10);
			$clockout_date = substr($item['clockout'],0,10);
			
			if(! $edit) {
				while ($clockout_date AND $clockin_date<>$clockout_date) {
					// first shift ends at 23:59:59 on the clockin date
					$first = $item;
					$first['clockout'] = $clockin_date.' 23:59:59';
					
					$new_data[] = $first;

					$clockin_date = format_date($item['clockin'],'Y-m-d',array('d'=>1));

					// next shift starts at midnight on the clockout date
					$item['clockin'] = $clockin_date.' 00:00:00';
				}
			}
			
			$new_data[] = $item;
		}
	}

	$timesheet_data = $new_data;
	$new_data = array();//reset

	$payroll_approved = false;
	if ($timesheet_ids) {
		$payroll_approved = checkPayrollStatus($timesheet_ids);
	}
	if ($payroll_approved) { $edit = false; }//never allow editing when the timesheet has been already approved for payroll
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------>
<!DOCTYPE html>
<html>
<head>
	<title>Timesheet</title>
	<?php
		//Standard headers included in the function
		include_once $_SERVER['ROOT_DIR'].'/inc/scripts.php';
	?>
	<style>
		.upload{
		    display: none !important;
		}
		.table td {
			vertical-align:top !important;
		}
		.text-bold {
			font-weight:bold;
		}
		.regularpay {
			background-color:white;
		}
		.overtime {
			background-color:#f5f5f5;
		}
		.doubletime {
			background-color:#eee;
		}

		#main-stats .stat .data {
			padding-top: 5px;
			padding-right: 5px;
		}
		#main-stats .stat .data .number {
			font-size: 20px;
			font-weight: 100;
			top: 12px;
			margin-right: 0px;
		}
		#main-stats .stat .data .info {
			top: 3px;
			left:10px;
			position: absolute;
		}
		#main-stats .stat .aux {
			margin-top: -4px;
			right: 8px;
		}
		#main-stats .stat.last .aux {
			margin-top: -8px;
		}
		.task-selection {
			width:240px;
		}
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<form method="get" action="timesheet.php">

		<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
			<div class="row" style="padding: 8px;" id="filterBar">
				<div class="col-md-5 mobile-hide" style="max-height: 30px;">
					<?php /* if (! $edit AND $userid AND ($user_editor OR ($user_access AND $userid<>$U['id']))): ?>
						<a href="/timesheet.php?edit=true<?=($userid ? '&userid=' . $userid : '')?><?=($payroll_num ? '&payroll_num=' . $payroll_num : '')?><?=($taskid ? '&taskid=' . $taskid : '')?>" class="btn btn-default btn-sm toggle-edit" style="margin-right: 10px;"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
					<?php endif; */ ?>
					<select id="user_select" name="userid" size="1" class="form-control input-sm select2 pull-right" style="max-width: 200px;" onChange="this.form.submit()">
						<option value =''> - Select User - </option>
						<?php
							$users = array();
							$query = "SELECT u.id, c.name FROM users u, contacts c ";
							$query .= "WHERE u.contactid = c.id AND u.hourly_rate > 0 AND c.status = 'Active' ";
							$query .= "GROUP BY u.id ORDER BY c.name ASC; ";
							$result = qdb($query) OR die(qe().'<BR>'.$query);
							while ($r = mysqli_fetch_assoc($result)) {
								$uid = $r['id'];
								$uname = $r['name'];

								$s = '';
								if ($userid == $uid) { $s = ' selected'; }
								if($user_access OR ($userid == $uid)) {
									echo '<option value="'.$uid.'"'.$s.'>'.$uname.'</option>'.chr(10);
								}
							}
						?>
					</select>
				</div>

				<div class="text-center col-md-2 remove-pad">
					<h2 class="minimal" id="filter-title">Timesheet</h2>
				</div>

				<div class="col-md-2 task-container">
					<select name="taskid" size="1" class="form-control input-sm select2 pull-right task-selection form-submit" style="max-width: 200px;">
						<option value =''> - Select Task - </option>
						<?php
							$sel_label = '';
							foreach (getUniqueTask($userid,$taskid,$task_label) as $task) {
								$s = '';
								$task_num = getTaskNum($task['taskid'], $task['task_label']);
								if (! $task_num) { continue; }

								if ($taskid == $task['taskid']) {
									$s = ' selected';
									$sel_label = $task['task_label'];
								}
								echo '<option value="'.$task['taskid'].'"'.$s.' data-label="'.$task['task_label'].'">'.$task_num.'</option>'.chr(10);
							}
						?>
					</select>
					<input type="hidden" name="task_label" class="task_label_input" value="<?=$sel_label;?>">
				</div>
				<div class="col-md-3">
					<div class="col-sm-6">
						<select name="payroll_num" id="payroll_history" size="1" class="form-control input-sm select2">
							<option value="">- Payroll History -</option>
							<?php
								if (! $date_range) {
									for($x = 1; $x <= 20; $x++) {
										$dateTime = $payroll->getPreviousPeriodStart($x);
										$dateTimeEnd = $payroll->getPreviousPeriodEnd($x);
										echo '<option value="'.$x.'" '.($x == $payroll_num ? 'selected' : '').'>'.$dateTime->format('m/d/Y') . ' - ' . $dateTimeEnd->format('m/d/Y') .'</option>';
									}
								}
							?>
						</select>
					</div>
<?php if ($user_editor) { ?>
					<div class="col-sm-6 text-right">
					<?php if($edit): ?>
						<a href="timesheet.php?userid=<?=$userid;?>" class="btn btn-default btn-sm">Cancel</a>
						<button class="btn btn-success btn-md btn-save" type="submit" name="type" value="edit"><i class="fa fa-save"></i> Save</button>
					<?php elseif ($userid AND $payroll_num): ?>
						<button class="btn btn-success btn-save" <?=(! $payroll_approved ? 'type="submit" name="type" value="payroll"' : 'disabled')?>>Approve Payroll</button>
					<?php else: ?>
						<a href="timesheet.php?userid=<?=$userid;?>&create=1" class="btn btn-primary btn-sm" title="Add Time Punch" data-toggle="tooltip" data-placement="left"><i class="fa fa-plus"></i></a>
					<?php endif; ?>
					</div>
<?php } ?>
				</div>
			</div>
		</div>
	</form>

	<?php if($user_access): ?>
		<form id="timesheet_form" action="save-timesheet.php" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="taskid" value="<?=$taskid;?>">
			<input type="hidden" name="task_label" value="<?=$task_label;?>">
			<input type="hidden" name="userid" value="<?=$userid;?>">
			<input type="hidden" name="payroll_num" value="<?=$payroll_num;?>">
			<input type="hidden" name="tsid" value="<?=$tsid;?>">
	<?php endif; ?>

		<div id="pad-wrapper">

			<?php if ($wages_access) { ?>
			<div id="main-stats">
	            <div class="row stats-row">
	                <div class="col-md-1 col-sm-1 stat text-right">
	                    <div class="data">
							<span class="info pull-left">Regular Pay</span>
	                        <span class="sum_total_reg number text-brown">$0.00</span>
	                    </div>
						<span class="aux total_reg_time"></span>
	                </div>
	                <div class="col-md-1 col-sm-1 stat">
	                    <div class="data">
							<span class="info">Travel Time</span>
	                        <span class="sum_total_travel number text-brown">$0.00</span>
	                    </div>
						<span class="aux total_travel_time"></span>
	                </div>
	                <div class="col-md-1 col-sm-1 stat">
	                </div>
	                <div class="col-md-1 col-sm-1 stat">
	                    <div class="data">
							<span class="info">OT Regular Rate</span>
	                        <span class="sum_total_reg_ot number text-black">$0.00</span>
	                    </div>
						<span class="aux total_reg_ot_time"></span>
	                </div>
	                <div class="col-md-1 col-sm-1 stat">
	                    <div class="data">
							<span class="info">OT Travel</span>
	                        <span class="sum_total_travel_ot number text-black">$0.00</span>
	                    </div>
						<span class="aux total_travel_ot_time"></span>
	                </div>
	                <div class="col-md-1 col-sm-1 stat">
	                </div>
	                <div class="col-md-1 col-sm-1 stat">
	                    <div class="data">
							<span class="info">DT Regular Rate</span>
	                        <span class="sum_total_reg_dt number text-black">$0.00</span>
	                    </div>
						<span class="aux total_reg_dt_time"></span>
	                </div>
	                <div class="col-md-1 col-sm-1 stat">
	                    <div class="data">
							<span class="info">DT Travel</span>
	                        <span class="sum_total_travel_dt number text-black">$0.00</span>
	                    </div>
						<span class="aux total_travel_dt_time"></span>
	                </div>
	                <div class="col-md-1 col-sm-1 stat">
	                </div>
	                <div class="col-md-1 col-sm-1 stat">
	                    <div class="data">
							<span class="info">Reimbursements</span>
	                        <span class="sum_total_reimbursement number text-black">$<?=number_format($reimbursements,2);?></span>
	                    </div>
						<span class="aux total_reimbursement"></span>
	                </div>
	                <div class="col-md-1 col-sm-1 stat">
	                </div>
	                <div class="col-md-1 col-sm-1 stat last">
	                    <div class="data">
							<span class="info">Total Pay</span>
	                        <span class="sum_total_pay number text-success" style="font-weight: 400; font-size: 25px;">$0.00</span>
	                    </div>
						<span class="aux total_time"></span>
	                </div>
	            </div>
	        </div>

	        <br>
			<?php } ?>

			<div class="row">
				<table class="table heighthover heightstriped table-condensed table-striped table-hover">
					<thead>
						<tr>
							<th>DATE</th>
							<th>TASK</th>
							<th>CLOCKIN</th>
							<th>CLOCKOUT</th>
							<th class="regularpay">
								<div class="col-md-12 text-center">
									REGULAR PAY
								</div>
								<?php if ($wages_access) { ?>
								<div class="col-md-4 text-center">Time</div>
								<div class="col-md-4 text-center">Rate</div>
								<div class="col-md-4 text-center">Amount</div>
								<?php } ?>
							</th>
							<th class="overtime">
								<div class="col-md-12 text-center">
									OVERTIME
								</div>
								<?php if ($wages_access) { ?>
								<div class="col-md-4 text-center">Time</div>
								<div class="col-md-4 text-center">Rate</div>
								<div class="col-md-4 text-center">Time</div>
								<?php } ?>
							</th>
							<th class="doubletime">
								<div class="col-md-12 text-center">
									DOUBLETIME
								</div>
								<?php if ($wages_access) { ?>
								<div class="col-md-4 text-center">Time</div>
								<div class="col-md-4 text-center">Rate</div>
								<div class="col-md-4 text-center">Amount</div>
								<?php } ?>
							</th>
							<th>CUMULATIVE</th>
							<th>
								<div class="col-md-12 text-center">
									TOTAL
								</div>
								<?php if ($wages_access) { ?>
								<div class="col-md-6 text-center">Time</div>
								<div class="col-md-6 text-center">Amount</div>
								<?php } ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php if ($create AND $userid){ //If the edit feature is on then allow the user to add new records for the timesheet ?>
							<tr>
								<!-- If edit is on and the user has permission then show input boxes for datetime of clockin and clockout -->
								<td>
									<input type="hidden" name="addTime[userid]" value="<?=$userid?>">
								</td>
								<td class="task-container">
									<select name="addTime[taskid]" size="1" class="form-control input-sm select2 task-selection">
										<option value =''> - Select Task - </option>
										<?php
											$sel_label = '';
											foreach (getUniqueTask($userid,$taskid,$task_label) as $task) {
												$s = '';
												$task_num = getTaskNum($task['taskid'], $task['task_label']);
												if (! $task_num) { continue; }

												if ($taskid == $task['taskid']) {
													$s = ' selected';
													$sel_label = $task['task_label'];
												}
												echo '<option value="'.$task['taskid'].'"'.$s.' data-label="'.$task['task_label'].'">'.$task_num.'</option>'.chr(10);
											}
										?>
									</select>
									<input type="hidden" name="addTime[task_label]" class="task_label_input" value="<?=$sel_label;?>">
								</td>
								<td>
									<div class="input-group datepicker-datetime date datetime-picker" data-hposition="right" data-format="M/D/YYYY h:mm:ss a">
		   		    			         <input type="text" name="addTime[clockin]" class="form-control input-sm" value="">
		           		       			 <span class="input-group-addon">
				       		                 <span class="fa fa-calendar"></span>
		       					         </span>
									</div>
								</td>
								<td>
									<div class="input-group datepicker-datetime date datetime-picker" data-hposition="right" data-format="M/D/YYYY h:mm:ss a">
		   		    			         <input type="text" name="addTime[clockout]" class="form-control input-sm" value="">
		           		       			 <span class="input-group-addon">
				       		                 <span class="fa fa-calendar"></span>
		       					         </span>
									</div>
								</td>
								
								<!-- Resume the data -->
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td class="text-right"><button class="btn btn-success btn-xs" type="submit" style="margin-right:20px"><i class="fa fa-save"></i></button></td>
							</tr>
						<?php } ?>
						<?php 
							foreach($timesheet_data as $item) { 
								// get the user's timesheet data from getTimesheet()
								$userTimesheet = $userTimesheets[$item['userid']];

								$date = substr($item['clockin'],0,10);

								$show_task = '';
								$task = format_task($item['taskid'], $item['task_label']);
								if ($task) {
									$T = order_type($item['task_label']);
									$show_task = $task.' <a href="service.php?order_type='.$T['type'].'&taskid='.$item['taskid'].'"><i class="fa fa-arrow-right"></i></a>';
								}
						?>
							<tr>
								<?php /*if($edit AND $item['id']==$tsid AND strtotime(format_date($item['clockin'])) > strtotime(format_date($payroll_start))):*/ ?>
								<?php if($edit AND $item['id']==$tsid): ?>
									<td>
										<input type="hidden" name="data[<?=$item['id'];?>][userid]" class="form-control input-sm" value="<?=$item['userid'];?>">
									</td>
									<td class="task-container">
										<?php
											$opts = '<option value=""> - Select Task - </option>';
											foreach (getUniqueTask($userid,$item['taskid'],$item['task_label']) as $task) {
												$s = '';
												$task_num = getTaskNum($task['taskid'], $task['task_label']);
												if (! $task_num) { continue; }

												if ($item['taskid'] == $task['taskid']) { $s = ' selected'; }
												$opts .= '<option value="'.$task['taskid'].'"'.$s.' data-label="'.$task['task_label'].'">'.$task_num.'</option>'.chr(10);
											}
										?>
										<select name="data[<?=$item['id'];?>][taskid]" size="1" class="form-control input-sm task-selection select2">
											<?=$opts;?>
										</select>
										<input type="hidden" name="data[<?=$item['id'];?>][task_label]" class="task_label_input" value="<?=$item['task_label'];?>">
									<td>
										<div class="input-group datepicker-datetime date datetime-picker" data-hposition="right" data-format="M/D/YYYY h:mm:ss a">
			   		    			         <input type="text" name="data[<?=$item['id'];?>][clockin]" class="form-control input-sm" value="<?=date('n/j/Y g:i:s a', strtotime($item['clockin']));?>">
			           		       			 <span class="input-group-addon">
					       		                 <span class="fa fa-calendar"></span>
			       					         </span>
										</div>
									</td>
									<td>
										<div class="input-group datepicker-datetime date datetime-picker" data-hposition="right" data-format="M/D/YYYY h:mm:ss a">
			   		    			         <input type="text" name="data[<?=$item['id'];?>][clockout]" class="form-control input-sm" value="<?=($item['clockout'] ? date('n/j/Y g:i:s a', strtotime($item['clockout'])) : '');?>">
			           		       			 <span class="input-group-addon">
					       		                 <span class="fa fa-calendar"></span>
			       					         </span>
										</div>
									</td>
								<?php else: ?>
									<td>
										<?php 
											if(empty($curdate) OR $curdate != format_date($item['clockin'])) {
												$curdate = format_date($item['clockin']);
												echo format_date($item['clockin']);
											}
										?>
									</td>
									<td><?=$show_task;?></td>
									<td><?=date('g:i a', strtotime($item['clockin']));?></td>
									<td>
										<?php 
											if(! empty($item['clockout'])) {
												if (substr($item['clockin'],0,10)<>substr($item['clockout'],0,10)) {
													echo date('n/j/y', strtotime($item['clockout'])).' ';
												}
												echo date('g:i a', strtotime($item['clockout']));
											}
										?>
									</td>
									
								<?php endif; ?>

								<!-- Resume the data -->
	
								<td class="regularpay">
									<div class="col-md-<?= ($wages_access ? '4' : '12'); ?> text-center">
										<?php 
											echo toTime($userTimesheet[$item['id']][$date]['REG_secs']);

											if ($item['rate']==11) {
												$total_travel_seconds += $userTimesheet[$item['id']][$date]['REG_secs'];
												$total_travel_pay += $userTimesheet[$item['id']][$date]['REG_pay'];
											} else {
												$total_reg_seconds += $userTimesheet[$item['id']][$date]['REG_secs'];
												$total_reg_pay += $userTimesheet[$item['id']][$date]['REG_pay'];
											}
										?>
									</div>
									<?php if ($wages_access) { ?>
									<div class="col-md-4 text-center">
										<?=format_price($item['rate']);?>
									</div>
									<div class="col-md-4 text-center">
										<?php 
											echo format_price($userTimesheet[$item['id']][$date]['REG_pay']);
										?>
									</div>
									<?php } ?>
								</td>
								<td class="overtime">
									<div class="col-md-<?= ($wages_access ? '4' : '12'); ?> text-center">
										<?php
											if($userTimesheet[$item['id']][$date]['OT_secs'])
												echo toTime($userTimesheet[$item['id']][$date]['OT_secs']);
											if ($item['rate']==11) {
												$total_travel_ot_seconds += $userTimesheet[$item['id']][$date]['OT_secs'];
											} else {
												$total_reg_ot_seconds += $userTimesheet[$item['id']][$date]['OT_secs'];
											}
										?>								
									</div>
									<?php if ($wages_access) { ?>
									<div class="col-md-4 text-center">
										<?=($userTimesheet[$item['id']][$date]['OT_secs'] ? format_price(1.5*$item['rate']) : '');?>
									</div>
									<div class="col-md-4 text-center">
										<?php 
											if($userTimesheet[$item['id']][$date]['OT_pay'])
												echo format_price($userTimesheet[$item['id']][$date]['OT_pay']);
											if ($item['rate']==11) {
												$total_travel_ot_pay += $userTimesheet[$item['id']][$date]['OT_pay'];
											} else {
												$total_reg_ot_pay += $userTimesheet[$item['id']][$date]['OT_pay'];
											}
										?>
									</div>
									<?php } ?>
								</td>
								<td class="doubletime">
									<div class="col-md-<?= ($wages_access ? '4' : '12'); ?> text-center">
										<?php
											if($userTimesheet[$item['id']][$date]['DT_secs'])
												echo toTime($userTimesheet[$item['id']][$date]['DT_secs']);
											if ($item['rate']==11) {
												$total_travel_dt_seconds += $userTimesheet[$item['id']][$date]['DT_secs'];
											} else {
												$total_reg_dt_seconds += $userTimesheet[$item['id']][$date]['DT_secs'];
											}
										?>
									</div>
									<?php if ($wages_access) { ?>
									<div class="col-md-4 text-center">
										<?=($userTimesheet[$item['id']][$date]['DT_secs'] ? format_price(2*$item['rate']) : '');?>
									</div>
									<div class="col-md-4 text-center">
										<?php 
											if($userTimesheet[$item['id']][$date]['DT_secs'])
												echo format_price($userTimesheet[$item['id']][$date]['DT_pay']);
											if ($item['rate']==11) {
												$total_travel_dt_pay += $userTimesheet[$item['id']][$date]['DT_pay'];
											} else {
												$total_reg_dt_pay += $userTimesheet[$item['id']][$date]['DT_pay'];
											}
										?>
									</div>
									<?php } ?>
								</td>
								<td>
									<?php
										if($userTimesheet[$item['id']][$date]['CUM_secs']) {
											echo toTime($userTimesheet[$item['id']][$date]['CUM_secs']);
										}
									?>					
								</td>
								<td>
									<div class="col-md-6 text-center">
										<?php
											echo toTime($userTimesheet[$item['id']][$date]['secsDiff']);
											$total_time += $userTimesheet[$item['id']][$date]['secsDiff'];
										?>
									</div>
									<?php if ($user_access) { ?>
									<div class="col-md-2 text-center">
										<?php if ($wages_access) { ?>
											<?=format_price($userTimesheet[$item['id']][$date]['totalPay']);?>
										<?php } ?>

									</div>
									<div class="col-md-4 text-right">
										<?php if ($user_access AND $item['userid']<>$U['id']) { ?>
											<input type="hidden" name="payroll[<?=$item['id'];?>]" class="form-control input-sm" value="<?=$userTimesheet[$item['id']][$date]['totalPay'];?>">
											<?php if ($edit AND $tsid==$item['id']) { ?>
												<a class="cancel info" href="javascript:void(0);" data-userid="<?=$item['userid'];?>" data-tsid="<?=$item['id'];?>" title="Cancel" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-close fa-lg"></i></a>
												<button class="btn btn-xs btn-success" type="submit"><i class="fa fa-save"></i></button>
											<?php } else if (! $edit AND ! $payroll_approved) { ?>
												<a class="delete" href="javascript:void(0);" data-userid="<?=$item['userid'];?>" data-tsid="<?=$item['id']?>"><i class="fa fa-trash" aria-hidden="true"></i></a>
												<a class="edit" href="javascript:void(0);" data-userid="<?=$item['userid'];?>" data-tsid="<?=$item['id'];?>"><i class="fa fa-pencil" aria-hidden="true"></i></a>
											<?php } ?>
										<?php } ?>
									</div>
									<?php } ?>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<td colspan="4"></td>
							<td colspan="">
								<div class="col-md-<?= ($wages_access ? '4' : '12'); ?> text-center text-bold">
									<?=toTime($total_reg_seconds)?>
								</div>
								<?php if ($wages_access) { ?>
								<div class="col-md-4 total_travel" data-total="<?=format_price($total_travel_pay);?>" data-time="<?=($total_travel_seconds ? number_format(($total_travel_seconds/3600),4).' hrs' : '');?>"></div>
								<div class="col-md-4 text-center text-bold total_reg" data-total="<?=format_price($total_reg_pay);?>" data-time="<?=($total_reg_seconds ? number_format(($total_reg_seconds/3600),4).' hrs' : '');?>">
									<?=format_price($total_reg_pay);?>
								</div>
								<?php } ?>
							</td>
							<td colspan="">
								<div class="col-md-<?= ($wages_access ? '4' : '12'); ?> text-center text-bold">
									<?=toTime($total_reg_ot_seconds);?>
								</div>
								<?php if ($wages_access) { ?>
								<div class="col-md-4 text-center text-bold">
								</div>
								<div class="col-md-4 text-center text-bold total_reg_ot" data-total="<?=format_price($total_reg_ot_pay);?>" data-time="<?=($total_reg_ot_seconds ? number_format(($total_reg_ot_seconds/3600),4).' hrs' : '');?>">
									<?=format_price($total_reg_ot_pay);?>
								</div>
								<?php } ?>
								<span class="hidden total_travel_ot" data-total="<?=format_price($total_travel_ot_pay);?>" data-time="<?=($total_travel_ot_seconds ? number_format(($total_travel_ot_seconds/3600),4).' hrs' : '');?>">
							</td>
							<td colspan="">
								<div class="col-md-<?= ($wages_access ? '4' : '12'); ?> text-center text-bold">
									<?=toTime($total_reg_dt_seconds)?>
								</div>
								<?php if ($wages_access) { ?>
								<div class="col-md-4 text-center text-bold">
								</div>
								<div class="col-md-4 text-center text-bold total_reg_dt" data-total="<?=format_price($total_reg_dt_pay);?>" data-time="<?=($total_reg_dt_seconds ? number_format(($total_reg_dt_seconds/3600),4).' hrs' : '');?>">
									<?=format_price($total_reg_dt_pay);?>
								</div>
								<?php } ?>
								<span class="hidden total_travel_dt" data-total="<?=format_price($total_travel_dt_pay);?>" data-time="<?=($total_travel_dt_seconds ? number_format(($total_travel_dt_seconds/3600),4).' hrs' : '');?>">
							</td>
							<td colspan="">
							</td>
							<td colspan="">
								<?php
									$total_all_pay = $total_reg_ot_pay + $total_travel_ot_pay + $total_travel_pay + $total_reg_pay + $total_reg_dt_pay + $total_travel_dt_pay + $reimbursements;
									$total_all_seconds = $total_reg_ot_seconds + $total_travel_ot_seconds + $total_travel_seconds + $total_reg_seconds + $total_reg_dt_seconds + $total_travel_dt_seconds;
								?>
								<div class="col-md-6 text-center text-bold">
									<?=toTime($total_time)?>
								</div>
								<?php if ($wages_access) { ?>
								<div class="col-md-6 text-center text-bold total_pay" data-total="<?=format_price($total_all_pay);?>" data-time="<?=($total_all_seconds ? number_format(($total_all_seconds/3600),4).' hrs' : '');?>">
									<?=format_price($total_all_pay);?>
								</div>
								<?php } else if ($user_access) { ?>
								<div class="col-md-6 text-center text-bold total_pay">
									<?=format_price($total_all_pay);?>
								</div>
								<?php } ?>
							</td>
						</tr>
					</tbody>
		        </table>
			</div>
		</div>
	<?php if($user_access): ?>
		</form>
	<?php endif; ?>

	<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
		$(document).ready(function() {
    		var total_reg_pay = $(".total_reg").data("total");
    		var total_reg_time = $(".total_reg").data("time");
    		var total_travel_pay = $(".total_travel").data("total");
    		var total_travel_time = $(".total_travel").data("time");

    		var total_reg_ot_pay = $(".total_reg_ot").data("total");
    		var total_reg_ot_time = $(".total_reg_ot").data("time");
    		var total_travel_ot_pay = $(".total_travel_ot").data("total");
    		var total_travel_ot_time = $(".total_travel_ot").data("time");

    		var total_reg_dt_pay = $(".total_reg_dt").data("total");
    		var total_reg_dt_time = $(".total_reg_dt").data("time");
    		var total_travel_dt_pay = $(".total_travel_dt").data("total");
    		var total_travel_dt_time = $(".total_travel_dt").data("time");

    		var total_pay = $(".total_pay").data("total");
    		var total_time = $(".total_pay").data("time");

    		$('.sum_total_reg').text(total_reg_pay);
    		$('.total_reg_time').text(total_reg_time);
    		$('.sum_total_travel').text(total_travel_pay);
    		$('.total_travel_time').text(total_travel_time);
    		$('.sum_total_reg_ot').text(total_reg_ot_pay);
    		$('.total_reg_ot_time').text(total_reg_ot_time);
    		$('.sum_total_travel_ot').text(total_travel_ot_pay);
    		$('.total_travel_ot_time').text(total_travel_ot_time);
    		$('.sum_total_travel_dt').text(total_travel_dt_pay);
    		$('.total_travel_dt_time').text(total_travel_dt_time);
    		$('.sum_total_reg_dt').text(total_reg_dt_pay);
    		$('.total_reg_dt_time').text(total_reg_dt_time);
    		$('.sum_total_pay').text(total_pay);
    		$('.total_time').text(total_reg_time);

    		$(document).on("change", ".task-selection", function(e) {
    			e.preventDefault();

				var v = $(this).find(':selected').data("label");
				$(this).closest(".task-container").find(".task_label_input").val(v);

				if ($(this).hasClass('form-submit')) {
					$(this).closest("form").submit();
				}
    		});

			$(".delete").on('click',function(e) {
    			e.preventDefault();

    			var tsid = $(this).data('tsid');
				modalAlertShow("<i class='fa fa-warning fa-lg'></i> You are deleting an employee's timesheet record!","This has significant implications. Are you absolutely sure you want to do this?<BR><BR><img src='https://media1.giphy.com/media/SW3PNayoSGXao/giphy.gif'>",true,'deleteTS',tsid);
    		});

			$(".cancel").on('click',function(e) {
    			e.preventDefault();

				goTS($(this).data('userid'),0);
    		});

			$(".edit").on('click',function(e) {
    			e.preventDefault();

				goTS($(this).data('userid'),$(this).data('tsid'));
    		});

    		$(document).on('click', ".upload_link", function(e){
		        e.preventDefault();

		        $(this).closest(".file_container").find(".upload").trigger("click");
		    });


    		$(document).on("click", ".btn-save", function(e) {
    			e.preventDefault();

    			var type = $(this).val();
    			input = $("<input>").attr("type", "hidden").attr("name", "type").val(type);
				$('#timesheet_form').append($(input));

    			$('#timesheet_form').submit();
    		});

    		$(document).on("change", ".upload", function(){
    			var f_file =  $(this).val();
			    var fileName = f_file.match(/[^\/\\]+$/);

				$(this).closest(".file_container").find(".file_name").text(fileName);
    		});

    		$(document).on("change", "#payroll_history", function(){
    			var payroll_num = $(this).val();
				if (! payroll_num) { payroll_num = false; }//force no selection below
    			var userid = $("#user_select").val();

				goTS(userid,0,payroll_num);
    		});

		});/* close $(document).ready */

		function deleteTS(tsid) {
			window.location.href = "save-timesheet.php?delete=" + tsid;
		}
		function goTS(userid,tsid,payroll_num) {
			if (! payroll_num && payroll_num!==false) {
				var payroll_num = '<?=$payroll_num;?>';
			}
			var taskid = '<?=$taskid;?>';
			var task_label = '<?=$task_label;?>';
			window.location.href = 'timesheet.php?userid='+userid+'&tsid='+tsid+'&taskid='+taskid+'&task_label='+task_label+'&payroll_num='+payroll_num;
		}
    </script>

</body>
</html>
