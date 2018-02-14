<?php	
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUser.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUsers.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCategory.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCompany.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getFinancialAccounts.php';

	// If true then the user is an admin
	// If not only display what the user has requested
	$user_admin = false;
	$deny_permission = false;
	$userid = $_REQUEST['user'];
	$taskid = $_REQUEST['task'];

	$approval = array_intersect($USER_ROLES,array(1,4,7));
	if($approval) {
		$user_admin = true;
	} else if($userid AND $userid != $GLOBALS['U']['id']) {
		$deny_permission = true;
		header('Location: /tasks.php?user=' . $GLOBALS['U']['id']);
		exit();
	} else {
		$userid = $U['id'];
	}

	//$userid = $U['id'];

	function getExpenses($userid, $user_admin = false, $filter = '') {
		$expenses = array();

		$query = "SELECT * FROM expenses WHERE 1 = 1 ";
		if($filter) {
			$query .= "AND item_id = " . $filter . " ";
		}
		if(! $user_admin) {
			if (! $userid) { $userid = $GLOBALS['U']['id']; }
			$query .= "AND userid = ".res($userid)." ";
		}
		$query .= "ORDER by datetime DESC LIMIT 0,200;";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$expenses[] = $r;
		}

		return $expenses;
	}

	function getTaskNum($item_id, $item_id_label) {
		$service_number = 0;
		$table = '';

		if($item_id_label == 'repair_item_id') {
			$table = 'repair_items';
			$field = 'ro_number';
		} else {
			$table = 'service_items';
			$field = 'so_number';
		}

		$query = "SELECT $field as so_number FROM $table WHERE id = ".res($item_id).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$service_number = $r['so_number'];
		}

		return $service_number;
	}

	function getReimbursementStatus($expense_id,$reimbursement=0) {
		$status = false;

		$query = "SELECT * FROM reimbursements WHERE expense_id = ".res($expense_id).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		// If nothing exists on this item then it's not paid out
		if(mysqli_num_rows($result)==0) { return ($status); }

		$r = mysqli_fetch_assoc($result);
		if($r['amount']) {
			$status = "<span style='color: #3c763d;'><b>Approved</b><br>".format_date($r['datetime'], 'D n/j/y')."</span>";
		} else if(! $r['amount']) {
			$status = "<span style='color: #a94442;'><b>Denied</b><br>".format_date($r['datetime'], 'D n/j/y')."</span>";
		}

		return $status;
	}

	function getUniqueTask() {
		$unique_id = array();
		$query = "SELECT DISTINCT item_id, item_id_label FROM expenses WHERE item_id IS NOT NULL;";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		while($r = mysqli_fetch_assoc($result)) {
			$unique_id[] = $r;
		}

		return $unique_id;
	}

	$expense_data = getExpenses($userid, $user_admin, $taskid);
	$financeAccounts = getFinancialAccounts("Credit");

	$financeHTML = '';

	foreach($financeAccounts as $account) {
		$financeHTML .= '<option value="'. $account['accountid'] .'">'. $account['bank'] .' '. $account['nickname'] .' '. substr($account['account_number'], -4) .'</option>';
	}
	
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------>
<!DOCTYPE html>
<html>
<head>
	<title>Expenses</title>
	<?php
		//Standard headers included in the function
		include_once $_SERVER['ROOT_DIR'].'/inc/scripts.php';
	?>
	<style>
		.upload{
		    display: none !important;
		}

		@media screen and (max-width:991px) {
			.row {
				margin: 0;
			}
		}
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<form id="expenses_form" action="/expense_edit.php" method="POST" enctype="multipart/form-data">
		<div class="table-header hidden-xs hidden-sm" id="filter_bar" style="width: 100%; min-height: 48px;">
			<div class="row" style="padding: 8px;" id="filterBar">
				<div class="col-md-4 mobile-hide" style="max-height: 30px;">
					<div class="col-md-3">
						<div class="btn-group medium">
					        <button data-toggle="tooltip" name="filter" type="submit" data-value="active" data-placement="bottom" title="" data-filter="active_radio" data-original-title="Active" class="btn btn-default btn-sm left filter_status active btn-warning">
					        	<i class="fa fa-sort-numeric-desc"></i>	
					        </button>

					        <button data-toggle="tooltip" name="filter" type="submit" data-value="completed" data-placement="bottom" title="" data-filter="complete_radio" data-original-title="Completed" class="btn btn-default btn-sm middle filter_status ">
					        	<i class="fa fa-history"></i>	
					        </button>

							<button data-toggle="tooltip" name="filter" type="submit" data-value="all" data-placement="bottom" title="" data-filter="all_radio" data-original-title="All" class="btn btn-default btn-sm right filter_status ">
					        	All
					        </button>
					    </div>
					</div>

					<div class="col-md-9 mobile-hid remove-pad">
						<select id="task_select" name="task_id" size="1" class="form-control input-sm select2 pull-right" style="max-width: 200px;">
							<option value =''> - Select Task - </option>
							<?php
								$users = getUsers(array(1,2,3,4,5,7,8));
								foreach (getUniqueTask() as $task) {
									$s = '';
									if ($taskid == $task['item_id']) { $s = ' selected'; }
									//if($user_admin OR ($userid == $uid)) {
										echo '<option value="'.$task['item_id'].'"'.$s.'>'.getTaskNum($task['item_id'], $task['item_id_label']).'</option>'.chr(10);
									//}
								}
							?>
						</select>
					</div>
				</div>

				<div class="text-center col-md-4 remove-pad">
					<h2 class="minimal" id="filter-title">Expenses</h2>
				</div>

				<div class="col-md-4" style="">
					<div class="col-md-4 col-sm-4 col-xs-3">
						
					</div>

					<div class="col-md-8 col-sm-8 col-xs-9 remove-pad">
						<select id="user_select" name="user_id" size="1" class="form-control input-sm select2 pull-right" style="max-width: 200px;">
							<option value =''> - Select User - </option>
							<?php
								$users = getUsers(array(1,2,3,4,5,7,8));
								foreach ($users as $uid => $uname) {
									$s = '';
									if ($userid == $uid) { $s = ' selected'; }
									if ($user_admin OR ($userid == $uid)) {
										echo '<option value="'.$uid.'"'.$s.'>'.$uname.'</option>'.chr(10);
									}
								}
							?>
						</select>

						<button class="btn btn-success btn-sm expenses_edit pull-right" type="submit" name="type" value="approve" style="" title="Approve" data-toggle="tooltip" data-placement="bottom">
							<i class="fa fa-check-circle" aria-hidden="true"></i>					
						</button>

						<button class="btn btn-danger btn-sm expenses_edit pull-right" type="submit" name="type" value="deny" style="margin-right: 10px;" title="Deny" data-toggle="tooltip" data-placement="bottom">
							<i class="fa fa-minus-circle" aria-hidden="true"></i>
						</button>
					</div>
				</div>
			</div>
		</div>
		<div id="pad-wrapper">

			<div class="row">
				<table class="table heighthover heightstriped table-condensed">
					<thead>
						<tr>
							<th class="col-sm-1">Expense Date</th>
							<th class="col-sm-1" style="min-width: 100px;">User</th>
							<th class="col-sm-1" style="min-width: 100px;">Task</th>
							<th class="col-sm-2">Category</th>
							<th class="col-sm-1">Account</th>
							<th class="col-sm-1">Vendor</th>
							<th class="col-sm-1" style="min-width: 100px;">Amount</th>
							<th class="col-sm-2">Notes</th>
							<th class="col-sm-1">Reimbursement?</th>
							<th class="col-sm-1" style="min-width: 140px;"> </th>
						</tr>
					</thead>
					<tbody>
							<form id="expenses_form" action="/expense_edit.php" method="POST" enctype="multipart/form-data">
								<tr>
									<td>
										<div class="form-group" style="margin-bottom: 0;">
							                <div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="" data-hposition="left">
			   			    			         <input type="text" name="expenseDate" class="form-control input-sm" value="<?=date("m/d/Y");?>">
			   	        		       			 <span class="input-group-addon">
						       		                 <span class="fa fa-calendar"></span>
			   	    					         </span>
											</div>
										</div>
									</td>
									<td>
										<?=getUser($U['id']);?>
										<input type="hidden" name="userid" value="<?=$U['id'];?>">
									</td>
									<td>General Use</td>
									<td>
										<select name="categoryid" class="form-control input-xs category-selector required">
										</select>
									</td>
									<td>
										<select name="financeid" size="1" class="form-control input-sm select2" data-placeholder="- Account -">
											<option value =''> - Account - </option>
											<?=$financeHTML;?>
										</select>
									</td>
									<td>
										<select name="companyid" class="form-control input-sm company-selector required" data-scope="Expenses">
										</select>
									</td>
									<td>
										<div class="input-group">
						                    <span class="input-group-addon">$</span>
						                    <input class="form-control input-sm" type="text" name="amount" placeholder="0.00" id="new_item_price" value="">
						                </div>
									</td>
									<td><input type="text" class="form-control input-sm" name="description"></td>
									<td class="text-center"><input type="checkbox" class="" name="reimbursement" value="1"></td>
									<td class="file_container">
										<span class="file_name" style="margin-right: 5px;"><a href="#"></a></span>
										<input type="file" class="upload" name="files" accept="image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,*.htm,*.html,*.xml" value="">
										<a href="#" class="upload_link btn btn-default btn-sm">
											<i class="fa fa-folder-open-o" aria-hidden="true"></i> Browse...
										</a>
										<button class="btn btn-success btn-sm pull-right" name="type" value="add_expense"><i class="fa fa-save" aria-hidden="true"></i></button>
									</td>
								</tr>
							</form>
<?php
						foreach($expense_data as $list):
							$status = false;
							$show_status = '';
							if ($list['reimbursement']) {
								$status = getReimbursementStatus($list['id']);
								$show_status = $status;
								if (! $status) {
									$show_status = "<span style='color: #8a6d3b;'>Pending</span>";	
								}
							}
?>
							<!-- <tr class="<?=($status ? 'complete' : 'active')?> expense_item" style="<?=($status ? 'display:none;' : '')?>"> -->
							<tr class="active expense_item">
								<td><?=format_date($list['expense_date']);?></td>
								<td><?=getUser($list['userid']);?></td>
								<td><?=($list['item_id'] ? getTaskNum($list['item_id'], $list['item_id_label']) : 'General Use');?></td>
								<td><?=getCategory($list['categoryid']);?></td>
								<td><?=getFinanceName($list['financeid']);?></td>
								<td><?=getCompany($list['companyid']);?></td>
								<td class="text-right"><?=format_price($list['units']*$list['amount'],true,' ');?></td>
								<td><?=$list['description'];?></td>
								<td class="text-center">
									<?php
										if($list['reimbursement']) {
											echo $show_status;

											if ($user_admin AND ! $status) {
												echo ' &nbsp; <input type="checkbox" name="expenses['.$list['id'].']" value="'.$list['amount'].'">';
											}
										}
									?>
								</td>
								<td class="file_container">
									<?php
										if ($list['file']) {
											// replace temp dir location (if exists) to uploads reader script
											$list['file'] = str_replace($TEMP_DIR,'uploads/',$list['file']);
											echo '<a href="'.$list['file'].'" target="_new"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>';
										}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
		        </table>
			</div>
		</div>
	</form>

	<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
    	(function($){
    		$(document).on('click', ".upload_link", function(e){
		        e.preventDefault();

		        $(this).closest(".file_container").find(".upload").trigger("click");
		        // $("#upload:hidden").trigger('click');
		    });

    		$(document).on("change", "#user_select", function() {
    			// alert($(this).val());
    			var taskid = 0;

    			if($("#task_select").val()) {
    				taskid = $("#task_select").val();

    				if($(this).val() != '') {
    					window.location.href = "/expenses.php?user=" + $(this).val() + '&task=' + taskid;
    				} else {
    					window.location.href = "/expenses.php?task=" + taskid;
    				}
  
    			} else {
    				//alert($(this).val());
    				if($(this).val() != '') {
    					window.location.href = "/expenses.php?user=" + $(this).val();
    				} else {
    					window.location.href = "/expenses.php";
    				}
    			}
    		});

    		$(document).on("change", "#task_select", function() {
    			var userid = 0;

    			if($("#user_select").val()) {
    				userid = $("#user_select").val();

    				if($(this).val() != '') {
    					window.location.href = "/expenses.php?task=" + $(this).val() + '&user=' + userid;
    				} else {
    					window.location.href = "/expenses.php?user=" + userid;
    				}

    				//window.location.href = "/expenses.php?task=" + $(this).val() + '&user=' + userid;
    			} else {
    				if($(this).val() != '') {
    					window.location.href = "/expenses.php?task=" + $(this).val();
    				} else {
    					window.location.href = "/expenses.php";
    				}
    				// window.location.href = "/expenses.php?task=" + $(this).val();
    			}
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

    		$(document).on("click", ".filter_status", function(e){
    			e.preventDefault();

				var type = $(this).data('value');
				$('.filter_item').hide();
				$('.filter_status').removeClass('active');
				$('.filter_status').removeClass('btn-warning');
				$('.filter_status').removeClass('btn-success');
				$('.filter_status').removeClass('btn-info');
				$('.filter_status').addClass('btn-default');

				var btn,type2;

				if (type=='completed') {
					btn = 'success';
					type2 = type;
					$('.active').hide();
					$('.complete').show();
				} else if (type=='active') {
					btn = 'warning';
					type2 = type;
					$('.active').show();
					$('.complete').hide();
				} else {
					type = 'all';
					type2 = 'filter';
					btn = 'info';
					$('.active').show();
					$('.complete').show();
				}

				//alert(btn);

				$('.filter_status[data-value="'+type+'"]').addClass('btn-'+btn);
				$('.filter_status[data-value="'+type+'"]').addClass('active');
			});

    	})(jQuery);
    </script>

</body>
</html>
