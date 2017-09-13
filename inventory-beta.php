<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/calcQuarters.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getLocation.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCondition.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$T = order_type('Purchase');
	function getSource($id,$order_type='Purchase') {
		global $T;

		if (! $id) { return false; }

		$query = "SELECT ".$T['order']." order_number FROM ".$T['items']." WHERE id = '".res($id)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			return false;
		}
		$r = mysqli_fetch_assoc($result);
		return ($r['order_number']);
	}

	function getCompanyID($order_number,$order_type='Purchase') {
		global $T;

		if (! $order_number) { return false; }

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

	$summary_btn = 'default';
	$detail_btn = 'default';
	if ($summary) {
		$summary_btn = 'primary active';
	} else if ($detail) {
		$detail_btn = 'primary active';
	}

	$order_search = '';
	if (isset($_REQUEST['order_search']) AND trim($_REQUEST['order_search'])) { $order_search = trim($_REQUEST['order_search']); }

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
		$goodstock_btn = 'warning active';
		$goodstock_text = '';
		setcookie('goodstock',$goodstock,$expiry);
	} else {
		$goodstock_btn = 'default';
		setcookie('goodstock',$goodstock,$past_time);
	}
	if ($badstock) {
		$badstock_btn = 'purple active';
		$badstock_text = '';
		setcookie('badstock',$badstock,$expiry);
	} else {
		$badstock_btn = 'default';
		setcookie('badstock',$badstock,$past_time);
	}
	if ($outstock) {
		$outstock_btn = 'danger active';
		$outstock_text = '';
		setcookie('outstock',$outstock,$expiry);
	} else {
		$outstock_btn = 'default';
		setcookie('outstock',$outstock,$past_time);
	}

	$inner_display = ' style="display:none"';
	if ($detail) {
		$inner_display = '';
	}

	// displayed only on first occurrence of a nested/inner table
	$inner_header = '
					<tr class="inner-result"'.$inner_display.'>
						<th class="col-sm-3">Serial</th>
						<th class="col-sm-5">History</th>
						<th class="col-sm-4">Notes</th>
					</tr>
	';

	$part_options = '';
	$part_str = '';
	$partids = array();
	$partids_csv = '';
	$inv_rows = '';
	if ($search) {
		$results = hecidb($search);
		foreach ($results as $partid => $P) {
			// gather unique list of partids
			$partids[$partid] = $P;

			if ($partids_csv) { $partids_csv .= ','; }
			$partids_csv .= $partid;
		}

		$records = array();
		$query = "SELECT * FROM inventory WHERE partid IN (".$partids_csv.") ";
		$query .= "ORDER BY IF(status='shelved' OR status='received',0,1), IF(conditionid>0,0,1), date_created DESC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$key = $r['partid'].'.'.$r['locationid'].'.'.$r['conditionid'].'.'.$r['status'].'.'.$r['purchase_item_id'].'.'.substr($r['date_created'],0,10);

			$qty = $r['qty'];
			if ($r['serial_no']) { $qty = 1; }

			if (! isset($records[$key])) {
				$r['qty'] = 0;
				$r['entries'] = array();
				$records[$key] = $r;
			}
			$records[$key]['qty'] += $qty;
			$records[$key]['entries'][] = array('serial_no'=>$r['serial_no'],'notes'=>$r['notes']);
		}

		foreach ($records as $r) {
			$prefix = '';
			$order_number = getSource($r['purchase_item_id'],'Purchase');
			$order_ln = '';

			if (! $goodstock AND $r['conditionid']>0) { continue; }
			if (! $badstock AND $r['conditionid']<0) { continue; }
			if (! $outstock AND ($r['status']<>'shelved' AND $r['status']<>'received')) { continue; }

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
		<tr class="'.$cls.'">
			<td>'.getLocation($r['locationid']).'</td>
			<td>
				<div class="qty inner-toggler">'.$qty.'</div>
			</td>
			<td>'.getCondition($r['conditionid']).'</td>
			<td>'.$prefix.$order_number.$order_ln.'</td>
			<td>'.$company.$company_ln.'</td>
			<td>'.format_date($r['date_created'],'n/j/y').'</td>
			<td></td>
			<td></td>
		</tr>
		<tr class="inner-result"'.$inner_display.'>
			<td colspan="8" class="text-center">
				<table class="table table-condensed table-results text-left">
			';

			$inners = '';
			foreach ($r['entries'] as $entry) {
				$inners .= $inner_header.'
					<tr class="">
						<td class="col-sm-3">'.$entry['serial_no'].'</td>
						<td class="col-sm-5"></td>
						<td class="col-sm-4">'.$entry['notes'].'</td>
					</tr>
				';

				$inner_header = '';
			}

			$inv_rows .= $inners.'
				</table>
			</td>
		</tr>
			';
		}

		$query = "SELECT * FROM inventory WHERE serial_no = '".res($search)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$P = hecidb($r['partid'],'id');
			$partids[$r['partid']] = $P[$r['partid']];
		}
	}

	foreach ($partids as $partid => $P) {
		$part_str = trim($P['part'].' '.$P['heci']);

		$part_options .= '<option value="'.$partid.'">'.$P['part'].' '.$P['heci'].'</option>'.chr(10);
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
		.inner-toggler {
			cursor:pointer;
		}
	</style>
</head>
<body>

	<?php include_once 'inc/navbar.php'; ?>

	<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
		<form class="form-inline" method="get" action="inventory-beta.php" enctype="multipart/form-data" >

		<div class="row" style="padding:8px">
			<div class="col-sm-1">
				<div class="btn-group">
					<button type="submit" name="inventory-summary" value="1" class="btn btn-<?php echo $summary_btn; ?> btn-xs left" data-toggle="tooltip" data-placement="bottom" title="Summary Results (default)"><i class="fa fa-th-large"></i></button>
					<button type="submit" name="inventory-detail" value="1" class="btn btn-<?php echo $detail_btn; ?> btn-xs right" data-toggle="tooltip" data-placement="bottom" title="Detail Results"><i class="fa fa-th"></i></button>
				</div>
			</div>
			<div class="col-sm-1">
				<select name="locationid" size="1" class="location-selector">
					<option value="">- Select Location -</option>
				</select>
			</div>
			<div class="col-sm-1">
				<div class="input-group">
					<input type="text" name="order_search" value="<?php echo $order_search; ?>" class="form-control input-sm" placeholder="PO Search...">
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
					<button class="btn btn-<?php echo $goodstock_btn; ?> btn-sm left" name="btn-goodstock" value="<?php echo !$goodstock; ?>"><i class="fa fa-dot-circle-o<?php echo $goodstock_text; ?>"></i></button>
					<button class="btn btn-<?php echo $badstock_btn; ?> btn-sm middle" name="btn-badstock" value="<?php echo !$badstock; ?>"><i class="fa fa-circle<?php echo $badstock_text; ?>"></i></button>
					<button class="btn btn-<?php echo $outstock_btn; ?> btn-sm right" name="btn-outstock" value="<?php echo !$outstock; ?>"><i class="fa fa-minus-circle<?php echo $outstock_text; ?>"></i></button>
				</div>
			</div>
			<div class="col-sm-2">
				<div class="form-group pull-right">
					<select name="companyid" size="1" class="company-selector">
						<option value="">- Select Company -</option>
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
			<select name="revs[]" class="select2 form-control" data-placeholder="Select week(s) or leave blank for all" data-allow-clear="false" multiple="multiple">
				<option value="">- <?php echo $n; ?> Result<?php echo $ext; ?> -</option>
				<?php echo $part_options; ?>
			</select>
		</div>
		<div class="col-sm-6">
			<h3 class="text-center"><?php echo $part_str; ?></h3>
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
				<a href="javascript:void(0);" id="results-toggle"><i class="fa fa-list-ol"></i><sup><i class="fa fa-sort-desc"></i></sup></a>
			</th>
		</tr></thead>
		<?php echo $inv_rows; ?>
	</table>

		</div>
	</div>

</form>
</div><!-- pad-wrapper -->


<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

    <script type="text/javascript">
        $(document).ready(function() {
			$(".inner-toggler").click(function() {
//				$("#results-toggle").trigger();
				toggleResults($("#results-toggle"));
			});
			$("#results-toggle").click(function() {
				toggleResults($(this));
			});
		});

		function toggleResults(e) {
			var toggler = e.find("sup i.fa");
			if (toggler.hasClass("fa-sort-desc")) {
				var method = 'show';
			} else {
				var method = 'hide';
			}

			e.closest("table").find(".inner-result").each(function() {
				if (method=='show') {
					$(this).fadeIn('fast');
				} else {
					$(this).fadeOut('fast');
				}
			});

			toggler.toggleClass("fa-sort-asc fa-sort-desc");
		}
	</script>

</body>
</html>
