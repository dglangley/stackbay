<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/calcQuarters.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/dictionary.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/cmp.php';

	// Formatting
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';

	// Getters
	include_once $_SERVER["ROOT_DIR"].'/inc/getRulesets.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRecords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCount.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFavorites.php';

	$DEBUG = 0;

	$record_start = '';
	$record_end = '';
	$company_filter = '';
	$sales_min = '';
	$sales_max = '';
	$min_price = false;
	$max_price = false;

	$favorites = 0;

	function getRulesetActions($rulesetid) {
		$actions = array();

		$query = "SELECT * FROM ruleset_actions WHERE rulesetid = ".res($rulesetid)." LIMIT 1;";
		$result = qedb($query);

		// For now set it to only 1 result
		while($r = mysqli_fetch_assoc($result)) {
			$actions = $r;
		}

		return $actions;
	}

	function generateEmail($options) {

	}

	function getMinerData($RULESET_FILTERS) {
		global $favorites, $record_start, $record_end, $company_filter,$sales_min,$sales_max,$min_price,$max_price, $CMP, $QTYS;

		$FILTERS = true;

		$report_type = 'summary';
		$market_table = 'Demand';

		$data = array();

		if($RULESET_FILTERS['market']) {
			$market_table = $RULESET_FILTERS['market'];
		}

		$keyword = $RULESET_FILTERS['keyword'];

		$startDate = format_date($RULESET_FILTERS['start_date'],'m/d/Y');
		$endDate = format_date($RULESET_FILTERS['end_date'],'m/d/Y');

		$min_records = $RULESET_FILTERS['min_records'];
		$max_records = $RULESET_FILTERS['max_records'];

		// Based on finding it seems if there isn't a set min_price then set it to 1.00
		$min_price = ($RULESET_FILTERS['min_price'] ?: 1.00);
		$max_price = $RULESET_FILTERS['max_price'];

		$min_stock = ($RULESET_FILTERS['min_stock']?:false);
		$max_stock = ($RULESET_FILTERS['max_stock']?:false);

		// $favorites = $RULESET_FILTERS['favorites'];

		$companyid = $RULESET_FILTERS['companyid'];

		// These are global
		$record_start = $startDate;
		$record_end = $endDate;

		$company_filter = $companyid;

		$results = getRecords($keyword,'','csv',$market_table);

		// print '<pre>' . print_r($results, true) . '</pre>';

		$grouped = array();
		$rows = '';

		foreach ($results as $r) {
			$partid = $r['partid'];

			if (! isset($r['favorite'])) { $r['favorite'] = ''; }

			$db = hecidb($partid,'id');
			$H = $db[$partid];

			$r['key'] = '';
			if ($H['heci']) {
				$r['key'] = substr($H['heci'],0,7);
			} else {
				$r['primary_part'] = format_part($H['primary_part']);
				$r['key'] = $r['primary_part'];
			}

			if ($report_type=='detail') {
				$key = $r['cid'].'.'.$partid;
			} else {
				$key = $r['key'];
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

			// echo $stk_qty . '<BR>';

			if (isset($grouped[$key])) {
				if ($grouped[$key]['stk']===false) { $grouped[$key]['stk'] = $stk_qty; }
				else if ($stk_qty!==false) { $grouped[$key]['stk'] += $stk_qty; }

				$grouped[$key]['partids'][$partid] = $partid;
			} else {
				$r['partids'] = array($partid=>$partid);
				$grouped[$key] = $r;
			}
		}

		$ord = 'datetime';//default
		$dir = 'desc';

		uasort($grouped,$CMP($ord,$dir));

		foreach ($grouped as $key => $r) {
			$partid = $r['partid'];
	
			// determine if a favorite, because when filters are set, we have to circumvent normal favorites method
			if ($FILTERS) {
				// get favorites for any of the partids in this group, see grouping above
				$fav = getFavorites($r['partids']);
				if (count($fav)) {
					$r['favorite'] = 1;
				} else if ($favorites) {// if filter option for favorites is set, this group must have a favorite
					continue;
				}
			}
	
			$fav = 'fa-star-o';
			if ($r['favorite']) {
				$fav = 'fa-star text-danger';
			} else {
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
			// print '<pre>' . print_r($r, true) . '</pre>';

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

			// print '<pre>' . print_r($r, true) . '</pre>';
	
			$rows .= '
					<tr class="'.$cls.'">
						<td>
							<i class="fa '.$fav.'"></i>
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
						<td>'.getRep($r['userid']).'</td>
						<td class="text-right">'.format_price($r['price']).'</td>
						<td class="text-center">
							<input type="checkbox" name="searches[]" class="item-check" value="'.$r['key'].'" checked>
						</td>
					</tr>
			';
		}

		// print_r($rows);

		return $rows;
	}

	$rulesets = getRulesets();

	$actions = array();

	$htmlRows = '';

	foreach($rulesets as $ruleset) {
		$action = getRulesetActions($ruleset['id']);

		$htmlRows = getMinerData($ruleset);

		break;

		if($action['action'] == 'email') {
			generateEmail($action);
		}
	}

	$TITLE = 'Test';

?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>

	<!-- any page-specific customizations -->
	<style type="text/css">
	</style>
</head>
<body>

<div id="pad-wrapper">
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
						Stk <a href="javascript:void(0);" class="sorter" data-ord="stk" data-dir="desc"><i class="fa fa-sort-numeric-desc"></i></a>
					</th>
										<th class="col-sm-7">
						<span class="line"></span>
						Description <a href="javascript:void(0);" class="sorter" data-ord="descr" data-dir="asc"><i class="fa fa-sort-alpha-asc"></i></a>
					</th>
					<th class="col-sm-1">
						<span class="line"></span>
						Date <a href="javascript:void(0);" class="sorter" data-ord="date" data-dir="asc"><i class="fa fa-sort-amount-asc"></i></a>
					</th>
					<th class="col-sm-1">
						<span class="line"></span>
						# Demand					</th>
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
						<input type="checkbox" class="checkAll" value="" checked="">
					</th>
				</tr>
			</thead>
			<tbody>
				<?= $htmlRows; ?>
			</tbody>
		</table>
	</div>
</div><!-- pad-wrapper -->

</body>
</html>