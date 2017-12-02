<?php	
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUser.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUsers.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';

	// If true then the user is an admin
	// If not only display what the user has requested
	$user_admin = false;
	$deny_permission = false;
	$userid = $_REQUEST['user'];
	$taskid = $_REQUEST['task'];

	if(in_array("4", $USER_ROLES)) {
		$user_admin = true;
	} else if($userid AND $userid != $GLOBALS['U']['id']) {
		$deny_permission = true;
		header('Location: /tasks.php?user=' . $GLOBALS['U']['id']);
		exit();
	} else {
		$userid = $U['id'];
	}

	function getExpenses($userid, $user_admin = false, $filter = '') {
		$expenses = array();

		if($user_admin AND ! $userid) {
			$query = "SELECT * FROM expenses";
			if($filter) {
				$query .= " WHERE item_id = " . $filter;	
			}
			$query .= " ORDER by datetime DESC;";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			while($r = mysqli_fetch_assoc($result)) {
				$expenses[] = $r;
			}
		} else {
			$query = "SELECT * FROM expenses ";
			$subquery = "";
			if ($userid) {
				$subquery .= "userid = ".res($userid)." ";
			}
			if($filter) {
				if ($subquery) { $subquery .= "AND "; }
				$subquery .= "item_id = ".$filter." ";
			}
			$query .= "WHERE ".$subquery;
			$query .= " ORDER by datetime DESC;";

			$result = qdb($query) OR die(qe() . ' ' . $query);

			while($r = mysqli_fetch_assoc($result)) {
				$expenses[] = $r;
			}
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

	function getStatus($expense_id) {
		$status = '';

		$query = "SELECT * FROM reimbursements WHERE expense_id = ".res($expense_id).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		// If something exists on this item then  
		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			if($r['amount']) {
				$status = "<span style='color: #3c763d;'><b>Approved</b>: ".$r['datetime']."</span>";
			} else if(! $r['amount']) {
				$status = "<span style='color: #a94442;'><b>Denied</b>: ".$r['datetime']."</span>";
			} else {
				$status = "<span style='color: #8a6d3b;'>Pending</span>";	
			}
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

	$expense_data = ($userid ? getExpenses($userid, $user_admin, $taskid) : getExpenses($GLOBALS['U']['id'], $user_admin, $taskid));
	
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
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<form id="expenses_form" action="/expense_edit.php" method="POST" enctype="multipart/form-data">
		<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
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

						<button class="btn btn-success btn-sm expenses_edit pull-right" type="submit" name="type" value="approve" style="">
							<i class="fa fa-check-circle" aria-hidden="true"></i>					
						</button>

						<button class="btn btn-danger btn-sm expenses_edit pull-right" type="submit" name="type" value="deny" style="margin-right: 10px;">
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
							<th class="col-md-1">#</th>
							<th class="col-md-1">USER</th>
							<th class="col-md-1">Expense Date</th>
							<th class="col-md-3">DESCRIPTION</th>
							<th class="col-md-1">TASK#</th>
							<th class="col-md-2">AMOUNT</th>
							<th class="col-md-2">RECEIPT</th>
							<th class="col-md-1">STATUS</th>
						</tr>
					</thead>
					<tbody>
						<?php $counter = 1; foreach($expense_data as $list): ?>
							<tr class="<?=(getStatus($list['id']) ? 'complete' : 'active')?> expense_item" style="<?=(getStatus($list['id']) ? 'display:none;' : '')?>">
								<td><?=$counter;?></td>
								<td><?=getUser($list['userid']);?></td>
								<td><?=format_date($list['expense_date']);?></td>
								<td><?=$list['description'];?></td>
								<td><?=($list['item_id'] ? getTaskNum($list['item_id'], $list['item_id_label']) : 'General Use');?></td>
								<td><?=format_price($list['units']*$list['amount']);?></td>
								<td class="file_container">
									<?php
										if ($list['file']) {
											// replace temp dir location (if exists) to uploads reader script
											$list['file'] = str_replace($TEMP_DIR,'uploads/',$list['file']);
											echo '<a href="'.$list['file'].'" target="_new"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>';
										}
									?>
								</td>
								<td>
									<?php if(getStatus($list['id'])) { ?>
										<?=getStatus($list['id']);?>
									<?php } else { ?>
										<span style='color: #8a6d3b;'>Pending</span>
										<?php if($user_admin) { ?>
											<input type="checkbox"  class="pull-right" name="expenses[<?=$list['id'];?>]" value="<?=$list['amount'];?>">
										<?php } ?>
									<?php } ?>
								</td>
							</tr>
						<?php $counter++; endforeach; ?>
						<?php if($userid) { ?>
							<form id="expenses_form" action="/expense_edit.php" method="POST" enctype="multipart/form-data">
								<tr>
									<td></td>
									<td><?=$GLOBALS['U']['name']?></td>
									<td>
										<div class="form-group" style="margin-bottom: 0;">
							                <div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="11/09/2017" data-hposition="right">
			   			    			         <input type="text" name="expenseDate" class="form-control input-sm" value="">
			   	        		       			 <span class="input-group-addon">
						       		                 <span class="fa fa-calendar"></span>
			   	    					         </span>
											</div>
										</div>
									</td>
									<td><input type="text" class="form-control input-sm" name="description"></td>
									<td>General Use</td>
									<td>
										<div class="input-group">
						                    <span class="input-group-addon">$</span>
						                    <input class="form-control input-sm" type="text" name="amount" placeholder="0.00" id="new_item_price" value="">
						                </div>
									</td>
									<td class="file_container">
										<span class="file_name" style="margin-right: 5px;"><a href="#"></a></span>
										<input type="file" class="upload" name="files" accept="image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,*.htm,*.html,*.xml" value="">
										<a href="#" class="upload_link btn btn-default btn-sm">
											<i class="fa fa-folder-open-o" aria-hidden="true"></i> Browse...
										</a>
									</td>
									<td>
										<button class="btn btn-success btn-sm pull-right" name="type" value="add_expense"><i class="fa fa-plus" aria-hidden="true"></i></button>
									</td>
								</tr>
							</form>
						<?php } ?>
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
