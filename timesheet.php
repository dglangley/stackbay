<?php	
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUser.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUsers.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/newTimesheet.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/payroll.php';

	// If true then the user is an admin
	// If not only display what the user has requested
	$user_admin = false;
	$deny_permission = false;
	$userid = $_REQUEST['user'];
	$edit =  $_REQUEST['edit'];
	$payroll_num =  $_REQUEST['payroll'];

	// Timesheet variable flags
	$curdate = ''; 

	// These are running totals
	$total_time = 0;
	$total_pay = 0;

	$total_reg_seconds = 0;
	$total_reg_pay = 0;

	$total_ot_seconds = 0;
	$total_ot_pay = 0;

	$total_dt_second = 0;
	$total_dt_pay = 0;

	// Set the pay period start date & time
	$pay_period = '';

	if(in_array("4", $USER_ROLES)) {
		$user_admin = true;
	} else if($userid != $GLOBALS['U']['id'] OR $edit) {
		$deny_permission = true;
		header('Location: /timesheet.php?user=' . $GLOBALS['U']['id'] . ($payroll ? '&payroll=' . $payroll : ''));
		exit();
	}

	function getServiceNumber($taskid, $table = 'repair_items', $field = 'ro_number') {
		$service_number = 0;

		$query = "SELECT $field as so_number FROM $table WHERE id = ".res($taskid).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$service_number = $r['so_number'];
		}

		return $service_number;
	}

	function getTaskNum($item_id, $item_id_label) {
		$service_number = 0;
		$table = '';
		$type = '';

		if($item_id_label == 'repair_item_id') {
			$table = 'repair_items';
			$field = 'ro_number';
			$type = 'Repair# ';
		} else {
			$table = 'service_items';
			$field = 'so_number';
			$type = 'Service# ';
		}

		$query = "SELECT $field as so_number FROM $table WHERE id = ".res($item_id).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$service_number = $r['so_number'];
		}

		return $type . $service_number;
	}

	function getUniqueTask() {
		$unique_id = array();
		$query = "SELECT DISTINCT taskid, task_label FROM timesheets WHERE taskid IS NOT NULL;";
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

		$ids = join("','",$ids); 

		$query = "SELECT * FROM timesheet_approvals WHERE timesheetid IN ($ids);";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if(mysqli_num_rows($result)) {
			return true;
		}

		return false;
	}

	function toTime($secs) {
		// given $secs seconds, what is the time g:i:s format?
		$hours = floor($secs/3600);

		// what are the remainder of seconds after taking out hours above?
		$secs -= ($hours*3600);
		$mins = floor($secs/60);
		$secs -= ($mins*60);

		return (str_pad($hours,2,0,STR_PAD_LEFT).':'.str_pad($mins,2,0,STR_PAD_LEFT).':'.str_pad($secs,2,0,STR_PAD_LEFT));
	}

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

		$timesheet_data = ($userid ? $payroll->getTimesheets($userid, false, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')) : $payroll->getTimesheets($GLOBALS['U']['id'], $user_admin, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')));
	} else {

		$timesheet_data = ($userid ? $payroll->getTimesheets($userid) : $payroll->getTimesheets($GLOBALS['U']['id'], $user_admin));
	}

	$payroll_start = $currentPayroll->format('Y-m-d H:i:s');
	$payroll_end = $currentPayrollEnd->format('Y-m-d H:i:s');

	$timesheet_ids = array();

	foreach($timesheet_data as $list) {
		$timesheet_ids[] = $list['id'];
	}

	$checkPayroll = checkPayrollStatus($timesheet_ids);
	// echo $checkPayroll;
	// print_r($timesheet_ids);

	// print "<pre>" . print_r(getTimesheet(6), true) . "</pre>";

	$userTimesheet = getTimesheet(6);
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

		#main-stats .stat .data .number {
			font-size: 20px;
			font-weight: 100;
		}
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<?php if($user_admin): ?>
		<form id="timesheet_form" action="/timesheet_edit.php" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="userid" class="form-control input-sm" value="<?=$userid;?>">
			<input type="hidden" name="payroll_num" class="form-control input-sm" value="<?=$payroll_num;?>">
	<?php endif; ?>
		<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
			<div class="row" style="padding: 8px;" id="filterBar">
				<div class="col-md-4 mobile-hide" style="max-height: 30px;">
					<div class="col-md-8">
						<?php if($user_admin && ! $edit): ?>
							<a href="/timesheet.php?edit=true<?=($userid ? '&user=' . $userid : '')?><?=($payroll_num ? '&payroll=' . $payroll_num : '')?>" class="btn btn-default btn-sm toggle-edit" style="margin-right: 10px;"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
						<?php endif; ?>
						<select id="user_select" name="user_id" size="1" class="form-control input-sm select2 pull-right" style="max-width: 200px;">
							<option value =''> - Select User - </option>
							<?php
								$users = getUsers(array(1,2,3,4,5,7));
								foreach ($users as $uid => $uname) {
									$s = '';
									if ($userid == $uid) { $s = ' selected'; }
									if($user_admin OR ($userid == $uid)) {
										echo '<option value="'.$uid.'"'.$s.'>'.$uname.'</option>'.chr(10);
									}
								}
							?>
						</select>
					</div>

					<div class="col-md-4 date_container mobile-hid remove-pad">
						<select id="task_select" name="task_id" size="1" class="form-control input-sm select2 pull-right" style="max-width: 200px;">
							<option value =''> - Select Task - </option>
							<?php
								//$users = getUsers(array(1,2,3,4,5,7));
								foreach (getUniqueTask() as $task) {
									$s = '';
									// if ($taskid == $task['item_id']) { $s = ' selected'; }
									//if($user_admin OR ($userid == $uid)) {
									echo '<option value="'.$task['taskid'].'"'.$s.'>'.getTaskNum($task['taskid'], $task['task_label']).'</option>'.chr(10);
									//}
								}
							?>
						</select>
					</div>
				</div>

				<div class="text-center col-md-4 remove-pad">
					<h2 class="minimal" id="filter-title">Timesheet</h2>
				</div>

				<div class="col-md-4" style="">
					<div class="col-md-6 col-sm-6">
						<select name="" id="payroll_history" size="1" class="form-control input-sm select2">
							<option value="">- Payroll History -</option>
							<option value="current" <?=($payroll_num == 'current' ? 'selected' : '');?>><?=$currentPayroll->format('m/d/Y') . ' - ' . $currentPayrollEnd->format('m/d/Y')?></option>
							<?php for($x = 1; $x <= 20; $x++) {
								$dateTime = $payroll->getPreviousPeriodStart($x);
								$dateTimeEnd = $payroll->getPreviousPeriodEnd($x);
								echo '<option value="'.$x.'" '.($x == $payroll_num ? 'selected' : '').'>'.$dateTime->format('m/d/Y') . ' - ' . $dateTimeEnd->format('m/d/Y') .'</option>';
							} ?>
						</select>
						
					</div>

					<div class="col-md-6 col-sm-6 remove-pad">
						
						<?php if($edit): ?>
							<button class="btn btn-success btn-sm pull-right" type="submit" name="type" value="edit">
								<i class="fa fa-check-circle" aria-hidden="true"></i>					
							</button>
						<?php elseif($payroll_num): ?>
							<button class="btn btn-success pull-right" <?=(! $checkPayroll ? 'type="submit" name="type" value="payroll"' : 'disabled')?>>Approve Payroll</button>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<div id="pad-wrapper">

			<div id="main-stats">
	            <div class="row stats-row">
	                <div class="col-md-3 col-sm-3 stat">
	                    <div class="data">
	                        <span class="sum_total_reg number text-brown">$0.00</span>
							<span class="info">Regular Pay</span>
	                    </div>
	                </div>
	                <div class="col-md-3 col-sm-3 stat">
	                    <div class="data">
	                        <span class="sum_total_ot number text-black">$0.00</span>
							<span class="info">Overtime Pay</span>
	                    </div>
	                </div>
	                <div class="col-md-3 col-sm-3 stat">
	                    <div class="data">
	                        <span class="sum_total_dt number text-black">$0.00</span>
							<span class="info">Doubletime Pay</span>
	                    </div>
	                </div>
	                <div class="col-md-3 col-sm-3 stat last">
	                    <div class="data">
	                        <span class="sum_total_pay number text-success" style="font-weight: 400; font-size: 25px;">$0.00</span>
							<span class="info">Total Pay</span>
	                    </div>
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
							<th>
								<div class="col-md-12 text-center">
									REGULAR PAY
								</div>
								<div class="col-md-4 text-center">Time</div>
								<div class="col-md-4 text-center">Rate</div>
								<div class="col-md-4 text-center">Amount</div>
							</th>
							<th>
								<div class="col-md-12 text-center">
									OVERTIME
								</div>
								<div class="col-md-6 text-center">Time</div>
								<div class="col-md-6 text-center">Amount</div>
							</th>
							<th>
								<div class="col-md-12 text-center">
									DOUBLETIME
								</div>
								<div class="col-md-6 text-center">Time</div>
								<div class="col-md-6 text-center">Amount</div>
							</th>
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
						<?php 
							foreach($timesheet_data as $item) { 
								// Defined flags
								// $travel = ($item['rate'] == 12.5 ? true : false);
								// $line_seconds = strtotime($item['clockout']) - strtotime($item['clockin']);

								// // Reset Variables per Line
								// $reg_pay = 0;
								// $dt_pay = 0;
								// $ot_pay = 0;
								// $travel_pay = 0;

								// $calc_ot = 0;
						?>
							<tr>
								<!-- If edit is on and the user has permission then show input boxes for datetime of clockin and clockout -->
								<?php if($edit && strtotime(format_date($item['clockin'])) > strtotime(format_date($payroll_start))): ?>
									<td>
										<input type="hidden" name="data[<?=$item['id'];?>][userid]" class="form-control input-sm" value="<?=$item['userid'];?>">
									</td>
									<td><?=getServiceNumber($item['taskid']);?></td>
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
									<td><?=getServiceNumber($item['taskid']);?></td>
									<td><?=date('g:i a', strtotime($item['clockin']));?></td>
									<td>
										<?php 
											if(! empty($item['clockout'])) {
												echo date('g:i a', strtotime($item['clockout']));
											}
										?>
									</td>
									
								<?php endif; ?>

								<!-- Resume the data -->
	
								<td>
									<div class="col-md-4 text-center">
										<?php 
											echo toTime($userTimesheet[$item['id']]['REG_secs']);
											$total_reg_seconds += $userTimesheet[$item['id']]['REG_secs'];
										?>							
									</div>
									<div class="col-md-4 text-center">
										<?=format_price($item['rate']);?>
									</div>
									<div class="col-md-4 text-center">
										<?php 
											echo format_price($userTimesheet[$item['id']]['REG_pay']);
											$total_reg_pay += $userTimesheet[$item['id']]['REG_pay'];
										?>
									</div>
								</td>
								<td>
									<div class="col-md-6 text-center">
										<?php
											if($userTimesheet[$item['id']]['OT_secs'])
												echo toTime($userTimesheet[$item['id']]['OT_secs']);
											$total_ot_seconds += $userTimesheet[$item['id']]['OT_secs'];
										?>								
									</div>
									<div class="col-md-6 text-center">
										<?php 
											if($userTimesheet[$item['id']]['OT_pay'])
												echo format_price($userTimesheet[$item['id']]['OT_pay']);
											$total_ot_pay += $userTimesheet[$item['id']]['OT_pay'];
										?>
									</div>
								</td>
								<td>
									<div class="col-md-6 text-center">
										<?php
											if($userTimesheet[$item['id']]['DT_secs'])
												echo toTime($userTimesheet[$item['id']]['DT_secs']);
											$total_dt_seconds += $userTimesheet[$item['id']]['DT_secs'];
										?>						
									</div>
									<div class="col-md-6 text-center">
										<?php 
											echo format_price($userTimesheet[$item['id']]['DT_pay']);
											$total_dt_pay += $userTimesheet[$item['id']]['DT_pay'];
										?>
									</div>
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
										<input type="hidden" name="payroll[<?=$item['id'];?>]" class="form-control input-sm" value="<?=$userTimesheet[$item['id']]['totalPay'];?>">
										<a class="delete_time" href="#" data-timeid="<?=$item['id']?>"><i class="fa fa-trash" aria-hidden="true"></i></a>
									</div>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<td colspan="4"></td>
							<td colspan="">
								<div class="col-md-4 text-center"><strong><?=toTime($total_reg_seconds)?></strong></div>
								<div class="col-md-4"></div>
								<div class="col-md-4 text-center total_reg" data-total="<?=format_price($total_reg_pay);?>"><strong><?=format_price($total_reg_pay);?></strong></div>
							</td>
							<td colspan="">
								<div class="col-md-6 text-center"><strong><?=toTime($total_ot_seconds);?></strong></div>
								<div class="col-md-6 text-center total_ot" data-total="<?=format_price($total_ot_pay);?>"><strong><?=format_price($total_ot_pay);?></strong></div>
							</td>
							<td colspan="">
								<div class="col-md-6 text-center"><strong><?=toTime($total_dt_seconds)?></strong></div>
								<div class="col-md-6 text-center total_dt" data-total="<?=format_price($total_dt_pay);?>"><strong><?=format_price($total_dt_pay);?></strong></div>
							</td>
							<td colspan="">
								<div class="col-md-6 text-center"><strong><?=toTime($total_time)?></strong></div>
								<div class="col-md-6 text-center total_pay" data-total="<?=format_price($total_ot_pay + $total_reg_pay + $total_dt_pay);?>">
									<strong><?=format_price($total_ot_pay + $total_reg_pay + $total_dt_pay);?></strong>
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
    		var total_dt_pay = $(".total_dt").data("total");
    		var total_ot_pay = $(".total_ot").data("total");
    		var total_reg_pay = $(".total_reg").data("total");
    		var total_pay = $(".total_pay").data("total");

    		$('.sum_total_reg').text(total_reg_pay);
    		$('.sum_total_ot').text(total_ot_pay);
    		$('.sum_total_dt').text(total_dt_pay);
    		$('.sum_total_pay').text(total_pay);

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

    		$(document).on("change", "#user_select", function() {
    			// alert($(this).val());
    			window.location.href = "/timesheet.php?user=" + $(this).val();
    		});

    		$(document).on("click", ".expenses_edit", function(e) {
    			e.preventDefault();

    			var type = $(this).val();
    			input = $("<input>").attr("type", "hidden").attr("name", "type").val(type);
				$('#expenses_form').append($(input));

    			$('#expenses_form').submit();
    		});

    		$(document).on("change", ".upload", function(){
    			var f_file =  $(this).val();
			    var fileName = f_file.match(/[^\/\\]+$/);

				$(this).closest(".file_container").find(".file_name").text(fileName);
    		});

    		$(document).on("change", "#payroll_history", function(){
    			var payroll_num = $(this).val();
    			var userid = getUrlParameter('user');
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
