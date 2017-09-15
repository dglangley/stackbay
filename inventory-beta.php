<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/calcQuarters.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getLocation.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCondition.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

/***** DAVID *****/
/*
To do:
1) Incoming PO's as top, italicized lines
2) Action column functionality
3) What to do with customer property? In repair?
6) Serial results should show part# in multiple-select dropdown, with a filter on Serial that can be cleared to reveal all part results
*/

	function getSource($id,$order_type='Purchase') {
		if (! $id) { return false; }

		$T = order_type('Purchase');

		$query = "SELECT ".$T['order']." order_number FROM ".$T['items']." WHERE id = '".res($id)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			return false;
		}
		$r = mysqli_fetch_assoc($result);
		return ($r['order_number']);
	}

	function getCompanyID($order_number,$order_type='Purchase') {
		if (! $order_number) { return false; }

		$T = order_type('Purchase');

		$query = "SELECT companyid FROM ".$T['orders']." WHERE ".$T['order']." = '".res($order_number)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			return false;
		}
		$r = mysqli_fetch_assoc($result);
		return ($r['companyid']);
	}

	$search = '';
	if (isset($_REQUEST['s']) AND trim($_REQUEST['s'])) { $search = trim($_REQUEST['s']); }
	else if (isset($_REQUEST['s2']) AND trim($_REQUEST['s2'])) { $search = trim($_REQUEST['s2']); }
	else if (isset($_REQUEST['search']) AND trim($_REQUEST['search'])) { $search = trim($_REQUEST['search']); }
	$_REQUEST['s'] = '';

	$locationid = 0;
	if (isset($_REQUEST['locationid']) AND $_REQUEST['locationid']>0) { $locationid = trim($_REQUEST['locationid']); }

	$companyid = 0;
	if (isset($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) { $companyid = trim($_REQUEST['companyid']); }

	$expiry = time() + (7 * 24 * 60 * 60);
	$past_time = time() - 1000;
	$summary = '';
	$detail = '';
	if (isset($_REQUEST['inventory-summary'])) {
		$summary = $_REQUEST['inventory-summary'];
		setcookie('inventory-summary',$_REQUEST['inventory-summary'],$expiry);
		setcookie('inventory-detail',false,$past_time);
	} else if (isset($_REQUEST['inventory-detail'])) {
		$detail = $_REQUEST['inventory-detail'];
		setcookie('inventory-detail',$_REQUEST['inventory-detail'],$expiry);
		setcookie('inventory-summary',false,$past_time);
	} else {
		if (isset($_COOKIE['inventory-summary'])) { $summary = $_COOKIE['inventory-summary']; }
		if (isset($_COOKIE['inventory-detail'])) { $detail = $_COOKIE['inventory-detail']; }
	}

	$order_search = '';
	if (isset($_REQUEST['order_search']) AND trim($_REQUEST['order_search'])) { $order_search = trim($_REQUEST['order_search']); }

	$startDate = '';
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}
	$endDate = date('m/d/Y');
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}
	if ($startDate) {
		$dbStartDate = format_date($startDate, 'Y-m-d').' 00:00:00';
		$dbEndDate = format_date($endDate, 'Y-m-d').' 23:59:59';
	}

	$goodstock_text = ' text-warning';
	$badstock_text = ' text-purple';
	$outstock_text = ' text-danger';
	$goodstock_btn = 'default';
	$badstock_btn = 'default';
	$outstock_btn = 'default';
	if (isset($_REQUEST['btn-goodstock'])) {
		if ($_REQUEST['btn-goodstock']==1) {
			$goodstock = 1;
		} else {
			$goodstock = 0;
		}
	} else if (isset($_COOKIE['goodstock'])) {
		$goodstock = $_COOKIE['goodstock'];
	}

	if (isset($_REQUEST['btn-badstock'])) {
		if ($_REQUEST['btn-badstock']) {
			$badstock = 1;
		} else {
			$badstock = 0;
		}
	} else if (isset($_COOKIE['badstock'])) {
		$badstock = $_COOKIE['badstock'];
	}

	if (isset($_REQUEST['btn-outstock'])) {
		if ($_REQUEST['btn-outstock']) {
			$outstock = 1;
		} else {
			$outstock = 0;
		}
	} else if (isset($_COOKIE['outstock'])) {
		$outstock = $_COOKIE['outstock'];
	}

	// if selected, or if no buttons selected, select good stock by default
	if ($goodstock OR (! $goodstock AND ! $badstock AND ! $outstock)) {
		$goodstock = 1;
		setcookie('goodstock',$goodstock,$expiry);
	} else {
		$goodstock_btn = 'default';
		setcookie('goodstock',$goodstock,$past_time);
	}
	if ($badstock) {
		setcookie('badstock',$badstock,$expiry);
	} else {
		$badstock_btn = 'default';
		setcookie('badstock',$badstock,$past_time);
	}
	if ($outstock) {
		setcookie('outstock',$outstock,$expiry);
	} else {
		$outstock_btn = 'default';
		setcookie('outstock',$outstock,$past_time);
	}


	/***** DON'T MOVE THIS CODE: strategically placed so we can activate stock buttons when user is looking for ORDER RESULTS *****/

	// get all purchase_item_id, returns_item_id, repair_item_id and sales_item_id from respective orders matching $order_search
	$ids = array('purchase_item_id'=>array(),'returns_item_id'=>array(),'repair_item_id'=>array(),'sales_item_id'=>array());
	$order_matches = 0;
	if ($order_search OR $companyid) {
		$goodstock = 1;
		$badstock = 1;
		$outstock = 1;

		$case_types = array('Purchase','Sale','Return','Repair');
		foreach ($case_types as $order_type) {
			$T = order_type($order_type);

			$query = "SELECT items.id FROM ".$T['items']." items ";
			if ($companyid) { $query .= ", ".$T['orders']." orders "; }
			$query .= "WHERE 1 = 1 ";
			if ($order_search) { $query .= "AND items.".$T['order']." = '".res($order_search)."' "; }
			if ($companyid) { $query .= "AND items.".$T['order']." = orders.".$T['order']." AND orders.companyid = '".res($companyid)."' "; }
			$query .= "; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			while ($r = mysqli_fetch_assoc($result)) {
				$ids[$T['inventory_label']][] = $r['id'];
				$order_matches++;
			}
		}
	}

	/***** END DONT MOVE *****/


	$part_options = '';
	$part_str = '';
	$partids = array();
	$partids_csv = '';
	$qtys = array();
	$inv_rows = '';
	$serial_match = array();//when set, is keyed by partid so results on a given partid only show the discovered serial ($search)
	if ($search) {
		$results = hecidb($search);
		foreach ($results as $partid => $P) {
			// gather unique list of partids
			$partids[$partid] = $P;

			if ($partids_csv) { $partids_csv .= ','; }
			$partids_csv .= $partid;
		}

		$query = "SELECT * FROM inventory WHERE serial_no = '".res($search)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$goodstock = 1;
			$badstock = 1;
			$outstock = 1;
			$detail = 1;
			$summary = 0;
		}
		while ($r = mysqli_fetch_assoc($result)) {
			if (! isset($partids[$r['partid']])) {
				$P = hecidb($r['partid'],'id');
				$partids[$r['partid']] = $P[$r['partid']];

				if ($partids_csv) { $partids_csv .= ','; }
				$partids_csv .= $r['partid'];
			}
			$serial_match[$r['partid']] = $r['serial_no'];
		}
	}

	// style settings for summary/detail buttons
	$summary_btn = 'default';
	$detail_btn = 'default';
	if ($summary) {
		$summary_btn = 'primary active';
	} else if ($detail) {
		$detail_btn = 'primary active';
	}

	// placed separately here for purposes of single-user overrides (such as in $order_search) instead of saving cookies
	if ($goodstock) {
		$goodstock_btn = 'warning active';
		$goodstock_text = '';
	}
	if ($badstock) {
		$badstock_btn = 'purple active';
		$badstock_text = '';
	}
	if ($outstock) {
		$outstock_btn = 'danger active';
		$outstock_text = '';
	}

	$records = array();
	if ($partids_csv OR $locationid OR $order_matches OR ($dbStartDate AND $dbEndDate)) {
		$query = "SELECT i.* FROM inventory i ";
		if ($order_matches>0) {
			$query .= ", inventory_history h ";
		}
		$query .= "WHERE 1 = 1 ";
		if ($partids_csv) { $query .= "AND i.partid IN (".$partids_csv.") "; }
		if ($locationid) { $query .= "AND i.locationid = '".res($locationid)."' "; }
		if ($order_matches>0) {
			$query .= "AND h.invid = i.id ";
			$subquery = "";
			foreach ($ids as $item_label => $arr) {
				if (count($arr)==0) { continue; }
	
				foreach ($arr as $item_id) {
					if ($subquery) { $subquery .= "OR "; }
					$subquery .= "(h.field_changed = '".$item_label."' AND h.value = '".$item_id."') ";
				}
			}
			if ($subquery) { $query .= "AND (".$subquery.") "; }
		} else {
/*
			if (! $outstock) {
				$query .= "AND (i.status = 'shelved' OR i.status = 'received') ";
				if (! $badstock AND $goodstock) { $query .= "AND i.conditionid > 0 "; }
				if (! $goodstock AND $badstock) { $query .= "AND i.conditionid < 0 "; }
			}
*/
		}
		if ($dbStartDate AND $dbEndDate) {
			$query .= "AND i.date_created BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
		}
		$query .= "ORDER BY IF(status='shelved' OR status='received',0,1), IF(conditionid>0,0,1), date_created DESC; ";
//		echo $query.'<BR>';
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			if (isset($serial_match[$r['partid']]) AND $serial_match[$r['partid']]<>$r['serial_no']) { continue; }

			$key = $r['partid'].'.'.$r['locationid'].'.'.$r['conditionid'].'.'.$r['status'].'.'.$r['purchase_item_id'].'.'.substr($r['date_created'],0,10);

			// gather unique list of partids
			if (! isset($partids[$r['partid']])) {
				$H = hecidb($r['partid'],'id');
				$partids[$r['partid']] = $H[$r['partid']];
			}

			$qty = $r['qty'];
			if ($r['serial_no']) { $qty = 1; }

			if (! isset($records[$key])) {
				$r['qty'] = 0;
				$r['entries'] = array();
				$records[$key] = $r;
			}
			$records[$key]['qty'] += $qty;
			$records[$key]['entries'][] = array('serial_no'=>$r['serial_no'],'notes'=>$r['notes'],'id'=>$r['id']);
		}
	}

	$inner_display = ' style="display:none"';
	if ($detail) {
		$inner_display = '';
	}

	// displayed only on first occurrence of a nested/inner table
	$inner_header = '
					<tr class="inner-result"'.$inner_display.'>
						<th class="col-sm-3">Serial</th>
						<th class="col-sm-4">History</th>
						<th class="col-sm-4">Notes</th>
						<th class="col-sm-1">Action</th>
					</tr>
	';

	$goodcount = 0;
	$badcount = 0;
	$outcount = 0;
	$j = 0;
	foreach ($records as $r) {
		$prefix = '';
		$order_number = getSource($r['purchase_item_id'],'Purchase');
		$order_ln = '';

		if ($r['conditionid']>0 AND ($r['status']=='shelved' OR $r['status']=='received')) { $goodcount += $r['qty']; }
		if ($r['conditionid']<0 AND ($r['status']=='shelved' OR $r['status']=='received')) { $badcount += $r['qty']; }
		if ($r['status']<>'shelved' AND $r['status']<>'received') { $outcount += $r['qty']; }

		// exclude results that the user hasn't included
		if (! $goodstock AND $r['conditionid']>0) { continue; }
		if (! $badstock AND $r['conditionid']<0) { continue; }
		if (! $outstock AND ($r['status']<>'shelved' AND $r['status']<>'received')) { continue; }

		if (! isset($qtys[$r['partid']])) { $qtys[$r['partid']] = 0; }
		$qtys[$r['partid']] += $r['qty'];

		$company = '';
		$company_ln = '';
		if ($order_number) {
			$prefix = 'PO';
			$order_ln = ' <a href="/'.$prefix.$order_number.'" target="_new"><i class="fa fa-arrow-right"></i></a>';
			$cid = getCompanyID($order_number,'Purchase');
			$company = getCompany($cid);
			$company_ln = ' <a href="/profile.php?companyid='.$cid.'" target="_new"><i class="fa fa-book"></i></a>';
		}

		$cls = '';
		if ($r['status']=='shelved' OR $r['status']=='received') {
			if ($r['conditionid']>0) {
				$cls = 'in-stock';
			} else {
				$cls = 'bad-stock';
			}
		} else {
			$cls = 'out-stock';
		}

		if ($r['status']=='shelved' OR $r['status']=='received') { $qty = $r['qty']; }
		else { $qty = '0 <span class="info">('.$r['qty'].')</span>'; }

		$inv_rows .= '
		<tr class="valign-top '.$cls.'" data-partid="'.$r['partid'].'" data-role="summary" data-row="'.$j.'">
			<td>'.getLocation($r['locationid']).'</td>
			<td>
				<div class="qty results-toggler">'.$qty.'</div>
			</td>
			<td>'.getCondition($r['conditionid']).'</td>
			<td>'.$prefix.$order_number.$order_ln.'</td>
			<td>'.$company.$company_ln.'</td>
			<td>'.format_date($r['date_created'],'n/j/y').'</td>
			<td></td>
			<td class="text-center">
				<input type="checkbox" name="partid[]" value="'.$r['partid'].'" class="item-check checkInner" checked>
				<a href="javascript:void(0);" class="results-toggler"><i class="fa fa-list-ol"></i><sup><i class="fa fa-sort-desc"></i></sup></a>
				<div class="dropdown">
					<a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-chevron-down"></i></a>
					<ul class="dropdown-menu pull-right text-left" role="menu">
						<li><a href="javascript:void(0);"><i class="fa fa-pencil"></i> Edit group</i></a></li>
					</ul>
				</div>
			</td>
		</tr>
		<tr class="inner-result" data-partid="'.$r['partid'].'" data-role="inner" data-row="'.$j.'"'.$inner_display.'>
			<td colspan="8" class="text-center">
				<table class="table table-condensed table-results text-left">
		';

		// repair link used for each serial
		$repair_ln = '';
		if ($r['status']=='shelved' OR $r['status']=='received') {
			$repair_ln = '<li><a href="javascript:void(0);"><i class="fa fa-wrench"></i> Send to Repair</i></a></li>';
		}

		$inners = '';
		foreach ($r['entries'] as $entry) {
			$inners .= $inner_header.'
					<tr class="">
						<td class="col-sm-3">'.$entry['serial_no'].'</td>
						<td class="col-sm-4"></td>
						<td class="col-sm-4">'.$entry['notes'].'</td>
						<td class="col-sm-1 text-right">
							<input type="checkbox" name="inventoryid[]" value="'.$entry['id'].'" class="item-check" checked>
							<div class="dropdown" data-inventoryid="'.$entry['id'].'">
								<a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-chevron-down"></i></a>
								<ul class="dropdown-menu pull-right text-left">
									<li><a href="javascript:void(0);" data-id="'.$entry['id'].'" class="btn-history"><i class="fa fa-history"></i> History</i></a></li>
									'.$repair_ln.'
									<li><a href="javascript:void(0);" class="edit-inventory"><i class="fa fa-pencil"></i> Edit this entry</i></a></li>
								</ul>
							</div>
						</td>
					</tr>
			';

			$inner_header = '';
		}

		$inv_rows .= $inners.'
				</table>
			</td>
		</tr>
		';
		$j++;
	}

	foreach ($partids as $partid => $P) {
		$part_str = trim($P['part'].' '.$P['heci']);

		$qty = 0;
		if (isset($qtys[$partid]) AND $qtys[$partid]>0) { $qty = $qtys[$partid]; }
		$part_options .= '<option value="'.$partid.'">Qty '.$qty.'- '.$P['part'].' '.$P['heci'].'</option>'.chr(10);
	}

	$n = count($partids);
	$ext = 's';
	if ($n==1) { $ext = ''; }
?>
<!DOCTYPE html>
<html>
<head>
	<title>Inventory</title>
	<?php
		include_once 'inc/scripts.php';
	?>

	<style type="text/css">
		.table-results {
			width:95%;
			margin-left:auto;
			margin-right:auto;
		}
		.qty {
			border:1px inset #eee !important;
			background-color:#fafafa !important;
			border-radius:3px;
			width:40px;
			min-width:30px;
			max-width:80px;
			text-align:center;
			font-weight:bold;
		}
		.results-toggler {
			cursor:pointer;
		}
		a.results-toggler {
			margin-right:12px;
		}
	</style>
</head>
<body>

	<?php include_once 'inc/navbar.php'; ?>

	<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
		<form class="form-inline" method="get" action="inventory-beta.php" enctype="multipart/form-data" id="filters-form" >

		<div class="row" style="padding:8px">
			<div class="col-sm-1">
				<div class="btn-group">
					<button type="submit" name="inventory-summary" id="inventory-summary" value="1" class="btn btn-<?php echo $summary_btn; ?> btn-xs left" data-toggle="tooltip" data-placement="bottom" title="Summary Results (default)"><i class="fa fa-th-large"></i></button>
					<button type="submit" name="inventory-detail" id="inventory-detail" value="1" class="btn btn-<?php echo $detail_btn; ?> btn-xs right" data-toggle="tooltip" data-placement="bottom" title="Detail Results"><i class="fa fa-th"></i></button>
				</div>
			</div>
			<div class="col-sm-1">
				<select name="locationid" size="1" class="location-selector" id="location-filter">
<?php
					if ($locationid) { echo '<option value="'.$locationid.'" selected>'.getLocation($locationid).'</option>'.chr(10); }
					else { echo '<option value="">- Select Location -</option>'; }
?>
				</select>
			</div>
			<div class="col-sm-1">
				<div class="input-group">
					<input type="text" name="order_search" value="<?php echo $order_search; ?>" class="form-control input-sm" placeholder="PO/RO/RMA...">
					<span class="input-group-btn">
						<button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-filter"></i></button>
					</span>
            	</div>
			</div>
			<div class="col-sm-2">
				<div class="input-group">
					<div class="date_container mobile-hid remove-pad">
						<div class="col-sm-6 remove-pad">
							<div class="input-group date datetime-picker" data-format="MM/DD/YYYY">
					            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>">
					            <span class="input-group-addon">
					                <span class="fa fa-calendar"></span>
					            </span>
					        </div>
						</div>
						<div class="col-sm-6 remove-pad">
							<div class="input-group date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>">
					            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>">
					            <span class="input-group-addon">
					                <span class="fa fa-calendar"></span>
					            </span>
						    </div>
						</div>
					</div>
					<span class="input-group-btn">
						<button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-filter" aria-hidden="true"></i></button>
					</span>
				</div>
			</div>
			<div class="col-sm-2 text-center"><h2 class="minimal">Inventory</h2></div>
			<div class="col-sm-2">
				<div class="input-group">
					<input type="text" name="s2" value="<?php echo $search; ?>" class="form-control input-sm" placeholder="Filter by Part/Serial...">
					<span class="input-group-btn">
						<button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-filter"></i></button>
					</span>
            	</div>
			</div>
			<div class="col-sm-1 text-center">
				<div class="btn-group">
					<button class="btn btn-<?php echo $goodstock_btn; ?> btn-narrow btn-sm left" name="btn-goodstock" value="<?php echo !$goodstock; ?>" data-toggle="tooltip" data-placement="bottom" title="Good Stock"><i class="fa fa-dot-circle-o<?php echo $goodstock_text; ?>"></i> <?php echo $goodcount; ?></button>
					<button class="btn btn-<?php echo $badstock_btn; ?> btn-narrow btn-sm middle" name="btn-badstock" value="<?php echo !$badstock; ?>" data-toggle="tooltip" data-placement="bottom" title="Bad Stock"><i class="fa fa-circle<?php echo $badstock_text; ?>"></i> <?php echo $badcount; ?></button>
					<button class="btn btn-<?php echo $outstock_btn; ?> btn-narrow btn-sm right" name="btn-outstock" value="<?php echo !$outstock; ?>" data-toggle="tooltip" data-placement="bottom" title="Zero Stock"><i class="fa fa-minus-circle<?php echo $outstock_text; ?>"></i> <?php echo $outcount; ?></button>
				</div>
			</div>
			<div class="col-sm-2">
				<div class="form-group pull-right">
					<select name="companyid" size="1" class="company-selector">
						<option value="">- Select Company -</option>
						<?php if ($companyid) { echo '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'.chr(10); } ?>
					</select>
					<button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-filter"></i></button>
            	</div>
			</div>
		</div>

		</form>
	</div>


<div id="pad-wrapper">
<form class="form-inline results-form" method="get" action="save-results.php" enctype="multipart/form-data" >

	<div class="row">
		<div class="col-sm-3">
<?php if ($n>0) { ?>
			<select name="revs[]" class="select2 form-control rev-select" data-placeholder="Select week(s) or leave blank for all" data-allow-clear="false" multiple="multiple">
				<option value="">- <?php echo $n; ?> Result<?php echo $ext; ?> -</option>
				<?php echo $part_options; ?>
			</select>
<?php } ?>
		</div>
		<div class="col-sm-6">
			<?php if ($n==1) { echo '<h3 class="text-center">'.$part_str.'</h3>'; } ?>
		</div>
		<div class="col-sm-3">
		</div>
	</div>
	<br/>

	<div class="row">
		<div class="table-wrapper">

	<table class="table table-striped table-condensed table-inventory">
		<thead><tr>
			<th class="col-sm-2">
				Location
			</th>
			<th class="col-sm-1">
				Qty
			</th>
			<th class="col-sm-2">
				Condition
			</th>
			<th class="col-sm-2">
				Source
			</th>
			<th class="col-sm-2">
				Company
			</th>
			<th class="col-sm-1">
				Date
			</th>
			<th class="col-sm-1">
			</th>
			<th class="col-sm-1">
				<input type="checkbox" value="1" class="checkAll" checked>
				<a href="javascript:void(0);" id="results-toggle" class="results-toggler"><i class="fa fa-list-ol"></i><sup><i class="fa fa-sort-desc"></i></sup></a>
				<a href="javascript:void(0);"><i class="fa fa-chevron-down"></i></a>
			</th>
		</tr></thead>
		<?php echo $inv_rows; ?>
	</table>

		</div>
	</div>

</form>
</div><!-- pad-wrapper -->


<?php include_once $_SERVER["ROOT_DIR"].'/modal/history.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/modal/inventory.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

    <script type="text/javascript">
        $(document).ready(function() {
			$(".results-toggler").click(function() {
				toggleResults();//$("#results-toggle"));
			});
			$("#location-filter").change(function() {
				$('#loader-message').html('Please wait while Inventory is loaded...');
				$('#loader').show();

				$(this).closest("form").submit();
			});
			$(".rev-select").click(function() {
				var partid = $(this).find("option:selected").val();

				$(".table-inventory").find("tr").each(function() {
					row_id = $(this).data('partid');
					if (! row_id) { return; }

					if (partid==0 || row_id==partid) {
						if ($(this).data('role')!='inner' || ($(this).data('role')=='inner' && ! $("#inventory-detail").hasClass('btn-default'))) {
							$(this).show();
						}
					} else {
						$(this).hide();
					}
				});
			});
			$(".checkInner").click(function(){
				$(this).closest('tr').next('tr').find('.item-check:checkbox').not(this).prop('checked', this.checked);
			});
			$(".edit-inventory").click(function() {
				var inventoryid = $(this).closest("div").data('inventoryid');
				if (! inventoryid) { return; }

				console.log(window.location.origin+"/json/inventory.php?inventoryid="+inventoryid);
				$.ajax({
					url: 'json/inventory.php',
					type: 'get',
					data: {'inventoryid':inventoryid},
					success: function(json, status) {
						if (json.message && json.message!='') {
							// alert the user when there are errors
							alert(json.message);
							return;
						}

						var M = $("#modal-inventory");

						$("#modalInventoryTitle").html(json.name);

						$("#inventory-inventoryid").val(json.id);
						$("#inventory-serial").val(json.serial_no);

						$("#inventory-partid").data('partid',json.partid);
						$("#inventory-partid").populateSelected(json.partid,json.name);

						$("#inventory-locationid").populateSelected(json.locationid,json.location);

						$("#inventory-conditionid").populateSelected(json.conditionid,json.condition);

						$("#inventory-notes").val(json.notes);

						$("#inventory-status").html(json.status);

						M.modal("show");
					},
					error: function(xhr, desc, err) {
						console.log("Details: " + desc + "\nError:" + err);
					}
				}); // end ajax call
			});

			$("#inventory-save").click(function() {
				$('#loader-message').html('Please wait while updates are saved...');
				$('#loader').show();

				// check for filters form and add elements
				var f = $(this).closest("form");

				var ff = $("#filters-form");
				ff.find("input").each(function() {
					$('<input>').attr({
						type: 'hidden',
						name: $(this).attr('name'),
						value: $(this).val(),
					}).appendTo(f);
				});
				ff.find("select").each(function() {
					$('<input>').attr({
						type: 'hidden',
						name: $(this).attr('name'),
						value: $(this).val(),
					}).appendTo(f);
				});

				f.submit();
			});
		});


		function toggleResults() {
			var toggler = $("#results-toggle").find("sup i.fa");
			if (toggler.hasClass("fa-sort-desc")) {
				var method = 'show';
			} else {
				var method = 'hide';
			}

			$(".table-inventory").find(".inner-result").each(function() {
				if (method=='show') {
					$(this).fadeIn('fast');
				} else {
					$(this).fadeOut('fast');
				}
			});

			$(".results-toggler").each(function() {
				$(this).find("sup i.fa").toggleClass("fa-sort-asc fa-sort-desc");
			});
		}
	</script>

</body>
</html>
