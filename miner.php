<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRecords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCount.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFavorites.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRulesets.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/datepickers.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/dictionary.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/cmp.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';

	$report_type = 'summary';
	if (isset($_REQUEST['report_type']) AND $_REQUEST['report_type']=='detail') { $report_type = 'detail'; }

	$market_table = 'Demand';
	if (isset($_REQUEST['market_table']) AND $_REQUEST['market_table']=='Supply') { $market_table = 'Supply'; }
	else if (isset($_REQUEST['market_table']) AND $_REQUEST['market_table']=='Sale') { $market_table = 'Sale'; }

	$FILTERS = false;

	$keyword = '';
	if (isset($_REQUEST['keyword'])) { $keyword = strtoupper(trim($_REQUEST['keyword'])); $FILTERS = true; }

	$min_records = '';
	if (isset($_REQUEST['min_records'])) { $min_records = trim($_REQUEST['min_records']); $FILTERS = true; }
	$max_records = '';
	if (isset($_REQUEST['max_records'])) { $max_records = trim($_REQUEST['max_records']); $FILTERS = true; }

	$min_price = '';
	if ($_REQUEST['min_price']) { $min_price = trim($_REQUEST['min_price']); $FILTERS = true; }
	$max_price = '';
	if ($_REQUEST['max_price']) { $max_price = trim($_REQUEST['max_price']); $FILTERS = true; }

	$min_stock = false;
	if (isset($_REQUEST['min_stock']) AND ($_REQUEST['min_stock'])<>'') { $min_stock = trim($_REQUEST['min_stock']); $FILTERS = true; }
	$max_stock = false;
	if (isset($_REQUEST['max_stock']) AND ($_REQUEST['max_stock'])<>'') { $max_stock = trim($_REQUEST['max_stock']); $FILTERS = true; }

	$min_sum = false;
	if (isset($_REQUEST['min_sum']) AND ($_REQUEST['min_sum'])<>'') { $min_sum = trim($_REQUEST['min_sum']); $FILTERS = true; }
	$max_sum = false;
	if (isset($_REQUEST['max_sum']) AND ($_REQUEST['max_sum'])<>'') { $max_sum = trim($_REQUEST['max_sum']); $FILTERS = true; }

	$favorites = 0;
	if ($_REQUEST['favorites']) { $favorites = $_REQUEST['favorites']; }

	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }

	//Calculate the standard year range, output quarters as an array, and make 
	$last_week = date('m/d/Y', strtotime('-1 week', strtotime($today)));

	$range_num = '';
	$range_period = '';
	if (isset($_REQUEST['range_num']) AND isset($_REQUEST['range_period'])) {
		$range_num = trim($_REQUEST['range_num']);
		$range_period = $_REQUEST['range_period'];
	}

	if ($range_num AND $range_period) {
		$startDate = format_date($today,'m/d/Y',array($range_period=>-$range_num));
		$endDate = '';
	} else {
		$startDate = format_date($last_week, 'm/d/Y');
 		if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
			$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
		} else if ($market_table=='Demand') {
			$startDate = '';
		}

		$endDate = '';
		if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
			$endDate = format_date($_REQUEST['END_DATE'],'m/d/Y');
			$FILTERS = true;
		}
	}

	if (! $startDate AND ! $endDate AND ! $min_records AND $max_records=='' AND ! $min_price AND ! $max_price) {
		$startDate = format_date($last_week, 'm/d/Y');
		if ($market_table=='Demand') { $min_price = 1; }
	}

	// New get parameter for rulesetid
	$rulesetid = 0;
	if (isset($_REQUEST['rulesetid']) AND $_REQUEST['rulesetid']) { $rulesetid = $_REQUEST['rulesetid']; }

	$RULESET_FILTERS = array();
	
	// If this is set then get all the filter parameters 
	if($rulesetid) {
		$RULESET_FILTERS = getRuleset($rulesetid);

		// After getting them all set all the values above with the overwritten ruleset
		$keyword = $RULESET_FILTERS['keyword'];

		$startDate = format_date($RULESET_FILTERS['start_date'],'m/d/Y');
		$endDate = format_date($RULESET_FILTERS['end_date'],'m/d/Y');

		$range_num = $RULESET_FILTERS['range_num'];
		$range_period = $RULESET_FILTERS['range_period'];
		if ($range_num AND $range_period) {
			$startDate = format_date($today,'m/d/Y',array($range_period=>-$range_num));
			$endDate = '';
		}

		$min_records = $RULESET_FILTERS['min_records'];
		$max_records = $RULESET_FILTERS['max_records'];

		// Based on finding it seems if there isn't a set min_price then set it to 1.00
		$min_price = $RULESET_FILTERS['min_price'];
		$max_price = $RULESET_FILTERS['max_price'];

		$min_stock = ($RULESET_FILTERS['min_stock']?:false);
		$max_stock = ($RULESET_FILTERS['max_stock']?:false);

		$min_sum = ($RULESET_FILTERS['min_sum']?:false);
		$max_sum = ($RULESET_FILTERS['max_sum']?:false);

		// $favorites = $RULESET_FILTERS['favorites'];

		$companyid = $RULESET_FILTERS['companyid'];

		// Set filters to true as there are filters now
		$FILTERS = true;
	}

	// for getRecords()
	$record_start = $startDate;
	$record_end = $endDate;

	// global parameter for getRecords()
	$company_filter = $companyid;

	// Used top build the dropdown list
	// Passing in a ruleset id makes it so it can either be selected on the list or skipped
	function rulesetList($rulesetid){
		// Used to build the dropdown
		$rulesets = getRulesets();

		$htmlRows = '';

		foreach($rulesets as $r) {
			// class="select_ruleset"
			$htmlRows .= '
						<li style="width:250px">
								<a class="" href="/miner.php?rulesetid='.$r['id'].'" data-rulesetid="'.$r['id'].'" style="max-width:200px; overflow:hidden; text-overflow:ellipsis">'.$r['name'].'</a>
								<a href="/ruleset_actions.php?rulesetid='.$r['id'].'" class="pull-right" style="margin-top: -24px;">
									<i class="fa fa-tasks" aria-hidden="true"></i>
								</a>
						</li>
			';
		}

		return $htmlRows;
	}

	function sumQty($heci, $partid, $companyid, $date) {
		global $report_type;

		if($heci) {
			$parts = hecidb($heci);
		}

		if(! empty($parts) AND $report_type == 'summary') {
			foreach($parts as $key => $row) {
				$sum_qty += getPartQty($key, $companyid);
			}
		} else {
			$sum_qty += getPartQty($partid, $companyid);
		}

		return $sum_qty;
	}

	function getPartQty($partid, $companyid) {
		global $report_type, $market_table, $startDate, $endDate, $min_price, $max_price;

		$table = "demand";
		$qty_field = "request_qty";

		// Returned value
		$sum_qty = 0;
		$parts = array();

		// Change table to be used to grab data
		if($market_table == 'Supply') {
			$table = 'availability';
			$qty_field = "avail_qty";
		}

		$qty = 0;

		// If it is a heci then we need to use keywords to match it instead
		// $query = "SELECT * FROM keywords, parts_index, parts;";
		$query = "SELECT SUM(".res($qty_field).") as sum_qty FROM ".res($table)." d, search_meta s WHERE partid = ".res($partid)." AND metaid = s.id";

		 if($report_type == 'detail') {
			// detail shows out individual comapnies that has the part vs summary having no company and the entire part grouped together
			$query .= " AND companyid = ".res($companyid);
		}

		// Add in other used filters to also be accounted for in the totaling process
		if($startDate) {$query .= " AND s.datetime >= ".fres(format_date($startDate, 'Y-m-d H:i:s'));}
		if($endDate) {$query .= " AND s.datetime <= ".fres(format_date($endDate, 'Y-m-d H:i:s'));}

		if ($min_price AND $market_table == 'Demand'){$query .= " AND quote_price >= ".$min_price." ";}
		if ($max_price AND $market_table == 'Demand'){$query .= " AND quote_price <= ".$max_price." ";}

		if ($min_price AND $market_table == 'Supply'){$query .= " AND avail_price >= ".$min_price." ";}
		if ($max_price AND $market_table == 'Supply'){$query .= " AND avail_price <= ".$max_price." ";}

		if($report_type == 'detail') {
			$query .= " GROUP BY companyid";
		}
		// End query
		$query .= ";";

		$result = qedb($query);
		if(qnum($result)) {
			$r = qrow($result);

			$qty = $r['sum_qty'];
		}

		return $qty;
	}

	if ($favorites AND ! $FILTERS) {
		$query = "SELECT *, '1' favorite FROM favorites f, parts p ";
		$query .= "WHERE p.id = f.partid ";
		$query .= "ORDER BY p.part; ";//f.id DESC; ";
		$results = qedb($query);
	} else {
		$results = getRecords($keyword,'','csv',$market_table);
	}

	$grouped = array();
	$rows = '';
	foreach ($results as $r) {
		$partid = $r['partid'];

		if (! isset($r['favorite'])) { $r['favorite'] = ''; }

		$db = hecidb($partid,'id');
		$H = $db[$partid];

		$r['key'] = '';
		$r['primary_part'] = format_part($H['primary_part']);

		if ($H['heci']) {
			$r['key'] = substr($H['heci'],0,7);
		} else {		
			$r['key'] = $r['primary_part'];
		}

		$key = $r['key'];

		if ($report_type=='detail') {
			$key .= $r['cid'].'.'.$r['price'];
		} 

		$r['company'] = $r['name'];
		foreach ($H as $k => $v) {
			$r[$k] = $v;
		}

		$stk_qty = false;
		if (! isset($QTYS[$partid])) {
			$stk_qty = getQty($partid);
		}
		$r['stk'] = $stk_qty;

		if (isset($grouped[$key])) {
			if ($grouped[$key]['stk']===false) { $grouped[$key]['stk'] = $stk_qty; }
			else if ($stk_qty!==false AND ! isset($grouped[$key]['partids'][$partid])) { $grouped[$key]['stk'] += $stk_qty; }

			$grouped[$key]['partids'][$partid] = $partid;
		} else {
			$r['partids'] = array($partid=>$partid);
			$grouped[$key] = $r;
		}
	}

	$ord = 'date';//default
	if (isset($_REQUEST['ord'])) { $ord = $_REQUEST['ord']; }
	$dir = 'desc';
//	$dir = 'date';
	if (isset($_REQUEST['dir'])) { $dir = $_REQUEST['dir']; }

	// convert shortcuts to real field names
	if ($ord=='date') { $ord = 'datetime'; }
	else if ($ord=='descr') { $ord = 'part'; }

	uasort($grouped,$CMP($ord,$dir));

	foreach ($grouped as $key => $r) {
		$partid = $r['partid'];

		// determine if a favorite, because when filters are set, we have to circumvent normal favorites method
		if ($FILTERS) {
			// get favorites for any of the partids in this group, see grouping above
			$fav = getFavorites($r['partids'],$U['id']);
			if (count($fav)) {
				$r['favorite'] = 1;
			} else if ($favorites) {// if filter option for favorites is set, this group must have a favorite
				continue;
			}
		}

		$fav_icon = 'fa-star-o';
		if ($r['favorite']) {
			$fav_icon = 'fa-star text-danger';
		}

		// Determine the sum qty here
		$sum_qty = sumQty(substr($r['heci'],0,7), $partid, $r['cid'], $r['datetime']);

		if($min_sum AND $min_sum > $sum_qty) {
			continue;
		}

		if($max_sum AND $max_sum < $sum_qty) {
			continue;
		}

		$r['count'] = getCount($r['partids'],$startDate,$endDate,$market_table,$companyid);
		if ($r['count']<$min_records OR ($max_records<>'' AND $r['count']>$max_records)) { continue; }

		$partname = $r['primary_part'];
		if ($r['heci']) { $partname .= ' '.substr($r['heci'],0,7); }

		$aliases = '';
		foreach ($r['aliases'] as $alias) {
			if ($aliases) { $aliases .= ' '; }
			$aliases .= $alias;
		}
		if ($aliases) { $partname .= ' <small>'.$aliases.'</small>'; }
		$descr = $r['manf'];
		if ($r['system']) { $descr .= ' '.$r['system']; }
		if ($r['description']) { $descr .= ' '.$r['description']; }

		$cls = '';

		$stk_qty = $r['stk'];
		if ($min_stock!==false) {
			if ($stk_qty===false OR $stk_qty<$min_stock) { continue; }
		}
		if ($max_stock!==false) {
			if ($stk_qty>$max_stock) { continue; }
		}
		if ($stk_qty===false) { $stk_qty = '-'; }
		else if ($stk_qty>0) { $cls = 'in-stock'; }

		$company_col = '';
		if ($report_type=='detail') {
			$company_col = '<td><a href="profile.php?companyid='.$r['cid'].'"><i class="fa fa-building"></i></a> '.$r['company'].'</td>';
		}

		$rows .= '
				<tr class="'.$cls.'">
					<td>
						<i class="fa '.$fav_icon.'"></i>
					</td>
					<td>
						<strong>'.$stk_qty.'</strong>
					</td>
					'.$company_col.'
					<td>
						'.$partname.'<br/>
						<span class="info"><small>'.dictionary($descr).'</small></span>
					</td>
					<td>
						'.format_date($r['datetime'],'M j, Y').'
					</td>
					<td>'.$r['count'].'</td>
					<td>'.$sum_qty.'</td>
					<td>'.getRep($r['userid']).'</td>
					<td class="text-right">'.format_price($r['price']).'</td>
					<td class="text-center">
						<input type="checkbox" name="searches[]" class="item-check" value="'.$r['key'].'" checked>
					</td>
				</tr>
		';
	}

	$TITLE = 'Miner';
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
		.input-group.datepicker-date {
			width:110px;
			min-width:110px;
			max-width:110px;
		}
		.input-group.datepicker-date .input-sm {
			padding-left:3px;
			padding-right:3px;
		}
		.input-group .input-group-addon {
			padding: 2px 4px;
		}
		.company-select2 .select2-container {
			width:150px !important;
		}
	</style>
</head>
<body>

<?php include_once 'inc/navbar.php'; ?>
<?php include_once 'modal/ruleset.php'; ?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:76px;">
	<form class="form-inline" method="get" action="miner.php" enctype="multipart/form-data" id="filters-form" >
	<input type="hidden" name="ord" value="<?=$ord;?>" id="ord">
	<input type="hidden" name="dir" value="<?=$dir;?>" id="dir">

	<div class="row" style="padding:8px">
		<div class="col-sm-1 colm-sm-0-5">
				    <div class="btn-group">
						<button class="btn btn-xs left btn-radio <?= ($report_type=='summary' ? 'active btn-primary' : ''); ?>" type="submit" data-value="summary" data-toggle="tooltip" data-placement="bottom" title="most requested">
							<i class="fa fa-sort-numeric-desc"></i>	
						</button>
						<input type="radio" name="report_type" value="summary" class="hidden"<?php if ($report_type=='summary') { echo ' checked'; } ?>>

						<button class="btn btn-xs right btn-radio <?= ($report_type=='detail' ? 'active btn-primary' : ''); ?>" type="submit" data-value="detail" data-toggle="tooltip" data-placement="bottom" title="most recent">
							<i class="fa fa-history"></i>	
						</button>
						<input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>
					</div>
					<br/>
					<input name="favorites" value="1" class="hidden" type="checkbox"<?=($favorites ? ' checked' : '');?>>
					<button type="button" class="btn btn-xs btn-favorites btn-<?=($favorites ? 'danger' : 'default');?>" title="Favorites" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-star"></i></button>
		</div>
		<div class="col-sm-1 colm-sm-0-5 nopadding-right nopadding-left">
			<select name="market_table" size="1" class="select2 form-control">
				<option value="Supply" <?= ($market_table=='Supply' ? 'selected' : ''); ?>>Supply</option>
				<option value="Demand" <?= ($market_table=='Demand' ? 'selected' : ''); ?>>Demand</option>
				<option value="Sale" <?= ($market_table=='Sale' ? 'selected' : ''); ?>>Sales</option>
			</select>
<!--
			<div class="btn-group">
		        <button class="btn btn-xs left btn-radio btn-warning <?= ($market_table=='Supply' ? 'active' : ''); ?>" type="submit" data-value="Supply">
		        	<small>Spply</small>
		        </button>
				<input type="radio" name="market_table" value="Supply" class="hidden"<?php if ($market_table=='Supply') { echo ' checked'; } ?>>
		        <button class="btn btn-xs right btn-radio btn-success <?= ($market_table=='Demand' ? 'active' : ''); ?>" type="submit" data-value="Demand">
		        	<small>Demnd</small>
		        </button>
		        <input type="radio" name="market_table" value="Demand" class="hidden"<?php if ($market_table=='Demand') { echo ' checked'; } ?>>
		        <button class="btn btn-xs right btn-radio btn-success <?= ($market_table=='Sale' ? 'active' : ''); ?>" type="submit" data-value="Sale">
		        	<small>Sales</small>
		        </button>
		        <input type="radio" name="market_table" value="Sale" class="hidden"<?php if ($market_table=='Sale') { echo ' checked'; } ?>>
		    </div>
-->
		</div>
		<div class="col-sm-3">
			<span class="info pull-right" style="margin-top:6px">or</span>
			<?=datepickers($startDate,$endDate);?>
		</div>
		<div class="col-sm-1 nopadding-left">
			<input type="text" name="range_num" value="<?=$range_num;?>" class="form-control input-sm date-class" data-class="2" style="width:30%">
			<select name="range_period" class="select2 form-control" style="padding-top:3px; width:50%">
				<option value="d" <?= ($range_period=='d' ? 'selected' : ''); ?>>Days</option>
				<option value="m" <?= (($range_period=='m' OR ! $range_period) ? 'selected' : ''); ?>>Months</option>
			</select>
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><img src="img/pickaxe.png" style="width:24px; vertical-align:top; margin-top:4px" /> 
				<?php echo $TITLE; ?>
				<span class="dropdown">
					<a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-caret-down"></i></a>
					<ul class="dropdown-menu text-left">
						<?= rulesetList($rulesetid); ?>
						<li>
							<a data-toggle="modal" href="#modal-ruleset"><i class="fa fa-plus"></i> Save Ruleset</a>
						</li>
					</ul>
				</span>
			</h2>
			<span class="info"></span>
		</div>

		<div class="col-sm-1">
			<div class="input-group">
				<input type="text" name="keyword" value="<?= $keyword; ?>" class="form-control input-sm" placeholder="Keyword...">
				<span class="input-group-btn">
					<button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-filter"></i></button>
				</span>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="dropdown show">
				<a class="btn btn-sm btn-default dropdown-toggle" href="#" role="button" id="dropdownFilter" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Qty Filters <i class="fa fa-icon fa-caret-down"></i>
				</a>

				<div class="dropdown-menu filter-dropdown-menu" aria-labelledby="dropdownFilter">
					<div class="bg-repairs" style="padding: 10px 15px;">
						<div class="input-group">
							<input type="text" name="min_records" class="form-control input-sm" value="<?= $min_records; ?>" placeholder = "0"/>
							<span class="input-group-addon">-</span>
							<input type="text" name="max_records" class="form-control input-sm" value="<?= $max_records; ?>" placeholder = "9999"/>
						</div>
						<span class="info text-center" style="display: block;">Hit Count</span>
					</div>
					<!-- <BR> -->
					<div class="bg-sales" style="padding: 10px 15px;">
						<div class="input-group">
							<input type="text" name="min_price" id="min_price" class="form-control input-sm" value="<?= ($min_price<>'' ? format_price($min_price, false, '', true) : ''); ?>" placeholder = "0.00"/>
							<span class="input-group-addon">-</span>
							<input type="text" name="max_price" id="max_price" class="form-control input-sm" value="<?= ($max_price<>'' ? format_price($max_price, false, '', true) : ''); ?>" placeholder = "99.00"/>
						</div>
						<span class="info text-center" style="display: block;">Price Range</span>
					</div>
					<!-- <BR> -->
					<div class="bg-purchases" style="padding: 10px 15px;">
						<div class="input-group">
							<input type="text" name="min_stock" id="min_stock" class="form-control input-sm" value="<?= ($min_stock!==false ? $min_stock : ''); ?>" placeholder = "0"/>
							<span class="input-group-addon">-</span>
							<input type="text" name="max_stock" id="max_stock" class="form-control input-sm" value="<?= ($max_stock!==false ? $max_stock : ''); ?>" placeholder = "9999"/>
						</div>
						<span class="info text-center" style="display: block;">Stock Qty</span>
					</div>
					<!-- <BR> -->
					<div class="" style="padding: 10px 15px;">
						<div class="input-group">
							<input type="text" name="min_sum" id="min_stock" class="form-control input-sm" value="<?= ($min_sum!==false ? $min_sum : ''); ?>" placeholder = "0"/>
							<span class="input-group-addon">-</span>
							<input type="text" name="max_sum" id="max_stock" class="form-control input-sm" value="<?= ($max_sum!==false ? $max_sum : ''); ?>" placeholder = "9999"/>
						</div>
						<span class="info text-center" style="display: block;">Qty</span>
					</div>
				</div>
			</div>
		</div>
		<div class="col-sm-2">
			<div class="row">
				<div class="col-sm-10">
					<div class="input-group company-select2">
						<select name="companyid" id="companyid" class="company-selector">
							<?= ($companyid ? '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'.chr(10) : ''); ?>
						</select>
						<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
					</div>
				</div>
				<div class="col-sm-2">
					<div class="dropdown pull-right">
						<button class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fa fa-chevron-down"></i></button>
						<ul class="dropdown-menu pull-right text-left" role="menu">
							<li><a href="javascript:void(0);" class="btn-download"><i class="fa fa-share-square-o"></i> Export to CSV</a></li>
							<li><a href="javascript:void(0);" class="btn-market"><i class="fa fa-cubes"></i> Open in Market</a></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
<form class="form-inline" method="POST" action="market.php" enctype="multipart/form-data" id="form_submit">
<textarea id="search_text" name="s2" class="hidden"></textarea>

	<div class="table-responsive">
		<table class="table table-condensed table-hover table-striped table-items">
			<thead>
				<tr>
					<th class="col-sm-1 colm-sm-0-5">
						<span class="line"></span>
						Fav
					</th>
					<th class="col-sm-1 colm-sm-0-5">
						<span class="line"></span>
						Stk <a href="javascript:void(0);" class="sorter" data-ord="stk" data-dir="<?= (($ord=='stk' AND $dir=='desc') ? 'asc"><i class="fa fa-sort-numeric-asc"></i>' : 'desc"><i class="fa fa-sort-numeric-desc"></i>'); ?></a>
					</th>
					<?php
						if ($report_type=='detail') {
							$cdir = 'asc';
							if ($ord=='company' AND $dir=='asc') { $cdir = 'desc'; }

							echo '<th class="col-sm-2"><span class="line"></span>Company <a href="javascript:void(0);" class="sorter" data-ord="company" data-dir="'.$cdir.'"><i class="fa fa-sort-alpha-'.$cdir.'"></i></a></th>';
						}
					?>
					<th class="col-sm-<?= ($report_type=='detail' ? '5' : '7'); ?>">
						<span class="line"></span>
						Description <a href="javascript:void(0);" class="sorter" data-ord="descr" data-dir="<?= (($ord=='part' AND $dir=='asc') ? 'desc"><i class="fa fa-sort-alpha-desc"></i>' : 'asc"><i class="fa fa-sort-alpha-asc"></i>'); ?></a>
					</th>
					<th class="col-sm-1">
						<span class="line"></span>
						Date <a href="javascript:void(0);" class="sorter" data-ord="date" data-dir="<?= (($ord=='datetime' AND $dir=='desc') ? 'asc"><i class="fa fa-sort-amount-asc"></i>' : 'desc"><i class="fa fa-sort-amount-desc"></i>'); ?></a>
					</th>
					<th class="col-sm-1">
						<span class="line"></span>
						# <?= $market_table; ?>
					</th>
					<th class="col-sm-1">
						<span class="line"></span>
						<?=$market_table;?> Qty
					</th>
					<th class="col-sm-1">
						<span class="line"></span>
						Rep
					</th>
					<th class="col-sm-1 colm-sm-0-5">
						<span class="line"></span>
						Price
					</th>
					<th class="col-sm-1 colm-sm-0-5 text-center">
						<span class="line"></span>
						<input type="checkbox" class="checkAll" value="" checked>
					</th>
				</tr>
			</thead>
			<tbody>
				<?=$rows;?>
			</tbody>
		</table>
	</div>

</form>
</div>
<!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
		$('.btn-favorites').on('click',function() {
			setFavOptions();
		});
		setFavOptions();

		$('.sorter').click(function() {
			$("#ord").val($(this).data('ord'));
			$("#dir").val($(this).data('dir'));
			$("#filters-form").submit();
		});
		$('.btn-report').click(function() {
			var btnValue = $(this).data('value');
			$(this).closest("div").find("input[type=radio]").each(function() {
				if ($(this).val()==btnValue) { $(this).attr('checked',true); }
			});
		});
		$('.btn-market').click(function() {
			var s = '';
			$(".item-check:checked").each(function() {
				s += $(this).val()+"\n";
			});
			$("#search_text").val(s);
			$("#search_text").closest("form").submit();
		});
		$(".btn-download").on("click",function() {
			$("#form_submit").prop('action','downloads/inventory-export-<?=$today;?>.csv');
			$("#form_submit").submit();
		});

		$('.date-class').change(function() {
			var date_class = $(this).data('class');
			$('.date-class').each(function() {
				if ($(this).data('class')!=date_class) { $(this).val(''); }
			});
		});

		// Prevent closing of the bootstrap dropdown menu when clicking on elements inside the dropdown
		$('.filter-dropdown-menu').on("click.bs.dropdown", function (e) { 
			e.stopPropagation(); 
			e.preventDefault(); 
		});
	});

	$('input[name="keyword"]').keypress(function(e) {
		if(e.which == 13) {
			let value = $('input[name="report_type"]:checked').val();

			var input = $("<input>").attr("type", "hidden").attr("name", 'report_type').val(value);
			$('#filters-form').append(input);
		}
	});

	$('#modal-ruleset').on('shown.bs.modal', function (e) {
		// First empty the modal sections
		$('#ruleset_inputs').empty();

		// Get all the input values from the miner table header and make hidden inputs on the modal to have it generated
		$('#filters-form input').each(function(){
			var name = $(this).attr('name');
			var value = $(this).val();

			var input = $("<input>").attr("type", "hidden").attr("name", name).val(value);

			$('#ruleset_inputs').append(input);
		});

		// Add in the company select2
		// But making it into a flat input hidden
		var companyid = $("#filters-form #companyid").val();
		
		var input = $("<input>").attr("type", "hidden").attr("name", "companyid").val(companyid);

		$('#ruleset_inputs').append(input);
	});
	
	$('.select_ruleset').click(function(e) {
		e.preventDefault();

		var rulesetid = $(this).data('rulesetid');
		var input = $("<input>").attr("type", "hidden").attr("name", "rulesetid").val(rulesetid);
		//console.log(input);
		$(this).closest('form').append($(input));

		$(this).closest('form').submit();
	});

	function setFavOptions() {
		var favorites = $("#filters-form").find("input[name=favorites]").prop('checked');
		$("#companyid").prop('disabled',favorites);
		$("#min_price").prop('disabled',favorites);
		$("#max_price").prop('disabled',favorites);
	}
</script>

</body>
</html>
