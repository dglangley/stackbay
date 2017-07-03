<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/calcQuarters.php';
	include_once $rootdir.'/inc/form_handle.php';

	function getSource($pi_id) {
		if (! $pi_id) { return (''); }

		$query = "SELECT po_number FROM purchase_items WHERE id = '".res($pi_id)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return (''); }
		$r = mysqli_fetch_assoc($result);

		return ($r['po_number'].' <a href="/order_form.php?on='.$r['po_number'].'&ps=p" target="_new"><i class="fa fa-arrow-right"></i></a>');
	}

	$RATES = array();
	function getSalesReps($selected_repid=0,$force_selected=false) {
		global $RATES;

		$reps = '';
		if (! $force_selected) { $reps = '<option value="0">- Select a Rep -</option>'.chr(10); }
		$query = "SELECT u.id, c.name, u.commission_rate, r.privilegeid FROM contacts c, users u, user_roles r, user_privileges p ";
		$query .= "WHERE c.id = u.contactid AND u.id = r.userid AND r.privilegeid = p.id ";
		$query .= "AND (p.privilege = 'Sales' OR p.privilege = 'Management') ";
		$query .= "AND c.status = 'Active' ";
		if ($force_selected) { $query .= "AND u.id = '".$selected_repid."' "; }
		$query .= "ORDER BY c.name ASC; ";
		$result = qdb($query) OR die("Could not get sales reps from database");
		while ($r = mysqli_fetch_assoc($result)) {
			$name = $r['name'];
			$RATES[$r['id']] = $r['commission_rate'];

			$s = '';
			if ($selected_repid==$r['id']) { $s = ' selected'; }
			$reps .= '<option value="'.$r['id'].'"'.$s.'>'.$name.'</option>'.chr(10);
		}
		return ($reps);
	}

	function getSalesAmount($so_number=0) {
		$sale_amount = 0;
		if (! $so_number) { return ($sale_amount); }

		$query = "SELECT SUM(qty*price) amount FROM sales_items WHERE so_number = '".res($so_number)."'; ";
		$result = qdb($query) OR die("Could not get sales items data for SO# ".$so_number);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$sale_amount += $r['amount'];
		}

		$query = "SELECT SUM(qty*price) amount FROM sales_charges WHERE so_number = '".res($so_number)."'; ";
		$result = qdb($query) OR die("Could not get sales charges data for SO# ".$so_number);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$sale_amount += $r['amount'];
		}

		return ($sale_amount);
	}

	function getInvoiceAmount($invoice_no=0) {
		$inv_amt = 0;
		if (! $invoice_no) { return ($inv_amt); }
		$query2 = "SELECT SUM(qty*amount) amount FROM invoice_items WHERE invoice_no = '".$invoice_no."'; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$inv_amt += $r2['amount'];
		}
		return ($inv_amt);
	}
	
	//=========================================================================================
	//==================================== FILTERS SECTION ====================================
	//=========================================================================================
	
	//Company Id is grabbed from the search field at the top, but only if one has been passed in
	$company_filter = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) { 
		$company_filter = $_REQUEST['companyid']; 
	}

	$rep_filter = 0;
	if (isset($_REQUEST['repid']) AND is_numeric($_REQUEST['repid']) AND $_REQUEST['repid']>0) { 
		$rep_filter = $_REQUEST['repid']; 
	}
	// restrict user access to other rep's info if they don't have admin or management roles
	if (!in_array("1", $USER_ROLES) AND !in_array("4", $USER_ROLES)) {
		$rep_filter = $U['id'];
		$reps_list = getSalesReps($rep_filter,true);
	} else {
		$reps_list = getSalesReps($rep_filter);
	}

	$history_date = '';
	if (isset($_REQUEST['history_date']) AND $_REQUEST['history_date']){
		$history_date = $_REQUEST['history_date'];
	}

	$order = '';
	if (isset($_REQUEST['order']) AND $_REQUEST['order']){
		$order = $_REQUEST['order'];
	}
	
	$keyword = '';
	$part_string = '';

	$filter = $_REQUEST['filter'];
	if (isset($_REQUEST['s']) AND $_REQUEST['s']) {
		$keyword = $_REQUEST['s'];
		$order = $keyword;
	}

	if ($keyword) {
    	$part_list = getPipeIds($keyword);
    	foreach ($part_list as $id => $array) {
    	    $part_string .= $id.',';
    	}
    	$part_string = rtrim($part_string, ",");
	}

	$startDate = '';
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}

	$endDate = date('m/d/Y');
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with Comms home set as title -->
<head>
	<title>Commissions Home</title>
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
		.comm-row, .comm-row td {
			margin:0 !important;
			padding:0 !important;
		}
		.comm-row td:last-child {
			padding-bottom:10px !important;
		}
		.rep-selector {
			width:120px;
		}
		.comm-item {
			margin-left:5px !important;
		}
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<?php if($_REQUEST['payment']): ?>
		<div id="item-updated-timer" class="alert alert-success fade in text-center" style="position: fixed; width: 100%; z-index: 9999; top: 48px;">
		    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
		    <strong>Success!</strong> Payment has been updated.
		</div>
	<?php endif; ?>

	<form class="form-inline" method="get" action="/commissions.php">
    <table class="table table-header table-filter">
		<tr>
		<td class = "col-md-2">
		    <div class="col-md-6">
			    <div class="btn-group medium">
			        <button data-toggle="tooltip" name="filter" type="submit" value="active" data-placement="bottom" title="" data-filter="active_radio" data-original-title="Active" class="btn btn-default btn-sm left filter_status <?=($filter == 'active' || !$filter ? 'active btn-warning' : '');?>">
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

			<div class="col-md-6">
			    <div class="btn-group">
					<select name="repid" id="repid" class="rep-selector form-control input-sm">
						<?php echo $reps_list; ?>
					</select>
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
			<h2 class="minimal">Commissions</h2>
		</td>
		<td class="col-md-1 text-center">
			<div class="input-group">
				<input type="text" name="order" class="form-control input-sm" value ='<?php echo $order?>' placeholder = "Order #"/>
				<span class="input-group-btn">
					<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
				</span>
			</div>
		</td>
		<td class="col-md-1 text-center">
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
			<select name="history_date" size="1" class="form-control input-sm" style="width:140px" disabled>
				<?php echo $payouts; ?>
			</select>
		</td>
		<td class="col-md-3">
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
			</td>
		</tr>
	</table>
	</form>
	
    <div id="pad-wrapper">
	

<!--================================================================================-->
<!--=============================   PRINT TABLE ROWS   =============================-->
<!--================================================================================-->
<?php
	//Establish a blank array for receiving the results from the table
	$orders = array();
	$query = "SELECT so.so_number, so.created, so.sales_rep_id, so.companyid, i.invoice_no, i.date_invoiced ";
	$query .= "FROM sales_orders so, invoices i ";
	$query .= "WHERE so.so_number = i.order_number AND i.order_type = 'Sale' ";
   	if ($startDate) {
   		$dbStartDate = format_date($startDate, 'Y-m-d');
   		$dbEndDate = format_date($endDate, 'Y-m-d');
   		$query .= "AND so.created between CAST('".$dbStartDate."' AS DATE) and CAST('".$dbEndDate."' AS DATE) ";
	}
	if ($order) { $query .= "AND (so.so_number = '".res($order)."' OR i.invoice_no = '".res($order)."') "; }
	$query .= "GROUP BY so.so_number, i.invoice_no ORDER BY so.so_number ASC; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$commissionid = $r['id'];
//		$r['sale_amount'] = getSalesAmount($r['so_number']);
		$r['inv_amount'] = getInvoiceAmount($r['invoice_no']);
//		if (! $r['inv_amount']) { continue; }
		$r['amount'] = $r['commission_amount'];

		// get amount already paid out
		$r['commissions'] = array();

		$query2 = "SELECT rep_id, SUM(c.commission_amount) commission_amount, SUM(p.amount) paid_amount FROM commissions c ";
		$query2 .= "LEFT JOIN commission_payouts p ON c.id = p.commissionid ";
		$query2 .= "WHERE c.invoice_no = '".$r['invoice_no']."' ";
		if ($rep_filter) { $query2 .= "AND rep_id = '".res($rep_filter)."' "; }
		$query2 .= "GROUP BY rep_id HAVING paid_amount IS NULL OR commission_amount <> paid_amount; ";
		$result2 = qdb($query2) OR die("Problem pulling commissions for invoice# ".$r['invoice_no']);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$r['commissions'][] = $r2;
		}
		if (count($r['commissions'])==0) { continue; }

		$orders[] = $r;
	}

	$comm_reps = array();
	$comm_rows = '';
	foreach ($orders as $r) {
		$inv_amt = getInvoiceAmount($r['invoice_no']);

		$comm_rows .= '
			<tr class="success">
				<td> '.date("m/d/Y", strtotime($r['date_invoiced'])).' </td>
				<td> '.getRep($r['sales_rep_id'],'id','first_name').' </td>
				<td> '.$r['so_number'].' <a href="/order_form.php?on='.$r['so_number'].'&ps=s" target="_new"><i class="fa fa-arrow-right"></i></a> </td>
				<td> '.getCompany($r['companyid']).' </td>
				<td> '.$r['invoice_no'].' <a href="/docs/INV'.$r['invoice_no'].'.pdf" target="_new"><i class="fa fa-arrow-right"></i></a> </td>
				<td class="text-right"> '.format_price($inv_amt).' </td>
			</tr>
		';

		$inventories = array();
		$query2 = "SELECT i.id, ii.partid, i.purchase_item_id, ii.amount, i.serial_no ";
		$query2 .= "FROM invoice_items ii, inventory i, inventory_history h, sales_items si, invoices inv ";
		$query2 .= "WHERE ii.invoice_no = '".$r['invoice_no']."' AND ii.partid = si.partid AND i.id = h.invid ";
		$query2 .= "AND h.field_changed = 'sales_item_id' AND h.value = si.id ";
		$query2 .= "AND si.so_number = inv.order_number AND inv.order_type = 'Sale' AND inv.invoice_no = ii.invoice_no; ";
		$result2 = qdb($query2) OR die("Could not pull comm/inventory records for invoice ".$r['invoice_no']);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$inventories[$r2['id']] = $r2;
		}

		$num_comms = count($r['commissions']);
		if ($num_comms>0) {
			$comm_rows .= '
			<tr class="comm-row">
				<td colspan="6">
					<table class="table table-condensed">
			';
		}
		foreach ($r['commissions'] as $c) {
			$comm_rows .= '
						<tr>
							<td class="col-md-1"> </td>
							<td class="col-md-2"> <strong>'.getRep($c['rep_id']).'</strong> </td>
							<td class="col-md-2"> </td>
							<td class="col-md-1"> </td>
							<td class="col-md-1 text-right"> <strong>Sale Price</strong> </td>
							<td class="col-md-1 text-right"> <strong>COGS (Avg)</strong> </td>
							<td class="col-md-1 text-right"> <strong>Profit</strong> </td>
							<td class="col-md-1"> </td>
							<td class="col-md-1 text-right">
								<strong>Commission</strong>
							</td>
							<td class="col-md-1 text-right" style="padding:0px !important;">
<!--
								<a class="btn btn-default btn-xs btn-details"><i class="fa fa-caret-down"></i></a>
-->
							</td>
						</tr>
			';

			foreach ($inventories as $inventoryid => $I) {
				$cogs = 0;
				$comm_amount = 0;
				$chk = ' checked';
				$cls = ' warning';
				$query2 = "SELECT commission_amount, commission_rate, cogsid, item_id, item_id_label FROM commissions c ";
				$query2 .= "WHERE inventoryid = '".$inventoryid."' AND rep_id = '".$c['rep_id']."' AND invoice_no = '".$r['invoice_no']."'; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$cogsid = $r2['cogsid'];

					// get cogs from sales_cogs table with associated inventoryid and sales_item_id
					$query3 = "SELECT cogs_avg cogs FROM sales_cogs WHERE id = $cogsid; ";//inventoryid = '".$inventoryid."' AND sales_item_id ";
					//if ($r2['sales_item_id']) { $query3 .= "= '".$r2['sales_item_id']."' "; } else { $query3 .= "IS NULL "; }
					//$query3 .= "; ";
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					if (mysqli_num_rows($result3)>0) {
						$r3 = mysqli_fetch_assoc($result3);
						$cogs = round($r3['cogs'],2);
					}

					$profit = $I['amount']-$cogs;
					$comm_amount = $r2['commission_amount'];
				} else {
					$chk = '';
					$cls = '';
					$query2 = "SELECT average, actual FROM inventory_costs WHERE inventoryid = '".$inventoryid."'; ";
					$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
					if (mysqli_num_rows($result2)>0) {
						$r2 = mysqli_fetch_assoc($result2);
						if ($r2['average']>0) { $cogs = $r2['average']; }
						else { $cogs = $r2['actual']; }
						$cogs = round($cogs,2);
						$profit = $I['amount']-$cogs;
						$comm_amount = $profit*($RATES[$c['rep_id']]/100);
					}
				}
				// No negative commissions allowed on negative profit sales
				if ($profit<0 AND $comm_amount<0) { $comm_amount = 0; }

				$comm_amount = round($comm_amount,2);
				// sum comm amount for this rep
				if (! isset($comm_reps[$c['rep_id']])) { $comm_reps[$c['rep_id']] = 0; }
				if ($chk) { $comm_reps[$c['rep_id']] += $comm_amount; }

				$serial = $I['serial_no'];
				$sale_amount = $I['amount'];
				$pi_id = $I['purchase_item_id'];

				$query3 = "SELECT * FROM parts WHERE id = '".$I['partid']."'; ";
				$result3 = qdb($query3);
				$r3 = mysqli_fetch_assoc($result3);
				$parts = explode(' ',$r3['part']);
				$part = $parts[0];
				$heci = $r3['heci'];

				$source_ln = getSource($pi_id);

				$comm_rows .= '
						<tr class="'.$cls.'">
							<td class="col-md-1" style="padding:0px !important">
								<input type="checkbox" name="comm['.$inventoryid.']" class="comm-item" data-repid="'.$c['rep_id'].'" data-amount="'.$comm_amount.'"'.$chk.'>
							</td>
							<td class="col-md-2"> '.$part.' '.$heci.' </td>
							<td class="col-md-2"> '.$serial.' </td>
							<td class="col-md-1">
								'.$source_ln.'
							</td>
							<td class="col-md-1 text-right">
								'.format_price($sale_amount).'
							</td>
							<td class="col-md-1 text-right">
								'.format_price($cogs).'
							</td>
							<td class="col-md-1 text-right">
								'.format_price($profit).'
							</td>
							<td class="col-md-1"> </td>
							<td class="col-md-1 text-right">
								'.format_price($comm_amount).'
							</td>
							<td class="col-md-1"> </td>
						</tr>
				';
			}
		}
		if ($num_comms>0) {
			$comm_rows .= '
					</table>
				</td>
			</tr>
			';
		}
	}

	$comm_stats = '';
	$num_reps = count($comm_reps);
	$col_width = floor(12/$num_reps);
	foreach ($comm_reps as $rep_id => $rep_amt) {
		$comm_stats .= '
                <div class="col-md-'.$col_width.' col-sm-'.$col_width.' stat">
                    <div class="data" id="'.$rep_id.'">
                        <span class="number text-brown">'.format_price(round($rep_amt,2),true,'').'</span>
						<span class="info"><label><input type="checkbox" class="comm-master" checked> '.getRep($rep_id).'</label></span>
                    </div>
                </div>
		';
	}
?>

        <!-- upper main stats -->
        <div id="main-stats">
            <div class="row stats-row">
				<?php echo $comm_stats; ?>
            </div>
        </div>
		<hr/>
        <!-- end upper main stats -->
		<form class="form-inline" method="get" action="/commissions.php">
		<table class="table table-hover table-striped table-condensed">
			<tr>
				<th>
					<span class="line"></span>
					Invoice Date
				</th>
				<th>
					<span class="line"></span>
					Sales Rep
				</th>
				<th>
					<span class="line"></span>
					Sales Order
				</th>
				<th>
					<span class="line"></span>
					Company
				</th>
				<th>
					<span class="line"></span>
					Invoice
				</th>
				<th class="text-right">
					<span class="line"></span>
					Total Sale
				</th>
			</tr>
			<?php echo $comm_rows; ?>
		</table>
		</form>
	</div>

<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
		$(document).ready(function() {
			$(".btn-details").on("click",function() {
				alert('hi');
			});
			// calc commissions based on checked items
			$(".comm-item").on("click",function() {
				calcCommissions();
			});
			$(".comm-master").on("click",function() {
				var repid = $(this).closest(".data").attr('id');
				var rep_checked = $(this).prop('checked');
				$(".comm-item").each(function() {
					if ($(this).data('repid')!=repid) { return; }
					$(this).prop('checked',rep_checked);
				});
				calcCommissions();
			});
		});
		function calcCommissions() {
			$(".stat .data").each(function() {
				var repid = $(this).attr('id');
				var rep_comm = $(this).find(".number");
				var n = rep_comm.html().replace('$','').replace(',','');
				var amount = 0;
				$(".comm-item:checked").each(function() {
					if ($(this).data('repid')!=repid) { return; }
					amount += $(this).data('amount');
				});
				rep_comm.html('$'+amount.formatMoney(2));
			});
		}
    </script>

</body>
</html>
