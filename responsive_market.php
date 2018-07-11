<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	include_once $_SERVER["ROOT_DIR"] . '/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/dictionary.php';

	// Getter
	include_once $_SERVER["ROOT_DIR"].'/inc/getField.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	// Builder for Responsive
	include_once $_SERVER["ROOT_DIR"].'/responsive/responsive_market_builder.php';
	include_once $_SERVER["ROOT_DIR"].'/responsive/responsive_builder.php';

	$_REQUEST['s'] = 'ERB6';

	// STRAIGHT RIP FROM MARKET PAGE
	// ANDREW LOOK INTO CLEANING UP WHAT IS NOT NEEDED FOR MOBILE
	$list_date = $now;

	$companyid = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$contactid = 0;
	if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid'])) { $contactid = $_REQUEST['contactid']; }
	$PR = '';
	if (isset($_REQUEST['dq_count']) AND is_numeric(trim($_REQUEST['dq_count'])) AND trim($_REQUEST['dq_count'])<>'') { $PR = trim($_REQUEST['dq_count']); }
	$salesMin = '';
	if (isset($_REQUEST['sales_count']) AND is_numeric(trim($_REQUEST['sales_count'])) AND trim($_REQUEST['sales_count'])<>'') { $salesMin = trim($_REQUEST['sales_count']); }
	$favorites = '';
	if (isset($_REQUEST['favorites']) AND is_numeric(trim($_REQUEST['favorites']))) { $favorites = $_REQUEST['favorites']; }
	$startDate = '';
	if (isset($_REQUEST['startDate']) AND $_REQUEST['startDate']) { $startDate = $_REQUEST['startDate']; }
	$endDate = '';
	if (isset($_REQUEST['endDate']) AND $_REQUEST['endDate']) { $endDate = $_REQUEST['endDate']; }
	$demandMin = '';
	if (isset($_REQUEST['demand_min']) AND is_numeric(trim($_REQUEST['demand_min'])) AND trim($_REQUEST['demand_min'])<>'') { $demandMin = trim($_REQUEST['demand_min']); }
	$demandMax = '';
	if (isset($_REQUEST['demand_max']) AND is_numeric(trim($_REQUEST['demand_max'])) AND trim($_REQUEST['demand_max'])<>'') { $demandMax = trim($_REQUEST['demand_max']); }
	$ln = '';
	if (isset($_REQUEST['ln']) AND is_numeric(trim($_REQUEST['ln'])) AND trim($_REQUEST['ln'])<>'') { $ln = trim($_REQUEST['ln']); }
	$searchid = '';
	if (isset($_REQUEST['searchid']) AND is_numeric($_REQUEST['searchid'])) { $searchid = $_REQUEST['searchid']; }
	$listid = 0;
	if (isset($_REQUEST['listid']) AND is_numeric(trim($_REQUEST['listid'])) AND trim($_REQUEST['listid'])<>'') { $listid = trim($_REQUEST['listid']); }
	$list_type = 'slid';//default, short for 'search_lists' id field, which is a no-strings-attached search blob text
	if (isset($_REQUEST['list_type']) AND trim($_REQUEST['list_type'])<>'') { $list_type = trim($_REQUEST['list_type']); }
/*
	$taskid = 0;
	if (isset($_REQUEST['taskid']) AND is_numeric(trim($_REQUEST['taskid'])) AND trim($_REQUEST['taskid'])<>'') { $taskid = trim($_REQUEST['taskid']); }
	$task_label = '';
	if (isset($_REQUEST['task_label']) AND trim($_REQUEST['task_label'])<>'') { $task_label = trim($_REQUEST['task_label']); }
	if ($taskid AND $task_label) {
		$listid = $taskid;
		$list_type = $task_label;
	}
*/

	//default field handling variables
	$col_search = 1;
	if (isset($_REQUEST['search_field'])) { $col_search = $_REQUEST['search_field']; }
	$sfe = false;//search from end
	$col_qty = 2;
	if (isset($_REQUEST['qty_field']) AND $_REQUEST['qty_field']<>'') { $col_qty = $_REQUEST['qty_field']; }
	$qfe = false;//qty from end
	$col_price = false;
	if (isset($_REQUEST['price_field']) AND $_REQUEST['price_field']<>'') { $col_price = $_REQUEST['price_field']; }
	$pfe = false;//price from end

	$record_type = 'demand';//default
	// default slider options for demand
	$buy_checked = '';
	$sell_checked = 'checked';
	$slider_toggle = 'off';

	$title_info = '';
	$lines = array();
	if (isset($_REQUEST['s']) AND trim($_REQUEST['s'])) {
		$lines = array(trim($_REQUEST['s']));

		$listid = logSearch($_REQUEST['s'],$col_search,$sfe,$col_qty,$qfe,$col_price,$pfe);
	} else if (isset($_REQUEST['s2']) AND trim($_REQUEST['s2'])) {
		$lines = explode(chr(10),$_REQUEST['s2']);

		$listid = logSearch($_REQUEST['s2'],$col_search,$sfe,$col_qty,$qfe,$col_price,$pfe);
	} else if (isset($_REQUEST['slid'])) {
		$listid = $_REQUEST['slid'];

		$query = "SELECT * FROM search_lists WHERE id = '".res($listid)."'; ";
		$result = qedb($query);
		$list = qfetch($result,'Could not find list');

		$lines = explode(chr(10),$list['search_text']);
		$fields = $list['fields'];
		$col_search = substr($fields,0,1);
		$col_qty = substr($fields,1,1);
		$col_price = substr($fields,2,1);
		if (strlen($list['fields'])>3) {
			$sfe = substr($fields,3,1);
			$qfe = substr($fields,4,1);
			$pfe = substr($fields,5,1);
		}
	} else if ((isset($_REQUEST['metaid']) AND $_REQUEST['metaid']) OR (isset($_REQUEST['upload_listid']) AND $_REQUEST['upload_listid']) OR (isset($_REQUEST['listid']) OR $_REQUEST['listid'])) {
		$processed = true;
		if ($listid AND $list_type=='Service') {
			include_once $_SERVER["ROOT_DIR"].'/inc/getItemOrder.php';
			include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

			$TITLE = getItemOrder($listid, $list_type, true);

			$T = order_type($list_type);
//			$ORDER = getOrder($order_number, $type);
			$title_info = 'Bill of Materials';

			$buy_checked = 'checked';
			$sell_checked = '';
			$slider_toggle = 'on';
		} else if (! isset($_REQUEST['metaid']) AND (isset($_REQUEST['upload_listid']) OR isset($_REQUEST['listid']))) {
			$upload_listid = ($_REQUEST['upload_listid'] ? $_REQUEST['upload_listid'] : $_REQUEST['listid']);
			$list_type = 'metaid';

			$query = "SELECT filename, metaid, type, processed FROM uploads WHERE id = '".res($upload_listid)."'; ";
			$result = qedb($query);
			$r = qrow($result);
			$listid = $r['metaid'];
			$TITLE = $r['filename'];
			$record_type = $r['type'];
			if (! $r['processed']) { $processed = false; }
		} else {
			$listid = $_REQUEST['metaid'];
			$list_type = 'metaid';

			$query = "SELECT filename, type, processed FROM uploads WHERE metaid = '".res($listid)."'; ";
			$result = qedb($query);
			if (qnum($result)>0) {
				$r = qrow($result);
				$TITLE = $r['filename'];
				$record_type = $r['type'];
				if (! $r['processed']) { $processed = false; }
			} else {
				// detect type so we can extract data from searches table based on searchid in the corresponding records table (demand or availability)
				$query = "SELECT * FROM demand WHERE metaid = '".res($listid)."'; ";
				$result = qedb($query);
				if (qnum($result)>0) { $record_type = 'demand'; } else { $record_type = 'availability'; }
			}
		}
		if ($record_type=='availability') {
			$buy_checked = 'checked';
			$sell_checked = '';
			$slider_toggle = 'on';
		}

		if (! $processed) {
			$ALERTS[] = "Please wait while I process your list. If you do not have an email from me within 10 or 15 minutes, ".
						"you may have unorganized data in your list that I cannot handle.";
		}

		if ($list_type=='metaid') {
			$query = "SELECT * FROM search_meta WHERE id = '".res($listid)."'; ";
			$result = qedb($query);
			$r = qrow($result);
			$list_date = $r['datetime'];
			$companyid = $r['companyid'];
			$contactid = $r['contactid'];
			if ($TITLE=='Market') { $TITLE = 'Quote '.$listid; }
		}
	}
	if (! $title_info) { $title_info = format_date($list_date,'M j, Y g:i:sa'); }

	foreach ($lines as $l => $line) {
		$F = preg_split('/[[:space:]]+/',$line);

		$search = getField($F,$col_search,$sfe);
		if ($search===false) { continue; }

		$qty = getField($F,$col_qty,$qfe);
		if (! $qty OR ! is_numeric($qty)) { $qty = 1; }

		$price = getField($F,$col_price,$pfe);
		if ($price===false) { $price = ''; }
	}

	$chartW = 180;
	$chartH = 120;

	$category = "Sale";
	if ($list_type=='Service') { $category = $list_type; }

	$TITLE = 'MOBILE MARKET (BETA)';
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo 'Responsive Market BETA'; ?></title>
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
		<h3 class="text-center"><?=$TITLE;?></h3>
		<BR>
		<?=buildBlock(($_REQUEST['s'] ?:$title_info), array(array('market_block' => '<div id="parts_summary"></div>')));?>
		<?=buildBlock("Market", array(array('market_block' => '<div id="market_summary"></div>')));?>
		<?=buildBlock("Average Cost", array(array('market_block' => '<div id="purchase_summary"></div>')));?>
		<?=buildBlock("Shelflife", array(array('market_block' => '<div id="sales_summary"></div>')));?>
		<?=buildBlock("Proj Req", array(array('market_block' => '<div id="demand_summary"></div>')));?>
		<BR>

		<div class="table-responsive">
			<div class="table table-condensed" id="results">
			</div>
		</div>
	</div>

	<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>
	<script src="js/mobile_task.js?id=<?php echo $V; ?>"></script>

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

	<script src="js/mobile_market.js?id=<?php echo $V; ?>"></script>
	<script src="js/contacts.js?id=<?php echo $V; ?>"></script>

</body>
</html>
