<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getRecords.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/calcTerm.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/calcQuarters.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/display_part.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/cmp.php';

	// TYPE = (Repair, Sale, Purchase) array
	$order_filter =  isset($_REQUEST['keyword']) ? $_REQUEST['keyword'] : '';
	if (! isset($types)) { $types = array(); }
	if (isset($_REQUEST['order_type'])) {
		$types =  $_REQUEST['order_type'];
	}
	$startDate = isset($_REQUEST['START_DATE']) ? $_REQUEST['START_DATE'] : format_date($GLOBALS['now'],'m/d/Y',array('d'=>-60));
	$endDate = (isset($_REQUEST['END_DATE']) AND ! empty($_REQUEST['END_DATE'])) ? $_REQUEST['END_DATE'] : format_date($GLOBALS['now'],'m/d/Y');
	$company_filter = isset($_REQUEST['companyid']) ? ucwords($_REQUEST['companyid']) : '';
	$view = isset($_REQUEST['view']) ? $_REQUEST['view'] : '';

	$filter = 'active';
	if (isset($_REQUEST['filter']) AND $_REQUEST['filter']) {
		$filter = $_REQUEST['filter'];
	}

	// Search with the global top filter
	if (isset($_REQUEST['s']) AND $_REQUEST['s']) {
		$report_type = 'detail';
		$order_filter = $_REQUEST['s'];
	}

	//Report type is set to summary as a default. This is where the button functionality comes in to play
	if (! isset($report_type) AND ! $DEV_ENV) {//might be set from a meta script such as receivables.php
		if (isset($_REQUEST['report_type']) AND ($_REQUEST['report_type']=='summary' OR $_REQUEST['report_type']=='detail')) { $report_type = $_REQUEST['report_type']; }
		else if (isset($_COOKIE['report_type']) AND ($_COOKIE['report_type']=='summary' OR $_COOKIE['report_type']=='detail')) { $report_type = $_COOKIE['report_type']; }
		//This is saved as a cookie in order to cache the results of the button function within the same window
		setcookie('report_type', $report_type, (time() + (7 * 24 * 60 * 60)));
	}

	// Global Variables being used
	$invoice_amt = 0;
	$payment_amt = 0;

	$subTotal = 0;
	$creditTotal = 0;
	$paymentTotal = 0;
	$amountTotal = 0;

	// because report type can be changed temporarily below, based on user searches, we don't want the main context to be overridden
	// throughout clicks on this page in case the user is otherwise accustomed to a particular setting, see above
	$master_report_type = $report_type;
	//$master_report_type = 'summary';

	//$types = explode(',', $types);

	// Be aware that $T is now an array of all the types instead of 1
	$Ts = array();

	foreach($types as $type) {
		$Ts[] = order_type($type);
	}

	function getInvoiceData($order_number, $order_type, $T) {
		/***** INVOICE DATA *****/
		global $invoice_amt;

		$invoice_amt = 0;

		$freight = 0;
		$sales_tax = 0;
		$invoices = array();

		$query = "SELECT ".$T['order']." FROM ".$T['orders']." WHERE order_number =".fres($order_number)." AND order_type=".fres($order_type)." AND status <> 'Void';";

		$result = qedb($query);

		while ($r = mysqli_fetch_assoc($result)) {
			$query2 = "SELECT qty, amount FROM ".$T['items']." WHERE ".$T['order']." = '".$r[$T['order']]."';";

			$result2 = qedb($query2);

			while ($r2 = mysqli_fetch_assoc($result2)) {
				$invoice_amt += ($r2['qty']*$r2['amount']);
			}

			$r['invoice_total'] = $invoice_amt;

			$invoices[] = $r;
		}

		return $invoices;
	}

	function summarizeOrders($ORDERS, $filter) {
		global $order_filter, $filter;

		$summarized_orders = array();

		foreach ($ORDERS as $order){
			$order_amt = $order['price'] * $order['qty'];
			$charges = 0;

			if ($order['status']=='Void' AND $filter<>'all') { continue; }

			$status = 'complete';
			// This is to filter out orders if the user decides to search for a specific order
			if ($order_filter AND $order_filter <> $order['order_num']) { continue; }

			if (array_key_exists('repair_code_id',$order)) {
				if (! $order['repair_code_id'] AND $filter<>'all') { continue; }
			}

			$order['price'] = format_price($order['price'],true,'',true);
			$total_pcs += $order['qty'];

			if(!array_key_exists($order['order_num'], $summarized_orders)){
				$summarized_orders[$order['order_num']] = array(
					'date' => '',
					'cid' => 0,
					'company' => '',
					'items' => '',
					'summed' => 0,
					'order_subtotal' => 0,
				);
			}

			$item_id = 0;
			$complete_qty = 0;

			$T = order_type($order['order_type']);

			// Missing needed variables being used within the queries
			switch ($order['order_type']) {
				case 'Service':
					$fqty_field = '';
					$credit_table = '';
					break;
				case 'Outsourced':
					$fqty_field = '';
					$credit_table = '';
					break;
				case 'Purchase':
					$fqty_field = 'qty_received';
					$credit_table = 'credit_items';
					break;
				case 'Repair':
					$fqty_field = 'qty';
					$credit_table = '';
					break;
				case 'Sale':
				default:
					$fqty_field = 'qty_shipped';
					$credit_table = 'credit_items';
					break;
			}

			if($fqty_field) {
				$query = "SELECT *, ".$fqty_field." complete_qty FROM ".$T['items']." WHERE partid = ".fres($order['partid'])." AND ".$T['order']." = ".fres($order['order_num']).";";
				$result = qedb($query);

				if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$item_id = $r['id'];
					$complete_qty = $r['complete_qty'];
				}
			}

			if ($T['charges']) {
				$query = "SELECT * FROM ".$T['charges']." WHERE ".$T['order']." = ".fres($order['order_num'])."; ";
				$result = qedb($query);

				while ($r2 = mysqli_fetch_assoc($result)) {
					$charges += $r2['qty']*$r2['price'];
				}
			}

	        $credit_total = 0;

	        if ($item_id) {
	        	$credit_total = getCredit($order['order_type'], $item_id, $T['item_label'], $order['order_num']);
	        } 

	        if($order['qty'] > $complete_qty) {
	        	$status = 'active';
		    }

			$summarized_orders[$order['order_num']]['date'] = $order['datetime'];
			$summarized_orders[$order['order_num']]['cid'] = $order['cid'];
			$summarized_orders[$order['order_num']]['items'] += $order['qty'];
			$summarized_orders[$order['order_num']]['order_subtotal'] += $order_amt;
			$summarized_orders[$order['order_num']]['order_charges'] = $charges;
			$summarized_orders[$order['order_num']]['company'] = $order['name'];
			$summarized_orders[$order['order_num']]['credit'] += ($credit_total == '' ? 0 : $credit_total);
			$summarized_orders[$order['order_num']]['status'] = $status;
			$summarized_orders[$order['order_num']]['order_type'] = $order['order_type'];
			$summarized_orders[$order['order_num']]['partids'][] = array(
				'partid' => $order['partid'], 
				'price' => $order['price'], 
				'qty' => $order['qty'], 
				'complete_qty' => $complete_qty, 
				'credit' => ($credit_total == '' ? 0 : $credit_total),
			);
		}

		return $summarized_orders;
	}

	function getCredit($order_type, $item_id, $item_label, $order_number) {
		$credit_total;
		
		if ($order_type=='Sale') {
			$query = "SELECT SUM(qty*amount) total FROM credit_items WHERE item_id = ".fres($item_id)." AND item_id_label = '".res($item_label)."'; ";
			$result = qedb($query);

			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$credit_total = $r['total'];
			}

		} else if($order_type == 'Purchase') {
            // david's purchase credits hack for now; updated 7-21-17 now that we have purchase_credits, we need to adopt above method (under 'sales')
			// but we first need to implement a mechanism that generates credits from the RTV process...
            $query = "SELECT p.price, (s.qty*p.price) total FROM purchase_items p, sales_items s ";
			$query .= "WHERE po_number = ".fres($order['order_num'])." AND s.ref_1 = p.id AND s.ref_1_label = '".$item_label."'; ";
            $result = qedb($query);

            if (mysqli_num_rows($result)>0) {
                $r = mysqli_fetch_assoc($result);
                $credit_total = $r['total'];
            }

			$query = "SELECT SUM(qty*amount) total FROM purchase_credit_items WHERE purchase_item_id = ".fres($item_id)."; ";
			$result = qedb($query);

			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$credit_total += $r['total'];
			}
        }

        return $credit_total;
	}

	function buildPayment($order_number, $details) {
		$output = '';
		$outputRows = '';
		global $paymentTotal, $payment_amt;

		$payment_amt = 0;
		$init = true;

		if($details['order_type'] == 'Outsourced') {
			$details['order_type'] = 'Service';
		}

		$query = "SELECT order_number, order_type, ref_number, ref_type, SUM(amount) as amount, paymentid ";
		$query .= "FROM payment_details WHERE order_number = ".fres($order_number)." AND order_type = ".fres($details['order_type'])." GROUP BY order_number, paymentid;";

		$result = qdb($query) OR die(qe() . ' ' .$query);

		while ($r = mysqli_fetch_assoc($result)) {
			$payment_amt += $r['amount']; 

			$outputRows .= '
					<li style="text-align: left;">
						<a style="cursor: pointer; width: 100%;" class="paid-data" data-paymentid="'.$r['paymentid'].'">
							<i class="fa fa-usd" aria-hidden="true"></i>
							Payment #'.$r['paymentid'].'
						</a>
					</li>
			';
		}

		$output = '-' . format_price($payment_amt) . '
				<div class ="btn-group">
					<button type="button" class="dropdown-toggle" data-toggle="dropdown" '.(mysqli_num_rows($result) == 0 ? 'disabled' : '').'>
                      <span class="caret" '.(mysqli_num_rows($result) == 0 ? 'style="border-top: 4px solid #999;"' : '').'></span>
                    </button>
					<ul class="dropdown-menu">
		';

		$output .= $outputRows;

		$output .= '

					</ul>
				</div>
		';

		return $output;
	}

	function buildRows($ORDERS) {
		// Global Variables being used
		global $invoice_amt, $payment_amt, $subTotal, $paymentTotal, $creditTotal, $amountTotal;
		// Global Filters
		global $company_filter, $master_report_type, $filter, $view;

		//print "<pre>" . print_r($ORDERS, true) . "</pre>";

		$html_rows = '';
		$init = true;

		foreach($ORDERS as $order_number => $details) {
			$total = 0;
			// $invoicesTotal = 0;

			// get order type parameters
			$Ts = order_type($details['order_type']);

			// This pulls either Invoice or Bills
			$T = order_type($Ts['collection']);

			// Check for Invoice Data Here
			$invoices = getInvoiceData($order_number, $details['order_type'], $T);

			$payments_module = buildPayment($order_number, $details);

			// Calculate the total amount due for this line item
			if (count($invoices) > 0) {
				$total = (floatval(trim($invoice_amt)) - floatval(trim($details['credit'])) - floatval(trim($payment_amt)));
			} else {
				$total = (floatval(trim($details['order_subtotal'] + $details['order_charges'])) - floatval(trim($details['credit'])) - floatval(trim($payment_amt)));
			}

			$invoice_num = 0;

			$status  = ($total <= 0 ? 'complete' : 'active');

			if ($filter<>$status AND $filter<>'all') { continue; }

			// If there is no invoice for this order then spoof one with N/A
			// With a unique class that adds CSS to grey out subtotal
			if(empty($invoices)) {
				$invoices = array('invoice_no' => 0);
			}

			// document payment amount to totals
			$paymentTotal += $payment_amt;

			// Generate lines based on how many invoices there is present or if non the N/A
			foreach($invoices as $invoice) {
				if($invoice_num == 0) {
					$html_rows .= '<tr>';
					$html_rows .= '		<td>'.format_date($details['date']).'</td>';
					if(! $company_filter) {
						$html_rows .= '		<td>'.getCompany($details['cid']).' <a href="/profile.php?companyid='.$details['cid'].'" target="_blank"><i class="fa fa-building" aria-hidden="true"></i></a></td>';
					}
					$html_rows .= '		<td>'.$order_number.' <a href="/'.(strtoupper(substr($details['order_type'],0,1)).'O').$order_number.'"><i class="fa fa-arrow-right" aria-hidden="true"></i></a></td>';

					if(! $invoice[$T['order']]) {
						$html_rows .= '		<td><span class="info">N/A</span></td>';
					} else {
						if($T['abbrev'] == "BILL") {
							$html_rows .= '		<td>'.$invoice[$T['order']].' <a target="_blank" href="/bill.php?bill='.$invoice[$T['order']].'"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></td>';
						} else {
							$html_rows .= '		<td>'.$invoice[$T['order']].' <a target="_blank" href="/docs/'. $T['abbrev'] . $invoice[$T['order']].'.pdf"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></td>';
						}
					}

					if(! $invoice[$T['order']]) {
						$html_rows .= '		<td class="text-right"><span class="info">'.format_price($details['order_subtotal']).'</span></td>';
					}else {
						if($invoice_amt <> $details['order_subtotal']) {
							$html_rows .= '		<td class="text-right"><i class="fa fa-warning" title="Order subtotal: '.format_price($details['order_subtotal']).'" data-toggle="tooltip" data-placement="bottom"></i> '.format_price($invoice_amt).'</td>';
						} else {
							$html_rows .= '		<td class="text-right">'.format_price($details['order_subtotal']).'</td>';
						}
					}

					$html_rows .= '		<td class="text-right">-'.format_price($details['credit']).'</td>';
					$html_rows .= '		<td class="text-right">'.$payments_module.'</td>';
					$html_rows .= '		<td class="text-right">'.calcTerm($order_number, $details['order_type']).'</td>';
					$html_rows .= '		<td class="text-right">'.format_price($total);

					if($company_filter) {
						$html_rows .= ' 		<input class="pull-right payment_orders" type="checkbox" data-type="'.$details['order_type'].'" data-order="'.$order_number.'" style="margin: 4px 8px;" '.($status == 'complete' ? 'disabled' : '').'>';
					}

					$html_rows .= '		</td>';
					$html_rows .= '</tr>';

					$invoice_num++;
				} else {
					$html_rows .= '<tr>';
					if(! $company_filter) {
						$html_rows .= '		<td colspan="3"></td>';
					} else {
						$html_rows .= '		<td colspan="2"></td>';
					}
					if($T['abbrev'] == "BILL") {
							$html_rows .= '		<td>'.$invoice[$T['order']].' <a target="_blank" href="/bill.php?bill='.$invoice[$T['order']].'"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></td>';
						} else {
							$html_rows .= '		<td>'.$invoice[$T['order']].' <a target="_blank" href="/docs/'. $T['abbrev'] . $invoice[$T['order']].'.pdf"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></td>';
						}
					// $html_rows .= '		<td>'.$invoice[$T['order']].' <a target="_blank" href="/docs/'. $T['abbrev'] . $invoice[$T['order']].'.pdf"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></td>';
					$html_rows .= '		<td colspan="5"></td>';
					$html_rows .= '</tr>';
				}

				if($master_report_type == 'detail') {
					$html_rows .= buildSubRows($details['partids'], $init);
				}
			}

			$subTotal += ($invoice_amt?:$details['order_subtotal']);
			$creditTotal += $details['credit'];
			$amountTotal += $total;

			$init = false;
		}

		// Build the last row here with totals summary
		$html_rows .= '
			<tr class="nohover" style="background: #EEE;">
	        	<td colspan="4"></td>
	        	<td class="text-right"><strong>'.format_price($subTotal).'</strong></td>
	        	<td class="text-right"><strong>-'.format_price($creditTotal).'</strong></td>
	        	<td class="text-right"><strong>-'.format_price($paymentTotal).'</strong></td>
	            <td class="text-right" colspan="2">
	                <strong>'.format_price($amountTotal).'</strong>
	            </td>
	        </tr>
        ';

		return $html_rows;
	}

	function buildSubRows($details, $header) {
		$html_rows = '';
		$init = true;
		if(! empty($details)) {
			foreach($details as $sub) {
				$html_rows .= '<tr>';

				if($init) {
					$html_rows .= '
						<td colspan="12" class="">
							<table class="table table-condensed commission-table">
								<tbody>';
				}

				if($header AND $init) {
				$html_rows .= '
									<tr>
										<th class="col-md-4">Description</th>
										<th class="col-md-1">Qty</th>
										<th class="col-md-1">Filled</th>
										<th class="col-md-1 text-right">Price (ea)</th>
										<th class="col-md-1 text-right">Ext Price</th>
										<th class="col-md-1 text-right">Credits</th>
										<th class="col-md-3 text-right"></th>
									</tr>';
				}
				$html_rows .= '
									<tr>
										<td class="col-md-4">'.display_part($sub['partid'], true).'</td>
										<td class="col-md-1">'.$sub['qty'].'</td>
										<td class="col-md-1">'.$sub['complete_qty'].'</td>
										<td class="col-md-1 text-right"><span class="info">'.format_price(($sub['price']) ?: 0).'</span></td>
										<td class="col-md-1 text-right"><span class="info">'.format_price(($sub['price'] * $sub['qty']) ?: 0).'</span></td>
										<td class="col-md-1 text-right"><span class="info">'.format_price(($sub['credit']) ?: 0).'</span></td>
										<td class="col-md-3"></td>
									</tr>
				';

				$init = false;
			}

			$html_rows .= '
								</tbody>
							</table>
						</td>
					</tr>';
		}

		return $html_rows;
	}

	$ORDERS = array();

	//getRecords($search_arr = '',$partid_array = '',$array_format='csv',$market_table='demand',$results_mode=0, $start = '', $end = '')
	foreach($Ts as $T) {
		$ORDERS = array_merge($ORDERS, getRecords('','','',$T['type'], '', $startDate, $endDate));
	}

	// Sort all the data by the date created
	uasort($ORDERS,'cmp_datetime');

	// Summarize all the records
	$ORDERS = summarizeOrders($ORDERS, '');

	// print "<pre>" . print_r(getRecords('','','',$T['type']), true) . "</pre>";

	$TITLE = ($company_filter ? getCompany($company_filter) :"Accounts");

	// Pre build some HTML elements here that require an if statement
	$payment_drop = '';
	if($company_filter) {
		$payment_drop = '
			<div class="dropdown pull-right" style="margin-left: 10px;">
				<button class="btn btn-default btn-sm payment_module" data-toggle="dropdown">
					<i class="fa fa-usd"></i>
				</button>
			</div>
		';
	}

	// Build out the months for the date search feature
	$html_quarters = '';
	$quarters = calcQuarters();

	foreach ($quarters as $qnum => $q) {
		$html_quarters .= '
				    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="'.$q['start'].'" data-end="'.$q['end'].'">Q'.$qnum.'</button>
		';
	}

	for ($m=1; $m<=5; $m++) {
		$month = format_date($today,'M m/t/Y',array('m'=>-$m));
		$mfields = explode(' ',$month);
		$month_name = $mfields[0];
		$mcomps = explode('/',$mfields[1]);
		$MM = $mcomps[0];
		$DD = $mcomps[1];
		$YYYY = $mcomps[2];
		$html_quarters .= '
								<button class="btn btn-sm btn-default right small btn-report" type="button" data-start="'.date($MM."/01/".$YYYY).'" data-end="'.date($MM."/".$DD."/".$YYYY).'">'.$month_name.'</button>
		';
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
		require_once 'modal/payments_account.php';
	?>

	<!-- any page-specific customizations -->
	<style type="text/css">
		/*Print class disables CSS*/
		.print .table-header {
			display: none !important;
		}

		.print #pad-wrapper {
			margin-top: 0 !important;
		}

		.print a, .print input, .print .btn-group, .print .dropdown {
			display: none;
		}
	</style>
</head>
<body class="<?=$view;?>">

<?php if($view != 'print') { include_once 'inc/navbar.php'; } ?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form" >
		<div class="row" style="padding:8px">
			<div class="col-sm-1">
				<div class="btn-group">
			        <button class="glow left large btn-radio <?=($master_report_type == 'summary' ? 'active':'')?>" type="submit" data-value="summary" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="summary">
			        	<i class="fa fa-ticket"></i>	
			        </button>
					<input type="radio" name="report_type" value="summary" class="hidden" <?=($master_report_type == 'summary' ? 'checked':'')?>>

			        <button class="glow right large btn-radio <?=($master_report_type == 'detail' ? 'active':'')?>" type="submit" data-value="detail" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="details">
			        	<i class="fa fa-list"></i>	
		        	</button>
					<input type="radio" name="report_type" value="detail" class="hidden" <?=($master_report_type == 'detail' ? 'checked':'')?>>
			    </div>
			</div>
			<div class="col-sm-1">
				<div class="btn-group medium">
			        <button data-toggle="tooltip" name="filter" type="submit" value="active" data-placement="bottom" title="" data-filter="active_radio" data-original-title="Active" class="btn btn-default btn-sm left filter_status <?=($filter == 'active' ? 'active btn-warning' : '');?>">
			        	<i class="fa fa-sort-numeric-desc"></i>	
			        </button>
			        <input type="radio" name="filter" value="active" class="filter-checkbox hidden" <?=($filter == 'active' ? 'checked' : '');?>>

			        <button data-toggle="tooltip" name="filter" type="submit" value="complete" data-placement="bottom" title="" data-filter="complete_radio" data-original-title="Completed" class="btn btn-default btn-sm middle filter_status <?=($filter == 'complete' ? 'active btn-success' : '');?>">
			        	<i class="fa fa-history"></i>	
			        </button>
			        <input type="radio" name="filter" value="complete" class="filter-checkbox hidden" <?=($filter == 'complete' ? 'checked' : '');?>>

					<button data-toggle="tooltip" name="filter" type="submit" value="all" data-placement="bottom" title="" data-filter="all_radio" data-original-title="All" class="btn btn-default btn-sm right filter_status <?=($filter == 'all' ? 'active btn-info' : '');?>">
			        	All
			        </button>
			        <input type="radio" name="filter" value="all" class="filter-checkbox hidden" <?=($filter == 'all' ? 'checked' : '');?>>
				</div>
			</div>
			<div class="col-sm-3">
				<div class="form-group">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
			            <input type="text" name="START_DATE" class="form-control input-sm" value="<?=$startDate?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
			        </div>
				</div>
				<div class="form-group">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="">
			            <input type="text" name="END_DATE" class="form-control input-sm" value="<?=$endDate?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
				    </div>
				</div>
				<div class="form-group">
					<button class="btn btn-primary btn-sm btn-filter" type="submit"><i class="fa fa-filter" aria-hidden="true"></i></button>
					<div class="btn-group" id="dateRanges">
						<div id="btn-range-options">
							<button class="btn btn-default btn-sm">&gt;</button>
							<div class="animated fadeIn hidden" id="date-ranges">
						        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="<?php echo date("m/01/Y"); ?>" data-end="<?php echo date("m/d/Y"); ?>">MTD</button>
						        <?=$html_quarters;?>
							</div><!-- animated fadeIn -->
						</div><!-- btn-range-options -->
					</div><!-- btn-group -->
				</div><!-- form-group -->
			</div>
			<div class="col-sm-2 text-center">
				<h2 class="minimal"><?php echo substr($TITLE,0,15); ?></h2>
				<span class="info"></span>
			</div>
			<div class="col-sm-1">
				<div class="input-group">
					<input type="text" name="keyword" class="form-control input-sm upper-case auto-select" value="<?=$order_filter;?>" placeholder="Order #" autofocus="">
					<span class="input-group-btn">
						<button class="btn btn-primary btn-sm" type="submit"><i class="fa fa-filter" aria-hidden="true"></i></button>
					</span>
				</div>
			</div>
			<div class="col-sm-2">
				<div class="pull-right form-group">
					<select name="companyid" id="companyid" class="company-selector">
						<option value="">- Select a Company -</option>
						<?php 
							if ($company_filter) {echo '<option value="'.$company_filter.'" selected>'.(getCompany($company_filter)).'</option>'.chr(10);} 
							else {echo '<option value="">- Select a Company -</option>'.chr(10);} 
						?>
					</select>
					<button class="btn btn-primary btn-sm btn-filter" type="submit">
						<i class="fa fa-filter" aria-hidden="true"></i>
					</button>
				</div>
			</div>
			<div class="col-sm-2">
				<div class="btn-group medium">
			        <button data-toggle="tooltip" data-type="sale" data-placement="bottom" title="" data-original-title="Sales" class="btn btn-default btn-sm filter-types left <?=(in_array("Sale", $types)?'active btn-success' : '');?>">
			        	S
			        </button>
			        <input class="sale-checkbox hidden" type="checkbox" name="order_type[]" value="Sale" <?=(in_array("Sale", $types)?'checked' : '');?>>

			        <button data-toggle="tooltip" data-type="purchase" data-placement="bottom" title="" data-original-title="Purchases" class="btn btn-default btn-sm filter-types middle <?=(in_array("Purchase", $types)?'active btn-warning' : '');?>">
			        	P	
			        </button>
			        <input class="purchase-checkbox hidden" type="checkbox" name="order_type[]" value="Purchase" <?=(in_array("Purchase", $types)?'checked' : '');?>>

					<button data-toggle="tooltip" data-type="repair" data-placement="bottom" title="" data-original-title="Repairs" class="btn btn-default btn-sm filter-types right <?=(in_array("Repair", $types)?'active btn-info' : '');?>">
			        	R
			        </button>
			        <input class="repair-checkbox hidden" type="checkbox" name="order_type[]" value="Repair" <?=(in_array("Repair", $types)?'checked' : '');?>>

			        <button data-toggle="tooltip" data-type="service" data-placement="bottom" title="" data-original-title="Services" class="btn btn-default btn-sm filter-types right <?=(in_array("Service", $types)?'active btn-purple' : '');?>">
			        	SS
			        </button>
			        <input class="service-checkbox hidden" type="checkbox" name="order_type[]" value="Service" <?=(in_array("Service", $types)?'checked' : '');?>>

			        <button data-toggle="tooltip" data-type="outsourced" data-placement="bottom" title="" data-original-title="Outsourced" class="btn btn-default btn-sm filter-types right <?=(in_array("Outsourced", $types)?'active btn-default' : '');?>">
			        	OS
			        </button>
			        <input class="outsourced-checkbox hidden" type="checkbox" name="order_type[]" value="Outsourced" <?=(in_array("Outsourced", $types)?'checked' : '');?>>
				</div>

				<button data-toggle="tooltip" name="view" value="print" data-placement="bottom" title="" data-original-title="Print View" class="btn btn-default btn-sm filter-types pull-right">
			        <i class="fa fa-print" aria-hidden="true"></i>
		        </button>
			</div>
		</div>
	</form>
</div>

<div id="pad-wrapper">

<?php if($view == 'print') { ?>
	<button data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Exit Print View" class="btn btn-default btn-sm pull-right exit_print">
	    <i class="fa fa-print" aria-hidden="true"></i>
	</button>
<?php } ?>

<?php if($view) { ?>
	<h3 class="text-center minimal" <?=$company_filter ? 'style="margin-bottom: 20px;"' : '';?>><?=getCompany($company_filter);?></h3>
<?php } ?>

<form class="form-inline" method="get" action="" enctype="multipart/form-data" >
	<table class="table table-hover table-striped table-condensed">
		<thead>
            <tr>
                <th class="col-md-1">
                    Date 
                </th>

                <?php if(! $company_filter) { 
	                echo '<th class="col-md-2">
	                    <span class="line"></span>
	                    Company
	                </th>';
	            } ?>

                <th class="col-md-2">
                    <span class="line"></span>
                    Order#
                </th>
                <th class="col-md-2">
                    <span class="line"></span>
                    Invoice/Bill
                </th>
                <th class="col-md-1 text-right">
                	<span class="line"></span>
                    Subtotal
                </th>
                <th class="col-md-1 text-right">
                	<span class="line"></span>
                    Credits
                </th>
                <th class="col-md-1 text-right">
                	<span class="line"></span>
                    Payments
                </th>
                <th class="col-md-1 text-right">
                	<span class="line"></span>
                    Due Date
                </th>
                <th class="col-md-1 text-right">
                	<span class="line"></span>
                    Amount Due

                    <?=$payment_drop;?>
                </th>
            </tr>
        </thead>

        <tbody>
        	<?=buildRows($ORDERS);?>
        </tbody>
	</table>
</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>
<script src="js/payment.js"></script>

<script type="text/javascript">
	$(document).ready(function() {
		$(document).on("click", ".exit_print", function(e) {
			location.href=location.href.replace(/&?view=([^&]$|[^&]*)/i, "");
		});

		$(document).on("click", ".filter-types", function(e) {
			var type = $(this).data("type");

			var checkbox = $("." + type + "-checkbox");
			checkbox.prop("checked", !checkbox.prop("checked"));

			//$("#filters-form").submit();
		});

		$(document).on("click", ".filter_status", function(e) {
			$(".filter-checkbox").prop('checked', false);
		});
	});
</script>

</body>
</html>
