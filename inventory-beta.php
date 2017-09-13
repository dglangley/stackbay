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

	$order_search = '';
	if (isset($_REQUEST['order_search']) AND trim($_REQUEST['order_search'])) { $order_search = trim($_REQUEST['order_search']); }

	$part_options = '';
	$part_str = '';
	$partids = array();
	$inv_rows = '';
	if ($search) {
		$results = hecidb($search);
		foreach ($results as $partid => $P) {
			$records = array();
			$query = "SELECT * FROM inventory WHERE partid = '".$partid."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($result)>0) {
				$partids[$partid] = $P;
			}
			while ($r = mysqli_fetch_assoc($result)) {
				$key = $r['partid'].'.'.$r['locationid'].'.'.$r['conditionid'].'.'.$r['purchase_item_id'].'.'.substr($r['date_created'],0,10);
				$qty = $r['qty'];
				if ($r['serial_no']) { $qty = 1; }

				if (! isset($records[$key])) {
					$r['qty'] = 0;
					$records[$key] = $r;
				}
				$records[$key]['qty'] += $qty;
			}

			foreach ($records as $r) {
				$order_number = getSource($r['purchase_item_id'],'Purchase');
				$order_ln = '';

				$company = '';
				$company_ln = '';
				if ($order_number) {
					$order_ln = ' <a href="/PO'.$order_number.'" target="_new"><i class="fa fa-arrow-right"></i></a>';
					$cid = getCompanyID($order_number,'Purchase');
					$company = getCompany($cid);
					$company_ln = ' <a href="/profile.php?companyid='.$cid.'" target="_new"><i class="fa fa-book"></i></a>';
				}

				$inv_rows .= '
		<tr>
			<td>'.getLocation($r['locationid']).'</td>
			<td>'.$r['qty'].'</td>
			<td>'.getCondition($r['conditionid']).'</td>
			<td>'.$order_number.$order_ln.'</td>
			<td>'.$company.$company_ln.'</td>
			<td>'.format_date($r['date_created'],'n/j/y').'</td>
			<td></td>
			<td></td>
		</tr>
				';
			}
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
</head>
<body>

	<?php include_once 'inc/navbar.php'; ?>

	<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
		<form class="form-inline results-form" method="post" action="inventory-beta.php" enctype="multipart/form-data" >

		<div class="row" style="padding:8px">
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
			<div class="col-sm-4 text-center"><h2 class="minimal">Inventory</h2></div>
			<div class="col-sm-2">
				<div class="input-group">
					<input type="text" name="s2" value="<?php echo $search; ?>" class="form-control input-sm" placeholder="Filter by Part/Serial...">
					<span class="input-group-btn">
						<button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-filter"></i></button>
					</span>
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
<form class="form-inline results-form" method="post" action="save-results.php" enctype="multipart/form-data" >

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

	<table class="table table-striped table-condensed">
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
				Action
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
		});
	</script>

</body>
</html>
