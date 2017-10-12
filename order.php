<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUsers.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRefLabels.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCondition.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getWarranty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrderNumber.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	$labels = getRefLabels();
	$ref_labels = '';
	foreach ($labels as $label) {
		$ref_labels .= '<li><a href="javascript:void(0);">'.$label.'</a></li>'.chr(10);
	}
	$LN = 1;
	$WARRANTYID = array();//tries to assimilate new item warranties to match existing item warranties
	function addItemRow($id,$T) {
		global $ref_labels,$LN,$WARRANTYID;

		$dropdown1_attr = ' data-toggle="dropdown"';
		$dropdown2_attr = ' data-toggle="dropdown"';
		if ($id) {
			$row_cls = '';
			$query = "SELECT * FROM ".$T['items']." WHERE id = '".res($id)."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($result)==0) { return (''); }
			$r = mysqli_fetch_assoc($result);
			$r['qty_attr'] = '';
			$r['part_cls'] = 'select2';
			$r['name'] = '';
			if ($r['partid']) {
				$H = hecidb($r['partid'],'id');
				$r['name'] = '<option value="'.$r['partid'].'" selected>'.$H[$r['partid']]['name'].'</option>'.chr(10);
			}
			if (! isset($r['amount']) AND isset($r['price'])) { $r['amount'] = $r['price']; }
			$r['input-search'] = '';
			$r['save'] = '';
			if (strstr($r['ref_1_label'],'item_id')) {
				$T2 = order_type($r['ref_1_label']);
				$ref_1_order = getOrderNumber($r['ref_1'],$T2['items'],$T2['order']);

				$r['ref_1_label_btn'] = $T2['abbrev'];
				$dropdown1_attr = '';
				$r['ref_1_field'] = '<input type="text" name="ref_1_aux['.$id.']" class="form-control input-sm" value="'.$ref_1_order.'" readonly>';
				$r['ref_1_hidden'] = '<input type="hidden" name="ref_1['.$id.']" value="'.$r['ref_1'].'">';
			} else {
				if (! $r['ref_1_label']) { $r['ref_1_label'] = 'Ref'; }
				$r['ref_1_label_btn'] = $r['ref_1_label'];

				$r['ref_1_field'] = '<input type="text" name="ref_1['.$id.']" class="form-control input-sm" value="'.$r['ref_1'].'">';
				$r['ref_1_hidden'] = '';
			}
			if (strstr($r['ref_2_label'],'item_id')) {
				$T2 = order_type($r['ref_2_label']);
				$ref_2_order = getOrderNumber($r['ref_2'],$T2['items'],$T2['order']);

				$r['ref_2_label_btn'] = $T2['abbrev'];
				$dropdown2_attr = '';
				$r['ref_2_field'] = '<input type="text" name="ref_2_aux['.$id.']" class="form-control input-sm" value="'.$ref_2_order.'" readonly>';
				$r['ref_2_hidden'] = '<input type="hidden" name="ref_2['.$id.']" value="'.$r['ref_2'].'">';
			} else {
				if (! $r['ref_2_label']) { $r['ref_2_label'] = 'Ref'; }
				$r['ref_2_label_btn'] = $r['ref_2_label'];

				$r['ref_2_field'] = '<input type="text" name="ref_2['.$id.']" class="form-control input-sm" value="'.$r['ref_2'].'">';
				$r['ref_2_hidden'] = '';
			}
			if ($T['warranty']) {
				$WARRANTYID[$r[$T['warranty']]]++;
			}
			// increment so that new rows don't start at 1
			if ($r['line_number']>0) { $LN = ($r['line_number']+1); }
		} else {
			// sort warranties of existing items in descending so we can get the most commonly-used, and default to that
			$warrantyid = $T['warrantyid'];
			krsort($WARRANTYID);
			foreach ($WARRANTYID as $wid => $n) { $warrantyid = $wid; }

			$row_cls = 'search-row';
			$r = array(
				'line_number'=>$LN,
				'part_cls'=>'hidden',
				'name'=>'',
				'input-search'=>'
			<div class="input-group input-shadow input-search">
				<input type="text" name="" value="" id="partSearch" class="form-control input-sm" placeholder="Search for item..." tabindex="1">
				<span class="input-group-btn">
					<button class="btn btn-primary btn-sm" type="button" id="btn-partsearch"><i class="fa fa-search"></i></button>
				</span>
			</div>
				',
				'ref_1_label'=>'Ref',
				'ref_1_label_btn'=>'Ref',
				'ref_1_field'=>'<input type="text" name="ref_1['.$id.']" class="form-control input-sm" value="">',
				'ref_1_hidden'=>'',
				'ref_2_label'=>'Ref',
				'ref_2_label_btn'=>'Ref',
				'ref_2_field'=>'<input type="text" name="ref_2['.$id.']" class="form-control input-sm" value="">',
				'ref_2_hidden'=>'',
				'delivery_date'=>format_date($GLOBALS['today'],'m/d/y',array('d'=>7)),
				'conditionid'=>2,
				'warranty'=>$warrantyid,
				'qty'=>'',
				'qty_attr'=>'readonly',
				'amount'=>'',
				'save'=>'<button type="button" class="btn btn-success btn-sm btn-saveitem"><i class="fa fa-save"></i></button>',
			);
		}
		if (round($r['amount'],2)==$r['amount']) { $amount = number_format($r['amount'],2); }
		else { $amount = $r['amount']; }

		$row = '
	<tr class="'.$row_cls.'">
		<td class="col-md-4 part-container">
			<div class="pull-left" style="width:9%">
				<input type="text" name="ln['.$id.']" value="'.$r['line_number'].'" class="form-control input-sm line-number">
			</div>
			<select name="partid['.$id.']" size="1" class="part-selector '.$r['part_cls'].'">
				'.$r['name'].'
			</select>
			'.$r['input-search'].'
		</td>
		<td class="col-md-1">
			<div class="input-group dropdown">
				<span class="input-group-btn dropdown-toggle"'.$dropdown1_attr.'>
					<button class="btn btn-default btn-sm btn-narrow btn-dropdown" type="button">'.$r['ref_1_label_btn'].'</button>
					<input type="hidden" name="ref_1_label['.$id.']" value="'.$r['ref_1_label'].'">
				</button></span>
				'.$r['ref_1_field'].'
				'.$r['ref_1_hidden'].'
				<input type="hidden" name="ref_1['.$id.']" class="form-control input-sm" value="'.$r['ref_1'].'">
				<ul class="dropdown-menu dropdown-button">
					'.$ref_labels.'
				</ul>
			</div>
        </td>
		<td class="col-md-1">
			<div class="input-group dropdown">
				<span class="input-group-btn dropdown-toggle"'.$dropdown2_attr.'">
					<button class="btn btn-default btn-sm btn-narrow btn-dropdown" type="button">'.$r['ref_2_label_btn'].'</button>
					<input type="hidden" name="ref_2_label['.$id.']" value="'.$r['ref_2_label'].'">
				</span>
				'.$r['ref_2_field'].'
				'.$r['ref_2_hidden'].'
				<ul class="dropdown-menu dropdown-button">
					'.$ref_labels.'
				</ul>
			</div>
		</td>
		<td class="col-md-1">
			<div class="input-group date datetime-picker" data-format="MM/DD/YY">
				<input type="text" name="delivery_date['.$id.']" class="form-control input-sm" value="'.format_date($r['delivery_date'],'m/d/y').'">
				<span class="input-group-addon">
					<span class="fa fa-calendar"></span>
				</span>
			</div>
		</td>
		<td class="col-md-1">
			<select name="conditionid['.$id.']" size="1" class="form-control input-sm condition-selector">
				<option value="'.$r['conditionid'].'" selected>'.getCondition($r['conditionid']).'</option>
			</select>
		</td>
		<td class="col-md-1">
			<select name="warrantyid['.$id.']" size="1" class="form-control input-sm warranty-selector">
				<option value="'.$r['warranty'].'" selected>'.getWarranty($r['warranty'],'warranty').'</option>
			</select>
		</td>
		<td class="col-md-1 text-center">
			<input type="text" name="qty['.$id.']" value="'.$r['qty'].'" class="form-control input-sm item-qty" '.$r['qty_attr'].'>
		</td>
		<td class="col-md-1">
			<input type="text" name="amount['.$id.']" value="'.$amount.'" class="form-control input-sm text-right" tabindex="100">
		</td>
		<td class="col-md-1 text-right">
			<div id="extamount_new" class="ext_amount"></div>
			'.$r['save'].'
		</td>
	</tr>
		';

		return ($row);
	}

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
		$invoice = $order_number;
		$TITLE = 'Invoice '.$order_number;

		$ORDER = getOrder($order_number,'Invoice');
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
		.part-container .select2 {
			width:90% !important;
		}
	</style>
</head>
<body data-scope="<?php echo $order_type; ?>">

<?php include_once 'inc/navbar.php'; ?>

<form class="form-inline" method="POST" action="save-order.php" enctype="multipart/form-data" >
<input type="hidden" name="order_number" value="<?php echo $order_number; ?>">
<input type="hidden" name="order_type" value="<?php echo $order_type; ?>">

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">

	<div class="row" style="padding:8px">
		<div class="col-sm-1">
			<select name="sales_rep_id" size="1" class="form-control input-sm select2">
<?php
	$users = getUsers(array(4,5));
	foreach ($users as $uid => $uname) {
		$s = '';
		if (($ORDER['sales_rep_id'] AND $uid==$ORDER['sales_rep_id']) OR $U['id']==$uid) { $s = ' selected'; }
		echo '<option value="'.$uid.'"'.$s.'>'.$uname.'</option>'.chr(10);
	}
?>
			</select>
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
			<button type="button" class="btn btn-success btn-submit"><i class="fa fa-save"></i> Save</button>
		</div>
	</div>

</div>

<?php
	if (! isset($EDIT)) { $EDIT = false; }
if (! $invoice) {
$EDIT = true;
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

<?php
	foreach ($ORDER['items'] as $r) {
		echo addItemRow($r['id'],$T);
	}
	echo addItemRow(0,$T);
?>
	</tbody>
</table>

</div><!-- pad-wrapper -->

</form>

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script src="js/part_search.js?id=<?php echo $V; ?>"></script>
<script type="text/javascript">
	$(document).ready(function() {
		$(".btn-submit").on('click', function() {
			var errs = false;
			$(".required").each(function() {
				if (! $(this).val()) {
					$(this).addClass('has-error');
					$(this).closest("div").find(".select2-selection").addClass('has-error');
					errs = true;
				} else {
					$(this).removeClass('has-error');
					$(this).closest("div").find(".select2-selection").removeClass('has-error');
				}
			});
			if (errs===true) {
				modalAlertShow("Form Error","This form requires certain fields to be completed. You have not done your job.");
				return;
			}

			$(this).closest("form").submit();
		});
		$(".btn-saveitem").on('click', function() {
			var row = $(this).closest("tr");
			$(this).closest("tbody").find(".found_parts").each(function() {
				row.saveItem($(this));
			});

			$(this).closest("tbody").find(".found_parts").remove();
			var ln = row.find(".line-number");
			var new_ln = parseInt(ln.val());
			//if (! new_ln) { new_ln = 0; }
			new_ln++;
			ln.val(new_ln);

			$(this).closest("tr").find("input[type=text]").not(".line-number").val("");
		});
		jQuery.fn.saveItem = function(e) {
			var qty_field = e.find(".part_qty");
			var qty = qty_field.val().trim();
			if (qty == '' || qty == '0') { return; }

			var part_sel = $(this).find(".part-selector");
			var cloned_row = $(this).clone();

			// set qty of new row to qty of user-specified qty on revision found
			cloned_row.find(".item-qty").val(qty);
			var part = cloned_row.find(".part-selector");
			var partid = qty_field.data('partid');
			var descr = e.find(".part").find(".descr-label").html();
			part.populateSelected(partid, descr);
			part.select2();
			part.show();

			cloned_row.insertBefore($(this));

			// do not want this new row confused with the original search row
			cloned_row.removeClass('search-row');
			// remove search field from new cloned row
			cloned_row.find(".input-search").remove();
			// remove save button
			cloned_row.find(".btn-saveitem").remove();
			// remove readonly status on qty field
			cloned_row.find(".item-qty").prop('readonly',false);
		};
	});
</script>

</body>
</html>
