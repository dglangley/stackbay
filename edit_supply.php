<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSearch.php';

	$companyid = 0;
	if (! empty($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$list_type = 'Supply';
	if (! empty($_REQUEST['list_type'])) { $list_type = $_REQUEST['list_type']; }
	$partids = array();
	if (! empty($_REQUEST['partids'])) { $partids = $_REQUEST['partids']; }
	$datetime = '';
	if (! empty($_REQUEST['datetime'])) { $datetime = $_REQUEST['datetime']; }

	$partid_csv = '';
	foreach ($partids as $partid) {
		if ($partid_csv) { $partid_csv .= ','; }
		$partid_csv .= $partid;
	}

	$T = order_type($list_type);

	$rows = '';
	$query = "SELECT m.companyid, m.".$T['datetime']." datetime, s.id searchid, m.source, l.".$T['qty']." qty, l.".$T['amount']." amount, l.partid ";
	$query .= "FROM ".$T['orders']." m, ".$T['items']." l ";
	$query .= "LEFT JOIN searches s ON l.searchid = s.id ";
	$query .= "WHERE m.companyid = '".res($companyid)."' AND l.partid IN (".$partid_csv.") ";
	if ($datetime) { $query .= "AND m.".$T['datetime']." LIKE '".res(substr($datetime,0,10))."%' "; }
	$query .= "AND m.".$T['item_label']." = l.".$T['order']." ";
	$query .= "GROUP BY m.companyid, s.id, ".$T['amount']." ";
	$query .= "ORDER BY m.".$T['datetime']." DESC ";
	$query .= "; ";
	$result = qedb($query);
	while ($r = qrow($result)) {
		$H = hecidb($r['partid'],'id')[$r['partid']];
		$rows .= '
				<tr>
					<td>'.getCompany($r['companyid']).'</td>
					<td>'.$H['primary_part'].' '.$H['heci'].'</td>
					<td>'.format_date($r['datetime'],'n/j/y g:ia').'</td>
					<td>'.getSearch($r['searchid']).'</td>
					<td><img src="img/'.$r['source'].'.png" class="bot-icon" /></td>
					<td>'.$r['qty'].'</td>
					<td>'.$r['amount'].'</td>
					<td> </td>
				</tr>
		';
	}

	$TITLE = 'Supply';
	$SUBTITLE = '';
?>
<!DOCTYPE html>
<html>
<head>
	<title><?=$TITLE;?></title>
	<?php
		/*** includes all required css includes ***/
		include_once $_SERVER["ROOT_DIR"].'/inc/scripts.php';
	?>

	<!-- any page-specific customizations -->
	<style type="text/css">
	</style>
</head>
<body>

<?php include_once $_SERVER["ROOT_DIR"].'/inc/navbar.php'; ?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form">

	<div class="row" style="padding:8px">
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><?=$TITLE;?></h2>
			<span class="info"><?=$SUBTITLE;?></span>
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-2">
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
<form class="form-inline" method="POST" action="save-supply.php" enctype="multipart/form-data" >

	<div class="table-responsive">
		<table class="table table-condensed table-hover table-striped">
			<thead>
				<tr>
					<th>Company</th>
					<th>Description</th>
					<th>Date</th>
					<th>Search</th>
					<th>Source</th>
					<th>Qty</th>
					<th>Price</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?=$rows;?>
			</tbody>
		</table>
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
