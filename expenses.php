<?php	
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUser.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getUsers.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCategory.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCompany.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getItemOrder.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getFinancialAccounts.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/order_type.php';

	$userid = 0;
	$taskid = 0;
	$companyid = 0;
	$STATUS = 'active';
	$expenseid = 0;

	$admin = false;
	if ($U['admin'] OR $U['manager'] OR $U['accounting']) { $admin = true; }

	if ($admin) {
		if (isset($_REQUEST['userid'])) { $userid = $_REQUEST['userid']; }
		if (isset($_REQUEST['taskid'])) { $taskid = $_REQUEST['taskid']; }
	} else {
		if ($userid AND $userid != $GLOBALS['U']['id']) {
			header('Location: /expenses.php?userid=' . $GLOBALS['U']['id']);
			exit;
		}

		$userid = $U['id'];
	}

	if (isset($_REQUEST['filter_companyid'])) { $companyid = $_REQUEST['filter_companyid']; }
	if (isset($_REQUEST['id'])) { $expenseid = $_REQUEST['id']; }

	if (! empty($_REQUEST['status']) AND ($_REQUEST['status']=='active' OR $_REQUEST['status']=='complete' OR $_REQUEST['status']=='all')) {
		$STATUS = $_REQUEST['status'];
	}

	function getExpenses($userid, $taskid = '', $companyid=0) {
		global $STATUS,$expenseid;

		$expenses = array();

		if (! $expenseid AND $userid==$GLOBALS['U']['id']) {
			$expenses[0] = array(
				'expense_date' => $GLOBALS['now'],
				'item_id' => 0,
				'item_id_label' => 'General Use',
				'userid' => $GLOBALS['U']['id'],
				'units' => 1,
			);
		}

		$query = "SELECT * FROM expenses WHERE 1 = 1 ";
		if ($taskid) {
			$query .= "AND item_id = " . $taskid . " ";
		}
		if ($companyid) {
			$query .= "AND companyid = '".res($companyid)."' ";
		}
		if ($userid) {
			$query .= "AND userid = ".res($userid)." ";
		}
		if ($STATUS<>'all') {
			$query .= "AND status = '".ucfirst($STATUS)."' ";
		}
		$query .= "ORDER by datetime DESC LIMIT 0,200;";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$expenses[$r['id']] = $r;
		}

		return $expenses;
	}

	function getReimbursementStatus($expense_id,$reimbursement=0) {
		$status = false;

		$query = "SELECT * FROM reimbursements WHERE expense_id = ".res($expense_id).";";
		$result = qedb($query);

		// If nothing exists on this item then it's not paid out
		if(mysqli_num_rows($result)==0) { return ($status); }

		$r = mysqli_fetch_assoc($result);
		if($r['amount']) {
			$status = '<span class="text-success" title="Approved" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-check-circle"></i> '.format_date($r['datetime'], 'D n/j/y').'</span>';
		} else if(! $r['amount']) {
			$status = '<span class="text-danger" title="Denied" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-minus-circle"></i> '.format_date($r['datetime'], 'D n/j/y').'</span>';
		}

		return $status;
	}

	function getUniqueTask() {
		$unique_id = array();
		$query = "SELECT DISTINCT item_id, item_id_label FROM expenses WHERE item_id IS NOT NULL;";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$unique_id[] = $r;
		}

		return $unique_id;
	}

	// get accounts listed in expenses dropdown
	$expenses = getExpenses($userid, $taskid, $companyid);
	$financeAccounts = getFinancialAccounts("Credit");

	$finance_accounts = '';
	if (! $expenseid) { $finance_accounts = '<option value=""> - Account - </option>'; }
	foreach($financeAccounts as $account) {
		$finance_accounts .= '<option value="'. $account['accountid'] .'">'. $account['bank'] .' '. $account['nickname'] .' '. substr($account['account_number'], -4) .'</option>';
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

<body class="sub-nav">
	
<?php include 'inc/navbar.php'; ?>

<div class="table-header hidden-xs" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">

	<form id="filter_form" action="/expenses.php" method="GET" enctype="multipart/form-data">
			<div class="row" style="padding: 8px;" id="filterBar">
				<div class="col-md-1">
						<div class="btn-group medium">
					        <button data-toggle="tooltip" name="status" type="submit" value="active" data-placement="bottom" title="Active" class="btn btn-default btn-sm left<?=($STATUS=='active' ? ' active btn-warning' : '');?>">
					        	<i class="fa fa-sort-numeric-desc"></i>	
					        </button>

					        <button data-toggle="tooltip" name="status" type="submit" value="complete" data-placement="bottom" title="Complete" class="btn btn-default btn-sm middle<?=($STATUS=='complete' ? ' active btn-success' : '');?>">
					        	<i class="fa fa-history"></i>	
					        </button>

							<button data-toggle="tooltip" name="status" type="submit" value="all" data-placement="bottom" title="All" class="btn btn-default btn-sm right<?=($STATUS=='all' ? ' active btn-info' : '');?>">
					        	All
					        </button>
					    </div>

				</div>
				<div class="col-md-4">
					<div class="form-group">

						<select name="userid" size="1" class="form-control input-sm select2" style="max-width: 160px;">
							<option value =''> - Select User - </option>
							<?php
								$users = getUsers(array(1,2,3,4,5,7,8));
								foreach ($users as $uid => $uname) {
									if (! $admin AND $userid<>$uid) { continue; }

									$s = '';
									if ($userid == $uid) { $s = ' selected'; }
									echo '<option value="'.$uid.'"'.$s.'>'.$uname.'</option>'.chr(10);
								}
							?>
						</select>

						<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>

					</div>
				</div>

				<div class="text-center col-md-2 remove-pad">
					<h2 class="minimal" id="filter-title">Expenses</h2>
				</div>

				<div class="col-md-2">

					<div class="form-group">
						<select name="taskid" size="1" class="form-control input-sm select2" style="max-width: 200px;">
							<option value =''> - Select Task - </option>
							<?php
								$users = getUsers(array(1,2,3,4,5,7,8));
								foreach (getUniqueTask() as $task) {
									$s = '';
									if ($taskid == $task['item_id']) { $s = ' selected'; }
									echo '<option value="'.$task['item_id'].'"'.$s.'>'.getItemOrder($task['item_id'], $task['item_id_label'], true).'</option>'.chr(10);
								}
							?>
						</select>
						<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
					</div>
				</div>

				<div class="col-md-2">
					<div class="input-group company-select2">
						<select name="filter_companyid" id="companyid" class="company-selector" data-scope="Expenses">
							<?= ($companyid ? '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'.chr(10) : ''); ?>
						</select>
						<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
					</div>
				</div>

				<div class="col-md-1 text-right" style="padding-right:30px">
				</div>
			</div>
	</form>
</div>

		<div id="pad-wrapper">

			<form id="expenses_form" action="/save-expenses.php" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="status" value="<?= $STATUS; ?>">
			<input type="hidden" name="userid" value="<?= $userid; ?>">
			<input type="hidden" name="taskid" value="<?= $taskid; ?>">
			<input type="hidden" name="filter_companyid" value="<?= $companyid; ?>">
			<input type="hidden" name="expenseid" value="<?= $expenseid; ?>">

			<div class="row">
				<table class="table heighthover heightstriped table-condensed">
					<thead>
						<tr>
							<th class="col-sm-1">Date</th>
							<th class="col-sm-1 colm-sm-0-5">User</th>
							<th class="col-sm-2 <?=(($userid==$U['id'] OR $expenseid) ? 'colm-sm-1-5' : '');?>">Task</th>
							<th class="col-sm-2 <?=(($userid==$U['id'] OR $expenseid) ? 'colm-sm-1-5' : '');?>">Category</th>
							<th class="col-sm-1">Account</th>
							<th class="col-sm-1 colm-sm-1-5">Vendor</th>
							<th class="col-sm-1">Amount</th>
							<th class="col-sm-1 colm-sm-1-5">Notes</th>
							<th class="col-sm-1">Reimbursement?</th>
							<th class="col-sm-1 <?=(($userid==$U['id'] OR $expenseid) ? '' : 'colm-sm-1-5');?>">
								<?php if ($GLOBALS['admin'] AND ! $expenseid) { ?>
									<button class="btn btn-danger btn-xs expense-save" type="submit" name="type" value="deny" style="margin-right: 10px;" title="Deny" data-toggle="tooltip" data-placement="bottom">
										<i class="fa fa-minus-circle" aria-hidden="true"></i>
									</button>

									<button class="btn btn-success btn-xs expense-save" type="submit" name="type" value="approve" style="" title="Approve" data-toggle="tooltip" data-placement="bottom">
										<i class="fa fa-check-circle" aria-hidden="true"></i>					
									</button>
								<?php } ?>
							</th>
						</tr>
					</thead>
					<tbody>
<?php
						foreach($expenses as $id => $list):
							$expense_task = 'General Use';

							// abbreviate user with First name, Last initial
							$uname = getUser($list['userid']);
							$names = explode(' ',$uname);
							$fullname = $names[0].' '.substr($names[1],0,1);

							// edit this row
							if (($id==0 AND ! $expenseid) OR $id==$expenseid) {
								$expense_date = '
									<div class="form-group" style="margin-bottom: 0;">
						                <div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="" data-hposition="left">
		   			    			         <input type="text" name="expense_date" class="form-control input-sm" value="'.format_date($list['expense_date'],"m/d/Y").'">
		   	        		       			 <span class="input-group-addon">
					       		                 <span class="fa fa-calendar"></span>
		   	    					         </span>
										</div>
									</div>
								';

								$expense_user = $fullname.'<input type="hidden" name="expense_userid" value="'.$U['id'].'">';

								if ($list['item_id'] AND $list['item_id_label']) {
									$default_option = '<option value="'.$list['item_id'].'" selected>'.getItemOrder($list['item_id'], $list['item_id_label'], true).'</option>';
								} else {
									$default_option = '<option value="" selected>'.$expense_task.'</option>';
								}
								$expense_task = '
									<select name="item_id" class="form-control input-xs select2">
										'.$default_option.'
									</select>
									<input type="hidden" name="item_id_label" value="'.$list['item_id_label'].'">
								';

								$expense_category = '
									<select name="categoryid" class="form-control input-xs category-selector required">
										<option value="'.$list['categoryid'].'" selected>'.getCategory($list['categoryid']).'</option>
									</select>
								';

								$expense_account = '
									<select name="financeid" size="1" class="form-control input-sm select2" data-placeholder="- Account -">
										'.$finance_accounts.'
									</select>
								';

								$expense_company = '
									<select name="companyid" class="form-control input-sm company-selector required" data-scope="Expenses">
										<option value="'.$list['companyid'].'" selected>'.getCompany($list['companyid']).'</option>
									</select>
								';

								$expense_amount = '
									<div class="input-group">
					                    <span class="input-group-addon">$</span>
					                    <input class="form-control input-sm" type="text" name="amount" placeholder="0.00" id="new_item_price" '.
											'value="'.number_format(($list['amount']*$list['units']),2,'.','').'">
										<input type="hidden" name="units" value="'.$list['units'].'">
					                </div>
								';

								$expense_descr = '<input type="text" class="form-control input-sm" name="description" value="'.$list['description'].'">';

								$expense_reimb = '<input type="checkbox" class="" name="reimbursement" value="1"'.($list['reimbursement'] ? ' checked' : '').'>';

								$expense_file = '';
								if ($expenseid AND $expenseid==$id) {
									$expense_file = substr($list['file'],0,15).'...';
								} else {
									$expense_file = '
									<span class="file_name" style="margin-right: 5px;"><a href="#"></a></span>
									<input type="file" multiple="multiple" class="upload" name="files[]" value=""> 
									<!-- accept="image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,*.htm,*.html,*.xml,*.zip" -->
									<a href="#" class="upload_link btn btn-default btn-xs">
										<i class="fa fa-folder-open-o" aria-hidden="true"></i> Browse...
									</a>
									';
								}
								$expense_file .= '
									<button class="btn btn-success btn-xs" style="margin-left:5px" name="type" type="submit" value="'.(($expenseid AND $expenseid==$id) ? 'edit' : 'add').'"><i class="fa fa-save" aria-hidden="true"></i></button>
								';
							} else {
								$expense_date = format_date($list['expense_date']);

								if ($list['item_id']) {
									$expense_task = getItemOrder($list['item_id'], $list['item_id_label'], true).' '.
										'<a href="service.php?taskid='.$list['item_id'].'&task_label='.$list['item_id_label'].'" target="_new.'.$list['item_id'].'">'.
										'<i class="fa fa-arrow-right"></i></a>';
								}

								$expense_user = $fullname;

								$expense_category = getCategory($list['categoryid']);

								$expense_account = getFinanceName($list['financeid']);

								$expense_company = getCompany($list['companyid']);

								$expense_amount = format_price($list['units']*$list['amount'],true,' ');

								$expense_descr = $list['description'];

								$expense_reimb = '';
								if ($list['reimbursement']) {
									$st = getReimbursementStatus($list['id']);
									$expense_reimb = $st;
									if (! $st) {
										if ($admin) {
											$expense_reimb = '<input type="checkbox" name="reimbursement['.$list['id'].']" value="'.$list['units']*$list['amount'].'"> ';
										}
										$expense_reimb .= '<span class="text-gray">Pending</span>';
									}
								}

								$expense_file = '';
								if ($list['file']) {
									// replace temp dir location (if exists) to uploads reader script
									$list['file'] = str_replace($TEMP_DIR,'uploads/',$list['file']);
									$expense_file = '<a href="'.$list['file'].'" target="_new"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>';
								}

								if ($GLOBALS['admin']) {
									$recorded = false;
									$query = "SELECT * FROM reimbursements WHERE expense_id = '".res($list['id'])."'; ";
									$result = qedb($query);
									if (qnum($result)>0) {
										$recorded = true;
									} else {
										$T = order_type($list['item_id_label']);

										if ($T['status_code']) {
											$query = "SELECT ".$T['status_code']." status_code FROM ".$T['items']." WHERE id = '".$list['item_id']."'; ";
											$result = qedb($query);
											if (qnum($result)>0) {
												$r = qrow($result);
												if ($r['status_code']) { $recorded = true; }
											}
										}
									}

									if (! $recorded) {
										$expense_file = '
									<a href="javascript:void(0);" data-id="'.$list['id'].'" title="Edit" data-toggle="tooltip" data-placement="left" class="expense-edit"><i class="fa fa-pencil"></i></a>
									<a href="javascript:void(0);" data-id="'.$list['id'].'" title="Delete" data-toggle="tooltip" data-placement="bottom" class="expense-del text-gray"><i class="fa fa-trash"></i></a>
										';
									}
								} else {
									$expense_file = ' <a href="javascript:void(0);" title="Edit" data-toggle="tooltip" data-placement="left" class="expense-mgr"><i class="fa fa-pencil"></i></a>';
								}
							}
?>
							<tr class="active">
								<td><?=$expense_date;?></td>
								<td><?=$expense_user;?></td>
								<td><?=$expense_task;?></td>
								<td><?=$expense_category;?></td>
								<td><?=$expense_account;?></td>
								<td><?=$expense_company;?></td>
								<td class="text-right"><?=$expense_amount;?></td>
								<td><?=$expense_descr;?></td>
								<td><?=$expense_reimb;?></td>
								<td class="file_container text-right" style="padding-right:30px"><?=$expense_file;?></td>
							</tr>

						<?php endforeach; ?>

					</tbody>
		        </table>
			</div>

			</form>
		</div>

	<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
    	(function($){
    		$(document).on("click", ".expense-mgr", function(e) {
    			e.preventDefault();

				modalAlertShow('Manager Required','Please talk to your manager about editing or deleting an expense.',false);
    		});

<?php if ($admin) { ?>
			// The modal is used below for deleting objects
			$("#alert-continue").removeClass('btn-primary').addClass('btn-danger');
			$("#alert-continue").html('<i class="fa fa-trash"></i> Permanently Delete');
    		$(document).on("click", ".expense-del", function(e) {
				var expenseid = $(this).data('id');
    			e.preventDefault();

				modalAlertShow('Please Confirm','Deleting an expense is irreversible and could have accounting implications. Are you sure?',true,'goExpense',expenseid,'delete');
    		});
    		$(document).on("click", ".expense-edit", function(e) {
				var expenseid = $(this).data('id');

				goExpense(expenseid,'edit');
    		});
<?php } ?>
    	})(jQuery);

<?php if ($admin) { ?>
		function goExpense(id,type) {
			// edit default url
			var url = 'expenses.php?id='+id;
			if (! type) {
				var type = 'delete';
				url = 'save-expenses.php?id='+id;
			}
			document.location.href = url+'&type='+type+'&userid=<?=$userid;?>&taskid=<?=$taskid;?>&filter_companyid=<?=$companyid;?>&status=<?=$STATUS;?>';
		}
<?php } ?>
    </script>

</body>
</html>
