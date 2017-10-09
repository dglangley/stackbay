<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRefLabels.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCondition.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getWarranty.php';
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

	$default_conditionid = 2;
	$default_warrantyid = $T['warrantyid'];
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
<body data-scope="<?php echo $order_type; ?>">

<?php include_once 'inc/navbar.php'; ?>

<form class="form-inline" method="get" action="save-order.php" enctype="multipart/form-data" >

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">

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
		<div class="col-sm-2 text-right">
			<button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save</button>
		</div>
	</div>

</div>

<?php
	if (! isset($EDIT)) { $EDIT = false; }
$EDIT = true;

	$labels = getRefLabels();
	$ref_labels = '';//<li><a href="javascriptLvoid(0);">- Label -</a></li>'.chr(10);
	foreach ($labels as $label) {
		$ref_labels .= '<li><a href="javascript:void(0);">'.$label.'</a></li>'.chr(10);
	}

	include_once $_SERVER["ROOT_DIR"].'/sidebar.php';
?>

<div id="pad-wrapper">

<table class="table table-responsive table-condensed table-striped" id="search_input">
	<thead>
	<tr>
		<th class="col-md-4"><div class="pull-left padding-right20">Ln</div> Description of Charges</th>
		<th class="col-md-1">Ref 1</th>
		<th class="col-md-1">Ref 2</th>
		<th class="col-md-1">Delivery</th>
		<th class="col-md-1">
			<select name="conditionid_master" size="1" class="form-control input-sm condition-selector" data-placeholder="- Condition -">
			</select>
		</th>
		<th class="col-md-1">
			<select name="warrantyid_master" size="1" class="form-control input-sm warranty-selector" data-placeholder="- Warranty -">
			</select>
		</th>
		<th class="col-md-1">Qty</th>
		<th class="col-md-1">Amount</th>
		<th class="col-md-1">Ext Amount</th>
	</tr>
	</thead>

	<tbody>
	<tr class="search-row">
		<td class="col-md-4">
			<div class="pull-left">
				<input type="text" name="ln[0]" value="1" class="form-control input-sm line-number">
			</div>
			<div class="input-group input-shadow">
				<select name="partid[0]" size="1" class="select2 part-selector hidden">
				</select>
				<input type="text" name="" value="" id="partSearch" class="form-control input-sm" placeholder="Search for item..." tabindex="1">
				<span class="input-group-btn">
					<button class="btn btn-primary btn-sm" type="button" id="btn-partsearch"><i class="fa fa-search"></i></button>
				</span>
			</div>
		</td>
		<td class="col-md-1">
			<div class="input-group dropdown">
				<span class="input-group-btn dropdown-toggle" data-toggle="dropdown">
					<button class="btn btn-default btn-sm btn-dropdown" type="button">Ref</button>
					<input type="hidden" name="ref_1_label[0]" value="">
				</button></span>
				<input type="text" name="ref_1[0]" class="form-control input-sm" value="">
				<ul class="dropdown-menu dropdown-button">
					<?php echo $ref_labels; ?>
				</ul>
			</div>
        </td>
		<td class="col-md-1">
			<div class="input-group dropdown">
				<span class="input-group-btn dropdown-toggle" data-toggle="dropdown">
					<button class="btn btn-default btn-sm btn-dropdown" type="button">Ref</button>
					<input type="hidden" name="ref_2_label[0]" value="">
				</span>
				<input type="text" name="ref_2[0]" class="form-control input-sm" value="">
				<ul class="dropdown-menu dropdown-button">
					<?php echo $ref_labels; ?>
				</ul>
			</div>
		</td>
		<td class="col-md-1">
			<div class="input-group date datetime-picker" data-format="MM/DD/YY">
				<input type="text" name="delivery_date[0]" class="form-control input-sm" value="<?php echo format_date($today,'m/d/y',array('d'=>7)); ?>">
				<span class="input-group-addon">
					<span class="fa fa-calendar"></span>
				</span>
			</div>
		</td>
		<td class="col-md-1">
			<select name="conditionid[0]" size="1" class="form-control input-sm condition-selector">
				<option value="<?php echo $default_conditionid; ?>" selected><?php echo getCondition($default_conditionid); ?></option>
			</select>
		</td>
		<td class="col-md-1">
			<select name="warrantyid[0]" size="1" class="form-control input-sm warranty-selector">
				<option value="<?php echo $default_warrantyid; ?>" selected><?php echo getWarranty($default_warrantyid,'warranty'); ?></option>
			</select>
		</td>
		<td class="col-md-1 text-center">
			<input type="text" name="qty[0]" value="" class="form-control input-sm item-qty" readonly>
		</td>
		<td class="col-md-1">
			<input type="text" name="amount[0]" class="form-control input-sm text-right" tabindex="100">
		</td>
		<td class="col-md-1 text-right">
			<div id="extamount_new" class="ext_amount"></div>
			<button type="button" class="btn btn-success btn-sm btn-saveitem"><i class="fa fa-save"></i></button>
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
		$(".btn-saveitem").on('click', function() {
			var row = $(this).closest("tr");
			var qty_field,qty,partid,part_sel;
			$(this).closest("tbody").find(".found_parts").each(function() {
				qty_field = $(this).find(".part_qty");
				qty = qty_field.val().trim();
				row.find(".item-qty").val(qty);
				partid = qty_field.data('partid');
				part_sel = row.find(".part-selector");
				part_sel.populateSelected(partid, partid);

				row.saveItem();
			});
		});
		jQuery.fn.saveItem = function() {
			var cloned_row = $(this).clone();
			var part = cloned_row.find(".part-selector");
			part.show();
			cloned_row.insertBefore($(this));
		};
	});
</script>

</body>
</html>
