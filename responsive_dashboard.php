<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"] . '/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/dictionary.php';

	// Getter
	include_once $_SERVER["ROOT_DIR"] . '/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getClass.php';

	// Builder for Responsive
	include_once $_SERVER["ROOT_DIR"].'/responsive/responsive_builder.php';

	$keyword = '';
	if ((isset($_REQUEST['s']) AND $_REQUEST['s']) OR (isset($_REQUEST['keyword']) AND $_REQUEST['keyword'])) {
		if (isset($_REQUEST['s']) AND $_REQUEST['s']) { $keyword = $_REQUEST['s']; }
		else if (isset($_REQUEST['keyword']) AND $_REQUEST['keyword']) { $keyword = $_REQUEST['keyword']; }
		$keyword = trim($keyword);

		$matches = array();
		$matches_csv = '';
		$query = "SELECT i.so_number, i.id, i.line_number FROM service_orders o ";
		$query .= "LEFT JOIN service_items i ON o.so_number = i.so_number ";
		$query .= "WHERE (cust_ref = '".res($keyword)."' OR o.so_number = '".res($keyword)."' ";
		$query .= "OR CONCAT(i.so_number,'-',i.line_number) = '".res($keyword)."' OR task_name RLIKE '".res($keyword)."' ";
		$query .= "OR public_notes RLIKE '".res($keyword)."' ";
		$query .= "); ";
		$result = qdb($query) OR die(qe().'<BR>'.$keyword);
		while ($r = mysqli_fetch_assoc($result)) {
			if ($r['id']) {
				if ($matches_csv) { $matches_csv .= ','; }
				$matches_csv .= $r['id'];
			}
			$matches[] = $r['so_number'].'-'.$r['line_number'];
		}

		$query = "SELECT so_number, i.id, i.line_number FROM service_items i, addresses a ";
		$query .= "LEFT JOIN company_addresses ca ON ca.addressid = a.id ";
		$query .= "WHERE a.id = item_id AND item_label = 'addressid' ";
		$query .= "AND (a.street RLIKE '".res($keyword)."' OR a.city RLIKE '".res($keyword)."' ";
		$query .= "OR ca.nickname RLIKE '".res($keyword)."' OR ca.alias RLIKE '".res($keyword)."' OR ca.notes RLIKE '".res($keyword)."') ";

		if ($matches_csv) {
			$query .= "AND i.id NOT IN (".$matches_csv.") ";
		}
		$query .= "; ";
		$result = qdb($query) OR die(qe().'<BR>'.$keyword);
		while ($r = mysqli_fetch_assoc($result)) {
			$matches[] = $r['so_number'].'-'.$r['line_number'];
		}

		if (count($matches)==1) {
			header('Location: service.php?order_number='.$matches[0]);
			exit;
		}

		// in order for user to be able to 'reset' view to default services home, we want to reset search string
		// so that if they at first were switching between modes (say, sales to services) with a sales-related
		// search string, the subsequent click would show all services instead of the bogus search string
		$_REQUEST['s'] = '';
	}

	$dashboard = ucwords($_REQUEST['type']?:'Service');

	// Hack to invoke quotes view change
	$quote =  isset($_REQUEST['quote']) ? $_REQUEST['quote'] : '';

	// search paramaters here
	$company_filter = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) { 
		$company_filter = $_REQUEST['companyid']; 
	}

	// if no start date passed in, or invalid, set to beginning of previous month
	$startDate = '';
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}
	
	if (! $startDate) { $startDate = format_date($today,'m/d/Y',array('d'=>-90)); }

	$endDate = date('m/d/Y');
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}

	// is the user permitted for any management roles?
	$sales = array_intersect($USER_ROLES, array(5));
	$logistics = array_intersect($USER_ROLES, array(9));
	$management = $GLOBALS['U']['manager'];
	$admin = $GLOBALS['U']['admin'];

	if (! isset($_REQUEST['status']) OR ! $_REQUEST['status']) { $status = 'open'; }
	else { $status = $_REQUEST['status']; }

	$lines_searched = array();

	if($dashboard == 'Service') {
		$query = "SELECT o.*, i.* FROM ";
		// if no permissions, join the table with assignments to be sure this user is assigned in order to view
		if (! $management AND ! $managerid AND ! $logistics) { $query .= "service_assignments sa, "; }
		// Create an extra bypass for the user with privilege of logistics
		// If the user is logistics and doesnt have any of the management or admin privileges then show based on their class
		if (! $management AND ! $managerid AND $logistics) { $query .= "user_classes uc, "; }
		$query .= "service_orders o, service_items i ";
		$query .= "LEFT JOIN addresses a ON (i.item_id = a.id AND i.item_label = 'addressid') ";
		$query .= "LEFT JOIN company_addresses ca ON ca.addressid = a.id ";
		$query .= "WHERE o.so_number = i.so_number ";
		// Omitt CCO AND ICO from the query
		$query .= "AND (i.ref_2_label <> 'service_item_id' OR i.ref_2_label IS NULL) ";
		if (! $management AND ! $managerid AND ! $logistics) { $query .= "AND sa.userid = '".$GLOBALS['U']['id']."' AND sa.item_id = i.id AND sa.item_id_label = 'service_item_id' "; }

		// If the user is logistics and doesnt have any of the management or admin privileges then show based on their class
		if (! $management AND ! $managerid AND $logistics) { $query .= "AND o.classid = uc.classid AND uc.userid = '".$GLOBALS['U']['id']."' "; }
		if ($keyword) {
			$query .= "AND (";
			$query .= "i.task_name RLIKE '".$keyword."' OR a.street RLIKE '".$keyword."' OR a.city RLIKE '".$keyword."' OR o.public_notes RLIKE '".$keyword."' ";
			$query .= "OR o.so_number = '".$keyword."' OR CONCAT(i.so_number,'-',i.line_number) = '".$keyword."' ";
			$query .= "OR ca.nickname RLIKE '".res($keyword)."' OR ca.alias RLIKE '".res($keyword)."' OR ca.notes RLIKE '".res($keyword)."' ";
			$query .= ") ";
		} else if ($startDate) {
			$dbStartDate = format_date($startDate, 'Y-m-d 00:00:00');
			$dbEndDate = format_date($endDate, 'Y-m-d 23:59:59');
			$query .= "AND datetime BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
		}
		if ($company_filter) {
			$query .= "AND companyid = '".res($company_filter)."' ";
		}
		if ($classid) {
			$query .= "AND o.classid = '".res($classid)."' ";
		}
		$query .= "GROUP BY i.id ";
		$query .= "ORDER BY datetime DESC, o.so_number DESC, i.line_number ASC, task_name ASC; ";

		if($quote) {
			// Change the query to quotes only
			$query = "SELECT o.*, i.* FROM ";
			$query .= "service_quotes o, service_quote_items i ";
			$query .= "LEFT JOIN addresses a ON (i.item_id = a.id AND i.item_label = 'addressid') ";
			$query .= "LEFT JOIN company_addresses ca ON ca.addressid = a.id ";
			$query .= "WHERE o.quoteid = i.quoteid ";

			if ($keyword) {
				$query .= "AND (";
				$query .= "a.street RLIKE '".$keyword."' OR a.city RLIKE '".$keyword."' OR o.public_notes RLIKE '".$keyword."' ";
				$query .= "OR o.quoteid = '".$keyword."' OR CONCAT(i.quoteid,'-',i.line_number) = '".$keyword."' ";
				$query .= "OR ca.nickname RLIKE '".res($keyword)."' OR ca.alias RLIKE '".res($keyword)."' OR ca.notes RLIKE '".res($keyword)."' ";
				$query .= ") ";
			} else if ($startDate) {
				$dbStartDate = format_date($startDate, 'Y-m-d 00:00:00');
				$dbEndDate = format_date($endDate, 'Y-m-d 23:59:59');
				$query .= "AND datetime BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
			}
			if ($company_filter) {
				$query .= "AND companyid = '".res($company_filter)."' ";
			}
			if ($classid) {
				$query .= "AND o.classid = '".res($classid)."' ";
			}
			$query .= "GROUP BY i.id ";
			$query .= "ORDER BY datetime DESC, o.quoteid DESC, i.line_number ASC; ";
		}

		// echo $query;

		$result = qedb($query);

		while($r = qrow($result)) {
			$r['class'] = '';
			if ($r['task_name']) { $r['class'] = $job['task_name']; }
			else { $r['class'] = getClass($r['classid']); }

			// if ($r['class']=='Internal' AND $classid<>10) { continue; }

			$lines_searched[] = $r;
		}
	}

	$text_lines = array();

	// print_r($lines_searched);

	// Generate landing page titles
	foreach($lines_searched as $lines) {
		$sitename = '';
		if($lines['item_label'] == 'addressid' AND $lines['item_id']) {
			$sitename = getSiteName($lines['companyid'], $lines['item_id']);
		}

		$text_lines[$lines['id']] = $lines['class'].' '.($lines['quoteid']?:$lines['so_number']).'-'.$lines['line_number'] . ' ' . $sitename;
	}

	$TITLE = $dashboard . ' Dashboard';
?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo $dashboard . ' Dashboard'; ?></title>
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

		<?php if($lines_searched > 1) {
			echo '.summary_block { display: none; }';
		} ?>

		.fa-pencil, .bot-icon {
			display: none;
		}

		.col-results h5 {
			font-weight: bold;
		}

		.col-results h4, .col-results h5, .col-results h6 {
			font-size: 11px;
		}

		#detail_market .items-row, #detail_purchase .items-row, #detail_sale .items-row, #detail_market .items-row {
			margin: 10px 0;
		}

		.title_labels {
      		font-size: 12px;
		}

		#detail_notes .notes_container {
			margin: 0;
		}

		#detail_notes .container hr {
			margin-top: 5px !important;
			margin-bottom: 5px !important;
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
		<div class="landing_block_back title_link" style="display: none; font-size: 14px; margin-bottom: 10px;">
 			<i class="fa fa-angle-left pull-left" aria-hidden="true"></i> Back
		</div>

		<h3 class="text-center"><?=$TITLE;?></h3>
		<BR>

		<?php 
			// print_r($lines_searched);

			if($lines_searched > 1) { 
				echo buildLandingBlocks($text_lines, true, ($quote ? 'service_quote' : ''));
			}

			// echo buildBlock('Notes', $lines_searched,'', 'notes_summary');
		?>
	</div>

	<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

	<script type="text/javascript">
		$(document).ready(function() {
			companyid = '<?=$companyid;?>';
			contactid = '<?=$contactid;?>';
			listid = '<?=$listid;?>';
			list_type = '<?=$list_type;?>';
			category = setCategory();
			PR = '<?=$PR;?>';
			salesMin = '<?=$salesMin;?>';
			favorites = '<?=$favorites;?>';
			startDate = '<?=$startDate;?>';
			endDate = '<?=$endDate;?>';
			demandMin = '<?=$demand_min;?>';
			demandMax = '<?=$demand_max;?>';
			line_number = '<?=$ln;?>';
			searchid = '<?=$searchid;?>';
		});
	</script>

	<script src="js/contacts.js?id=<?php echo $V; ?>"></script>

</body>
</html>
