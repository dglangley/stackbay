<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/display_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getWarranty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$order = 0;
	if (isset($_REQUEST['order']) AND trim($_REQUEST['order'])) { $order = trim($_REQUEST['order']); }
	// legacy support
	if (! $order AND isset($_REQUEST['on'])) { $order = trim($_REQUEST['on']); }

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

	<?php include_once 'inc/navbar.php'; ?>

	<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
		<div class="row text-center" style="padding:8px">
			<h2 class="minimal"><?php echo $title; ?></h2>
		</div>
	</div>

	<div class="container-fluid full-height">
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

<?php
	$i = 0;
	$rows = '';
	$query = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = '".res($order)."'; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$descr = '';
		if ($r['partid']) {
			$H = hecidb($r['partid'],'id');
			$descr = display_part($H[$r['partid']]);
		}

		$bi_id = 0;
		$qty_recd = 0;
		$inner_rows = '';
		$query2 = "SELECT i.* ";
		if ($bill) { $query2 .= ", bs.* "; }
		$query2 .= "FROM inventory i, inventory_history h ";
		if ($bill) {
			$query2 .= "LEFT JOIN bill_shipments bs ON i.id = bs.inventoryid ";
		}
		$query2 .= "WHERE h.field_changed = '".$T['inventory_label']."' AND value = '".$r['id']."' ";
		$query2 .= "AND i.id = h.invid ";
		$query2 .= "GROUP BY i.id; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$qty = $r2['qty'];
			if ($qty==0) { $qty = 1; }
			$qty_recd += $qty;
			$serials[$r2['id']] = array('serial_no'=>$r2['serial_no'],'checked'=>$chkd,'qty'=>$qty);

			$serial = 'Bulk';
			if ($r2['serial_no']) { $serial = $r2['serial_no']; }

			$bill_item_id = 0;
			if ($r2['bill_item_id']) { $bill_item_id = $r2['bill_item_id']; }

			$inner_rows .= '
					<tr class="inner-result">
						<td><input type="hidden" name="bill_item_id['.$r2['id'].']" value="'.$bill_item_id.'"></td>
						<td><span class="info">'.$serial.'</span></td>
						<td> </td>
						<td><span class="info">'.$qty.'</span></td>
						<td colspan="3"> </td>
						<td class="text-right">
							<input type="checkbox" class="inventory-check" name="inventoryid[]" value="'.$r2['id'].'" data-itemid="'.$r['id'].'" data-qty="'.$qty.'" checked />
						</td>
					</tr>
			';

			// get existing bill ids
			if ($bill_no) {
				if ($r2['bill_item_id']) {
					$query3 = "SELECT * FROM bill_items WHERE id = '".$r2['bill_item_id']."'; ";
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					if (mysqli_num_rows($result3)>0) {
						$r3 = mysqli_fetch_assoc($result3);
						// only declare it the first time through so we don't duplicate qty on every serial
						if (! $bi_id) { $qty_billed += $r3['qty']; }

						$amount_billed = $r3['amount'];
					}

					// declare even though it will get set every loop
					$bi_id = $r2['bill_item_id'];
				}
			}
		}

		$itemid = 0;

		$rows .= '
					<tr class="warning">
						<td>'.$r['line_number'].'</td>
						<td>
							<input type="hidden" name="id['.$i.']" value="'.$itemid.'">
							'.$descr.'
						</td>
						<td>'.getWarranty($r['warranty'],'name').'</td>
						<td style="font-weight:bold">'.$r['qty'].'</td>
						<td><span class="info">'.$qty_recd.'</span></td>
						<td class="text-center">
							<div style="padding-left:20%; padding-right:20%">
								<input type="text" name="" value="'.$billed_qty.'" class="form-control input-xs billed_qtys" data-itemid="'.$r['id'].'" data-amount="'.$r['price'].'" readonly />
							</div>
						</td>
						<td class="text-right">'.$r['price'].'</td>
						<td class="text-right" style="font-weight:bold" id="extamt_'.$r['id'].'">'.format_price(round($r['qty']*$r['price'],2),true,' ').'</td>
					</tr>
		'.$inner_rows;

		$i++;
	}
?>

			<div class="col-md-10 container-body">
				<table class="table table-condensed table-striped table-hover">
					<thead>
					<tr>
						<th class="col-sm-1">Ln#</th>
						<th class="col-sm-3">Description</th>
						<th class="col-sm-2">Warranty</th>
						<th class="col-sm-1"><?php echo $T['abbrev']; ?> Qty</th>
						<th class="col-sm-1">Qty Recd</th>
						<th class="col-sm-1 text-center">Billed Qty</th>
						<th class="col-sm-1 text-right">Amount (ea)</th>
						<th class="col-sm-1 text-right">Ext Amount</th>
					</tr>
					</thead>
					<tbody>
						<?php echo $rows; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>


<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

	<script type="text/javascript">
		$(document).ready(function() {
			loadCharges();
			$(".inventory-check").click(function() {
				loadCharges();
			});
		});
		function loadCharges() {
			var billed_qtys = new Array();
			$(".inventory-check").each(function() {
				var itemid = $(this).data('itemid');
				var billed_qty = 0;
				if ($(this).prop('checked')===true) { billed_qty = parseInt($(this).data('qty')); }

				// declare at 0 before adding
				if (typeof billed_qtys[itemid] === 'undefined') { billed_qtys[itemid] = 0; }
				billed_qtys[itemid] += billed_qty;
			});
			$(".billed_qtys").each(function() {
				var itemid = $(this).data('itemid');

				var qty = 0;
				if (typeof billed_qtys[itemid] !== 'undefined') { qty = parseInt(billed_qtys[itemid]); }
				$(this).val(qty);

				var amt = parseFloat($(this).data('amount'));
				var ext = price_format(qty*amt);
				$("#extamt_"+itemid).html(ext);
			});
		}
	</script>

</body>
</html>
