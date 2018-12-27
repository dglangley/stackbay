<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	
	// Check the Mobile
	if(is_mobile()) {
		include_once $_SERVER["ROOT_DIR"].'/responsive_market.php';

		exit;
	}

	include_once $_SERVER["ROOT_DIR"].'/inc/getField.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	$TITLE = 'Market';
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
	$lim = 0;
	if (isset($_REQUEST['lim']) AND is_numeric(trim($_REQUEST['lim'])) AND trim($_REQUEST['lim'])<>'') { $lim = trim($_REQUEST['lim']); }
	$list_type = 'slid';//default, short for 'search_lists' id field, which is a no-strings-attached search blob text
	if (isset($_REQUEST['list_type']) AND trim($_REQUEST['list_type'])<>'') { $list_type = trim($_REQUEST['list_type']); }
	$import_quote = '';
	if (isset($_REQUEST['import_quote']) AND $_REQUEST['import_quote']) { $import_quote = 1; }
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

		if (count($lines)==1 AND is_numeric(trim($_REQUEST['s']))) {
			include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';

			$H = hecidb($_REQUEST['s']);
			if (empty($H)) {
				include_once $_SERVER["ROOT_DIR"].'/inc/isOrder.php';

				$params = isOrder($_REQUEST['s']);
				if ($params['type']) {
					header('Location: order.php?order_type='.$params['type'].'&order_number='.$params['search']);
					exit;
				}
			}
		}
	} else if (isset($_REQUEST['s2']) AND trim($_REQUEST['s2'])) {
		$lines = explode(chr(10),$_REQUEST['s2']);

		$listid = logSearch($_REQUEST['s2'],$col_search,$sfe,$col_qty,$qfe,$col_price,$pfe);
	} else if (isset($_REQUEST['slid']) AND $_REQUEST['slid']) {
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
		if ($listid AND ($list_type=='Service' OR $list_type=='Repair')) {
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
	if ($list_type=='Service' OR $list_type=='Repair') { $category = $list_type; }
?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
	?>

	<link href="css/market.css?id=<?php echo $V; ?>" rel="stylesheet" />
</head>
<body>

<?php include_once 'inc/navbar.php'; ?>

<form class="form-inline" method="POST" action="save-market.php" id="results-form">
<input type="hidden" name="listid" value="<?=$listid;?>">
<input type="hidden" name="lim" value="<?=$lim;?>">
<input type="hidden" name="list_type" value="<?=$list_type;?>">
<input type="hidden" name="category" id="category" value="<?=$category;?>">
<input type="hidden" name="handler" id="handler" value="List">
<input type="hidden" name="ln" value="<?=$ln;?>">
<input type="hidden" name="searchid" value="<?=$searchid;?>">
<!--
<input type="hidden" name="taskid" value="<?=$taskid;?>">
<input type="hidden" name="task_label" value="<?=$task_label;?>">
-->

<!-- FILTER BAR -->
<div class="table-header <?=(($listid AND ($list_type=='Service' OR $list_type=='Repair')) ? 'table-'.$list_type : '');?>" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">

	<div class="row" style="padding:8px">
		<div class="col-sm-2">
			<?= (($listid AND ($list_type=='Service' OR $list_type=='Repair')) ? '<a href="/service.php?order_type='.$list_type.'&taskid='.$listid.'&tab=materials" class="btn btn-default btn-sm pull-left" style="margin-right:15px"><i class="fa fa-arrow-left"></i></a>' : ''); ?>
			<div id="remote-warnings">
<?php
				$query = "SELECT * FROM remotes ORDER BY id ASC; ";
				$result = qdb($query);
				while ($r = mysqli_fetch_assoc($result)) {
					echo '<a class="btn btn-danger btn-sm hidden btn-remote" id="remote-'.$r['remote'].'" data-name="'.$r['name'].'" title="'.$r['name'].' API failed" data-toggle="tooltip" data-placement="bottom">'.
						'<img src="/img/'.$r['remote'].'.png" /></a>';
				}
?>
			</div>
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-<?= ($list_type=='Service' ? '1' : '2'); ?>">
			<div class="btn-group <?=(($listid AND ($list_type=='Service' OR $list_type=='Repair')) ? 'hidden' : '');?>" style="right:0; top:0; position:absolute">
				<button class="btn btn-xs btn-default btn-category left <?=(($list_type<>'Service' AND $list_type<>'Repair') ? 'active' : '');?>" type="button" title="equipment sales" data-toggle="tooltip" data-placement="bottom" rel="tooltip">Sale</button>
				<button class="btn btn-xs btn-default btn-category middle <?=(($list_type=='Service' OR $list_type=='Repair') ? 'active' : '');?>" type="button" title="services" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><?= $list_type; ?></button>
				<button class="btn btn-xs btn-default btn-category right" type="button" title="equipment repair" data-toggle="tooltip" data-placement="bottom" rel="tooltip">Repair</button>
			</div>
			<div class="slider-frame <?=(($listid AND ($list_type=='Service' OR $list_type=='Repair')) ? 'hidden' : '');?>" style="left:0; top:0; position:absolute">
				<!-- include radio's inside slider-frame to set appropriate actions to them -->
				<input class="hidden" value="Buy" type="radio" name="mode" <?=$buy_checked;?>>
				<input class="hidden" value="Sell" type="radio" name="mode" <?=$sell_checked;?>>
				<span data-off-text="Buy" data-on-text="Sell" class="slider-button slider-mode <?=$slider_toggle;?>" id="mode-slider">Buy</span>
			</div>
		</div>
		<div class="col-sm-<?= ($list_type=='Service' ? '4' : '2'); ?> text-center">
			<h2 class="minimal" style="max-height:33px; overflow:hidden"><?php echo $TITLE; ?></h2>
			<span class="info"><?php echo $title_info; ?></span>
		</div>
		<div class="col-sm-1 text-right col-total">
			<h3 class="text-blue" id="list_total">$ 0.00</h3>
			<span class="info">TOTAL</span>
		</div>
		<div class="col-sm-<?= ($list_type=='Service' ? '1' : '2'); ?> col-company">
			<select name="companyid" size="1" class="form-control <?=(($listid AND ($list_type=='Service' OR $list_type=='Repair')) ? 'hidden' : 'company-selector');?>">
				<?=($companyid ? '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>' : '');?>
			</select>
		</div>
		<div class="col-sm-1">
			<select name="contactid" id="contactid" size="1" class="form-control <?=(($listid AND ($list_type=='Service' OR $list_type=='Repair')) ? 'hidden' : 'contact-selector');?>" data-placeholder="- Contacts -">
				<?=($contactid ? '<option value="'.$contactid.'" selected>'.getContact($contactid).'</option>' : '');?>
			</select>
		</div>
		<div class="col-sm-1 text-center">
			<div class="btn-group settings">
				<button type="button" class="btn btn-md btn-success btn-save"><span class="hidden-xl"><i class="fa fa-save"></i></span><span class="hidden-lg2"><i class="fa fa-save"></i> Save</span></button>
				<button type="button" class="btn btn-md btn-gray dropdown-toggle" data-toggle="dropdown"><i class="fa fa-caret-down fa-lg"></i></button>
				<ul class="dropdown-menu dropdown-menu-right text-left save-menu">
					<li><a href="javascript:void(0);" class="text-success" data-btn="btn-success" data-handler="List"><i class="fa fa-save"></i> Save</a></li>
					<li><a href="javascript:void(0);" class="text-danger" data-btn="btn-danger" data-handler="WTB"><i class="fa fa-paper-plane"></i> WTB</a></li>
<!--
					<li><a href="javascript:void(0);" class="text-primary" data-btn="btn-primary" data-handler="PR" id="pr_handler"><i class="fa fa-share-square"></i> Request</a></li>
-->
				</ul>
			</div>
		</div>
	</div>

</div>

<div id="pad-wrapper" style="margin-top:110px">

	<?= ($list_type=='metaid' ? '<h5 class="text-center"><a href="m2.php?metaid='.$listid.'" style="color:brown">View in Market 2019</a></h5>' : ''); ?>
	<div class="table-responsive">
		<table class="table table-condensed" id="results">
		</table>
	</div>

</div><!-- pad-wrapper -->

</form>

<?php include_once 'modal/image.php'; ?>
<?php include_once 'modal/results.php'; ?>
<?php include_once 'modal/notes.php'; ?>
<?php include_once 'modal/parts.php'; ?>
<?php include_once 'modal/custom.php'; ?>
<?php include_once 'modal/remotes.php'; ?>
<?php include_once 'modal/contact.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<div class="hidden">
<canvas id="mChart" width="<?=$chartW;?>" height="<?=$chartH;?>"></canvas>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		companyid = '<?=$companyid;?>';
		contactid = '<?=$contactid;?>';
		listid = '<?=$listid;?>';
		lim = '<?=$lim;?>';
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
		import_quote = '<?=$import_quote;?>';
	});
</script>
<script src="js/market.js?id=<?php echo $V; ?>"></script>
<script src="js/contacts.js?id=<?php echo $V; ?>"></script>

<?php /* if ($list_type=='Repair') { ?>
<script type="text/javascript">
	$(document).ready(function() {
		$(".btn-save").removeClass('btn-success');
		$("#pr_handler").saveMenu();
	});
</script>
<?php } */ ?>

</body>
</html>
