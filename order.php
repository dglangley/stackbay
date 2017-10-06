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
/*
		$query = "SELECT * FROM invoices WHERE invoice_no = '".res($invoice)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			die("Invalid Invoice!");
		}
		$ORDER = mysqli_fetch_assoc($result);

		$T = order_type($ORDER['order_type']);

		$query = "SELECT *, ".$T['addressid']." addressid FROM ".$T['orders']." WHERE ".$T['order']." = '".res($ORDER['order_number'])."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$ORDER['bill_to_id'] = $r['addressid'];
			$ORDER['ship_to_id'] = $r['ship_to_id'];
			$ORDER['cust_ref'] = $r['cust_ref'];
			$ORDER['ref_ln'] = $r['ref_ln'];
			$ORDER['termsid'] = $r['termsid'];
			$ORDER['contactid'] = $r['contactid'];
			$ORDER['freight_carrier_id'] = $r['freight_carrier_id'];
			$ORDER['freight_account_id'] = $r['freight_account_id'];
			$ORDER['public_notes'] = $r['public_notes'];
			$ORDER['private_notes'] = $r['private_notes'];
		}
*/

		$title_helper = format_date($ORDER['date_invoiced'],'D n/j/y g:ia');
	} else {
		$TITLE = $order_type.' Order '.$order_number;

		$T = order_type($order_type);

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
	</style>
</head>
<body data-scope="Purchase">

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

<?php
	if (! isset($EDIT)) { $EDIT = false; }
$EDIT = true;

	include_once $_SERVER["ROOT_DIR"].'/sidebar.php';
?>

<div id="pad-wrapper">
<form class="form-inline" method="get" action="" enctype="multipart/form-data" >

</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
	});
</script>

</body>
</html>
