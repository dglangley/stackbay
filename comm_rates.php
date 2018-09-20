<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

	if (! $U['manager'] AND ! $U['admin']) {
		$ALERT = 'I might be an artificial intelligence but I was not born yesterday.';
		header('Location: user_management.php?ALERT='.urlencode($ALERT));
		exit;
	}

	$userid = 0;
	// userid passed in?
	if (isset($_REQUEST['userid']) AND is_numeric($_REQUEST['userid']) AND $_REQUEST['userid']>0) {
		// is this a valid sales person?
		$userid = $_REQUEST['userid'];

		$query = "SELECT * FROM user_privileges p, user_roles r, users u, contacts c ";
		$query .= "WHERE u.id = '".res($userid)."' AND u.contactid = c.id AND c.status = 'Active' ";
		$query .= "AND r.userid = u.id AND r.privilegeid = p.id AND p.privilege = 'Sales'; ";
		$result = qedb($query);
		if (qnum($result)==0) {
			$userid = 0;
		}
	}

	$commid = false;
	if (isset($_REQUEST['id']) AND is_numeric($_REQUEST['id']) AND $_REQUEST['id']>=0) {
		$commid = (int)$_REQUEST['id'];
	}

	if (! $userid) {
		$ALERT = 'User is not setup for commissions, please add Sales privilege first.';
		header('Location: user_management.php?ALERT='.urlencode($ALERT));
		exit;
	}

	$TITLE = 'Commission Rates for '.getUser($userid);
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
		.input-group.datepicker-date {
			width:100%;
			max-width:100%;
		}
	</style>
</head>
<body>

<?php include_once $_SERVER["ROOT_DIR"].'/inc/navbar.php'; ?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form" >

	<div class="row" style="padding:8px">
		<div class="col-sm-1">
			<a href="user_management.php" class="btn btn-sm btn-default" title="User Management" data-toggle="tooltip" data-placement="right"><i class="fa fa-long-arrow-left"></i></a>
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-6 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"></span>
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-2">
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
<form class="form-inline" method="post" action="save-rates.php" enctype="multipart/form-data" >
<input type="hidden" name="userid" value="<?= $userid; ?>">
<input type="hidden" name="id" value="<?= $commid; ?>">

	<div class="row">
		<div class="col-md-2">
			<h3 class="text-center pb-20">Commission Calculator</h3>
			<div class="row">
			<div class="col-sm-12">
				<label>Estimated Monthly Gross Profit (GP)
					<div class="input-group">
						<span class="input-group-addon" aria-hidden="true"><i class="fa fa-usd"></i></span>
						<input class="form-control input-sm" type="text" value="" placeholder="0.00">
					</div>
				</label>
			</div>
			</div>

			<div class="row">
			<div class="col-sm-7 margin-20">
				<label>Base Pay
					<div class="input-group">
						<span class="input-group-addon" aria-hidden="true"><i class="fa fa-usd"></i></span>
						<input type="text" class="form-control input-sm" value="" placeholder="0.00">
					</div>
				</label>
			</div>
			<div class="col-sm-5 margin-20">
				<label>Comm Rate
					<div class="input-group">
						<input type="text" class="form-control input-sm" value="" placeholder="0.0">
						<span class="input-group-addon"><i class="fa fa-percent" aria-hidden="true"></i></span>
					</div>
				</label>
			</div>
			</div>

			<div class="row">
			<div class="col-sm-12">
				<label>
					<input type="checkbox" value="1" name="subtract_base">
					Subtract Base Pay from GP
				</label>
			</div>
			</div>

			<div class="row">
			<div class="col-sm-8 margin-20">
				<label>Team Pay (if applicable)
					<div class="input-group">
						<span class="input-group-addon" aria-hidden="true"><i class="fa fa-usd"></i></span>
						<input type="text" class="form-control input-sm" value="" placeholder="0.00">
					</div>
				</label>
			</div>
			</div>

			<div class="row">
			<div class="col-sm-12">
				<label>
					<input type="checkbox" value="1" name="subtract_team">
					Subtract Team Pay from GP
				</label>
			</div>
			</div>

			<div class="row">
			<div class="col-sm-12 text-center margin-20">
				<button class="btn btn-sm btn-default" type="button">Calculate</button>
			</div>
			</div>

			<div class="row">
			<div class="col-sm-6 margin-20">
				<label>Monthly Income
					<div class="input-group">
						<span class="input-group-addon" aria-hidden="true"><i class="fa fa-usd"></i></span>
						<input type="text" class="form-control input-sm" value="" placeholder="0.00" readonly>
					</div>
				</label>
			</div>
			<div class="col-sm-6 margin-20">
				<label>Company Profit
					<div class="input-group">
						<span class="input-group-addon" aria-hidden="true"><i class="fa fa-usd"></i></span>
						<input type="text" class="form-control input-sm" value="" placeholder="0.00" readonly>
					</div>
				</label>
			</div>
			</div>
		</div>
		<div class="col-md-10">

<?php
	$rows = '';
	$rates = array();

	$query = "SELECT * FROM commission_rates WHERE rep_id = '".res($userid)."'; ";
	$result = qedb($query);
	while ($r = qrow($result)) {
		$rates[] = $r;
	}

	if ($commid===0) {//create new
		$rates[] = array('id'=>0);
	}
	foreach ($rates as $r) {
		$rate = '';
		if (round($r['rate'],2)==$r['rate']) { $rate = round($r['rate'],2); } else { $rate = $r['rate']; }

		$subtract_base = '';
		if ($r['subtract_base']) { $subtract_base = '<i class="fa fa-check"></i>'; }

		$subtract_team = '';
		if ($r['subtract_team']) { $subtract_team = '<i class="fa fa-check"></i>'; }

		$companies = '';

		$action = '';

		if ($commid==$r['id']) {
			$rate = '
					<div class="input-group">
						<input type="text" name="rate" value="'.$rate.'" placeholder="'.$rate.'" class="form-control input-sm">
						<span class="input-group-addon"><i class="fa fa-percent" aria-hidden="true"></i></span>
					</div>
			';

			$start = '
					<div class="form-group">
						<div class="input-group datepicker-date date datetime-picker">
							<input type="text" name="start_date" class="form-control input-sm" value="'.format_date($r['start_date'],'m/d/Y H:i:s').'">
							<span class="input-group-addon">
								<span class="fa fa-calendar"></span>
							</span>
						</div>
					</div>
			';
			$end = '
					<div class="form-group">
						<div class="input-group datepicker-date date datetime-picker">
							<input type="text" name="end_date" class="form-control input-sm" value="'.format_date($r['end_date'],'m/d/Y H:i:s').'">
							<span class="input-group-addon">
								<span class="fa fa-calendar"></span>
							</span>
						</div>
					</div>
			';

			$subtract_base = '<input type="checkbox" name="subtract_base" value="1"'.($r['subtract_base'] ? ' checked' : '').'>';
			$subtract_team = '<input type="checkbox" name="subtract_team" value="1"'.($r['subtract_team'] ? ' checked' : '').'>';

			$company_sels = '';
			$comp_array = explode(',',$r['companies']);
			foreach ($comp_array as $cid) {
				$company_sels .= '<option value="'.$cid.'" selected>'.getCompany($cid).'</option>'.chr(10);
			}
			$companies = '
					<select class="form-control company-selector" name="companies[]" data-placeholder="- Leave blank for ALL -" multiple>
						'.$company_sels.'
					</select>
			';

			$status = '
					<select class="form-control select2" name="status">
						<option value="Active"'.($r['status']=='Active' ? ' selected' : '').'>Active</option>
						<option value="Inactive"'.($r['status']=='Inactive' ? ' selected' : '').'>Inactive</option>
					</select>
			';

			$action = '<a href="comm_rates.php?userid='.$userid.'" class="btn btn-sm btn-default text-danger" title="Cancel" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-close"></i></a> '.
				'<button class="btn btn-success btn-sm" type="submit"><i class="fa fa-save"></i></button>';
		} else {
			$rate .= ' %';

			$start = format_date($r['start_date'],'n/j/y g:i:sa');
			$end = format_date($r['end_date'],'n/j/y g:i:sa');

			$comp_array = explode(',',$r['companies']);
			foreach ($comp_array as $cid) {
				if ($companies) { $companies .= ', '; }
				$companies .= getCompany($cid);
			}
			if (! $r['companies']) { $companies = 'ALL'; }

			if ($commid===false) { $action = '<a href="comm_rates.php?userid='.$userid.'&id='.$r['id'].'"><i class="fa fa-pencil"></i></a>'; }
		}

		$rows .= '
			<tr>
				<td>'.$rate.'</td>
				<td>'.$start.'</td>
				<td>'.$end.'</td>
				<td class="text-center">'.$subtract_base.'</td>
				<td class="text-center">'.$subtract_team.'</td>
				<td>'.$companies.'</td>
				<td>'.$status.'</td>
				<td class="text-center">'.$action.'</td>
			</tr>
		';
	}
?>

			<div class="table-responsive">
				<table class="table table-hover table-striped table-condensed">
					<thead>
					<tr>
						<th class="col-sm-1">Rate</th>
						<th class="col-sm-2">Start Date</th>
						<th class="col-sm-2">End Date</th>
						<th class="col-sm-1"><span title="from GP" data-toggle="tooltip" data-placement="bottom">Subtract Base*</span></th>
						<th class="col-sm-1"><span title="from GP" data-toggle="tooltip" data-placement="bottom">Subtract Team*</span></th>
						<th class="col-sm-3">Client Base</th>
						<th class="col-sm-1">Status</th>
						<th class="col-sm-1 text-center">
							<?= ($commid===false ? '<a href="comm_rates.php?userid='.$userid.'&id=0" class="btn btn-sm btn-primary" title="New Rate Plan" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-plus"></i></button>' : 'Action'); ?>
						</th>
					</tr>
					</thead>
					<tbody>
					<?= $rows; ?>
					</tbody>
				</table>
			</div>
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
