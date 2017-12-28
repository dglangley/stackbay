<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRep.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getInvoice.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPaidAmount.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getAddresses.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getClass.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getTerms.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/cmp.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';

	$DEBUG = 1;

	function getCOGSById($cogsid) {
		$cogs = 0;
		if (! $cogsid) { return ($cogs); }

		$query = "SELECT cogs_avg FROM sales_cogs WHERE id = '".res($cogsid)."'; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) { return ($cogs); }
		$r = mysqli_fetch_assoc($result);
		$cogs = $r['cogs_avg'];

		return ($cogs);
	}

	$startDate = '2017-10-01';
	$endDate = $today;

	$show_status = 'Open';
//	$s = '18427';

	$types = array('Sale','Repair','Service');
	$types = array('Sale');

	$date_today = strtotime($now);
	$secs_per_day = 60*60*24;

	$comms = array();
	foreach ($types as $order_type) {
		$T = order_type($order_type);

		$query = "SELECT c.invoice_no, c.invoice_item_id, c.inventoryid, c.item_id taskid, c.item_id_label task_label, c.id commissionid, ";
		$query .= "c.datetime, c.cogsid, c.rep_id, c.commission_rate, c.commission_amount, i.*, o.* ";
		$query .= "FROM commissions c, invoices i, ".$T['orders']." o ";
		$query .= "WHERE c.invoice_no = i.invoice_no AND i.order_number = o.".$T['order']." AND order_type = '".$order_type."' ";
		if ($startDate) {
			$dbStartDate = format_date($startDate, 'Y-m-d').' 00:00:00';
			$dbEndDate = format_date($endDate, 'Y-m-d').' 23:59:59';
			$query .= "AND o.".$T['datetime']." BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
		}
		if ($s) { $query .= "AND i.invoice_no = ".fres($s)." "; }
//		$query .= "GROUP BY c.invoice_item_id ";
		$query .= "; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$key = substr($r['date_invoiced'],0,10).'.'.$r['invoice_no'].'.'.$r['invoice_item_id'].'.'.$r['order_type'].'.'.$r['order_number'];

			$r['rep_name'] = getRep($r['rep_id']);

			$r['payouts'] = array();//array('paid_date'=>'','paid_amount'=>'','paid_userid'=>'');
			$r['commission_paid'] = 0;//$r['commission_amount'];

			$query2 = "SELECT paid_date, amount paid_amount, userid paid_userid FROM commission_payouts WHERE commissionid = '".$r['commissionid']."'; ";
			$result2 = qedb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$r['payouts'][] = $r2;
				$r['commission_paid'] += $r2['paid_amount'];
			}
			$comms[$key][] = $r;
		}
	}

	// sort array by key in ascending order
	ksort($comms);
	foreach ($comms as $key => $balances) {
		// sort by sales rep
		uasort($r,$CMP('rep_name','ASC'));
//		print "<pre>".print_r($r,true)."</pre>";

		$order_type = '';
		$date_invoiced = '';
		$sales_rep_id = '';
		$order_number = '';
		$companyid = '';
		$invoice_no = '';
		$charges = '';
		$payments = '';

		// gather balances per rep by walking thru all comms within this $key (in essense, invoice)
		// and sum all results within $balances so we can get a net total
/*
		$balances = array();
		foreach ($r as $k => $comm) {
			if (! isset($balances[$comm['invoice_item_id']][$comm['rep_id']][$comm['inventoryid']])) {
				$comm['balance'] = 0;
				$balances[$comm['invoice_item_id']][$comm['rep_id']][$comm['inventoryid']] = $comm;
			}

			$balances[$comm['invoice_item_id']][$comm['rep_id']][$comm['inventoryid']]['commission_amount'] += $comm['commission_amount'];
			$balances[$comm['invoice_item_id']][$comm['rep_id']][$comm['inventoryid']]['commission_paid'] += $comm['commission_paid'];
			$balances[$comm['invoice_item_id']][$comm['rep_id']][$comm['inventoryid']]['balance'] += $comm['commission_amount']-$comm['commission_paid'];

			if ($k==0) {
				$order_type = $comm['order_type'];
				$date_invoiced = $comm['date_invoiced'];
				$sales_rep_id = $comm['sales_rep_id'];
				$order_number = $comm['order_number'];
				$companyid = $comm['companyid'];
				$invoice_no = $comm['invoice_no'];
				$charges = getInvoice($invoice_no);
				$payments = getPaidAmount($invoice_no);
			}
		}
*/
//		print "<pre>".print_r($balances,true)."</pre>";

		$inners = '';
//		$k = 0;
		// walk thru balances
//		foreach ($balances as $invoice_item_id => $items) {
		foreach ($balances as $k => $items) {
			print "<pre>".print_r($items,true)."</pre>";
exit;
			$invoice_item_id = $items['invoice_item_id'];

			if ($k==0) {
				$order_type = $items['order_type'];
				$date_invoiced = $items['date_invoiced'];
				$sales_rep_id = $items['sales_rep_id'];
				$order_number = $items['order_number'];
				$companyid = $items['companyid'];
				$invoice_no = $items['invoice_no'];
				$charges = getInvoice($invoice_no);
				$payments = getPaidAmount($invoice_no);
			}
//			$k++;

			$item = array();
			$query2 = "SELECT * FROM invoice_items WHERE id = '".$invoice_item_id."'; ";
			$result2 = qedb($query2);
			if (mysqli_num_rows($result2)>0) {
				$item = mysqli_fetch_assoc($result2);
			}

			$last_rep = 0;
			foreach ($items as $rep_id => $arr) {

				foreach ($arr as $inventoryid => $v) {
					if (! $v['balance'] AND $show_status<>'All') { continue; }

					// when order type is set from variable within array above, display info as a header row
					if ($order_type) {
						$T = order_type($order_type);

						$class = $T['abbrev'];
						if (array_key_exists('task_name',$v) AND $v['task_name']) { $class = $v['task_name']; }
						else if (array_key_exists('classid',$v) AND $v['classid']) { $class = getClass($v['classid']); }

						// calculate 'on time' of payment within scope of terms based on termsid
						$order_date = strtotime($r[$T['datetime']]);
						$age_days = floor(($date_today-$order_date)/($secs_per_day));

						$due_days = getTerms($v['termsid'],'id','days');

						$row_cls = 'active';
						if ($payments>=$charges) { $row_cls = 'success'; }
						else if ($payments>0) { $row_cls = 'warning'; }
						else if ($age_days>$due_days) { $row_cls = 'danger'; }

						$inners .= '
			<tr class="'.$row_cls.'">
				<td>'.format_date($date_invoiced,'n/j/y').'</td>
				<td>'.strtoupper(getRep($sales_rep_id,'id','first_name')).'</td>
				<td>'.$class.' '.$order_number.'</td>
				<td>'.strtoupper(getCompany($companyid)).'</td>
				<td>INV# '.$invoice_no.'</td>
				<td class="text-amount">'.format_price($charges,true,' ').'</td>
				<td class="text-amount">'.format_price($payments,true,' ').'</td>
			</tr>
			<tr>
				<td colspan=7>
					<table class="table table-condensed" style="border-left:1px solid #ccc; border-right:1px solid #ccc; border-bottom:1px solid #ccc">
						';

						$order_type = '';
						$date_invoiced = '';
						$sales_rep_id = '';
						$order_number = '';
						$companyid = '';
						$invoice_no = '';
					}

					if ($rep_id<>$last_rep) {
						$inners .= '
						<thead>
							<tr class="comms">
								<th class="col-sm-1"> </th>
								<th class="col-sm-2"><span class="line"></span><strong>'.getRep($v['rep_id']).'</strong></th>
								<th class="col-sm-1">Details</th>
								<th class="col-sm-1">Source</th>
								<th class="col-sm-1 text-center"><span class="line"></span>Charge</th>
								<th class="col-sm-1 text-center"><span class="line"></span>COGS (Avg)</th>
								<th class="col-sm-1 text-center"><span class="line"></span>Profit</th>
								<th class="col-sm-1 text-center"><span class="line"></span>Comm Amount</th>
								<th class="col-sm-1 text-center"><span class="line"></span>Comm Paid</th>
								<th class="col-sm-1 text-center active"><span class="line"></span>Comm Due</th>
								<th class="col-sm-1"> </th>
							</tr>
						</thead>
						<tbody>
						';
						$last_rep = $rep_id;
					}

					$descr = '';
					if ($item['item_id']) {
						if ($item['item_label']=='partid' OR ! $item['item_label']) {
							getPart($item['item_id']);
							$P = $PARTS[$item['item_id']];
							$descr = trim($P['part'].' '.$P['heci']);
						} else if ($item['item_label']=='addressid') {
							$descr = address_out($item['item_id'],false,', ');
						}
					} else {
						$descr = $item['memo'];
					}

					$details = '';
					$source = '';
					$charge = 0;
					$cogs = getCOGSById($v['cogsid']);
					$cogs = round($cogs,2);
					if ($inventoryid) {
						$I = getInventory($inventoryid);
						if ($I['serial_no']) {
							$details = $I['serial_no'];
						} else {
							$details = 'Lot Qty '.$I['qty'];
						}
						$charge = $I['qty']*$item['amount'];

						if ($item['taskid'] AND $item['task_label']) {//new format
						} else {//legacy support
						}
					} else {
						$charge = $item['qty']*$item['amount'];
					}
					if ($cogs>$charge) { $cogs = $charge; }
					$profit = round($charge-$cogs,2);

					$inners .= '
							<tr class="comms">
								<td><input type="checkbox"></td>
								<td>'.$descr.'</td>
								<td>'.$details.'</td>
								<td>'.$source.'</td>
								<td class="text-amount">'.format_price($charge,true,' ').'</td>
								<td class="text-amount">'.format_price($cogs,true,' ').'</td>
								<td class="text-amount"><strong>'.format_price($profit,true,' ').'</strong></td>
								<td class="text-amount">'.format_price($v['commission_amount'],true,' ').' ('.$v['commission_rate'].'%)</td>
								<td class="text-amount">'.format_price($v['commission_paid'],true,' ').'</td>
								<td class="text-amount active">'.format_price($v['balance'],true,' ').'</td>
								<td> </td>
							</tr>
					';
				}/* end $arr */
			}/* end $items */
		}/* end $balances */

		if ($inners) {
			$rows .= $inners.'
						</tbody>
					</table>
				</td>
			</tr>
			';
		}
	}

	$TITLE = 'Commissions';
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
		.comms td,
		.comms th {
			font-size:95% !important;
		}
		.comms th {
			color:#aaa;
		}
		.text-amount {
			text-align:right;
			padding-right:16px !important;
		}
		.text-amount.active {
			font-weight:bold;
			font-size:105% !important;
		}
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

	<table class="table table-condensed table-striped table-inner">
		<thead>
			<tr>
				<th class="col-sm-1">Invoice Date</th>
				<th class="col-sm-1">Sales Rep</th>
				<th class="col-sm-1">Order No.</th>
				<th class="col-sm-3">Company</th>
				<th class="col-sm-2">Invoice</th>
				<th class="col-sm-1 text-center">Total Charges</th>
				<th class="col-sm-1 text-center">Payments</th>
			</tr>
		</thead>
		<tbody>
			<?=$rows;?>
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
