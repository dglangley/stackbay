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
	include_once $_SERVER["ROOT_DIR"].'/inc/getFinancialAccounts.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSiteName.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterials.php';

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

	$TITLE = (getClass($ORDER['classid']) ? : $type).' '.$ORDER[$T['order']].'-'.$ORDER_DETAILS['line_number'];

	$activities = getActivities();
	
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

	$expenses = getExpenses($taskid, $T['item_label']);
	
	$accountOptions = getFinancialAccounts();

	// built for mobile integration
	$accountSelect2 = array();

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
				'placeholder' => '', 
				'class' => '',
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
	$result = qedb($query);

	while($r = mysqli_fetch_assoc($result)) {
		$outsourced[] = $r;
	}

	// Labor Stuff Here
	// Labor data will have the userid, the user status E.G. active or inactive (with a date check, if they try to reclock in past the end date then they are not allowed), time worked
	$labor_data = array();

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

	$materials_data = getMaterials($taskid, $T);

	$materialsSelect2 = array(array('id' => '', 'text' => '- Select a Part -'));

	foreach($materials_data as $partid => $info) {
		$materialsSelect2[] = array('id' => $partid, 'text' => partDescription($partid, false, true));
	}

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
				'placeholder' => '', 
				'class' => 'material_options select2',
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

	$TITLE = 'Responsive BETA';
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
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
<body data-order-type="<?=$T['type']?>">

	<?php include_once 'inc/navbar.php'; ?>

	<div id="pad-wrapper">
		<?=buildBlock($title = getSiteName($ORDER['companyid'], $ORDER_DETAILS['item_id']), array($ORDER_DETAILS));?>
		<?=buildBlock($title = 'Activity', $activities, $activity_form);?>
		<?=buildBlock($title = 'Labor', $labor_data, $labor_form);?>
		<?=buildBlock($title = 'Materials', $materials_data, $materials_form);?>
		<?=buildBlock($title = 'Expense', $expenses, $expense_form);?>
		<?=buildBlock($title = 'Outside Services', $outsourced);?>
	</div>

	<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>
	<script src="js/mobile_task.js?id=<?php echo $V; ?>"></script>

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
	</script>

</body>
</html>
