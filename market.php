<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getField.php';
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

	$slid = 0;
	$metaid = 0;
	$lines = array();
	if (isset($_REQUEST['s']) AND trim($_REQUEST['s'])) {
		$lines = array(trim($_REQUEST['s']));

		$slid = logSearch($_REQUEST['s'],$col_search,$sfe,$col_qty,$qfe,$col_price,$pfe);
	} else if (isset($_REQUEST['s2']) AND trim($_REQUEST['s2'])) {
		$lines = explode(chr(10),$_REQUEST['s2']);

		$slid = logSearch($_REQUEST['s2'],$col_search,$sfe,$col_qty,$qfe,$col_price,$pfe);
	} else if (isset($_REQUEST['slid'])) {
		$slid = $_REQUEST['slid'];

		$query = "SELECT * FROM search_lists WHERE id = '".res($slid)."'; ";
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
		$list_type = '';
		if (! isset($_REQUEST['metaid']) AND (isset($_REQUEST['upload_listid']) OR isset($_REQUEST['listid']))) {
			$upload_listid = ($_REQUEST['upload_listid'] ? $_REQUEST['upload_listid'] : $_REQUEST['listid']);

			$query = "SELECT filename, metaid, type, processed FROM uploads WHERE id = '".res($upload_listid)."'; ";
			$result = qedb($query);
			$r = qrow($result);
			$metaid = $r['metaid'];
			$TITLE = $r['filename'];
			$list_type = $r['type'];
			if (! $r['processed']) { $processed = false; }
		} else {
			$metaid = $_REQUEST['metaid'];

			$query = "SELECT filename, type, processed FROM uploads WHERE metaid = '".res($metaid)."'; ";
			$result = qedb($query);
			if (qnum($result)>0) {
				$r = qrow($result);
				$TITLE = $r['filename'];
				$list_type = $r['type'];
				if (! $r['processed']) { $processed = false; }
			}
		}

		if (! $processed) {
			$ALERTS[] = "Please wait while I process your list. If you do not have an email from me within 10 or 15 minutes, ".
						"you may have unorganized data in your list that I cannot handle.";
		}

		$query = "SELECT * FROM search_meta WHERE id = '".res($metaid)."'; ";
		$result = qedb($query);
		$r = qrow($result);
		$list_date = $r['datetime'];
	}
	$title_info = format_date($list_date,'M j, Y g:i:sa');

	foreach ($lines as $ln => $line) {
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
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
	?>

	<link href="css/market.css" rel="stylesheet" />
</head>
<body>

<?php include_once 'inc/navbar.php'; ?>

<form class="form-inline" method="POST" action="save-market.php" id="results-form">
<input type="hidden" name="slid" value="<?=$slid;?>">
<input type="hidden" name="metaid" value="<?=$metaid;?>">
<input type="hidden" name="category" id="category" value="<?=$category;?>">
<input type="hidden" name="handler" id="handler" value="List">

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">

	<div class="row" style="padding:8px">
		<div class="col-sm-1">
<!--
			<button class="btn btn-sm btn-default" type="button">RFQs</button>
-->
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
			<div class="btn-group" style="right:0; top:0; position:absolute">
				<button class="btn btn-xs btn-default btn-category left active" type="button" title="equipment sales" data-toggle="tooltip" data-placement="bottom" rel="tooltip">Sale</button>
				<button class="btn btn-xs btn-default btn-category right" type="button" title="equipment repair" data-toggle="tooltip" data-placement="bottom" rel="tooltip">Repair</button>
			</div>
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"><?php echo $title_info; ?></span>
		</div>
		<div class="col-sm-1">
			<div class="slider-frame" style="left:0; top:0; position:absolute">
				<!-- include radio's inside slider-frame to set appropriate actions to them -->
				<input class="hidden" value="Buy" type="radio" name="mode">
				<input class="hidden" value="Sell" type="radio" name="mode" checked>
				<span data-off-text="Buy" data-on-text="Sell" class="slider-button slider-mode" id="mode-slider">Sell</span>
			</div>
		</div>
		<div class="col-sm-2 col-company">
			<select name="companyid" size="1" class="form-control company-selector">
			</select>
		</div>
		<div class="col-sm-1">
			<select name="contactid" size="1" class="form-control contact-selector" data-placeholder="- Contacts -">
			</select>
		</div>
		<div class="col-sm-1 text-right">
			<div class="btn-group settings">
				<button type="button" class="btn btn-md btn-success btn-save"><i class="fa fa-save"></i> Save</button>
				<button type="button" class="btn btn-md btn-success dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
				<ul class="dropdown-menu dropdown-menu-right text-left save-menu">
					<li><a href="javascript:void(0);" class="text-white btn-success" data-handler="List"><i class="fa fa-save"></i> Save</a></li>
					<li><a href="javascript:void(0);" data-handler="WTB"><i class="fa fa-paper-plane"></i> WTB</a></li>
					<li><a href="javascript:void(0);" data-handler="PR"><i class="fa fa-share-square"></i> Request</a></li>
				</ul>
			</div>
		</div>
	</div>

</div>

<div id="pad-wrapper">

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
<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<div class="hidden">
<canvas id="mChart" width="<?=$chartW;?>" height="<?=$chartH;?>"></canvas>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		companyid = '<?=$companyid;?>';
		contactid = '<?=$contactid;?>';
		slid = '<?=$slid;?>';
		metaid = '<?=$metaid;?>';
		category = setCategory();
		PR = '<?=$PR;?>';
		salesMin = '<?=$salesMin;?>';
		favorites = '<?=$favorites;?>';
		startDate = '<?=$startDate;?>';
		endDate = '<?=$endDate;?>';
		demandMin = '<?=$demand_min;?>';
		demandMax = '<?=$demand_max;?>';
	});
</script>
<script src="js/market.js?id=<?php echo $V; ?>"></script>
<script src="js/contacts.js?id=<?php echo $V; ?>"></script>

</body>
</html>
