<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$order = 0;
	if (isset($_REQUEST['order']) AND trim($_REQUEST['order'])) { $order = trim($_REQUEST['order']); }
	$type = '';
	if (isset($_REQUEST['type']) AND trim($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }
	$bill = '';
	if (isset($_REQUEST['bill']) AND trim($_REQUEST['bill'])) { $bill = trim($_REQUEST['bill']); }

	if (! $type) {
		$query = "SELECT * FROM purchase_orders WHERE po_number = '".res($order)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==1) {
			$type = 'Purchase';
		}
	}

	$T = order_type($type);

	$O = array('companyid'=>0);
	$query = "SELECT * FROM ".$T['orders']." WHERE ".$T['order']." = '".res($order)."'; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==1) {
		$O = mysqli_fetch_assoc($result);
	}
	$companyid = $O['companyid'];

	$title = '';
	if ($bill) { $title = 'Bill# '.$bill; }
	else { $title = 'New Bill for '.$T['abbrev'].' '.$order; }

	$invoice_no = '';
	$due_date = format_date($today,'m/d/Y',array('d'=>30));
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $title; ?></title>
	<?php
		include_once 'inc/scripts.php';
	?>

	<style type="text/css">
	</style>
</head>
<body>

	<div class="container-fluid full-height">
		<?php include_once 'inc/navbar.php'; ?>

		<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
			<div class="row text-center" style="padding:8px">
				<h2 class="minimal"><?php echo $title; ?></h2>
			</div>
		</div>

		<div class="row full-screen">
			<div class="col-md-2 sidebar">
				<div class="sidebar-section company-text">
					<h4 class="section-header">Information</h4>
					<?php echo getCompany($companyid); ?> <a href="/profile.php?companyid=<?php echo $companyid; ?>"><i class="fa fa-book"></i></a>
					<h3><?php echo $T['abbrev'].' '.$order; ?> <a href="/<?php echo $T['abbrev'].$order; ?>"><i class="fa fa-arrow-right"></i></a></h3>
				</div>
				<div class="sidebar-section">
					<h4 class="section-header">Vendor Invoice#</h4>
					<input type="text" name="invoice_no" value="<?php echo $invoice_no; ?>" class="form-control input-sm">
				</div>
				<div class="sidebar-section">
					<h4 class="section-header">Payment Due</h4>
					<div class="input-group datepicker-date date" data-format="MM/DD/YYYY">
			            <input type="text" name="due_date" class="form-control input-sm" value="<?php echo $due_date; ?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
					</div>
				</div>
			</div>

			<div class="col-md-10">
			</div>
		</div>
	</div>

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

    <script type="text/javascript">
        $(document).ready(function() {
		});
	</script>

</body>
</html>
