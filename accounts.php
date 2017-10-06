<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/calcQuarters.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/calcTerms.php';
	include_once $rootdir.'/inc/order_parameters.php';

	
	//=========================================================================================
	//==================================== FILTERS SECTION ====================================
	//=========================================================================================
	
	//Company Id is grabbed from the search field at the top, but only if one has been passed in
	$company_filter = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) { 
		$company_filter = $_REQUEST['companyid']; 
	}
	//Report type is set to summary as a default. This is where the button functionality comes in to play
	if (isset($_REQUEST['report_type']) AND ($_REQUEST['report_type']=='summary' OR $_REQUEST['report_type']=='detail')) { $report_type = $_REQUEST['report_type']; }
	else if (isset($_COOKIE['report_type']) AND ($_COOKIE['report_type']=='summary' OR $_COOKIE['report_type']=='detail')) { $report_type = $_COOKIE['report_type']; }
	//This is saved as a cookie in order to cache the results of the button function within the same window
	setcookie('report_type', $report_type, (time() + (7 * 24 * 60 * 60)));

	// because report type can be changed temporarily below, based on user searches, we don't want the main context to be overridden
	// throughout clicks on this page in case the user is otherwise accustomed to a particular setting, see above
	$master_report_type = $report_type;

	$order = '';
	if (isset($_REQUEST['order']) AND $_REQUEST['order']){
		$report_type = 'detail';
		$order = $_REQUEST['order'];
	}
	
	$keyword = '';
	$part_string = '';

	$filter = 'active';//$_REQUEST['filter'];
	if (isset($_REQUEST['filter']) AND $_REQUEST['filter']) {
		$filter = $_REQUEST['filter'];
	}
	if (isset($_REQUEST['s']) AND $_REQUEST['s']) {
		$report_type = 'detail';
		$keyword = $_REQUEST['s'];
		$order = $keyword;
	}

	$startDate = '';
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}
	//if no start date passed in, or invalid, set to beginning of previous month
	if (!$startDate) {
		$y = date("Y");
		// set date to beginning of the first month of the *previous* quarter
		$q = ceil(date("m")/3);
		$last_q = $q-1;
		if ($last_q==0) { $last_q = 4; $y -= 1; }
		$start_m = ($last_q*3)-2;
		//$startDate = format_date($today,'m/01/Y',array('m'=>-3));
		$startDate = format_date($y.'-'.$start_m.'-01','m/01/Y');
	}

	$endDate = date('m/d/Y');
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}

	$orders_table = 'sales';
	if (isset($_REQUEST['orders_table'])) {
		if ($_REQUEST['orders_table']=='purchases') { $orders_table = 'purchases'; }
	}
	$o = o_params($orders_table);
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with Accounts home set as title -->
<head>
	<title>Accounts Home</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<style>
		.goog-te-banner-frame.skiptranslate {
		    display: none !important;
	    } 
		body {
		    top: 0px !important; 
	    }
	    
		.capitalize {
		    text-transform: capitalize;
		}
/*	    .complete {
	    	color: rgb(129, 189, 130) !important;
	    }*/
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>
	<?php include_once 'inc/getRecords.php'; ?>
	<?php require_once 'modal/payments_account.php';  ?>

	<?php if($_REQUEST['payment']): ?>
		<div id="item-updated-timer" class="alert alert-success fade in text-center" style="position: fixed; width: 100%; z-index: 9999; top: 48px;">
		    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
		    <strong>Success!</strong> Payment has been updated.
		</div>
	<?php endif; ?>

	<!-- Wraps the entire page into a form for the sake of php trickery -->
	<form class="form-inline" method="get" action="/accounts.php">

    <table class="table table-header table-filter">
		<tr>
		<td class = "col-md-2">
			<div class="col-md-6">
			    <div class="btn-group">
			        <button class="glow left large btn-radio<?php if ($master_report_type=='summary') { echo ' active'; } ?>" type="submit" data-value="summary" data-toggle="tooltip" data-placement="bottom" title="summary">
			        <i class="fa fa-ticket"></i>	
			        </button>
					<input type="radio" name="report_type" value="summary" class="hidden"<?php if ($master_report_type=='summary') { echo ' checked'; } ?>>
			        <button class="glow right large btn-radio<?php if ($master_report_type=='detail') { echo ' active'; } ?>" type="submit" data-value="detail" data-toggle="tooltip" data-placement="bottom" title="details">
			        	<i class="fa fa-list"></i>	
		        	</button>
					<input type="radio" name="report_type" value="detail" class="hidden"<?php if ($master_report_type=='detail') { echo ' checked'; } ?>>
			    </div>
		    </div>

		    <div class="col-md-6">
			    <div class="btn-group medium">
			        <button data-toggle="tooltip" name="filter" type="submit" value="active" data-placement="bottom" title="" data-filter="active_radio" data-original-title="Active" class="btn btn-default btn-sm left filter_status <?=($filter == 'active' ? 'active btn-warning' : '');?>">
			        	<i class="fa fa-sort-numeric-desc"></i>	
			        </button>

			        <button data-toggle="tooltip" name="filter" type="submit" value="complete" data-placement="bottom" title="" data-filter="complete_radio" data-original-title="Completed" class="btn btn-default btn-sm middle filter_status <?=($filter == 'complete' ? 'active btn-success' : '');?>">
			        	<i class="fa fa-history"></i>	
			        </button>

					<button data-toggle="tooltip" name="filter" type="submit" value="all" data-placement="bottom" title="" data-filter="all_radio" data-original-title="All" class="btn btn-default btn-sm right filter_status <?=(($filter == 'all') ? 'active btn-info' : '');?>">
			        	All
			        </button>
			    </div>

			</div>
		</td>

		<td class = "col-md-3">
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

	for ($m=1; $m<=5; $m++) {
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
		</td>
		<td class="col-md-2 text-center">
			<h2 class="minimal"><?=ucwords($orders_table);?></h2>
			<a href="/accounts.php?report_type=<?=($master_report_type ? $master_report_type : 'detail');?><?=($_REQUEST['START_DATE'] ? '&START_DATE=' . $_REQUEST['START_DATE'] : '')?><?=($_REQUEST['END_DATE'] ? '&END_DATE=' . $_REQUEST['END_DATE'] : '')?><?=($order ? '&order=' . $order : '')?><?=($company_filter ? '&companyid=' . $company_filter : '')?>&filter=<?=$filter;?>&orders_table=<?=($orders_table == 'sales' ? 'purchases': 'sales');?>">Switch to <?=($orders_table == 'sales' ? 'Purchases': 'Sales');?></a>
			<input type="radio" name="orders_table" value="<?=$orders_table;?>" class="hidden" checked>
		</td>
		<td class="col-md-2 text-center">
			<div class="row">
				<div class="col-md-7">
					<input type="text" name="order" class="form-control input-sm" value ='<?php echo $order?>' placeholder = "Order #"/>
				</div>

				<?php if($orders_table == 'sales') { ?>
					<div class="col-md-3">
						<label class="checkbox-inline"><input type="checkbox" onChange="this.form.submit()" name='invoice' value="checked" <?=(($_REQUEST['invoice'] == 'checked') ? 'checked' : '')?>>Invoice#</label>
					</div>
				<?php } ?>
			</div>
<!--
			<input type="text" name="part" class="form-control input-sm" value ='<?php echo $part?>' placeholder = 'Part/HECI'/>
-->
		</td>
		<td class="col-md-3">
			<div class="col-md-10">
				<div class="pull-right form-group">
					<select name="companyid" id="companyid" class="company-selector">
						<option value="">- Select a Company -</option>
						<?php 
							if ($company_filter) {echo '<option value="'.$company_filter.'" selected>'.(getCompany($company_filter)).'</option>'.chr(10);} 
							else {echo '<option value="">- Select a Company -</option>'.chr(10);} 
						?>
					</select>
					<button class="btn btn-primary btn-sm" type="submit" >
						<i class="fa fa-filter" aria-hidden="true"></i>
					</button>
				</div>
			</div>
			<div class="col-md-2">
				<button type="button" class="btn btn-default btn-sm payment_module"  <?=(empty($_REQUEST['companyid']) ? 'disabled' : '');?>>
                	<i class="fa fa-usd" aria-hidden="true"></i>
                	<span class="caret"></span>
                </button>
			</div>
		</td>
		</tr>
	</table>
	<!-- If the summary button is pressed, inform the page and depress the button -->
	
<!------------------------------------------------------------------------------------>
<!---------------------------------- FILTERS OUTPUT ---------------------------------->
<!------------------------------------------------------------------------------------>
    <div id="pad-wrapper">
	
		<?php if ($company_filter) { echo '<h3 class="text-center minimal">'.getCompany($company_filter).'</h3>'; } ?>

		<div class="row filter-block">

		<br>
            <!-- orders table -->
            <div class="table-wrapper">

			<!-- If the summary button is pressed, inform the page and depress the button -->

<?php
	// format col widths based on content (company column, items detail, etc)
	// If there is a company declared, do not show the collumn for the company data. Set width by array
	if ($company_filter) {
		$total_span = 7;
		$footer_span2 = 2;
		if ($report_type=='summary') {
			$footer_span1 = 1;
			$widths = array(1,4,3,1,1,1,1,1);
		} else {
			$footer_span1 = 1;
			$widths = array(1,4,3,1,1,1,1,1);
		}
	} else {
		$total_span = 8;
		$footer_span2 = 2;
		if ($report_type=='summary') {
			$footer_span1 = 2;
			$widths = array(1,3,3,1,1,1,1,1);
		} else {
			$footer_span1 = 2;
			$widths = array(1,3,3,1,1,1,1,1);
		}
	}
	$c = 0;
?>
<!--================================================================================-->
<!--=============================   PRINT TABLE ROWS   =============================-->
<!--================================================================================-->
<?php
	//Establish a blank array for receiving the results from the table
	$results = array();
	$oldid = 0;
//	echo getCompany($company_filter,'id','oldid');
//	echo('Value Passed in: '.$company_filter);
	//If there is a company id, translate it to the old identifier
	if($company_filter != 0){$oldid = dbTranslate($company_filter, false);}
//	echo '<br>The value of this company in the old database is: '.$oldid;

	$rows = '';
	$total_pcs = 0;
	$total_amt = 0;
	$total_sub = 0;
	$total_cred = 0;
	$total_payments = 0;
	
	//Write the query for the gathering of data
	$record_start = $startDate;
	$record_end = $endDate;
	$results = getRecords($part_string,'','',$orders_table);
	if ($orders_table=='sales') {
		$repairs = getRecords($part_string,'','','repairs_completed');
		foreach ($repairs as $repair) {
			$results[] = $repair;
		}
	}

	//The aggregation method of form processing. Take in the information, keyed on primary sort field,
	//will prepare the results rows to make sorting and grouping easier without having to change the results
	$summary_rows = array();
	
    foreach ($results as $row){
		$id = $row['order_num'];
		$freight = 0;
		$invoiceid = array();

		$query = "SELECT invoice_no, freight FROM invoices WHERE order_number =".prep($id)." AND order_type='".$row['order_type']."';";
		$result = qdb($query) OR die(qe());
		while ($rowInvoice = $result->fetch_assoc()) {
			$freight += $rowInvoice['freight'];
			$invoiceid[] = $rowInvoice['invoice_no'];
		}

		if ($row['status']=='Void' AND $filter<>'All') { continue; }

		$status = 'complete';
		if ($order AND $order<>$id) { continue; }

		$row['price'] = format_price($row['price'],true,'',true);
		$ext_amt = $row['price']*$row['qty'];
		$total_pcs += $row['qty'];

		if(!array_key_exists($id, $summary_rows)){
			$summary_rows[$id] = array(
				'date' => '',
				'company' => '',
				'items' => '',
				'summed' => '',
			);
		}

		//Query to get the credit per item
		//test for sales first
		$charges_table = '';
		$item_id = 0;
		$complete_qty = 0;
		switch ($row['order_type']) {
			case 'Purchase':
				$items_table = 'purchase_items';
				$order_field = 'po_number';
				$fqty_field = 'qty_received';
				$charges_table = 'purchase_charges';
				break;
			case 'Repair':
				$items_table = 'repair_items';
				$order_field = 'ro_number';
				$fqty_field = 'qty';
				$charges_table = '';
				break;
			case 'Sale':
			default:
				$items_table = 'sales_items';
				$order_field = 'so_number';
				$fqty_field = 'qty_shipped';
				$charges_table = 'sales_charges';
				break;
		}

		$query = "SELECT *, ".$fqty_field." complete_qty FROM ".$items_table." WHERE partid = ".prep($row['partid'])." AND ".$order_field." = ".prep($id).";";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$query_row = mysqli_fetch_assoc($result);
			$item_id = $query_row['id'];
			$complete_qty = $query_row['complete_qty'];
		}

		if ($charges_table) {
			$query = "SELECT * FROM ".$charges_table." WHERE ".$order_field." = ".prep($id)."; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			while ($r2 = mysqli_fetch_assoc($result)) {
				$ext_amt += $r2['qty']*$r2['price'];
			}
		}

        $credit = 0;
        $credit_total = 0;
        if ($item_id) {
			if ($row['order_type']=='Sale') {
				$query = "SELECT amount, SUM(amount) as total FROM ".$o['credit_items']." WHERE ".$o['inv_item_id']." = ".prep($item_id).";";
				$result = qdb($query) OR die(qe().'<BR>'.$query);
				if (mysqli_num_rows($result)>0) {
					$query_row = mysqli_fetch_assoc($result);
					$credit = $query_row['amount'];
					$credit_total = $query_row['total'];
				}
			} else if($row['order_type'] == 'Purchase') {
                // david's purchase credits hack for now; updated 7-21-17 now that we have purchase_credits, we need to adopt above method (under 'sales')
				// but we first need to implement a mechanism that generates credits from the RTV process...
                $query = "SELECT p.price, (s.qty*p.price) total FROM purchase_items p, sales_items s ";
				$query .= "WHERE po_number = ".prep($id)." AND s.ref_1 = p.id AND s.ref_1_label = 'purchase_item_id'; ";
                $result = qdb($query) OR die(qe().'<BR>'.$query);
                if (mysqli_num_rows($result)>0) {
                    $r = mysqli_fetch_assoc($result);
                    $credit = $r['price'];
                    $credit_total = $r['total'];
                }
            }
        } 

        if($row['qty'] > $complete_qty) {
        	$status = 'active';
			// not sure why this was added, makes no sense to reduce the subtotal of charges on an order by the payment or credit amount;
			// these are taken AS credits and payments, not negative charges of items
        	//$ext_amt - ($credit_total == '' ? 0 : $credit_total) - $paymentTotal;
	    }

		$summary_rows[$id]['date'] = $row['datetime'];
		$summary_rows[$id]['cid'] = $row['cid'];
		$summary_rows[$id]['items'] += $row['qty'];
		$summary_rows[$id]['summed'] += ($ext_amt+$freight);
		$summary_rows[$id]['company'] = $row['name'];
		$summary_rows[$id]['credit'] = ($credit_total == '' ? 0 : $credit_total);
		$summary_rows[$id]['invoice'] = $invoiceid;
		$summary_rows[$id]['status'] = $status;
		$summary_rows[$id]['order_type'] = $row['order_type'];
		$summary_rows[$id]['partids'][] = array(
			'partid' => $row['partid'], 
			'price' => $row['price'], 
			'qty' => $row['qty'], 
			'complete_qty' => $complete_qty, 
			'credit' => ($credit == '' ? 0 : $credit),
		);
	}

	$init = true;

	foreach ($summary_rows as $id => $info) {
    	$paymentTotal = 0;

		$query = "SELECT * FROM payment_details WHERE order_number = ".prep($id)." AND order_type = ".prep($info['order_type']).";";
		$payment_results = qdb($query) OR die(qe() . ' ' .$query);

		$output = '
				<div class ="btn-group">
					<button type="button" class="dropdown-toggle" data-toggle="dropdown" '.(mysqli_num_rows($payment_results) == 0 ? 'disabled' : '').'>
                      <span class="caret" '.(mysqli_num_rows($payment_results) == 0 ? 'style="border-top: 4px solid #999;"' : '').'></span>
                    </button>
					<ul class="dropdown-menu">
		';

		while ($payment_rows = mysqli_fetch_assoc($payment_results)) {
			$paymentTotal += $payment_rows['amount']; 
			$output .= '
					<li style="text-align: left;">
						<a style="cursor: pointer; width: 100%;" class="paid-data" data-paymentid="'.$payment_rows['paymentid'].'">
							<i class="fa fa-usd" aria-hidden="true"></i>
							Payment #'.$payment_rows['paymentid'].'
						</a>
					</li>
			';
		}

		$output .= '

					</ul>
				</div>
		';

		$total = ($info['summed'] - $info['credit'] - $paymentTotal);
		$status  = ($total <= 0 ? 'complete' : 'active');

		if ($filter<>$status AND $filter<>'all') { continue; }
		$total_sub += $info['summed'];

//		$filter_comb = (($filter == $status || $filter == 'all' || !$filter) ? '' : 'hidden');
       	$rows .= '
				<tr class="'.$status.'">
					<td>'.format_date($info['date'], 'M j, Y').'</td>
		';

		if(!$company_filter){
			$rows .= '<td>'.$info['company'].'  <a href="/profile.php?companyid='.$info['cid'].'" target="_blank"><i class="fa fa-book" aria-hidden="true"></i></a></td>';
		}
		$rows .='
            		<td>
						<div class="row">
							<div class="col-md-6">'.$id.' <a href="/'.(strtoupper(substr($info['order_type'],0,1)).'O').$id.'"><i class="fa fa-arrow-right" aria-hidden="true"></i></a></div>
		';

		if($_REQUEST['invoice']) {		
           	$rows .= '
							<div class="col-md-6">
								'.(reset($info['invoice']) ? reset($info['invoice']) . ' <a target="_blank" href="/docs/INV'.reset($info['invoice']).'.pdf"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a>': 'N/A').'
							</div>
			';
		}

		$rows .= '
						</div>
					</td>
            		<td class="text-right">'.format_price($info['summed']).'</td>
                    <td class="text-right">-'.format_price($info['credit']).'</td>
                    <td class="text-right">-'.format_price($paymentTotal).$output.'</td>
                    <td class="text-right">'.calcTerms($id, $info['order_type']).'</td>
                    <td class="text-right total_cost">'.format_price($total).' <input class="pull-right payment_orders" type="checkbox" data-type="'.$info['order_type'].'" data-order="'.$id.'" style="margin: 4px 8px;" '.($status == 'complete' ? 'disabled' : '').'></td>
                </tr>
		';

		if ($_REQUEST['invoice'] && count($info['invoice']) > 1) {
			$infoArr = array_slice($info['invoice'],1);

			foreach($infoArr as $another) {
				$rows .='
				<tr class="'.$status.' '.$filter_comb.'">
	           		<td></td>
	           		<td></td>
	           		<td>
						<div class="row">
							<div class="col-md-6"></div>
							<div class="col-md-6">'.$another.' <a target="_blank" href="/docs/INV'.$another.'.pdf"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></div>
						</div>
					</td>
	           		<td></td>
	           		<td></td>
	           		<td></td>
	           		<td></td>
	           		<td></td>
	           	</tr>
				';
            }
		}

		if(!$filter_comb) {
			$total_amt += ($total);
			$total_payments += $paymentTotal;
			$total_cred += $info['credit'];
		}

		//Add in the dropdown element into the accounts page
		$rows .= '
					<tr class="'.$status.' '.$filter_comb.'" style="'.($report_type=='summary' ? 'display: none;' : '').'">
						<td colspan="'.$total_span.'">
							<table class="table table-condensed commission-table">
								<tbody>
		';
		if ($init) {
			$init = false;
			$rows .= '
									<tr>
										<th class="col-md-4">Part Description</th>
										<th class="col-md-1">Qty</th>
										<th class="col-md-1">Filled</th>
										<th class="col-md-1 text-right">Price (ea)</th>
										<th class="col-md-1 text-right">Ext Price</th>
										<th class="col-md-1 text-right">Credits</th>
										<th class="col-md-3 text-right"></th>
									</tr>
			';
		}

		foreach($info['partids'] as $part) {
			$credit_col = '';
			if ($part['credit']) {
				$credit_col = '-'.format_price($part['credit']);
			}

			$rows .= '
									<tr>
										<td class="col-md-4">'.display_part(current(hecidb($part['partid'], 'id'))).'</td>
										<td class="col-md-1">'.$part['qty'].'</td>
										<td class="col-md-1">'.$part['complete_qty'].'</td>
										<td class="col-md-1 text-right"><span class="info">'.format_price($part['price']).'</span></td>
										<td class="col-md-1 text-right"><span class="info">'.format_price($part['price'] * $part['qty']).'</span></td>
										<td class="col-md-1 text-right"><span class="info">'.$credit_col.'</span></td>
										<td class="col-md-3"></td>
									</tr>
			';
		}

		$rows .= '
								</tbody>
							</table>
						</td>
					</tr>
		';
	}

	if ($keyword) {
		echo '
	<div class="alert alert-warning text-center">
		<i class="fa fa-'.$alert_icon.' fa-2x"></i>
		Results limited to "'.$keyword.'"
	</div>
		';
	}
?>


	<!-- Declare the class/rows dynamically by the type of information requested (could be transitioned to jQuery) -->
                <div class="row">
                    <table class="table table-hover table-striped table-condensed">
                        <thead>
                            <tr>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    Date 
                                    <br>

                                </th>
<?php if (! $company_filter) { ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Company
                                </th>
<?php } ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                	<div class="row">
	                                	<div class="col-md-6">
		                                    <span class="line"></span>
		                                    Order#
	                                    </div>
	                                    <?php if($_REQUEST['invoice']) { ?>
		                                    <div class="col-md-6">
			                                    <span class="line"></span>
			                                    Invoice#
		                                    </div>
	                                    <?php } ?>
                                    </div>
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?> text-right">
                                	<span class="line"></span>
                                    Subtotal
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?> text-right">
                                	<span class="line"></span>
                                    Credits
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?> text-right">
                                	<span class="line"></span>
                                    Payments
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?> text-right">
                                	<span class="line"></span>
                                    Due Date
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?> text-right">
                                	<span class="line"></span>
                                    Amount Due
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        	<?php echo $rows; ?>
							<?php if ($rows) { ?>
	                            <!-- row -->
	                            <tr class="nohover" style="background: #EEE;">
	                            	<td colspan="<?php echo $footer_span1; ?>"> </td>
	                            	<td colspan=""> </td>
	                            	<td colspan="" class="text-right"><strong><?php echo format_price($total_sub,true,' '); ?></strong></td>
	                            	<td colspan="" class="text-right"><strong>-<?php echo format_price($total_cred,true,' '); ?></strong></td>
	                            	<td colspan="" class="text-right"><strong>-<?php echo format_price($total_payments,true,' '); ?></strong></td>
	                                <td class="text-right" colspan="<?php echo $footer_span2; ?>">
	                                    <strong><?php echo format_price($total_amt,true,' '); ?></strong>
	                                </td>
	                            </tr>
							<?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- end orders table -->


	</div>
	</form>

	<?php include_once 'inc/footer.php'; ?>
	<script src="js/payment.js"></script>

    <script type="text/javascript">

    (function($){
        $('#item-updated-timer').delay(1000).fadeOut('fast');
    })(jQuery);

    </script>

</body>
</html>
