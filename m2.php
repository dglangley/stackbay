<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUsers.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrderNumber.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getField.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$SEARCH = '';
	if (isset($_REQUEST['search']) AND trim($_REQUEST['search'])) { $SEARCH = trim($_REQUEST['search']); }
	else if (isset($_REQUEST['s']) AND trim($_REQUEST['s'])) { $SEARCH = trim($_REQUEST['s']); }
	else if (isset($_REQUEST['s2']) AND trim($_REQUEST['s2'])) { $SEARCH = trim($_REQUEST['s2']); }

	$TI = 1;

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

	// tells the script to pull the quoted materials rather than the materials on the task (for services with quotes)
	$import_quote = '';
	if (isset($_REQUEST['import_quote']) AND $_REQUEST['import_quote']) { $import_quote = 1; }

	$list_type = 'Demand';//default, short for 'search_lists' id field, which is a no-strings-attached search blob text
	if (isset($_REQUEST['list_type']) AND trim($_REQUEST['list_type'])<>'') { $list_type = trim($_REQUEST['list_type']); }
	$list_label = 'slid';
	if ($list_type=='Service' OR $list_type=='Repair') { $list_label = $list_type; }
	else if (isset($_REQUEST['list_label']) AND $_REQUEST['list_label']) { $list_label = $_REQUEST['list_label']; }

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

	$userid = 0;

	$TITLE = 'Market';
	$subtitle = '';//format_date($now,'M j, Y g:i:sa');
	$list_date = $now;

	$lines = array();
	$new_save = true;//is this an edit, or a new save?
	if ($SEARCH) {
		$lines = explode(chr(10),$_REQUEST['s']);

		$listid = logSearch($SEARCH,$col_search,$sfe,$col_qty,$qfe,$col_price,$pfe);
		// check if this is possibly an order# so we can redirect directly to order#
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

		$userid = $U['id'];
	} else if (isset($_REQUEST['slid']) AND $_REQUEST['slid']) {
		$listid = $_REQUEST['slid'];

		$query = "SELECT * FROM search_lists WHERE id = '".res($listid)."'; ";
		$result = qedb($query);
		$r = qrow($result);
		$userid = $r['userid'];

		$lines = explode(chr(10),$r['search_text']);
		$fields = $r['fields'];
		$col_search = substr($fields,0,1);
		$col_qty = substr($fields,1,1);
		$col_price = substr($fields,2,1);
		if (strlen($r['fields'])>3) {
			$sfe = substr($fields,3,1);
			$qfe = substr($fields,4,1);
			$pfe = substr($fields,5,1);
		}
	} else if ((isset($_REQUEST['metaid']) AND $_REQUEST['metaid']) OR (isset($_REQUEST['upload_listid']) AND $_REQUEST['upload_listid']) OR (isset($_REQUEST['listid']) OR $_REQUEST['listid'])) {
		$processed = true;
		if ($listid AND ($list_type=='Service' OR $list_type=='Repair')) {
			$new_save = false;

			include_once $_SERVER["ROOT_DIR"].'/inc/getItemOrder.php';

			$TITLE = getItemOrder($listid, $list_type, true);

			$T = order_type($list_type);
			$order_number = getOrderNumber($listid,$T['items'],$T['order'],true);
			$ORDER = getOrder($order_number, $list_type);
			$userid = $ORDER['sales_rep_id'];
			$subtitle = 'Bill of Materials';
		} else if (! isset($_REQUEST['metaid']) AND (isset($_REQUEST['upload_listid']) OR isset($_REQUEST['listid']))) {
			if (isset($_REQUEST['upload_listid'])) { $upload_listid = $_REQUEST['upload_listid']; }
			else if (isset($_REQUEST['listid'])) { $upload_listid = $_REQUEST['listid']; }

			$query = "SELECT * FROM uploads WHERE id = '".res($upload_listid)."'; ";
			$result = qedb($query);
			if (qnum($result)>0) {
				$r = qrow($result);
				$userid = $r['userid'];
				$listid = $r['metaid'];
				if ($listid) { $new_save = false; }
				$list_label = 'metaid';
				if (! $r['processed']) { $processed = false; }
				// convert table name to list type
				$T = order_type($r['type']);
				$list_type = $T['type'];

				$TITLE = $r['filename'];
			}
		} else {
			$listid = $_REQUEST['metaid'];
			$list_label = 'metaid';

			$query = "SELECT * FROM uploads WHERE metaid = '".res($listid)."'; ";
			$result = qedb($query);
			if (qnum($result)>0) {
				$r = qrow($result);
				$TITLE = $r['filename'];
				$userid = $r['userid'];
				if (! $r['processed']) { $processed = false; }
				// convert table name to list type
				$T = order_type($r['type']);
				$list_type = $T['type'];
			} else {
				// detect type so we can extract data from searches table based on searchid in the corresponding records table (demand or availability)
				$query = "SELECT * FROM demand WHERE metaid = '".res($listid)."'; ";
				$result = qedb($query);
				if (qnum($result)>0) { $list_type = 'Demand'; } else { $list_type = 'Supply'; }
			}
		}
		if (! $processed) {
			$ALERTS[] = "Please wait while I process your list. If you do not have an email from me within 10 or 15 minutes, ".
						"you may have unorganized data in your list that I cannot handle.";
		}

		if ($list_label=='metaid') {
			$query = "SELECT * FROM search_meta WHERE id = '".res($listid)."'; ";
			$result = qedb($query);
			if (qnum($result)>0) {
				$r = qrow($result);
				$new_save = false;
				$userid = $r['userid'];
				$list_date = $r['datetime'];
				$companyid = $r['companyid'];
				$contactid = $r['contactid'];
				if ($TITLE=='Market') { $TITLE = 'Quote '.$listid; }
			}
		}
	}
	if (! $subtitle) { $subtitle = format_date($list_date,'M j, Y g:i:sa'); }

	foreach ($lines as $l => $line) {
		$F = preg_split('/[[:space:]]+/',$line);

		$search = getField($F,$col_search,$sfe);
		if ($search===false) { continue; }

		$qty = getField($F,$col_qty,$qfe);
		if (! $qty OR ! is_numeric($qty)) { $qty = 1; }

		$price = getField($F,$col_price,$pfe);
		if ($price===false) { $price = ''; }
	}


	$search_type = '';
	$field = 'id';

/*
	$rows = '';
	if ($search_type=='sl') {
	} else if ($search_type=='meta') {
		$query = "SELECT * FROM search_meta ";
	} else {
	}
	$query .= "WHERE 1 = 1 ";
	if ($userid) {
		$query .= "AND userid = '".res($userid)."' ";
	}
	if ($SEARCH) {
		$query .= "AND $field = '".res($SEARCH)."' ";
	} else {
		if ($search_type=='meta') {
			$query .= "AND (source IS NULL OR source = 'email' OR source = 'import') ";
		}
		$query .= "ORDER BY $field DESC LIMIT 0,50 ";
	}
	$query .= "; ";
	$result = qedb($query);
	while ($r = qrow($result)) {
		if (substr($r['datetime'],0,10)==$today) { $date = format_date($r['datetime'],'g:ia'); }
		else { $date = format_date($r['datetime'],'n/j/y'); }

		$user_col = '';
		if ($userid<>$U['id']) {
			$user_col = '<td>'.getUser($r['userid']).'</td>';
		}

		$rows .= '
			<tr>
				<td>'.$date.'</td>
				<td><a href="company.php?companyid='.$r['companyid'].'"><i class="fa fa-building"></i></a> '.getCompany($r['companyid']).'</td>
				'.$user_col.'
				<td class="text-right"><a href="market.php?'.$search_type.'id='.$r['id'].'"><i class="fa fa-pencil"></i></a></td>
			</tr>
		';
	}

	$market_body = '';
	if ($rows) {
		$market_body = '
		<thead>
			<tr>
				<th class="col-sm-1"></th>
				<th class="col-sm-2"></th>
				'.(($userid<>$U['id']) ? '<th class="col-sm-1"></th>' : '').'
				<th class="col-sm-1"></th>
			</tr>
		</thead>
		<tbody>
			'.$rows.'
		</tbody>
		';
	}
*/

	$users = '';
	if ($userid) {
		$users = '<option value="'.$userid.'" selected>'.getUser($userid).'</option>'.chr(10);
	} else {
		$users = '<option value="'.$U['id'].'" selected>'.getUser($U['id']).'</option>'.chr(10);
//		$users = '<option value="All" selected>- Sales Rep -</option>'.chr(10);
	}

	$MENU = array(
		'Demand'=>array('btn'=>'success','text'=>'success','bg'=>'bg-sales','icon'=>'save','name'=>'WTB/Request'),
		'Supply'=>array('btn'=>'warning','text'=>'brown','bg'=>'bg-purchases','icon'=>'save','name'=>'WTS/Offer'),
		'Service'=>array('btn'=>'purple','text'=>'purple','bg'=>'bg-services','icon'=>'save','name'=>'Services'),
		'Repair'=>array('btn'=>'info','text'=>'primary','bg'=>'bg-repairs','icon'=>'save','name'=>'Repairs'),
		'WTB'=>array('btn'=>'danger','text'=>'danger','bg'=>'','icon'=>'paper-plane','name'=>'WTB'),
		/*'PR'=>array('btn'=>'primary','text'=>'primary','bg'=>'','icon'=>'share-square','name'=>'Request'),*/
	);
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once $_SERVER["ROOT_DIR"].'/inc/scripts.php';
	?>

	<!-- any page-specific customizations -->
	<style type="text/css">
	</style>

	<link href="css/market.css?id=<?php echo $V; ?>" rel="stylesheet" />
</head>
<body>

<?php include_once $_SERVER["ROOT_DIR"].'/inc/navbar.php'; ?>

<form class="form-inline" method="POST" action="save-m2.php" enctype="multipart/form-data" id="results-form">
<input type="hidden" name="list_type" id="list_type" value="<?=$list_type;?>">
<input type="hidden" name="listid" value="<?=$listid;?>">
<input type="hidden" name="list_label" value="<?=$list_label;?>">
<input type="hidden" name="ln" value="<?=$ln;?>">

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 70px; max-height:70px;">

	<div id="task_bar" class="row <?=$MENU[$list_type]['bg'];?>" style="padding:4px">
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
			<select name="userid" size="1" class="form-control input-sm <?=($userid ? 'select2' : 'user-selector');?>" data-placeholder="- Sales Rep -" data-noreset="true">
				<?=$users;?>
			</select>
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-1 text-right col-total">
			<h3 class="text-blue" id="list_total">$ 0.00</h3>
			<span class="info">TOTAL</span>
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><?=$TITLE;?></h2>
			<span class="info"><?=$subtitle;?></span>
		</div>
		<div class="col-sm-2 col-company">
			<div>
				<select name="companyid" size="1" tabindex="'.($TI++).'" class="form-control <?=(($listid AND ($list_type=='Service' OR $list_type=='Repair')) ? 'hidden' : 'company-selector');?>" <?=(! $new_save ? 'data-noreset="true"' : '');?>>
					<?=($companyid ? '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>' : '');?>
				</select><br/>
			</div>
			<div class="margin-20">
				<div id="company_info" class="pull-right text-right" style="width:100%"></div>
			</div>
		</div>
		<div class="col-sm-1">
			<select name="contactid" id="contactid" size="1" tabindex="'.($TI++).'" class="form-control <?=(($listid AND ($list_type=='Service' OR $list_type=='Repair')) ? 'hidden' : 'contact-selector');?>" data-placeholder="- Contacts -">
				<?=($contactid ? '<option value="'.$contactid.'" selected>'.getContact($contactid).'</option>' : '');?>
			</select>
		</div>
		<div class="col-sm-2">
<?php
	$btns = '';
	$btn_sel = '';
	foreach ($MENU as $btn_type => $B) {
		$btn = '<li><a href="javascript:void(0);" class="text-'.$B['text'].'" data-btn="btn-'.$B['btn'].'" data-bg="'.$B['bg'].'" data-handler="'.$btn_type.'">'.
			'<i class="fa fa-'.$B['icon'].'"></i> '.$B['name'].'</a></li>'.chr(10);
		if ($btn_type==$list_type) {
			$btn_sel = '<button type="button" tabindex="'.($TI++).'" class="btn btn-md btn-'.$B['btn'].' btn-save"><span class="hidden-xl">'.
				'<i class="fa fa-'.$B['icon'].'"></i></span><span class="hidden-lg2"><i class="fa fa-'.$B['icon'].'"></i> '.
				$B['name'].'</span></button>';
		}
		$btns .= $btn;
	}
/*
					<button type="button" class="btn btn-md btn-success btn-save"><span class="hidden-xl"><i class="fa fa-save"></i></span><span class="hidden-lg2"><i class="fa fa-save"></i> WTB/Request</span></button>
*/
?>
			<div class="pull-right">
				<div class="btn-group settings" style="padding-right:8px">
					<?=$btn_sel;?>
					<button type="button" class="btn btn-md btn-gray dropdown-toggle" data-toggle="dropdown"><i class="fa fa-caret-down fa-lg"></i></button>
					<ul class="dropdown-menu dropdown-menu-right text-left save-menu">
						<?=$btns;?>
					</ul>
				</div>
				<br/>
				<div class="btn-group pull-right" style="padding-top:5px; padding-right:8px">
					<button class="btn btn-xs btn-default btn-response-master left" data-type="disable" type="button" title="disable & collapse" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><i class="fa fa-close"></i></button>
					<button class="btn btn-xs btn-default btn-response-master middle" data-type="minimize" type="button" title="save, minimize" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><i class="fa fa-window-minimize"></i></button>
					<button class="btn btn-xs btn-default btn-response-master middle" data-type="noreply" type="button" title="save, no reply" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><i class="fa fa-square-o"></i></button>
					<button class="btn btn-xs btn-default btn-response-master right active" data-type="reply" type="button" title="save & reply" data-toggle="tooltip" data-placement="bottom" rel="tooltip"><i class="fa fa-check-square-o"></i></button>
				</div>
			</div>
		</div>
	</div>

</div>

<div id="pad-wrapper" class="margin-bottom-220">

	<div class="table-responsive">
		<table class="table table-condensed" id="results"></table>
	</div>

</div><!-- pad-wrapper -->

</form>

<?php include_once 'modal/image.php'; ?>
<?php include_once 'modal/results.php'; ?>
<?php include_once 'modal/notes.php'; ?>
<?php include_once 'modal/parts.php'; ?>
<?php include_once 'modal/custom.php'; ?>
<?php include_once 'modal/contact.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
		companyid = '<?=$companyid;?>';
		contactid = '<?=$contactid;?>';
		listid = '<?=$listid;?>';
		list_label = '<?=$list_label;?>';
		lim = '<?=$lim;?>';
		list_type = $("#list_type").val();
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
<script src="js/m2.js?id=<?php echo $V; ?>"></script>
<script src="js/contacts.js?id=<?php echo $V; ?>"></script>

</body>
</html>
