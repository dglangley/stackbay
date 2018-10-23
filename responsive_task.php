<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"] . '/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/dictionary.php';

	// Getter
	include_once $_SERVER["ROOT_DIR"].'/inc/getActivities.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getClass.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getExpenses.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRepairCode.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFinancialAccounts.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSiteName.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterials.php';

	// Timesheet tool to calculate the users time on this specific job
	include_once $_SERVER['ROOT_DIR'] . '/inc/getTimesheet.php';
	include_once $_SERVER['ROOT_DIR'] . '/inc/payroll.php';

	// Builder for Responsive
	include_once $_SERVER["ROOT_DIR"].'/responsive/responsive_builder.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getItemOrder.php';
	
	// If passed in order number then it should contain the line number aka 0000-1
	$order_number = '';
	$quote_order = '';
	$line_number = 0;

	$EDIT = false;
	$QUOTE_TYPE = false;
	
	$ACTIVE = ($_REQUEST['tab']?:'');

	// Default for now as we have a Repair.php to handle all repairs
	$type = "Service";
	if(isset($_REQUEST['order_type'])) { $type = $_REQUEST['order_type']; }

	$T = order_type($type);

	$taskid = 0;
	if(isset($_REQUEST['taskid'])) { 
		$taskid = $_REQUEST['taskid']; 
		$order_number = getItemOrder($taskid, $T['item_label']);
	}

	if(isset($_REQUEST['order_number'])) { 
		$order_number = $_REQUEST['order_number'];  
	}

	preg_match_all("/\d+/", $order_number, $order_line);

	$order_number = reset($order_line)[0];
	$line_number = reset($order_line)[1];

	// If there is no line number then assume it will be the first line
	if(! $line_number) {$line_number = 1; }

	$ORDER = getOrder($order_number, $type);

	// Add this for the order sidebar if statement
	$ORDER['order_number'] = $order_number;
	$ORDER['order_type'] = $type;

	$ORDER_ITEMS = $ORDER['items'];

	$ORDER_DETAILS = array();
	$QUOTE_DETAILS = array();

	// extract the exact line number information from the ORDER variable
	if($taskid) {
		$ORDER_DETAILS = $ORDER_ITEMS[$taskid];
	} else if($line_number) {
		foreach($ORDER_ITEMS as $rowID => $item) {
			if($item['line_number'] == $line_number) {
				$ORDER_DETAILS = $item;
				$taskid = $rowID;
				break;
			}
		}
	}

	$ORDER_DETAILS['companyid'] = $ORDER['companyid'];

	$item_id = $taskid;

	// This order has a quote
	if($ORDER_DETAILS['quote_item_id']) {
		preg_match_all("/\d+/", getItemOrder($ORDER_DETAILS['quote_item_id'], 'service_quote_items'), $quote_order_info);

		$quote_order = reset($quote_order_info)[0];
		$quote_linenumber = reset($quote_order_info)[1];

		$QUOTE = getOrder($quote_order, 'service_quote');

		$QUOTE_DETAILS = $QUOTE['items'][$ORDER_DETAILS['quote_item_id']];
	}

	function partDescription($partid, $desc = true, $part = true){
		$r = reset(hecidb($partid, 'id'));
		$parts = explode(' ',$r['part']);

		$display = "";

		if($part)
	   		$display .= "<span class = 'descr-label'>".$parts[0]." &nbsp; ".$r['heci']."</span>";
	    if($desc)
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf']).' &nbsp; '.dictionary($r['system']).
				'</span> <span class="description-label">'.dictionary($r['description']).'</span></div>';

	    return $display;
	}

	function clockedButton($taskid) {
		global $U, $T;

		// Will turn in an array using the is_clockedin function
		$clock = false;
		if ($U['hourly_rate']) {
			$clock = is_clockedin($U['id'], $taskid, $T['item_label']);
			if ($clock===false) {
				$clock = is_clockedin($U['id']);
				if (! $manager_access) { $view_mode = true; }
			}
		}

		$clockers = '';

		if ($U['hourly_rate']) {
			if ($taskid AND $clock['taskid']==$taskid) {
				$rp_cls = 'default btn-clock';
				$rp_title = 'Switch to Regular Pay';
				$tt_cls = 'default btn-clock';
				$tt_title = 'Switch to Travel Time';

				if ($clock['rate']==11) {
					$tt_cls = 'warning';
					$tt_title = 'Clocked In';
				} else {
					$rp_cls = 'primary';
					$rp_title = 'Clocked In';
				}

				$clockers = '
				<button class="btn btn-'.$rp_cls.'" type="button" data-type="clock" data-clock="in" data-toggle="tooltip" data-placement="bottom" title="'.$rp_title.'"><i class="fa fa-briefcase"></i></button>
				<button class="btn btn-'.$tt_cls.'" type="button" data-type="travel" data-clock="in" data-toggle="tooltip" data-placement="bottom" title="'.$tt_title.'"><i class="fa fa-car"></i></button>
				<button class="btn btn-default btn-clock text-danger" type="button" data-type="out" data-clock="out" data-toggle="tooltip" data-placement="bottom" title="Clock Out"><i class="fa fa-close"></i></button>
				';
			} else if ($clock['taskid']) {
				if ($clock['task_label']=='repair_item_id') { $task_type = 'Repair'; }
				else { $task_type = 'Service'; }
	
				$clockers = '
				<a class="btn btn-default" href="service.php?order_type='.$task_type.'&order_number='.getItemOrder($clock['taskid'], $clock['task_label']).'" data-toggle="tooltip" data-placement="bottom" title="Clocked In"><i class="fa fa-clock-o"></i> '.getItemOrder($clock['taskid'], $clock['task_label'], true).'</a>
				';
			} else {
				$clockers = '
				<button class="btn btn-danger pull-left" style="margin-right: 10px;" type="button" data-toggle="tooltip" data-placement="bottom" title="Not Clocked In"><i class="fa fa-close"></i></button>
				';
			}
		}

		return $clockers;
	}

	function getDocumentation($taskid, $label) {
		$documentData = array();

		// Query all DOcuments that pertain to this order / task
		$query = "SELECT * FROM service_docs WHERE item_id=".res($taskid)." AND item_label =".fres($label).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$documentData[] = $r;
		}

		return $documentData;
	}

	$TITLE = (getClass($ORDER['classid']) ? : $type).' '.$ORDER[$T['order']].'-'.$ORDER_DETAILS['line_number'];

	$activities = getActivities($ORDER_DETAILS, $T, $ORDER);
	
	$activity_form = array(
		'action' => 'task_activity.php', 
		'icon' => 'fa-plus-circle', 
		'fields' => array(
			array(
				'type' => 'text',
				'name' => 'notes', 
				'placeholder' => 'Notes...', 
			),
		),
	);

	$documentation_data = getDocumentation($taskid, $T['item_label']);

	$copOptions = array(
		array('id' => '', 'text' => '- Select Type -'),
		array('id' => 'MOP', 'text' => 'MOP'),
		array('id' => 'SOW', 'text' => 'SOW'),
		array('id' => 'COP', 'text' => 'COP'),
	);

	$documentation_form = array(
		'action' => 'task_documentation.php', 
		'icon' => 'fa-plus-circle', 
		'fields' => array(
			array(
				'type' => 'text',
				'name' => 'notes', 
				'placeholder' => 'Notes', 
			),
			array(
				'type' => 'select2',
				'name' => 'doc_type', 
				'placeholder' => '- Select Type -', 
				'class' => '', 
				'values' => $copOptions,
			),
			array(
				'type' => 'upload',
				'name' => 'files',
				'icon' => 'fa-folder-open-o',
				'class' => '',
				'acceptable' => 'image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,*.htm,*.html,*.xml,.docx',
			),
		),
	);

	$expenses = getExpenses($taskid, $T['item_label']);
	
	$accountOptions = getFinancialAccounts();

	// built for mobile integration
	$accountSelect2 = array(array('id' => '', 'text' => '- Select a Account -'));

	foreach($accountOptions as $option) {
		$accountSelect2[] = array(
			'id' => $option['accountid'],
			'text' => $option['bank'] .' '. $option['nickname'] .' '. substr($option['account_number'], -4),
		);
	}

	$expense_form = array(
		'action' => 'task_expenses.php', 
		'icon' => 'fa-plus-circle', 
		'fields' => array(
			array(
				'type' => 'hidden',
				'name' => 'mileage',
				'value' => $ORDER_DETAILS['mileage_rate'],
			),
			array(
				'type' => 'datepicker',
				'name' => 'date', 
				'placeholder' => '', 
				'class' => '',
			),
			array(
				'type' => 'select2',
				'name' => 'userid', 
				'placeholder' => '', 
				'class' => 'user-selector',
				'user' => true, 
			),
			array(
				'type' => 'select2',
				'name' => 'categoryid', 
				'placeholder' => '', 
				'class' => 'category-selector', 
			),
			array(
				'type' => 'select2',
				'name' => 'accountid', 
				'placeholder' => '- Select a Account -', 
				'class' => 'select2',
				// For unconventional static values but still want a select2 
				// Needs to be an array with id and text fields starting with array[0] No flat arrays allowed
				'values' => $accountSelect2,
			),
			array(
				'type' => 'select2',
				'name' => 'companyid', 
				'placeholder' => '', 
				'class' => 'company-selector',
				'scope' => 'Expenses',
			),
			array(
				'type' => 'text',
				'name' => 'amount', 
				'placeholder' => '0', 
				'class' => 'miles_expense',
				'left_icon' => 'fa-car',
				'property' => 'disabled',
			),
			// There is left_icon and right_icon
			array(
				'type' => 'text',
				'name' => 'amount', 
				'placeholder' => '0.00', 
				'class' => 'amount_expense',
				'left_icon' => 'fa-usd',
			),
			array(
				'type' => 'text',
				'name' => 'notes', 
				'placeholder' => 'Notes...', 
				'class' => '',
			),
			array(
				'type' => 'upload',
				'name' => 'files',
				'icon' => 'fa-folder-open-o',
				'class' => '',
				'acceptable' => 'image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,*.htm,*.html,*.xml,.docx',
			),
		),
	);

	// Outside Stuff Here
	$outsourced = array();
	$query = "SELECT o.companyid, o.public_notes, i.* FROM outsourced_orders o, outsourced_items i WHERE  ref_2_label=".fres($T['item_label'])." AND ref_2 = ".res($taskid)." AND o.os_number = i.os_number;";
	
	if($type == 'service_quote') {
		$query = "SELECT *, quote as price, 1 as qty, description as public_notes FROM service_quote_outsourced WHERE quote_item_id = ".res($taskid).";";
	}
	
	$result = qedb($query);

	while($r = mysqli_fetch_assoc($result)) {
		$outsourced[] = $r;
	}

	// Object created for payroll to calculate OT and DT
	// These are needed to operate Payroll correctly
	$payroll = new Payroll;

	$payroll->setHours(336);

	$currentPayroll = $payroll->getCurrentPeriodStart();
	$currentPayrollEnd = $payroll->getCurrentPeriodEnd();

	// Labor Stuff Here
	// Labor data will have the userid, the user status E.G. active or inactive (with a date check, if they try to reclock in past the end date then they are not allowed), time worked
	$labor_data = array();

	if($T['type'] != 'service_quote') {
		// Query into both service_assignments and the timesheet to see who has worked on this
		$query = "SELECT * FROM service_assignments WHERE item_id=".res($taskid)." AND item_id_label=".fres($T['item_label']).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			// If they are on this table then they are active as long as they are within the expiration of their pass to this assignment
			// If no end date then ignore
			$status = 'active';

			// End date is smaller than current date so access is now expired
			if(strtotime($r['end_datetime']) < strtotime($GLOBALS['now']) AND $r['end_datetime']) {
				$status = 'inactive';
			}
			
			// Set the used data here
			$labor_data[$r['userid']]['userid'] = $r['userid'];
			$labor_data[$r['userid']]['status'] = $status;
			$labor_data[$r['userid']]['start_datetime'] = $r['start_datetime'];
			$labor_data[$r['userid']]['end_datetime'] = $r['end_datetime'];
	}

		// Now query into the timesheet and see all users that have worked on this order
		// If the user is not on service assignments but has clocked time then add it in and leave them as status inactive
		$query = "SELECT * FROM timesheets WHERE taskid=".res($taskid)." AND task_label=".fres($T['item_label']).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			// User does not exist based on the service assignments query so set the user has inactive
			if(empty($labor_data[$r['userid']])) {
				$labor_data[$r['userid']]['status'] = 'inactive';
				$labor_data[$r['userid']]['userid'] = $r['userid'];
			}
		}

		foreach($labor_data as $userid => $row) {
			// utilizing the timesheet function get the pay of the user including OT and DT
			// Then might as well grab the total seconds using this
			$timesheet_data = $payroll->getTimesheets($userid, false, '', '', $taskid, $T['item_label']);
			$totalSeconds = 0;
			$totalPay = 0;

			$rate = $userTimesheet[$time['id']]['rate'];

			foreach($timesheet_data as $time) {
				$userTimesheet = getTimesheet($time['userid']);
				$totalSeconds += $userTimesheet[$time['id']]['REG_secs'] + $userTimesheet[$time['id']]['OT_secs'] + $userTimesheet[$time['id']]['DT_secs'];
				$totalPay += ($userTimesheet[$time['id']]['laborCost']);

				$labor_data[$userid]['payRate'] = '$'.number_format(($time['rate']?:0),2,'.','');
			}

			$labor_data[$userid]['totalSeconds'] = timeToStr(toTime($totalSeconds));
			$labor_data[$userid]['totalPay'] = '$'.number_format($totalPay,2,'.','');
			$labor_data[$userid]['regSeconds'] = timeToStr(toTime($userTimesheet[$time['id']]['REG_secs']));
			$labor_data[$userid]['OT'] = timeToStr(toTime($userTimesheet[$time['id']]['OT_secs']));
			$labor_data[$userid]['DT'] = timeToStr(toTime($userTimesheet[$time['id']]['DT_secs']));
		}

		$labor_form = array(
			'action' => 'task_labor.php', 
			'icon' => 'fa-plus-circle', 
			'fields' => array(
				array(
					'type' => 'select2',
					'name' => 'userid', 
					'placeholder' => '', 
					'class' => 'tech-selector',
				),
				array(
					'type' => 'datepicker',
					'name' => 'start_datetime', 
					'placeholder' => '', 
					'class' => '', 
				),
				array(
					'type' => 'datepicker',
					'name' => 'end_datetime', 
					'placeholder' => '', 
					'class' => '', 
				),
			),
		);
	} else {
		// Create a custom array for labor on quotes as we only have the hours and amount present
	
		$labor_data[] = $ORDER_DETAILS;
	}

	$materials_data = getMaterials($taskid, $T, true);

	$materialsSelect2 = array(array('id' => '', 'text' => '- Select a Part -'));

	// print_r($materials_data);

	foreach($materials_data as $partid => $info) {
		$materialsSelect2[] = array('id' => $info['partid'], 'text' => partDescription($info['partid'], false, true));
	}

	$materialOptions = array(array('id' => '', 'text' => '- Stock options (if applicable) -'));

	if($T['type'] != 'service_quote') {
		$materials_form = array(
			'action' => 'task_materials.php', 
			'icon' => 'fa-download', 
			'fields' => array(
				array(
					'type' => 'select2',
					'name' => '', 
					'placeholder' => '- Select a Part -', 
					'class' => 'materials_loader select2',
					// For unconventional static values but still want a select2 
					// Needs to be an array with id and text fields starting with array[0] No flat arrays allowed
					'values' => $materialsSelect2,
				),
				array(
					'type' => 'select2',
					'name' => '', 
					'placeholder' => '- Stock options (if applicable) -', 
					'class' => 'material_options select2',
					'values' => $materialOptions,
				),
				array(
					'type' => 'text',
					'name' => '', 
					'placeholder' => 'Qty', 
					'class' => 'populate_partid',
					'right_icon' => 'class_available',
					// For unconventional static values but still want a select2 
					// Needs to be an array with id and text fields starting with array[0] No flat arrays allowed
					'values' => $materialsSelect2,
					'property' => 'disabled',
				),
			),
		);
	}

	if($ORDER_DETAILS['status_code']) {
		$ticketStatus = getRepairCode($ORDER_DETAILS['status_code'], 'service');
	}

	// $TITLE = 'Responsive BETA';
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo 'Responsive BETA'; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
	?>

	<!-- any page-specific customizations -->
	<style type="text/css">
		.container-border{
			border: 1px solid #CCC;
			border-radius: 2px;  
		}

		.block_title {
			padding: 5px 10px;
			font-size: 16px;
			border-bottom: 1px solid #CCC;
		}

		section {
			margin-bottom: 20px;
			overflow: hidden;
		}

		.card-header, .card-content {
			border-bottom: 1px solid #CCC;
		}

		.row_striped {
			background-color: rgba(0,0,0,.05);
		}

		.col_pad_min {
			padding: 0 2px;;
		}

		.col_pad_remove {
			padding: 0;
		}

		.title_link {
			color: #428bca;
			cursor: pointer;
		}

		.btn-link, .block_title {
			text-align: left;
		}

		.fa-plus-circle {
			font-size: 20px;
		}

		.detail_block, .form_block {
			display: none;
		}

		.form_block {
			overflow: visible !important;
		}

		.lici_buttons {
			margin: 0 auto;
			width: 126px;
			min-height: 34px;
		}

		@media (max-width: 500px) {
			#pad-wrapper {
				margin-top: 60px;
			}

			.datepicker-date {
				width: 100% !important;
				max-width: 100% !important;
			}

			.select2 {
				width: 100% !important;
			}
		}
	</style>
</head>
<body data-order-type="<?=$T['type']?>" data-taskid="<?=$taskid;?>" data-techid="<?=$GLOBALS['U']['id'];?>">

	<?php include_once $_SERVER["ROOT_DIR"].'/inc/navbar.php'; ?>
	<?php 
		if($T['type'] != 'service_quote') {
			include_once $_SERVER["ROOT_DIR"].'/modal/lici.php'; 
		}
	?>

	<div id="pad-wrapper">
		<?php if(! $ticketStatus) { ?>
			<div class="col-md-12">			
				<div class="lici_buttons">
					<?=clockedButton($taskid)?>
				</div>
			</div>
			<BR>
		<?php } ?>

		<h3 class="text-center"><?=$TITLE;?></h3>
		<BR>
		<?=buildBlock($title = getSiteName($ORDER['companyid'], $ORDER_DETAILS['item_id']), array($ORDER_DETAILS));?>
		<?php if($T['type'] != 'service_quote') {
			echo buildBlock($title = 'Activity', $activities, $activity_form);
			echo buildBlock($title = 'Documentation', $documentation_data, $documentation_form);
		} ?>
		<?=buildBlock($title = 'Labor', $labor_data, $labor_form, ($T['type'] == 'service_quote' ? 'notes_summary' : ''));?>
		<?=buildBlock($title = 'Materials', $materials_data, $materials_form);?>
		<?php if($T['type'] != 'service_quote') {
			echo buildBlock($title = 'Expense', $expenses, $expense_form); 
		} ?>
		<?=buildBlock($title = 'Outside Services', $outsourced);?>
	</div>

	<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>
	<script src="js/mobile_task.js?id=<?php echo $V; ?>"></script>

	<?php if(! $ticketStatus AND $T['type'] != 'service_quote') { ?>
		<script type="text/javascript" src="js/lici.js"></script>
	<?php } ?>

	<script type="text/javascript">
		$(document).ready(function() {
			$(document).on("change", ".materials_loader", function(e){
				var partid = $(this).val();
				var taskid = "<?=$taskid?>";
				var type = "<?=$type?>";

				// alert(partid);
				console.log(window.location.origin+"/json/materials.php?partid="+partid+"&taskid="+taskid+"&type="+type);
				
				$('.material_options').select2('destroy');

				$('.material_options').select2({
					placeholder: '- No stock available -',
					width: '100%',
					ajax: {
						url: '/json/materials.php',
						dataType: 'json',
						data: function (params) {
							return {
								partid: partid,
								taskid: taskid,
								type: type,
							};
						},
						allowClear: true,
						processResults: function (data, params) { // parse the results into the format expected by Select2.
							// since we are using custom formatting functions we do not need to alter remote JSON data
							// except to indicate that infinite scrolling can be used
							console.log(data); 

							// data=$(this).select2('data')[0];

							params.page = params.page || 1;
							return {
								results: $.map(data, function(obj) {
									return { id: obj.id+'/'+obj.available, text: obj.text };
								})
							};
						},
						cache: true
					},
					escapeMarkup: function (markup) { return markup; },//let our custom formatter work
					minimumInputLength: 0
				});

				$(".material_options").select2("val", ""); 
			});

			$(".material_options").change(function() {
				var value = $(this).val();
				
				if(value) {
					values = value.split('/');

					if(values[0]) {
						$(".populate_partid").attr('name', value);
						$(".populate_partid").prop('disabled', false);

						$(".class_available strong").text(values[1]);
						// alert(value);
					} else {
						$(".populate_partid").prop('disabled', true);
						$(".class_available strong").text('-');
					}
				}
			});

			$('select[name="categoryid"]').change(function() {
				var categoryid = $(this).val();

				// alert(categoryid);

				if(categoryid == 91) {
					$('.miles_expense').prop("disabled", false);
					$('.amount_expense').prop("disabled", true);
				} else {
					$('.miles_expense').prop("disabled", true);
					$('.amount_expense').prop("disabled", false);
				}
			});
		});

		// After page load if a tab is set then scroll to it
		$(window).on('load', function() {
			var tab = "<?=$ACTIVE;?>";

			if($('#'+tab).length) {
				$('html, body').animate({
					scrollTop: $('#'+tab).offset().top - 60
				}, 1);
			}
		});

	</script>

</body>
</html>
