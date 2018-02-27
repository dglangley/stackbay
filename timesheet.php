<?php	
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';

	// If true then the user is an admin
	$user_admin = false;
	$time_pass = false;
	if (in_array("4", $USER_ROLES)) {
		$user_admin = true;
		if (isset($_COOKIE['time_pass'])) { $time_pass = $_COOKIE['time_pass']; }
	}

	$password = '';
	$loginErr = '';
	if (isset($_POST['password'])) {
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
	} else if (! $time_pass) {
		include 'timesheet_login.php';
		exit;
	}

	// allow admin to continue for up to an hour with one password verification
	if ($user_admin) {
		setcookie('time_pass',1,time()+3600);
	}

	include_once $_SERVER['ROOT_DIR'].'/inc/getUser.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUsers.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getReimbursements.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_task.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/newTimesheet.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/payroll.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/order_type.php';

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

	function getUniqueTask($userid=0) {
		$unique_id = array();
		$query = "SELECT DISTINCT taskid, task_label FROM timesheets ";
		$query .= "WHERE taskid IS NOT NULL ";
		if ($userid) { $query .= "AND userid = '".res($userid)."' "; }
		$query .= "; ";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		while($r = mysqli_fetch_assoc($result)) {
			$unique_id[] = $r;
		}

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

	// If not only display what the user has requested
	$userid = $_REQUEST['user'];
	$edit =  $_REQUEST['edit'];
	$payroll_num =  $_REQUEST['payroll'];
	$taskid =  $_REQUEST['taskid'];
	$task_label = 'service_item_id';//for now

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

	if (! $user_admin) {
		if (! $userid) { $userid = $U['id']; }
		if($userid != $U['id'] OR $edit) {
			header('Location: /timesheet.php?user=' . $U['id'] . ($payroll ? '&payroll=' . $payroll : ''));
			exit;
		}
	}

/*
	$startDate = format_date($today,'m/01/Y',array('m'=>-1));
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}

	$endDate = date('m/d/Y');
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}

	$dbStartDate = format_date($startDate,'Y-m-d 00:00:00');
	$dbEndDate = format_date($endDate,'Y-m-d 00:00:00');
	if ($startDate) {
		$dbStartDate = format_date($startDate, 'Y-m-d').' 00:00:00';
		$dbEndDate = format_date($endDate, 'Y-m-d').' 23:59:59';
	}
*/

	// Create a new object for payroll dates
	$payroll = new Payroll;

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

		$timesheet_data = ($userid ? $payroll->getTimesheets($userid, false, $startDate, $endDate, $taskid, $task_label) : $payroll->getTimesheets($GLOBALS['U']['id'], $user_admin, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $taskid, $task_label));
	} else {

		$startDate = $payroll_start;
		$endDate = $payroll_end;

		$timesheet_data = ($userid ? $payroll->getTimesheets($userid, false, $payroll_start, $payroll_end, $taskid, $task_label) : $payroll->getTimesheets($GLOBALS['U']['id'], $user_admin, $payroll_start, $payroll_end, $taskid, $task_label));
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


	if($timesheet_ids) {
		$checkPayroll = checkPayrollStatus($timesheet_ids);
	}
	// echo $checkPayroll;
	// print_r($timesheet_ids);

	// print "<pre>" . print_r(getTimesheet(6), true) . "</pre>";
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
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<form method="get" action="timesheet.php">

		<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
			<div class="row" style="padding: 8px;" id="filterBar">
				<div class="col-md-5 mobile-hide" style="max-height: 30px;">
					<?php if($user_admin && ! $edit): ?>
						<a href="/timesheet.php?edit=true<?=($userid ? '&user=' . $userid : '')?><?=($payroll_num ? '&payroll=' . $payroll_num : '')?><?=($taskid ? '&taskid=' . $taskid : '')?>" class="btn btn-default btn-sm toggle-edit" style="margin-right: 10px;"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
					<?php endif; ?>
					<select id="user_select" name="user" size="1" class="form-control input-sm select2 pull-right" style="max-width: 200px;" onChange="this.form.submit()">
						<option value =''> - Select User - </option>
						<?php
							$users = getUsers(array(1,2,3,4,5,7,8));
							$users = array();
							$query = "SELECT u.id, c.name FROM users u, contacts c ";
							$query .= "WHERE u.contactid = c.id AND u.hourly_rate > 0 AND c.status = 'Active' ";
							$query .= "GROUP BY u.id ORDER BY c.name ASC; ";
							$result = qdb($query) OR die(qe().'<BR>'.$query);
							while ($r = mysqli_fetch_assoc($result)) {
							//foreach ($users as $uid => $uname) {
								$uid = $r['id'];
								$uname = $r['name'];

								$s = '';
								if ($userid == $uid) { $s = ' selected'; }
								if($user_admin OR ($userid == $uid)) {
									echo '<option value="'.$uid.'"'.$s.'>'.$uname.'</option>'.chr(10);
								}
							}
						?>
					</select>
				</div>

				<div class="text-center col-md-2 remove-pad">
					<h2 class="minimal" id="filter-title">Timesheet</h2>
				</div>

				<div class="col-md-2">
					<select id="task_select" name="taskid" size="1" class="form-control input-sm select2 pull-right" style="max-width: 200px;" onChange="this.form.submit()">
						<option value =''> - Select Task - </option>
						<?php
							//$users = getUsers(array(1,2,3,4,5,7));
							foreach (getUniqueTask($userid) as $task) {
								$s = '';
								$task_num = getTaskNum($task['taskid'], $task['task_label']);
								if (! $task_num) { continue; }

								if ($taskid == $task['taskid']) { $s = ' selected'; }
								//if($user_admin OR ($userid == $uid)) {
								echo '<option value="'.$task['taskid'].'"'.$s.'>'.$task_num.'</option>'.chr(10);
								//}
							}
						?>
					</select>
				</div>
				<div class="col-md-2">
					<select name="" id="payroll_history" size="1" class="form-control input-sm select2">
						<option value="">- Payroll History -</option>
<!--
						<option value="current" <?=($payroll_num == 'current' ? 'selected' : '');?>><?=$currentPayroll->format('m/d/Y') . ' - ' . $currentPayrollEnd->format('m/d/Y')?></option>
-->
						<?php for($x = 1; $x <= 20; $x++) {
							$dateTime = $payroll->getPreviousPeriodStart($x);
							$dateTimeEnd = $payroll->getPreviousPeriodEnd($x);
							echo '<option value="'.$x.'" '.($x == $payroll_num ? 'selected' : '').'>'.$dateTime->format('m/d/Y') . ' - ' . $dateTimeEnd->format('m/d/Y') .'</option>';
						} ?>
					</select>

				</div>
				<div class="col-md-1">
<?php if ($user_admin) { ?>
					<?php if($edit): ?>
						<button class="btn btn-success btn-sm pull-right expenses_edit" type="submit" name="type" value="edit">
							<i class="fa fa-check-circle" aria-hidden="true"></i>					
						</button>
					<?php elseif($payroll_num): ?>
						<button class="btn btn-success pull-right" <?=(! $checkPayroll ? 'type="submit" name="type" value="payroll"' : 'disabled')?>>Approve Payroll</button>
					<?php endif; ?>
<?php } ?>
				</div>
			</div>
		</div>
	</form>

	<?php if($user_admin): ?>
		<form id="timesheet_form" action="/timesheet_edit.php" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="taskid" class="form-control input-sm" value="<?=$taskid;?>">
			<input type="hidden" name="userid" class="form-control input-sm" value="<?=$userid;?>">
			<input type="hidden" name="payroll_num" class="form-control input-sm" value="<?=$payroll_num;?>">
	<?php endif; ?>

		<div id="pad-wrapper">

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

			<div class="row">
				<table class="table heighthover heightstriped table-condensed">
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
								<div class="col-md-4 text-center">Time</div>
								<div class="col-md-4 text-center">Rate</div>
								<div class="col-md-4 text-center">Amount</div>
							</th>
							<th class="overtime">
								<div class="col-md-12 text-center">
									OVERTIME
								</div>
								<div class="col-md-4 text-center">Time</div>
								<div class="col-md-4 text-center">Rate</div>
								<div class="col-md-4 text-center">Time</div>
							</th>
							<th class="doubletime">
								<div class="col-md-12 text-center">
									DOUBLETIME
								</div>
								<div class="col-md-4 text-center">Time</div>
								<div class="col-md-4 text-center">Rate</div>
								<div class="col-md-4 text-center">Amount</div>
							</th>
							<th>CUMULATIVE</th>
							<th>
								<div class="col-md-12 text-center">
									TOTAL
								</div>
								<div class="col-md-6 text-center">Time</div>
								<div class="col-md-6 text-center">Amount</div>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php if($edit AND $userid){ //If the edit feature is on then allow the user to add new records for the timesheet ?>
							<tr>
								<!-- If edit is on and the user has permission then show input boxes for datetime of clockin and clockout -->
								<td>
									<input type="hidden" name="addTime[userid]" class="form-control input-sm" value="<?=$userid?>">
								</td>
								<td>
									<select id="task_select" name="addTime[taskid]" size="1" class="form-control input-sm select2 pull-right task-selection">
										<option value =''> - Select Task - </option>
										<?php
											//$users = getUsers(array(1,2,3,4,5,7));
											foreach (getUniqueTask($userid) as $task) {
												$s = '';
												$task_num = getTaskNum($task['taskid'], $task['task_label']);
												if (! $task_num) { continue; }

												if ($taskid == $task['taskid']) { $s = ' selected'; }
												//if($user_admin OR ($userid == $uid)) {
												echo '<option value="'.$task['taskid'].'"'.$s.' data-label="'.$task['task_label'].'">'.$task_num.'</option>'.chr(10);
												//}
											}
										?>
									</select>
									<input type="hidden" name="addTime[task_label]" class="form-control input-sm task_label_input" value="service_item_id">
								</td>
								<td>
									<div class="input-group datepicker-datetime date datetime-picker" data-hposition="right">
		   		    			         <input type="text" name="addTime[clockin]" class="form-control input-sm" value="">
		           		       			 <span class="input-group-addon">
				       		                 <span class="fa fa-calendar"></span>
		       					         </span>
									</div>
								</td>
								<td>
									<div class="input-group datepicker-datetime date datetime-picker" data-hposition="right">
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
								<td></td>
							</tr>
						<?php } ?>
						<?php 
							foreach($timesheet_data as $item) { 
								$userTimesheet = $userTimesheets[$item['userid']];

								$show_task = '';
								$task = format_task($item['taskid'], $item['task_label']);
								if ($task) {
									$T = order_type($item['task_label']);
									$show_task = $task.' <a href="service.php?order_type='.$T['type'].'&taskid='.$item['taskid'].'"><i class="fa fa-arrow-right"></i></a>';
								}
						?>
							<tr>
								<!-- If edit is on and the user has permission then show input boxes for datetime of clockin and clockout -->
								<?php if($edit && strtotime(format_date($item['clockin'])) > strtotime(format_date($payroll_start))): ?>
									<td>
										<input type="hidden" name="data[<?=$item['id'];?>][userid]" class="form-control input-sm" value="<?=$item['userid'];?>">
									</td>
									<td><?=$show_task;?>
									<td>
										<div class="input-group datepicker-datetime date datetime-picker" data-hposition="right">
			   		    			         <input type="text" name="data[<?=$item['id'];?>][clockin]" class="form-control input-sm" value="<?=date('m/d/Y g:i a', strtotime($item['clockin']));?>">
			           		       			 <span class="input-group-addon">
					       		                 <span class="fa fa-calendar"></span>
			       					         </span>
										</div>
									</td>
									<td>
										<div class="input-group datepicker-datetime date datetime-picker" data-hposition="right">
			   		    			         <input type="text" name="data[<?=$item['id'];?>][clockout]" class="form-control input-sm" value="<?=($item['clockout'] ? date('m/d/Y g:i a', strtotime($item['clockout'])) : '');?>">
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
									<div class="col-md-4 text-center">
										<?php 
											echo toTime($userTimesheet[$item['id']]['REG_secs']);

											if ($item['rate']==11) {
												$total_travel_seconds += $userTimesheet[$item['id']]['REG_secs'];
												$total_travel_pay += $userTimesheet[$item['id']]['REG_pay'];
											} else {
												$total_reg_seconds += $userTimesheet[$item['id']]['REG_secs'];
												$total_reg_pay += $userTimesheet[$item['id']]['REG_pay'];
											}
										?>
									</div>
									<div class="col-md-4 text-center">
										<?=format_price($item['rate']);?>
									</div>
									<div class="col-md-4 text-center">
										<?php 
											echo format_price($userTimesheet[$item['id']]['REG_pay']);
										?>
									</div>
								</td>
								<td class="overtime">
									<div class="col-md-4 text-center">
										<?php
											if($userTimesheet[$item['id']]['OT_secs'])
												echo toTime($userTimesheet[$item['id']]['OT_secs']);
											if ($item['rate']==11) {
												$total_travel_ot_seconds += $userTimesheet[$item['id']]['OT_secs'];
											} else {
												$total_reg_ot_seconds += $userTimesheet[$item['id']]['OT_secs'];
											}
										?>								
									</div>
									<div class="col-md-4 text-center">
										<?=($userTimesheet[$item['id']]['OT_secs'] ? format_price(1.5*$item['rate']) : '');?>
									</div>
									<div class="col-md-4 text-center">
										<?php 
											if($userTimesheet[$item['id']]['OT_pay'])
												echo format_price($userTimesheet[$item['id']]['OT_pay']);
											if ($item['rate']==11) {
												$total_travel_ot_pay += $userTimesheet[$item['id']]['OT_pay'];
											} else {
												$total_reg_ot_pay += $userTimesheet[$item['id']]['OT_pay'];
											}
										?>
									</div>
								</td>
								<td class="doubletime">
									<div class="col-md-4 text-center">
										<?php
											if($userTimesheet[$item['id']]['DT_secs'])
												echo toTime($userTimesheet[$item['id']]['DT_secs']);
											if ($item['rate']==11) {
												$total_travel_dt_seconds += $userTimesheet[$item['id']]['DT_secs'];
											} else {
												$total_reg_dt_seconds += $userTimesheet[$item['id']]['DT_secs'];
											}
										?>
									</div>
									<div class="col-md-4 text-center">
										<?=($userTimesheet[$item['id']]['DT_secs'] ? format_price(2*$item['rate']) : '');?>
									</div>
									<div class="col-md-4 text-center">
										<?php 
											if($userTimesheet[$item['id']]['DT_secs'])
												echo format_price($userTimesheet[$item['id']]['DT_pay']);
											if ($item['rate']==11) {
												$total_travel_dt_pay += $userTimesheet[$item['id']]['DT_pay'];
											} else {
												$total_reg_dt_pay += $userTimesheet[$item['id']]['DT_pay'];
											}
										?>
									</div>
								</td>
								<td>
<!--
									<?php
										if($userTimesheet[$item['id']]['CUM_secs']) {
											echo toTime($userTimesheet[$item['id']]['CUM_secs']);
										}
									?>					
-->
								</td>
								<td>
									<div class="col-md-6 text-center">
										<?php
											echo toTime($userTimesheet[$item['id']]['secsDiff']);
											$total_time += $userTimesheet[$item['id']]['secsDiff'];
										?>
									</div>
									<div class="col-md-6 text-center">
										<?=format_price($userTimesheet[$item['id']]['totalPay']);?>

										<?php if ($user_admin AND $item['userid']<>$U['id']) { ?>
											<input type="hidden" name="payroll[<?=$item['id'];?>]" class="form-control input-sm" value="<?=$userTimesheet[$item['id']]['totalPay'];?>">
											<a class="delete_time" href="#" data-timeid="<?=$item['id']?>"><i class="fa fa-trash" aria-hidden="true"></i></a>
										<?php } ?>
									</div>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<td colspan="4"></td>
							<td colspan="">
								<div class="col-md-4 text-center text-bold">
									<?=toTime($total_reg_seconds)?>
								</div>
								<div class="col-md-4 total_travel" data-total="<?=format_price($total_travel_pay);?>" data-time="<?=($total_travel_seconds ? number_format(($total_travel_seconds/3600),4).' hrs' : '');?>"></div>
								<div class="col-md-4 text-center text-bold total_reg" data-total="<?=format_price($total_reg_pay);?>" data-time="<?=($total_reg_seconds ? number_format(($total_reg_seconds/3600),4).' hrs' : '');?>">
									<?=format_price($total_reg_pay);?>
								</div>
							</td>
							<td colspan="">
								<div class="col-md-4 text-center text-bold">
									<?=toTime($total_reg_ot_seconds);?>
								</div>
								<div class="col-md-4 text-center text-bold">
								</div>
								<div class="col-md-4 text-center text-bold total_reg_ot" data-total="<?=format_price($total_reg_ot_pay);?>" data-time="<?=($total_reg_ot_seconds ? number_format(($total_reg_ot_seconds/3600),4).' hrs' : '');?>">
									<?=format_price($total_reg_ot_pay);?>
								</div>
								<span class="hidden total_travel_ot" data-total="<?=format_price($total_travel_ot_pay);?>" data-time="<?=($total_travel_ot_seconds ? number_format(($total_travel_ot_seconds/3600),4).' hrs' : '');?>">
							</td>
							<td colspan="">
								<div class="col-md-4 text-center text-bold">
									<?=toTime($total_reg_dt_seconds)?>
								</div>
								<div class="col-md-4 text-center text-bold">
								</div>
								<div class="col-md-4 text-center text-bold total_reg_dt" data-total="<?=format_price($total_reg_dt_pay);?>" data-time="<?=($total_reg_dt_seconds ? number_format(($total_reg_dt_seconds/3600),4).' hrs' : '');?>">
									<?=format_price($total_reg_dt_pay);?>
								</div>
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
								<div class="col-md-6 text-center text-bold total_pay" data-total="<?=format_price($total_all_pay);?>" data-time="<?=($total_all_seconds ? number_format(($total_all_seconds/3600),4).' hrs' : '');?>">
									<?=format_price($total_all_pay);?>
								</div>
							</td>
						</tr>
					</tbody>
		        </table>
			</div>
		</div>
	<?php if($user_admin): ?>
		</form>
	<?php endif; ?>

	<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
    	(function($){
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

    			$(".task_label_input").val($(this).find(':selected').data("label"));
    		});

    		$(document).on('click', '.delete_time', function(e){
    			e.preventDefault();

    			var timeid = $(this).data('timeid');
    			if(confirm("Are you sure you want to delete this time record?")) {
    				window.location.href = "/timesheet_edit.php?delete=" + timeid;
    			}
    		});

    		$(document).on('click', ".upload_link", function(e){
		        e.preventDefault();

		        $(this).closest(".file_container").find(".upload").trigger("click");
		        // $("#upload:hidden").trigger('click');
		    });

/*
    		$(document).on("change", "#user_select", function() {
    			// alert($(this).val());
    			window.location.href = "/timesheet.php?user=" + $(this).val();
    		});
*/

    		$(document).on("click", ".expenses_edit", function(e) {
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
    			var userid = $("#user_select").val();//getUrlParameter('user');
    			var edit = getUrlParameter('edit');

    			window.location.href = "/timesheet.php?user="+userid+(payroll_num ? "&payroll=" + payroll_num : ''); // + (edit ? '&edit=true' : '');
    		});

    		//Get the url argument parameter
			function getUrlParameter(sParam) {
			    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
			        sURLVariables = sPageURL.split('&'),
			        sParameterName,
			        i;
			
			    for (i = 0; i < sURLVariables.length; i++) {
			        sParameterName = sURLVariables[i].split('=');
			
			        if (sParameterName[0] === sParam) {
			            return sParameterName[1] === undefined ? true : sParameterName[1];
			        }
			    }
			}

    	})(jQuery);
    </script>

</body>
</html>
