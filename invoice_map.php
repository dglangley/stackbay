<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';

	$BDB = array();
	$query = "SELECT invoice_id, id FROM services_invoiceli; ";
	$result = qedb($query,'SVCS_PIPE');
	while ($r = mysqli_fetch_assoc($result)) {
		$BDB[$r['id']] = $r['invoice_id'];
	}

	$rows = '';
	$query = "SELECT * FROM maps_invoice m, invoice_items i WHERE m.invoice_item_id = i.id GROUP BY invoice_no; ";
	$result = qedb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$invoice_id = $BDB[$r['BDB_invoiceli_id']];

		$invoice_no = $r['invoice_no'];


		$rows .= '
			<tr>
				<td> </td>
				<td>'.$invoice_id.'</td>
				<td>'.$invoice_no.'</td>
				<td> </td>
			</tr>
		';
	}

	$TITLE = 'Invoice Map';
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
	</style>
</head>
<body>

<?php include_once 'inc/navbar.php'; ?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form" >

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
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"></span>
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
<form class="form-inline" method="get" action="" enctype="multipart/form-data" >

	<table class="table table-condensed table-hover table-striped">
		<thead>
			<tr>
				<th class="col-md-3"> </th>
				<th class="col-md-3">Old Invoice#</th>
				<th class="col-md-3">New Invoice#</th>
				<th class="col-md-3"> </th>
			</tr>
		</thead>
		<tbody>
			<?= $rows; ?>
		</tbody>
	</table>

</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
	});
</script>

</body>
</html>
