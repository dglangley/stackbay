<?php	
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUser.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUsers.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/newTimesheet.php';

	// If true then the user is an admin
	// If not only display what the user has requested
	$user_admin = false;
	$deny_permission = false;
	$userid = $_REQUEST['user'];
	$edit =  $_REQUEST['edit'];

	if(in_array("4", $USER_ROLES)) {
		$user_admin = true;
	} else if($userid != $GLOBALS['U']['id'] OR $edit) {
		$deny_permission = true;
		header('Location: /timesheet.php?user=' . $GLOBALS['U']['id']);
		exit();
	}

	function getTimesheets($userid, $user_admin) {
		$timesheets = array();

		if($user_admin) {
			$query = "SELECT * FROM timesheets ORDER by clockin DESC;";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			while($r = mysqli_fetch_assoc($result)) {
				$timesheets[] = $r;
			}
		} else {
			$query = "SELECT * FROM timesheets WHERE userid = ".res($userid)." ORDER by clockin DESC;";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			while($r = mysqli_fetch_assoc($result)) {
				$timesheets[] = $r;
			}
		}

		return $timesheets;
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

	function toTime($secs) {
		// given $secs seconds, what is the time g:i:s format?
		$hours = floor($secs/3600);

		// what are the remainder of seconds after taking out hours above?
		$secs -= ($hours*3600);
		$mins = floor($secs/60);
		$secs -= ($mins*60);

		return (str_pad($hours,2,0,STR_PAD_LEFT).':'.str_pad($mins,2,0,STR_PAD_LEFT).':'.str_pad($secs,2,0,STR_PAD_LEFT));
	}

	// function getUserRate($userid) {
	// 	$rate = 0;

	// 	$query = "SELECT hourly_rate FROM users WHERE id = ".res($userid).";";
	// 	$result = qdb($query) OR die(qe() . ' ' . $query);

	// 	if(mysqli_num_rows($result)) {
	// 		$r = mysqli_fetch_assoc($result);
	// 		$rate = $r['hourly_rate'];
	// 	}

	// 	return $rate;
	// }


	$timesheet_data = ($userid ? getTimesheets($userid) : getTimesheets($GLOBALS['U']['id'], $user_admin));

	$two_weeks_ago = date('Y-m-d', strtotime('-14 days', strtotime(date('Y-m-d H:i:s'))));

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
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<?php if($edit): ?>
		<form id="timesheet_form" action="/timesheet_edit.php" method="POST" enctype="multipart/form-data">
	<?php endif; ?>
		<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
			<div class="row" style="padding: 8px;" id="filterBar">
				<div class="col-md-4 mobile-hide" style="max-height: 30px;">
					<div class="col-md-8">
						<?php if($user_admin && ! $edit): ?>
							<a href="/timesheet.php?user=6&edit=true" class="btn btn-default btn-sm toggle-edit" style="margin-right: 10px;"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
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
						
					</div>
				</div>

				<div class="text-center col-md-4 remove-pad">
					<h2 class="minimal" id="filter-title">Timesheet</h2>
				</div>

				<div class="col-md-4" style="">
					<div class="col-md-4 col-sm-4 col-xs-3">
						
					</div>

					<div class="col-md-8 col-sm-8 col-xs-9 remove-pad">
						
						<?php if($edit): ?>
							<button class="btn btn-success btn-sm pull-right" type="submit">
								<i class="fa fa-check-circle" aria-hidden="true"></i>					
							</button>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<div id="pad-wrapper">

			<div class="row">
				<table class="table heighthover heightstriped table-condensed">
					<thead>
						<tr>
							<th>DATE</th>
							<th>TASK</th>
							<th>CLOCKIN</th>
							<th>CLOCKOUT</th>
							<th>PUNCH</th>
							<th>
								<div class="col-md-12 text-center">
									REGULAR PAY
								</div>
								<div class="col-md-6 text-center">Time</div>
								<div class="col-md-6 text-center">Amount</div>
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
									TRAVEL
								</div>
								<div class="col-md-6 text-center">Time</div>
								<div class="col-md-6 text-center">Amount</div>
							</th>
							<!-- <th>
								<div class="col-md-12 text-center">
									MILEAGE
								</div>
								<div class="col-md-6">Miles</div>
								<div class="col-md-6">AMT</div>
							</th> -->
							<th>TOTAL</th>
						</tr>
					</thead>
					<tbody>
						<?php 
							$curdate = ''; 
							$total_seconds = 0; 
							$work_seconds = 8 * 3600; 
							$dt_seconds = 12 * 3600; 
		
							$ot;
							$dt;

							$total_time = 0;

							$total_reg_seconds = 0;
							$total_reg_pay = 0;

							$total_ot_seconds = 0;
							$total_ot_pay = 0;

							$total_dt_second = 0;
							$total_dt_pay = 0;

							$total_miles = 0;
							$total_miles_pay = 0;

							foreach($timesheet_data as $item) { 
						?>
							<?php if($edit && strtotime(format_date($item['clockin'])) > strtotime(format_date($two_weeks_ago))): ?>
								<tr>
									<td>
										<?php 
											if(empty($curdate)) {
												$curdate = format_date($item['clockin']);
												$total_seconds = 0;
											} else if($curdate != format_date($item['clockin'])) {
												$curdate = format_date($item['clockin']);
												$total_seconds = 0;
											}
										?>
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
										<!-- <input type="datetime-local" name="data[<?=$item['id'];?>][clockin]" class="form-control input-sm" value="<?=date('Y-m-d\TH:i:s', strtotime($item['clockin']));?>"> -->
									</td>
									<td>
										<div class="input-group datepicker-datetime date datetime-picker" data-hposition="right">
			   		    			         <input type="text" name="data[<?=$item['id'];?>][clockout]" class="form-control input-sm" value="<?=($item['clockout'] ? date('m/d/Y g:i a', strtotime($item['clockout'])) : '');?>">
			           		       			 <span class="input-group-addon">
					       		                 <span class="fa fa-calendar"></span>
			       					         </span>
										</div>
										<!-- <input type="datetime-local" name="data[<?=$item['id'];?>][clockout]" class="form-control input-sm" value="<?=($item['clockout'] ? date('Y-m-d\TH:i:s', strtotime($item['clockout'])) : '');?>"> -->
									</td>
									<td>
										<?php 
											if(! empty($item['clockout'])) {
												$total_seconds += strtotime($item['clockout']) - strtotime($item['clockin']);
												$total_time += strtotime($item['clockout']) - strtotime($item['clockin']);
												echo toTime(strtotime($item['clockout']) - strtotime($item['clockin']));
											}
										?>
									</td>
									<td>
										<div class="col-md-6 text-center">
											<?php 
												if(! empty($item['clockout'])) {
													if (strtotime($item['clockout']) - strtotime($item['clockin']) < $work_seconds) {
														echo date('H:i:s',strtotime($item['clockout']) - strtotime($item['clockin']));
														$total_reg_seconds += strtotime($item['clockout']) - strtotime($item['clockin']);
													} else {
														echo '08:00';
														$total_reg_seconds += $work_seconds;
													}
												}
											?>							
										</div>
										<div class="col-md-6 text-center">
											<?php 
												if(! empty($item['clockout'])) {
													echo ((strtotime($item['clockout']) - strtotime($item['clockin']) < $work_seconds) ? format_price(number_format((($total_seconds) / 3600) * getUserRate($item['userid']), 2, '.', '')) : format_price(number_format((($work_seconds) / 3600) * getUserRate($item['userid']), 2, '.', '')));
												}
											?>
										</div>
										<div class="col-md-6 text-center">
																				
										</div>
									</td>
									<td>
										<div class="col-md-6 text-center">
											<?php
												if($total_seconds - $work_seconds > 0) {
													if($total_seconds - $dt_seconds > 0) {
														$calc_ot = $dt_seconds;
													} else {
														$calc_ot = $total_seconds - $work_seconds;
													}

													echo toTime($calc_ot);
													$total_ot_seconds += $calc_ot;
												}
											?>								
										</div>
										<div class="col-md-6 text-center">
											<?php 
												if($total_seconds - $work_seconds > 0) {
													if($total_seconds - $dt_seconds > 0) {
														$calc_ot = $dt_seconds;
													} else {
														$calc_ot = $total_seconds - $work_seconds;
													}
													echo format_price(number_format((($calc_ot) / 3600) * getUserRate($item['userid']) * 1.5, 2, '.', ''));
													$total_ot_pay += number_format((($calc_ot) / 3600) * getUserRate($item['userid']) * 1.5, 2, '.', '');
												} 
											?>
										</div>
									</td>
									<td>
										<div class="col-md-6 text-center">
											<?php
												if($total_seconds - $dt_seconds > 0) {
													echo toTime($total_seconds - $dt_seconds);
													$total_dt_seconds += $total_seconds - $dt_seconds;
												}
											?>						
										</div>
										<div class="col-md-6 text-center">
											<?php 
												if($total_seconds - $dt_seconds > 0) {
													echo format_price(number_format((($total_seconds - $dt_seconds) / 3600) * getUserRate($item['userid']) * 2, 2, '.', ''));
													$total_dt_pay += number_format((($total_seconds - $dt_seconds) / 3600) * getUserRate($item['userid']) * 2, 2, '.', '');
												} 
											?>
										</div>
									</td>
									<td>
										<div class="col-md-6 text-center">
																	
										</div>
										<div class="col-md-6 text-center">
											
										</div>
									</td>
									<td>
										<?=toTime($total_seconds)?> <a class="delete_time" href="#" data-timeid="<?=$item['id']?>"><i class="fa fa-trash" aria-hidden="true"></i></a>
									</td>
								</tr>
							<?php else: ?>
								<tr>
									<td>
										<?php 
											if(empty($curdate)) {
												$curdate = format_date($item['clockin']);
												echo format_date($item['clockin']);
												$total_seconds = 0;
											} else if($curdate != format_date($item['clockin'])) {
												$curdate = format_date($item['clockin']);
												echo format_date($item['clockin']);
												$total_seconds = 0;
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
									<td>
										<?php 
											if(! empty($item['clockout'])) {
												$total_seconds += strtotime($item['clockout']) - strtotime($item['clockin']);
												$total_time += strtotime($item['clockout']) - strtotime($item['clockin']);
												echo toTime(strtotime($item['clockout']) - strtotime($item['clockin']));
											} else {
												echo '';
											}
										?>
									</td>
									<td>
										<div class="col-md-6 text-center">
											<?php 
												if(! empty($item['clockout'])) {
													if ($total_seconds < $work_seconds) {
														echo date('H:i:s',strtotime($item['clockout']) - strtotime($item['clockin']));
														$total_reg_seconds += strtotime($item['clockout']) - strtotime($item['clockin']);
													} else {
														echo '08:00';
														$total_reg_seconds += $work_seconds;
													}
												}
											?>											
										</div>
										<div class="col-md-6 text-center">
											<?php 
												if(! empty($item['clockout'])) {
													echo (($total_seconds < 28800) ? format_price(number_format((($total_seconds) / 3600) * getUserRate($item['userid']), 2, '.', '')) : format_price(number_format(((28800) / 3600) * getUserRate($item['userid']), 2, '.', '')));
													$total_reg_pay += number_format(((28800) / 3600) * getUserRate($item['userid']), 2, '.', '');
												}
											?>
										</div>
									</td>
									<td>
										<div class="col-md-6 text-center">
											<?php
												if($total_seconds - $work_seconds > 0) {
													if($total_seconds - $dt_seconds > 0) {
														$calc_ot = $dt_seconds;
													} else {
														$calc_ot = $total_seconds - $work_seconds;
													}
													
													echo toTime($calc_ot);
													$total_ot_seconds += $calc_ot;
												}
											?>								
										</div>
										<div class="col-md-6 text-center">
											<?php 
												if($total_seconds - $work_seconds > 0) {
													if($total_seconds - $dt_seconds > 0) {
														$calc_ot = $dt_seconds;
													} else {
														$calc_ot = $total_seconds - $work_seconds;
													}
													echo format_price(number_format((($calc_ot) / 3600) * getUserRate($item['userid']) * 1.5, 2, '.', ''));
													$total_ot_pay += number_format((($calc_ot) / 3600) * getUserRate($item['userid']) * 1.5, 2, '.', '');
												} 
											?>
										</div>
									</td>
									<td>
										<div class="col-md-6 text-center">
											<?php
												if($total_seconds - $dt_seconds > 0) {
													echo toTime($total_seconds - $dt_seconds);
													$total_dt_seconds += $total_seconds - $dt_seconds;
												}
											?>						
										</div>
										<div class="col-md-6 text-center">
											<?php 
												if($total_seconds - $dt_seconds > 0) {
													echo format_price(number_format((($total_seconds - $dt_seconds) / 3600) * getUserRate($item['userid']) * 2, 2, '.', ''));
													$total_dt_pay += number_format((($total_seconds - $dt_seconds) / 3600) * getUserRate($item['userid']) * 2, 2, '.', '');
												} 
											?>
										</div>
									</td>
									<td>
										<div class="col-md-6 text-center">
							
										</div>
										<div class="col-md-6 text-center">

										</div>
									</td>
									<td><?=toTime($total_seconds)?></td>
								</tr>
							<?php endif; ?>
						<?php } ?>
						<tr>
							<td colspan="5"></td>
							<td colspan="">
								<div class="col-md-6 text-center"><strong><?=toTime($total_reg_seconds)?></strong></div>
								<div class="col-md-6 text-center"><strong><?=format_price($total_reg_pay)?></strong></div>
							</td>
							<td colspan="">
								<div class="col-md-6 text-center"><strong><?=toTime($total_ot_seconds)?></strong></div>
								<div class="col-md-6 text-center"><strong><?=format_price($total_ot_pay)?></strong></div>
							</td>
							<td colspan="">
								<div class="col-md-6 text-center"><strong></strong></div>
								<div class="col-md-6 text-center"><strong></strong></div>
							</td>
							<td colspan="">
								<div class="col-md-6 text-center"><strong></strong></div>
								<div class="col-md-6 text-center"><strong></strong></div>
							</td>
							<td colspan=""><strong><?=toTime($total_time)?></strong></td>
						</tr>
					</tbody>
		        </table>
			</div>
		</div>
	<?php if($edit): ?>
		</form>
	<?php endif; ?>

	<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
    	(function($){
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

    	})(jQuery);
    </script>

</body>
</html>
