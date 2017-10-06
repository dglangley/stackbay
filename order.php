<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	$order_number = 0;
	$order_type = '';

	$invoice = '';
	if (isset($_REQUEST['invoice']) AND trim($_REQUEST['invoice'])) { $invoice = trim($_REQUEST['invoice']); }

	if ($invoice) {
		$order_number = $invoice;
		$order_type = 'Invoice';
	} else {
		if (isset($_REQUEST['order_number']) AND trim($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
		if (isset($_REQUEST['order_type']) AND trim($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }
	}

	$title_helper = '';
	if ($order_type=='Invoice') {
		$TITLE = 'Invoice '.$invoice;

		$ORDER = getOrder($invoice,'Invoice');
		$T = order_type($ORDER['order_type']);

		$title_helper = format_date($ORDER['date_invoiced'],'D n/j/y g:ia');
	} else {
		$T = order_type($order_type);
		$TITLE = $T['abbrev'];
		if ($order_number) { $TITLE .= '# '.$order_number; }
		else { $TITLE = 'New '.$TITLE; }

		$ORDER = getOrder($order_number,$order_type);
		if ($ORDER===false) { die("Invalid Order"); }
		$ORDER['bill_to_id'] = $ORDER['addressid'];
		$ORDER['datetime'] = $ORDER['dt'];

		$title_helper = format_date($ORDER['datetime'],'D n/j/y g:ia');
	}
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
		.input-shadow input:focus {
			box-shadow: 2px 2px 3px #888888;
		}
	</style>
</head>
<body data-scope="<?php echo $T['order_type']; ?>">

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
			<span class="info"><?php echo $title_helper; ?></span>
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

<form class="form-inline" method="get" action="" enctype="multipart/form-data" >

<?php
	if (! isset($EDIT)) { $EDIT = false; }
$EDIT = true;

	include_once $_SERVER["ROOT_DIR"].'/sidebar.php';
?>

<div id="pad-wrapper">

<table class="table table-responsive table-condensed table-striped" id="search_input">
	<tbody>
	<tr class="search-row">
		<td class="col-md-5">
			<div class="input-group input-shadow">
				<input type="text" name="" value="" class="form-control input-sm">
				<span class="input-group-btn">
					<button class="btn btn-primary btn-sm" type="button"><i class="fa fa-search"></i></button>
				</span>
			</div>
		</td>
		<td class="col-md-7">
		</td>
	</tr>
	</tbody>
</table>

</div><!-- pad-wrapper -->

</form>

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script src="js/part_search.js?id=<?php echo $V; ?>"></script>
<script type="text/javascript">
	$(document).ready(function() {
	});
</script>

</body>
</html>
