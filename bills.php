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
	$bill_no = '';
	if (isset($_REQUEST['bill']) AND trim($_REQUEST['bill'])) { $bill_no = trim($_REQUEST['bill']); }
	else if (isset($_REQUEST['bill_no']) AND trim($_REQUEST['bill_no'])) { $bill_no = trim($_REQUEST['bill_no']); }

	if (! $order AND $bill_no) {
		$query = "SELECT order_number po_number FROM bills WHERE bill_no = '".res($bill_no)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			die("Could not find bill# ".$bill_no);
		}
		$r = mysqli_fetch_assoc($result);
		$order = $r['po_number'];
		$type = 'Purchase';
	}

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

	$bill_date = '';
	$notes = '';
	$title = '';
	$invoice_no = '';
	$due_date = format_date($today,'m/d/Y',array('d'=>30));
	if ($bill_no) {
		$title = 'Bill# '.$bill_no;

		$query = "SELECT cust_ref invoice_no, due_date, date_created, public_notes notes FROM bills WHERE bill_no = '".res($bill_no)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==1) {
			$r = mysqli_fetch_assoc($result);
			$notes = $r['notes'];
			$bill_date = format_date($r['date_created'],'D n/j/y g:ia');
			$invoice_no = $r['invoice_no'];
			$due_date = format_date($r['due_date'],'m/d/Y');
		}
	} else {
		$title = 'New Bill for '.$T['abbrev'].' '.$order;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $title; ?></title>
	<?php
		include_once 'inc/scripts.php';
	?>

	<style type="text/css">
		.billed_qtys {
			cursor:pointer;
		}
	</style>
</head>
<body>

	<?php include_once 'inc/navbar.php'; ?>

	<form id="bill-form" method="POST" action="save-bill.php">
	<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px">
		<div class="row text-center" style="padding:8px">
			<div class="col-sm-4">
			</div>
			<div class="col-sm-4 text-center">
				<h2 class="minimal"><?php echo $title; ?></h2>
				<span class="info" style="font-size:14px"><?php echo $bill_date; ?></span>
			</div>
			<div class="col-sm-4 text-right">
				<button type="button" class="btn btn-success btn-save"><i class="fa fa-save"></i> Save</button>
			</div>
		</div>
	</div>

	<div class="container-fluid full-height">
		<div class="row full-screen">
			<input type="hidden" name="bill_no" value="<?php echo $bill_no; ?>">
			<input type="hidden" name="order_number" value="<?php echo $order; ?>">
			<input type="hidden" name="companyid" value="<?php echo $companyid; ?>">

			<div class="col-md-2 sidebar" style="width:auto; position:inherit">
				<div class="sidebar-section company-text">
					<h4 class="section-header">Information</h4>
					<?php echo getCompany($companyid); ?> <a href="/profile.php?companyid=<?php echo $companyid; ?>"><i class="fa fa-building"></i></a>
					<h3><?php echo $T['abbrev'].' '.$order; ?> <a href="/<?php echo $T['abbrev'].$order; ?>"><i class="fa fa-arrow-right"></i></a></h3>
				</div>
				<div class="sidebar-section">
					<h4 class="section-header">Vendor Invoice#</h4>
					<input type="text" name="invoice_no" value="<?php echo $invoice_no; ?>" class="form-control input-sm" id="invoice_no">
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
				<div class="sidebar-section">
					<h4 class="section-header">Notes</h4>
					<input type="text" name="notes" value="<?php echo $notes; ?>" class="form-control input-xs">
				</div>
			</div>

<?php
	$i = 0;
	$rows = '';
	$total_charges = 0;
	$query = "SELECT t.*, t.id purchase_item_id ";
//	if ($bill_no) { $query .= ", bi.qty billed_qty, bi.id bill_item_id "; } else { $query .= ", '' billed_qty, '' bill_item_id "; }
	$query .= "FROM ".$T['items']." t ";
//	if ($bill_no) { $query .= ", bill_items bi "; }
	$query .= "WHERE t.".$T['order']." = '".res($order)."' ";
//	if ($bill_no) { $query .= "AND t.partid = bi.partid AND t.line_number = bi.line_number "; }
	$query .= "; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$items[] = $r;
	}

	$query = "SELECT *, amount price, '' purchase_item_id FROM bill_items ";
	$query .= "WHERE partid IS NULL AND memo IS NOT NULL AND item_id IS NULL AND item_id_label IS NULL AND bill_no = '".res($bill_no)."'; ";
	$result = qedb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$items[] = $r;
	}

	foreach ($items as $r) {
		$qty_recd = 0;
		$billed_qty = 0;

		$descr = '';
		if ($r['partid']) {
			$H = hecidb($r['partid'],'id');
			$descr = display_part($H[$r['partid']]);
		} else if (isset($r['memo']) AND $r['memo']) {
			$descr = $r['memo'];
			$qty_recd = $r['qty'];
			$billed_qty = $r['qty'];
		}
//		if ($bill_no) { $billed_qty = $r['billed_qty']; }

		$bi_id = 0;
		$inner_rows = '';
		$query2 = "SELECT i.* ";
		if ($bill_no) { $query2 .= ", bs.* "; }
		$query2 .= "FROM inventory_history h, inventory i ";
		if ($bill_no) {
			$query2 .= "LEFT JOIN bill_shipments bs ON i.id = bs.inventoryid ";
		}
		$query2 .= "WHERE h.field_changed = '".$T['inventory_label']."' AND h.value = '".$r['purchase_item_id']."' ";
		$query2 .= "AND i.id = h.invid ";
		$query2 .= "GROUP BY i.id; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$qty = $r2['qty'];
			if ($qty==0) { $qty = 1; }
			$qty_recd += $qty;

			$serial = 'Non-Serialized Qty';
			if ($r2['serial_no']) { $serial = $r2['serial_no']; }

			$bill_item_id = 0;
			$chk = '';
			if ($r2['bill_item_id']) {
				$bill_item_id = $r2['bill_item_id'];
			}
			if (! $bill_no OR $r2['bill_item_id']) {
				$chk = ' checked';
			}

			$inner_rows .= '
								<tr>
									<td class="col-sm-1"> </td>
									<td class="col-sm-6"><span class="info">'.$serial.'</span></td>
									<td class="col-sm-1"><span class="info">'.$qty.'</span></td>
									<td class="col-sm-3"> </td>
									<td class="col-sm-1" text-right">
										<input type="checkbox" class="item-check inventory-check" name="inventoryid['.$i.'][]" value="'.$r2['id'].'" data-itemid="'.$r['id'].'" data-qty="'.$qty.'" '.$chk.'/>
									</td>
								</tr>
			';

			// get existing bill ids
			if ($bill_no) {
				$query3 = "";
				if ($r2['bill_item_id']) {
					$query3 = "SELECT * FROM bill_items WHERE id = '".$r2['bill_item_id']."'; ";
				} else if (! $r2['packageid']) {
					$query3 = "SELECT * FROM bill_items ";
					$query3 .= "WHERE item_id_label = '".$T['inventory_label']."' AND item_id = '".$r['id']."' AND bill_no = '".$bill_no."'; ";
				}
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
				if (mysqli_num_rows($result3)>0) {
					$r3 = mysqli_fetch_assoc($result3);
					// only declare it the first time through so we don't duplicate qty on every serial
					if (! $bi_id) { $billed_qty += $r3['qty']; }

					$amount_billed = $r3['amount'];
//commented 1/17/18 since $billed_qty above is already counting
//					$billed_qty += $r3['qty'];
				}

				// declare even though it will get set every loop
				$bi_id = $r2['bill_item_id'];
			}
		}

		$itemid = 0;
		if ($bill_item_id) { $itemid = $bill_item_id; }

		$rows .= '
					<tr class="warning">
						<td>'.$r['line_number'].'</td>
						<td>
							<input type="hidden" name="items['.$i.']" value="'.$r['id'].'">
							<input type="hidden" name="item_labels['.$i.']" value="'.$T['item_label'].'">
							<input type="hidden" name="bill_items['.$i.']" value="'.$itemid.'">
							<input type="hidden" name="partids['.$i.']" value="'.$r['partid'].'">
							<input type="hidden" name="amounts['.$i.']" value="'.$r['price'].'">
							<input type="hidden" name="warranties['.$i.']" value="'.$r['warranty'].'">
							<input type="hidden" name="lns['.$i.']" value="'.$r['line_number'].'">
							'.$descr.'
						</td>
						<td>'.getWarranty($r['warranty'],'name').'</td>
						<td style="font-weight:bold">'.$r['qty'].'</td>
						<td><span class="info">'.$qty_recd.'</span></td>
						<td class="text-center">
							<div style="padding-left:20%; padding-right:20%" data-toggle="tooltip" data-placement="bottom" title="Auto-calcs qty when recd inventory is checked">
								<input type="text" name="qtys['.$i.']" value="'.$billed_qty.'" class="form-control input-xs billed_qtys" data-itemid="'.$r['id'].'" data-amount="'.$r['price'].'" onFocus="this.select()"/>
							</div>
						</td>
						<td class="text-right">'.$r['price'].'</td>
						<td class="text-right" style="font-weight:bold" id="extamt_'.$r['id'].'">'.format_price(round($r['qty']*$r['price'],2),true,' ').'</td>
						<td class="text-center">
							<input type="checkbox" name="" value="" class="checkInner" checked />
						</td>
					</tr>
					<tr class="inner-result">
						<td colspan="9">
							<table class="table table-condensed table-results text-left">
								'.$inner_rows.'
							</table>
						</td>
					</tr>
		';

		$total_charges += ($r['qty']*$r['price']);

		$i++;
	}
?>

			<div class="col-md-10 container-body">
				<table class="table table-condensed table-striped table-hover">
					<thead>
					<tr>
						<th class="col-sm-1">Ln#</th>
						<th class="col-sm-4">Description</th>
						<th class="col-sm-1">Warranty</th>
						<th class="col-sm-1"><?php echo $T['abbrev']; ?> Qty</th>
						<th class="col-sm-1">Qty Recd</th>
						<th class="col-sm-1 text-center">Billed Qty</th>
						<th class="col-sm-1 text-right">Amount (ea)</th>
						<th class="col-sm-1 text-right">Ext Amount</th>
						<th class="col-sm-1 text-center"> </th>
					</tr>
					</thead>
					<tbody>
						<?php echo $rows; ?>
						<tr style="margin-top:3px">
							<td class="text-right" colspan="8" style="font-size:13px">Total:</td>
							<td class="text-right">
								<div id="total" style="font-weight:bold; font-size:14px; background-color:#fff; border:1px solid #999; padding:2px 8px 2px 8px"><?php echo format_price($total_charges,true,' '); ?></div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	</form>


<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

	<script type="text/javascript">
		$(document).ready(function() {
			loadCharges();
			$(".inventory-check, .checkInner").click(function() {
				loadCharges();
			});
			$(".billed_qtys").on('click change keyup',function(e) {
				if (manual_entry!='') {
					loadCharges();
					return;
				}
				$(this).blur();

				var msg = "By manually entering a Billed Qty, you are overriding the system's ability "+
						"to track inventory receipts against this Bill ('Manual Entry Mode'). "+
						"Please confirm that you do NOT want this Bill to be linked to this order's inventory receipts.";
				modalAlertShow("Manual Entry Mode",msg,true,'confirmManualEntry',$(this));
			});
			$(".btn-save").click(function() {
				$("#invoice_no").val($("#invoice_no").val().trim());
				var inv = $("#invoice_no").val();
				if (inv=='') {
					alert("Don't forget the Invoice No.!");
					return;
				}
				$(this).closest("form").submit();
			});
		});
		var manual_entry = '<?php echo $bill_no; ?>';
		function confirmManualEntry(e) {
			manual_entry = true;
			loadCharges();
		}
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
			var total = 0;
			$(".billed_qtys").each(function() {
				var itemid = $(this).data('itemid');

				var qty = 0;
				if ($(this).val()!='') { qty = parseInt($(this).val()); }
				if (manual_entry==='' && typeof billed_qtys[itemid] !== 'undefined') { qty = parseInt(billed_qtys[itemid]); }
				$(this).val(qty);

				var amt = parseFloat($(this).data('amount'));
				var ext = qty*amt;
				total += ext;
				$("#extamt_"+itemid).html(price_format(ext));
			});
			$("#total").html(price_format(total));
		}
	</script>

</body>
</html>
