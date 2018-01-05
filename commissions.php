<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRep.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getInvoice.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPaidAmount.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCredits.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getAddresses.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getClass.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getTerms.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSalesReps.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/calcQuarters.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/cmp.php';

	$DEBUG = 0;

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

	$order = '';
	if (isset($_REQUEST['order']) AND trim($_REQUEST['order'])) {
		$order = trim($_REQUEST['order']);
	}
	if (isset($_REQUEST['s']) AND trim($_REQUEST['s'])) {
		$order = trim($_REQUEST['s']);
	}

	$history_date = '';
	if (isset($_REQUEST['history_date']) AND $_REQUEST['history_date']){
		$history_date = $_REQUEST['history_date'];
	}


	/***** FILTER VALUES *****/

	$comm_reps = array();
	$pending_comms = array();
	// restrict user access to other rep's info if they don't have management privileges
	$user_admin = false;
	if (! in_array("4", $USER_ROLES)) {
		$rep_filter = $U['id'];
		$reps_list = getSalesReps($rep_filter,true);
	} else {
		$user_admin = true;
		$reps_list = getSalesReps($rep_filter);
	}

	$startDate = format_date($today,'01/01/Y',array('y'=>-1));
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}

	$endDate = date('m/d/Y');
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}

	$status_filter = 'active';
	if (isset($_REQUEST['status_filter']) AND $_REQUEST['status_filter']) {
		$status_filter = $_REQUEST['status_filter'];
	}

	/***** END FILTERS *****/


	$types = array('Sale','Repair','Service');
//	$types = array('Service');

	$date_today = strtotime($now);
	$secs_per_day = 60*60*24;

	$comms = array();
	foreach ($types as $order_type) {
		$T = order_type($order_type);

		$query = "SELECT c.invoice_no, c.invoice_item_id, c.inventoryid, c.item_id taskid, c.item_id_label task_label, c.id commissionid, ";
		$query .= "c.datetime, c.cogsid, c.rep_id, c.commission_rate, c.commission_amount, i.*, o.* ";
		$query .= "FROM ";
		if ($history_date) { $query .= "commission_payouts p, "; }
		$query .= "commissions c, invoices i ";
		$query .= "LEFT JOIN ".$T['orders']." o ON i.order_number = o.".$T['order']." ";
		$query .= "WHERE c.invoice_no = i.invoice_no AND order_type = '".$order_type."' ";
		if ($history_date) {
			$query .= "AND c.id = p.commissionid AND paid_date LIKE '".res($history_date)."%' ";
		} else {
			if ($startDate) {
				$dbStartDate = format_date($startDate, 'Y-m-d').' 00:00:00';
				$dbEndDate = format_date($endDate, 'Y-m-d').' 23:59:59';
				//invoice date-based
				$query .= "AND o.".$T['datetime']." BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
				//commission date-based
				//$query .= "AND c.datetime BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
			}
		}
		if ($order) { $query .= "AND (i.invoice_no = ".fres($order)." OR o.".$T['order']." = ".fres($order).") "; }
		$query .= "AND i.status <> 'Void' ";
//		$query .= "GROUP BY c.invoice_item_id ";
		$query .= "ORDER BY c.invoice_no, c.invoice_item_id, c.inventoryid, c.rep_id, c.id ASC ";//c.commission_amount ASC ";
		$query .= "; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$key = substr($r['date_invoiced'],0,10).'.'.$r['invoice_no'].'.'.$r['order_type'].'.'.$r['order_number'];

			$r['rep_name'] = getRep($r['rep_id']);

			$r['payouts'] = array();//array('paid_date'=>'','paid_amount'=>'','paid_userid'=>'');
			$r['commission_paid'] = false;//$r['commission_amount'];

			$query2 = "SELECT paid_date, amount paid_amount, userid paid_userid FROM commission_payouts WHERE commissionid = '".$r['commissionid']."'; ";
			$result2 = qedb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$r['payouts'][] = $r2;
				if ($r['commission_paid']===false) { $r['commission_paid'] = 0; }//set to int
				$r['commission_paid'] += $r2['paid_amount'];
			}
			$comms[$key][] = $r;
		}
	}

//	echo '<BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR>';
	// sort array by key in ascending order
	ksort($comms);
	foreach ($comms as $key => $balances) {
//		print "<pre>".print_r($balances,true)."</pre>";

		// sort by sales rep
		uasort($balances,$CMP('rep_name','ASC'));

		// sort by inventoryid within sales rep grouping
//		uasort($balances,$CMP('inventoryid','ASC'));
//		print "<pre>".print_r($balances,true)."</pre>";

		$order_type = '';
		$date_invoiced = '';
		$sales_rep_id = 0;
		$order_number = 0;
		$companyid = 0;
		$invoice_no = 0;
		$charges = 0;
		$total_paid = 0;
		$payments = 0;
		$credits = 0;
		$chk = '';
		$inners = '';
		$i = 0;
		$last_rep = 0;
		$last_descr = '';
		$last_details = '';

		foreach ($balances as $k => $comm) {
			$invoice_item_id = $comm['invoice_item_id'];
			$balance = $comm['commission_amount'];

			if (! $history_date) {
				if ($comm['commission_paid']!==false) { $balance -= $comm['commission_paid']; }

				if (! $balance AND $status_filter<>'all') { continue; }
			}

			$rep_id = $comm['rep_id'];
			$inventoryid = $comm['inventoryid'];

			if ($i==0) {
				$order_type = $comm['order_type'];
				$date_invoiced = $comm['date_invoiced'];
				$sales_rep_id = $comm['sales_rep_id'];
				$order_number = $comm['order_number'];
				$companyid = $comm['companyid'];
				$invoice_no = $comm['invoice_no'];
				$charges = getInvoice($invoice_no);
				$payments = getPaidAmount($invoice_no);
				$credits = getCredits($order_number,$order_type);
				$total_paid = $payments+$credits;
			}

			$item = array();
			$query2 = "SELECT * FROM invoice_items WHERE id = '".$invoice_item_id."'; ";
			$result2 = qedb($query2);
			if (mysqli_num_rows($result2)>0) {
				$item = mysqli_fetch_assoc($result2);
			}

			$details = '';
			$source = '';
			$charge = 0;
			$cogs = getCOGSById($comm['cogsid']);
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
			if (! $history_date AND $cogs>$charge) {
				// changed this 1/4/18 because I don't think $cogs should actually be reset to $charge, just $profit should reflect the hit
				//$cogs = $charge;

				$balance = 0;
				if ($comm['commission_paid']!==false AND $status_filter<>'all') { continue; }
			}
			$profit = round($charge-$cogs,2);

			// when order type is set from variable within array above, display info as a header row
			if ($order_type) {
				$T = order_type($order_type);

				$class = $T['abbrev'];
				if (array_key_exists('task_name',$comm) AND $comm['task_name']) { $class = $comm['task_name']; }
				else if (array_key_exists('classid',$comm) AND $comm['classid']) { $class = getClass($comm['classid']); }

				// calculate 'on time' of payment within scope of terms based on termsid
				$order_date = strtotime($comm['date_invoiced']);//$r[$T['datetime']]);
				$age_days = floor(($date_today-$order_date)/($secs_per_day));

				$due_days = getTerms($comm['termsid'],'id','days');

				$row_cls = 'active';
				if ($total_paid>=$charges OR $history_date) {
					$row_cls = 'success';
					$chk = ' checked';
				} else if ($total_paid>0) {
					$row_cls = 'warning';
				} else if ($age_days>$due_days) {
					$row_cls = 'danger';
				}

				$order_ln = '';
				if ($order_number) {
					$order_ln = '<a href="/order.php?order_type='.$order_type.'&order_number='.$order_number.'" target="_new"><i class="fa fa-arrow-right"></i></a>';
				}
				$company_ln = '';
				if ($companyid) {
					$company_ln = '<a href="/profile.php?companyid='.$companyid.'" target="_new"><i class="fa fa-building"></i></a>';
				}

				$inners .= '
			<tr class="'.$row_cls.'">
				<td>'.format_date($date_invoiced,'n/j/y').'</td>
				<td>'.strtoupper(getRep($sales_rep_id,'id','first_name')).'</td>
				<td>'.($order_number ? $class : '').' '.$order_number.' '.$order_ln.'</td>
				<td>'.strtoupper(getCompany($companyid)).' '.$company_ln.'</td>
				<td>INV# '.$invoice_no.' <a href="/invoice.php?invoice='.$invoice_no.'" target="_new"><i class="fa fa-file-pdf-o"></i></a></td>
				<td class="text-amount">'.format_price($charges,true,' ').'</td>
				<td class="text-amount">'.format_price($total_paid,true,' ').'</td>
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
							<tr class="comms">
								<th class="col-sm-1"> </th>
								<th class="col-sm-2"><span class="line"></span><strong>'.getRep($rep_id).'</strong></th>
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
			$show_descr = $descr;
			$ln = '';
			if ($rep_id.'.'.$invoice_item_id.'.'.$descr==$last_descr) {
				$show_descr = '<span class="info">- same -</span>';
			} else if ($item['line_number']) {
				$ln = '<span class="info">'.$item['line_number'].'.</span> ';
			}

			$show_charge = format_price($charge,true,' ');
			$show_profit = format_price($profit,true,' ');
			$show_details = $details;
			if ($inventoryid) {
				$show_details .= ' <a href="javascript:void(0);" class="inventory-details" data-inventoryid="'.$inventoryid.'" data-toggle="tooltip" data-placement="bottom" title="'.$inventoryid.'"><i class="fa fa-history"></i></a>';
			}
			if ($rep_id.'.'.$details==$last_details) {
				$show_details = '<span class="info">- same -</span>';
				$show_charge = '';
				$show_profit = '';
			}

			// updating for stats banner
			if (! isset($comm_reps[$rep_id])) { $comm_reps[$rep_id] = 0; }
			if ($chk) { $comm_reps[$rep_id] += $balance; }

			if (! isset($pending_comms[$rep_id])) { $pending_comms[$rep_id] = 0; }
			$pending_comms[$rep_id] += $balance;

			$comm_rate = '';
			if ($comm['commission_rate']) {
				$comm_rate = ' ('.$comm['commission_rate'].'%)';
			}

			$inners .= '
							<tr class="comms">
								<td><input type="checkbox" name="comm['.$comm['commissionid'].']" class="comm-item" data-repid="'.$rep_id.'" value="'.$balance.'"'.$chk.'></td>
								<td>'.$ln.$show_descr.'</td>
								<td>'.$show_details.'</td>
								<td>'.$source.'</td>
								<td class="text-amount">'.$show_charge.'</td>
								<td class="text-amount">'.format_price($cogs,true,' ').'</td>
								<td class="text-amount"><strong>'.$show_profit.'</strong></td>
								<td class="text-amount">'.format_price($comm['commission_amount'],true,' ').$comm_rate.'</td>
								<td class="text-amount">'.format_price($comm['commission_paid'],true,' ').'</td>
								<td class="text-amount active">'.format_price($balance,true,' ').'</td>
								<td> </td>
							</tr>
			';
			$last_descr = $rep_id.'.'.$invoice_item_id.'.'.$descr;
			$last_details = $rep_id.'.'.$details;
			$i++;
		}/* end $balances */

		if ($inners) {
			$rows .= $inners.'
					</table>
				</td>
			</tr>
			';
		}
	}

	$comm_stats = '';
	$num_reps = count($comm_reps);

	// only user admins have privilege to approve commissions
	if ($user_admin) {
		$form_action = 'save-commissions.php';
		$col_width = floor(10/$num_reps);
	} else {
		$form_action = 'commissions.php';
		$col_width = floor(12/$num_reps);
	}

//	print "<pre>".print_r($comm_reps,true)."</pre>";
	foreach ($comm_reps as $rep_id => $rep_amt) {
		$pending_amt = 0;
		if (isset($pending_comms[$rep_id])) { $pending_amt = $pending_comms[$rep_id]; }

		$comm_stats .= '
                <div class="col-md-'.$col_width.' col-sm-'.$col_width.' stat">
                    <div class="data" id="'.$rep_id.'">
                        <span class="number text-brown">'.format_price(round($rep_amt,2),true,'').'</span>
						<span class="info"><label><input type="checkbox" class="comm-master" checked> <span class="rep-name">'.getRep($rep_id).'</span></label></span>
                    </div>
					'.(! $history_date ? '<span class="aux">'.format_price($pending_amt,true,'').' total pending</span>' : '').'
                </div>
		';
	}
	if ($user_admin AND $comm_stats AND ! $history_date) {
		$save_width = 12-($col_width*$num_reps);
		$comm_stats .= '
                <div class="col-md-'.$save_width.' col-sm-'.$save_width.' stat">
                    <div class="data">
                        <span class="number text-brown"></span>
						<span class="info"><button class="btn btn-success approve-comms" type="button">Approve Commissions</button></span>
                    </div>
                </div>
		';
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
		.table-filter > div,
		.table-filter > form > div {
			margin:8px !important;
		}
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
<div class="table-header table-filter" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form" >

	<div class="row">
		<div class="col-sm-1">
		    <div class="btn-group medium">
		        <button data-toggle="tooltip" name="status_filter" type="submit" value="active" data-placement="right" title="" data-filter="active_radio" data-original-title="Active" class="btn btn-default btn-sm left filter_status <?=($status_filter == 'active' || !$status_filter ? 'active btn-warning' : '');?>">
		        	<i class="fa fa-sort-numeric-desc"></i>	
		        </button>

		        <button data-toggle="tooltip" name="status_filter" type="submit" value="complete" data-placement="right" title="" data-filter="complete_radio" data-original-title="Complete" class="btn btn-default btn-sm middle filter_status <?=($status_filter == 'complete' ? 'active btn-success' : '');?>">
		        	<i class="fa fa-history"></i>	
		        </button>

				<button data-toggle="tooltip" name="status_filter" type="submit" value="all" data-placement="right" title="" data-filter="all_radio" data-original-title="All" class="btn btn-default btn-sm right filter_status <?=(($status_filter == 'all') ? 'active btn-info' : '');?>">
		        	All
		        </button>
		    </div>
		</div>
		<div class="col-sm-1">
		    <div class="btn-group">
				<select name="repid" id="repid" class="rep-selector form-control input-sm select2">
					<?php echo $reps_list; ?>
				</select>
		    </div>
		</div>
		<div class="col-sm-3">
			<div class="form-group">
				<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
		            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>">
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		        </div>
			</div>
			<div class="form-group">
				<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>">
		            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>">
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
			    </div>
			</div>
			<div class="form-group">
					<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
					<div class="btn-group" id="dateRanges">
						<div id="btn-range-options">
							<button class="btn btn-default btn-sm">&gt;</button>
							<div class="animated fadeIn hidden" id="date-ranges">
						        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="<?php echo date("m/01/Y"); ?>" data-end="<?php echo date("m/d/Y"); ?>">MTD</button>
<?php
	$quarters = calcQuarters();
	foreach ($quarters as $qnum => $q) {
		echo '
				    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="'.$q['start'].'" data-end="'.$q['end'].'">Q'.$qnum.'</button>
		';
	}

	for ($m=1; $m<=4; $m++) {
		$month = format_date($today,'M m/t/Y',array('m'=>-$m));
		$mfields = explode(' ',$month);
		$month_name = $mfields[0];
		$mcomps = explode('/',$mfields[1]);
		$MM = $mcomps[0];
		$DD = $mcomps[1];
		$YYYY = $mcomps[2];
		echo '
								<button class="btn btn-sm btn-default right small btn-report" type="button" data-start="'.date($MM."/01/".$YYYY).'" data-end="'.date($MM."/".$DD."/".$YYYY).'">'.$month_name.'</button>
		';
	}
?>
							</div><!-- animated fadeIn -->
						</div><!-- btn-range-options -->
					</div><!-- btn-group -->
			</div><!-- form-group -->
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"></span>
		</div>
		<div class="col-sm-2">
			<div class="input-group">
				<input type="text" name="order" class="form-control input-sm" value ='<?php echo $order?>' placeholder = "Order #"/>
				<span class="input-group-btn">
					<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
				</span>
			</div>
		</div>
		<div class="col-sm-1">
<?php
	$payouts = '<option value="">- Comm History -</option>'.chr(10);
	$query = "SELECT LEFT(paid_date,10) date FROM commission_payouts GROUP BY date ORDER BY paid_date DESC LIMIT 0,30; ";
	$result = qdb($query) OR die('Could not get commission payouts history');
	while ($r = mysqli_fetch_assoc($result)) {
		$s = '';
		if ($history_date==$r['date']) { $s = ' selected'; }
		$payouts .= '<option value="'.$r['date'].'"'.$s.'>'.format_date($r['date'],'n/j/y').'</option>'.chr(10);
	}
?>
			<div class="input-group">
				<select name="history_date" size="1" class="form-control input-sm select2 select-history" style="width:140px">
					<?php echo $payouts; ?>
				</select>
				<span class="input-group-btn">
					<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
				</span>
			</div>
		</div>
		<div class="col-sm-2">
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">

<!-- upper main stats -->
<?php if ($comm_stats) { ?>
		<style type="text/css">
			#pad-wrapper {
				margin-top:170px;
			}
		</style>
        <div id="main-stats" style="position:fixed; width:auto; left:0px; right:0px; top:93px; z-index:1001; box-shadow: 2px 1px 2px #888888; opacity:.9">
			<button type="button" class="btn btn-default show-comms form-control">Show Commissions</button>
            <div class="row stats-row hidden">
				<?php echo $comm_stats; ?>
            </div>
        </div>
<?php } ?>
<!-- end upper main stats -->

<form class="form-inline" method="get" action="<?=$form_action;?>" enctype="multipart/form-data" id="comm-form">

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

<?php include_once 'modal/inventory_details.php'; ?>
<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
		$(".show-comms").on("click", function() {
			$(this).hide();
			$("#main-stats").find(".stats-row").removeClass('hidden');
		});
		// calc commissions based on checked items
		$(".comm-item").on("click",function() {
			updateCommissions();
		});
		$(".select-history").on("change",function() {
			$(this).closest("form").find("input[name='order']").val('');
		});
		$(".comm-master").on("click",function() {
			var repid = $(this).closest(".data").attr('id');
			var rep_checked = $(this).prop('checked');
			$(".comm-item").each(function() {
				if ($(this).data('repid')!=repid) { return; }
				$(this).prop('checked',rep_checked);
			});
			updateCommissions();
		});
		$(".inventory-details").on('click', function() {
			var inventoryid = $(this).data('inventoryid');
			var html = '';

            console.log(window.location.origin+"/json/inventory_details.php?inventoryid="+inventoryid);
            $.ajax({
                url: 'json/inventory_details.php',
                type: 'get',
                data: {'inventoryid': inventoryid },
                success: function(json, status) {
					if (json.message && json.message!='Success') {
						alert(json.message);
						return;
					}

					$.each(json.results, function(k, item) {
						html += item.event+'<br/>';
					});

					$("#modal-details").find(".modal-body").html(html);
					$("#modal-details").modal('show');
				},
                error: function(xhr, desc, err) {
//					console.log(xhr);
                    console.log("Details: " + desc + "\nError:" + err);
                }
			});
		});
<?php if ($user_admin) { ?>
		$(".approve-comms").on("click",function() {
			updateCommissions();
			var modal_msg = '';
			var rep,amount;
			$("#main-stats").find(".data").each(function() {
				rep = $(this).find(".rep-name");
				if (rep.length==0) { return; }
				amount = $(this).find(".number").html();
				modal_msg += '<div class="row"><div class="col-sm-3">'+rep.html()+'</div><div class="col-sm-9"><strong>'+amount+'</strong></div></div>';
			});
			modalAlertShow('Please confirm approved commission payouts...',modal_msg,true,'approveComms');
		});
<?php } ?>
	});
<?php if ($user_admin) { ?>
		function approveComms() {
			$("#comm-form").submit();
		}
<?php } ?>
		function updateCommissions() {
			$(".stat .data").each(function() {
				var repid = $(this).attr('id');
				if (! repid || repid.length==0) { return; }

				var rep_comm = $(this).find(".number");
				var n = rep_comm.html().replace('$','').replace(',','');
				var amount = 0;
				$(".comm-item:checked").each(function() {
					if ($(this).data('repid')!=repid) { return; }
					amount += parseFloat($(this).val());//data('amount');
				});
				rep_comm.html('$'+amount.formatMoney(2));
			});
		}
</script>

</body>
</html>
