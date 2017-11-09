<?php	
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUser.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUsers.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';

	// If true then the user is an admin
	// If not only display what the user has requested
	$user_admin = false;
	$deny_permission = false;
	$userid = $_REQUEST['user'];

	if(in_array("4", $USER_ROLES)) {
		$user_admin = true;
	} else if($userid != $GLOBALS['U']['id']) {
		$deny_permission = true;
		header('Location: /tasks.php?user=' . $GLOBALS['U']['id']);
		exit();
	}

	function getExpenses($userid, $user_admin = false) {
		$expenses = array();

		if($user_admin) {
			$query = "SELECT * FROM service_expenses ORDER by datetime DESC;";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			while($r = mysqli_fetch_assoc($result)) {
				$expenses[] = $r;
			}
		} else {
			$query = "SELECT * FROM service_expenses WHERE userid = ".res($userid)." ORDER by datetime DESC;";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			while($r = mysqli_fetch_assoc($result)) {
				$expenses[] = $r;
			}
		}

		return $expenses;
	}

	function getTaskNum($service_item_id) {
		$service_number = 0;

		$query = "SELECT so_number FROM service_items WHERE id = ".res($service_item_id).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$service_number = $r['so_number'];
		}

		return $service_number;
	}

	function getStatus($expense_id) {
		$status = '';

		$query = "SELECT * FROM service_reimbursement WHERE expense_id = ".res($expense_id).";";
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

	$expense_data = ($userid ? getExpenses($userid) : getExpenses($GLOBALS['U']['id'], $user_admin));
	
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

					<div class="col-md-9 date_container mobile-hid remove-pad">
						
					</div>
				</div>

				<div class="text-center col-md-4 remove-pad">
					<h2 class="minimal" id="filter-title">Task Expenses</h2>
				</div>

				<div class="col-md-4" style="">
					<div class="col-md-4 col-sm-4 col-xs-3">
						
					</div>

					<div class="col-md-8 col-sm-8 col-xs-9 remove-pad">
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
							<th class="col-md-3">DESCRIPTION</th>
							<th class="col-md-1">TASK#</th>
							<th class="col-md-2">AMOUNT</th>
							<th class="col-md-2">UPLOAD</th>
							<th class="col-md-2">STATUS</th>
						</tr>
					</thead>
					<tbody>
						<?php $counter = 1; foreach($expense_data as $list): ?>
							<tr>
								<td><?=$counter;?></td>
								<td><?=getUser($list['userid']);?></td>
								<td><?=$list['description'];?></td>
								<td><?=getTaskNum($list['service_item_id']);?></td>
								<td><?=format_price($list['amount']);?></td>
								<td class="file_container">
									<span class="file_name" style="<?=$list['file'] ? 'margin-right: 5px;' : '';?>"><a href="<?=str_replace($TEMP_DIR,'uploads/',$list['file']);?>"><?=substr($list['file'], strrpos($list['file'], '/') + 1);?></a></span>
									<input type="file" class="upload" name="files[<?=$list['id'];?>]" accept="image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,*.htm,*.html,*.xml" value="">
									<a href="#" class="upload_link">
										<i class="fa fa-file-pdf-o" aria-hidden="true"></i>
									</a>
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
    			window.location.href = "/expenses.php?user=" + $(this).val();
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
