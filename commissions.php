<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/calcQuarters.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/terms.php';

	function getSource($pi_id) {
		if (! $pi_id) { return (''); }

		$query = "SELECT po_number FROM purchase_items WHERE id = '".res($pi_id)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return (''); }
		$r = mysqli_fetch_assoc($result);

		return ('PO'.$r['po_number'].' <a href="/order_form.php?on='.$r['po_number'].'&ps=p" target="_new"><i class="fa fa-arrow-right"></i></a>');
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

	function getPaidAmount($invoice_no=0) {
		$paid_amt = 0;
		if (! $invoice_no) { return ($paid_amt); }
		$query2 = "SELECT SUM(amount) amount FROM payment_details ";
		$query2 .= "WHERE (order_number = '".$invoice_no."' AND order_type = 'Invoice') ";
		$query2 .= "OR (ref_number = '".$invoice_no."' AND ref_type = 'invoice'); ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$paid_amt += $r2['amount'];
		}
		return ($paid_amt);
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
	$user_admin = false;
	if (!in_array("1", $USER_ROLES) AND !in_array("4", $USER_ROLES)) {
		$rep_filter = $U['id'];
		$reps_list = getSalesReps($rep_filter,true);
	} else {
		$user_admin = true;
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

	$startDate = format_date($today,'01/01/Y',array('y'=>-1));
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
		tr.order-header td {
			text-transform:uppercase;
		}
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

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
			<div class="input-group">
				<select name="history_date" size="1" class="form-control input-sm" style="width:140px">
					<?php echo $payouts; ?>
				</select>
				<span class="input-group-btn">
					<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
				</span>
			</div>
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
	$charge_types = array('Sale','Repair');
	foreach ($charge_types as $type) {
		$order_type = '';
		$order_table = '';
		if ($type=='Sale') {
			$order_abbrev = 'SO';
			$order_type = 'so_number';
			$order_table = 'sales_orders';
			$item_type = 'sales_items';
			$item_field = 'sales_item_id';
			$order_type = 'so_number';
		} else if ($type=='Repair') {
			$order_type = 'ro_number';
			$order_table = 'repair_orders';
			$order_abbrev = 'RO';
			$item_type = 'repair_items';
			$item_field = 'repair_item_id';
			$order_type = 'ro_number';
		}

		// get all invoices which are commissioned against
		$query = "SELECT o.".$order_type.", o.created, o.sales_rep_id, o.companyid, i.invoice_no, i.date_invoiced, o.termsid, i.status ";
		$query .= "FROM ".$order_table." o, invoices i ";
		if ($history_date) { $query .= ", commissions c, commission_payouts p "; }
		$query .= "WHERE o.".$order_type." = i.order_number AND i.order_type = '".$type."' AND i.status <> 'Voided' ";
		if ($history_date) { $query .= "AND i.invoice_no = c.invoice_no AND c.id = p.commissionid AND LEFT(p.paid_date,10) = '".res($history_date)."' "; }
   		if ($startDate) {
   			$dbStartDate = format_date($startDate, 'Y-m-d');
   			$dbEndDate = format_date($endDate, 'Y-m-d');
   			$query .= "AND o.created between CAST('".$dbStartDate."' AS DATE) and CAST('".$dbEndDate."' AS DATE) ";
		}
		if ($order) { $query .= "AND (o.".$order_type." = '".res($order)."' OR i.invoice_no = '".res($order)."') "; }
		$query .= "GROUP BY o.".$order_type.", i.invoice_no ORDER BY o.".$order_type." ASC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$commissionid = $r['id'];
			$r['inv_amount'] = 0;//getInvoiceAmount($r['invoice_no']);

			// get amount already paid out
			$r['commissions'] = array();

			// get invoice items, which we will use to compare for commissions against each invoiced item
			$query2 = "SELECT qty, amount, partid, id FROM invoice_items WHERE invoice_no = '".$invoice_no."'; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$item_amt = $r2['qty']*$r2['amount'];
				$inv_amt += $item_amt;

				$r['commissions'][$r2['id']] = array('qty'=>$r2['qty'],'amount'=>$r2['amount'],'partid'=>$r2['partid'],'comms'=>array());

				$query3 = "SELECT s.invoice_item_id FROM inventory_history h, ".$item_type." t, packages p, package_contents pc, invoice_shipments s ";
				$query3 .= "WHERE h.invid = '".res($r2['inventoryid'])."' AND h.invid = pc.serialid ";
				$query3 .= "AND h.field_changed = '".$item_field."' AND h.value = t.id ";
				$query3 .= "AND p.order_number = t.".$order_type." AND p.order_type = '".$type."' ";
				$query3 .= "AND p.id = pc.packageid AND pc.packageid = s.packageid AND s.invoice_item_id IS NOT NULL; ";
				$result3 = qdb($query3) OR die("Error getting inventory history and shipment data for inventoryid ".$r2['inventoryid']);
				if (mysqli_num_rows($result3)>0) {
					$r3 = mysqli_fetch_assoc($result3);
					$inv_item_id = $r3['invoice_item_id'];
				}


			// get commissioned items on this invoice
			$query2 = "SELECT * ";
			$query2 .= "FROM commissions c WHERE c.invoice_no = '".$r['invoice_no']."' ";
			if ($rep_filter) { $query2 .= "AND rep_id = '".res($rep_filter)."' "; }
			$query2 .= "ORDER BY rep_id ASC, IF(item_id_label='sales_item_id',0,1); ";
			$result2 = qdb($query2) OR die("Problem pulling commissions for invoice# ".$r['invoice_no']);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$inv_item_id = 0;
				// determine invoice item id if there's an inventoryid
				if (! $r2['invoice_item_id'] AND $r2['inventoryid']) {
					$query3 = "SELECT s.invoice_item_id FROM inventory_history h, ".$item_type." t, packages p, package_contents pc, invoice_shipments s ";
					$query3 .= "WHERE h.invid = '".res($r2['inventoryid'])."' AND h.invid = pc.serialid ";
					$query3 .= "AND h.field_changed = '".$item_field."' AND h.value = t.id ";
					$query3 .= "AND p.order_number = t.".$order_type." AND p.order_type = '".$type."' ";
					$query3 .= "AND p.id = pc.packageid AND pc.packageid = s.packageid AND s.invoice_item_id IS NOT NULL; ";
					$result3 = qdb($query3) OR die("Error getting inventory history and shipment data for inventoryid ".$r2['inventoryid']);
					if (mysqli_num_rows($result3)>0) {
						$r3 = mysqli_fetch_assoc($result3);
						$inv_item_id = $r3['invoice_item_id'];
					}
				}

				if (! isset($r['commissions'][$inv_item_id])) { $r['commissions'][$inv_item_id] = array('qty'=>0,'amount'=>0,'partid'=>0,'comms'=>array()); }
				$r['commissions'][$inv_item_id]['comms'][$r2['rep_id']][] = $r2;
			}

			$r['amount'] = $r['commission_amount'];

			$r['charge_type'] = $type;

			$comm_sales = array();
			$query2 = "SELECT rep_id, commission_amount, id, item_id, item_id_label, inventoryid ";
			$query2 .= "FROM commissions c WHERE c.invoice_no = '".$r['invoice_no']."' ";
			if ($rep_filter) { $query2 .= "AND rep_id = '".res($rep_filter)."' "; }
			//$query2 .= "GROUP BY rep_id ORDER BY rep_id ASC, IF(item_id_label='sales_item_id',0,1); ";
			$query2 .= "ORDER BY rep_id ASC, IF(item_id_label='sales_item_id',0,1); ";
			$result2 = qdb($query2) OR die("Problem pulling commissions for invoice# ".$r['invoice_no']);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$comm_amount = $r2['commission_amount'];
				$r2['paid_amount'] = 0;

				/***** VERIFY THAT COMMS WERE PAID OUT ON ALL ITEMS ON INVOICE *****/

/*
				$item_id = 0;
				$item_id_label = '';
				if ($r2['cogsid'] AND (! $r2['item_id'] OR ! $r2['item_id_label'])) {
					$query3 = "SELECT item_id, item_id_label FROM sales_cogs WHERE id = '".$r2['cogsid']."'; ";
					$result3 = qdb($query3) OR die("Could not fetch inventory record for id '".$r2['inventoryid']."'");
					if (mysqli_num_rows($result3)>0) {
						$r3 = mysqli_fetch_assoc($result3);
						if ($r3['item_id'] AND $r3['item_id_label']) {
							$item_id = $r3['item_id'];
							$item_id_label = $r3['item_id_label'];
						}
					}
				} else if ($r2['item_id'] AND $r2['item_id_label']) {
					$item_id = $r2['item_id'];
					$item_id_label = $r2['item_id_label'];
				}

				if (! isset($comm_sales[$r2['inventoryid']])) {
					$comm_sales[$r2['inventoryid']] = $r['inv_amount'];//default
					if ($item_id AND $item_id_label) {
						$qty = 0;
						$item_table = '';
						if ($item_id_label=='sales_item_id') {
							$item_table = 'sales_items';
						} else if ($item_id_label=='repair_item_id') {
							$item_table = 'repair_items';
						}
						$query3 = "SELECT i.qty, t.price, serial_no FROM inventory i, inventory_history h, ".$item_table." t ";
						$query3 .= "WHERE i.id = '".$r2['inventoryid']."' AND i.id = h.invid AND h.field_changed = '".$item_id_label."' ";
						$query3 .= "AND h.value = '".$item_id."' AND h.value = t.id; ";
						$result3 = qdb($query3) OR die("Could not fetch inventory record for id '".$r2['inventoryid']."'");
						if (mysqli_num_rows($result3)==0) {
							die("Could not fetch inventory record for id '".$r2['inventoryid']."'");
						}
						$r3 = mysqli_fetch_assoc($result3);
						$qty = $r3['qty'];
						if (! $qty AND $r3['serial_no']) { $qty = 1; }
						$sale_amount = $qty*$r3['price'];
						$comm_sales[$r2['inventoryid']] = $sale_amount;
						$comm_invoiced += $sale_amount;
					}
				}
*/

				/***** END VERIFY *****/

/*
				$query3 = "SELECT SUM(amount) amount FROM commission_payouts WHERE commissionid = '".$r2['id']."'; ";
				$result3 = qdb($query3) OR die("Problem pulling associated commission payouts for id ".$r2['id']);
				if (mysqli_num_rows($result3)>0) {
					$r3 = mysqli_fetch_assoc($result3);
					$r2['paid_amount'] = $r3['amount'];
				}
				if (! $history_date) {// AND $comm_invoiced==$r['inv_amount']) {
					$comm_amount -= $r2['paid_amount'];
					if ($comm_amount==0) { continue; }
				}

				$r['commissions'][] = $r2;
*/
			}
//			if (count($r['commissions'])==0 AND $comm_invoiced==$r['inv_amount']) { continue; }

			$orders[] = $r;
		}
	}
echo '<BR><BR>';
print_r($orders);

	$date_today = strtotime($now);
	$secs_per_day = 60*60*24;
	$comm_reps = array();
	$comm_rows = '';
	$pending_comms = array();
	foreach ($orders as $r) {
		$paid_amt = getPaidAmount($r['invoice_no']);
		$inv_amt = getInvoiceAmount($r['invoice_no']);

		$order_abbrev = '';
		$item_type = '';
		$item_field = '';
		$order_type = '';
		if ($r['charge_type']=='Sale') {
			$order_abbrev = 'SO';
			$item_type = 'sales_items';
			$item_field = 'sales_item_id';
			$order_type = 'so_number';
		} else if ($r['charge_type']=='Repair') {
			$order_abbrev = 'RO';
			$item_type = 'repair_items';
			$item_field = 'repair_item_id';
			$order_type = 'ro_number';
		}

		// calculate 'on time' of payment within scope of terms based on termsid
		$order_date = strtotime($r['created']);
		$days = floor(($date_today-$order_date)/($secs_per_day));
		$due_days = 0;
		if ($r['termsid']) {
			$due_days = getTermsInfo($r['termsid'],'id','days');
		}

		$paid = false;//trips when paid so we can default to checked or not
		$row_cls = 'active';
		if ($paid_amt>=$inv_amt OR $history_date) {
			$row_cls = 'success';
			$paid = true;
		} else if ($days>$due_days) {
			$row_cls = 'danger';
		}

		$comm_rows .= '
			<tr class="order-header '.$row_cls.'">
				<td> '.date("m/d/Y", strtotime($r['date_invoiced'])).' </td>
				<td> '.getRep($r['sales_rep_id'],'id','first_name').' </td>
				<td> '.$order_abbrev.$r[$order_type].' <a href="/order_form.php?on='.$r[$order_type].'&ps=s" target="_new"><i class="fa fa-arrow-right"></i></a> </td>
				<td> '.getCompany($r['companyid']).' <a href="/profile.php?companyid='.$r['companyid'].'"><i class="fa fa-arrow-right"></i></a> </td>
				<td> Inv# '.$r['invoice_no'].' <a href="/docs/INV'.$r['invoice_no'].'.pdf" target="_new"><i class="fa fa-arrow-right"></i></a> </td>
				<td class="text-right"> '.format_price($inv_amt).' </td>
				<td class="text-right"> '.format_price($paid_amt).' </td>
			</tr>
		';

/*
		$inventories = array();
		$query2 = "SELECT i.id, ii.partid, i.purchase_item_id, ii.amount, i.serial_no, ii.id invoice_item_id, item.id item_id, '".$item_field."' item_id_label ";
		$query2 .= "FROM invoices inv, inventory i, inventory_history h, ".$item_type." item, invoice_items ii ";
		if ($history_date) {
			$query2 .= "LEFT JOIN invoice_shipments s ON ii.id = s.invoice_item_id ";
			$query2 .= "LEFT JOIN package_contents pc ON s.packageid = pc.packageid ";
		}
		$query2 .= "WHERE ii.invoice_no = '".$r['invoice_no']."' AND ii.partid = item.partid AND i.id = h.invid ";
		if ($history_date) {
			$query2 .= "AND pc.serialid = i.id ";
		}
		$query2 .= "AND h.field_changed = '".$item_field."' AND h.value = item.id ";
		$query2 .= "AND item.".$order_type." = inv.order_number AND inv.order_type = '".$r['charge_type']."' AND inv.invoice_no = ii.invoice_no; ";
		$result2 = qdb($query2) OR die("Could not pull comm/inventory records for invoice ".$r['invoice_no']);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$inventories[$r2['id']] = $r2;
		}
*/

		$num_comms = count($r['commissions']);
		if ($num_comms>0) {
			$comm_rows .= '
			<tr class="comm-row">
				<td colspan="7">
					<table class="table table-condensed">
			';
		}
		foreach ($r['commissions'] as $invoice_item_id => $invoice_item) {
			$partid = $invoice_item['partid'];
			$sale_amount = $invoice_item['amount'];

			foreach ($invoice_item['comms'] as $comm_repid => $a) {
				$comm_rows .= '
						<tr>
							<td class="col-md-1"> </td>
							<td class="col-md-2"> <strong>'.getRep($comm_repid).'</strong> </td>
							<td class="col-md-2"> </td>
							<td class="col-md-1"> </td>
							<td class="col-md-1 text-right"> <strong>'.$r['charge_type'].' Price</strong> </td>
							<td class="col-md-1 text-right"> <strong>COGS (Avg)</strong> </td>
							<td class="col-md-1 text-right"> <strong>Profit</strong> </td>
							<td class="col-md-1"> </td>
							<td class="col-md-1 text-right">
								<strong>Comm Due</strong>
							</td>
							<td class="col-md-1 text-right" style="padding:0px !important;">
<!--
								<a class="btn btn-default btn-xs btn-details"><i class="fa fa-caret-down"></i></a>
-->
							</td>
						</tr>
				';

//			foreach ($inventories as $inventoryid => $I) {
			foreach ($a as $c) {
				$inventoryid = $c['inventoryid'];
				$serial = '';
				$pi_id = 0;
				if ($c['inventoryid']) {
					$query3 = "SELECT serial_no, purchase_item_id FROM inventory i WHERE i.id = '".$inventoryid."'; ";
					$result3 = qdb($query3) OR die("Could not find inventoryid ".$c['inventoryid']);
					if (mysqli_num_rows($result3)>0) {
						$r3 = mysqli_fetch_assoc($result3);
						$serial = $r3['serial_no'];
						$pi_id = $r3['purchase_item_id'];
					}
				}

				$cogs = 0;
				$comm_amount = 0;
				$chk = '';
				// if order (invoice) is paid, check it off to show user
				if ($paid) { $chk = 'checked'; }

				$cls = ' warning';
				$comm_edit = '';
				$comm_cls = '';
				$profit = 0;

//				$serial = $I['serial_no'];
//				$sale_amount = $I['amount'];
//				$pi_id = $I['purchase_item_id'];
				$source_ln = getSource($pi_id);

				$query3 = "SELECT * FROM parts WHERE id = '".$partid."'; ";
				$result3 = qdb($query3);
				$r3 = mysqli_fetch_assoc($result3);
				$parts = explode(' ',$r3['part']);
				$part = $parts[0];
				$heci = $r3['heci'];

				$results = array();
				$query2 = "SELECT commission_amount, commission_rate, cogsid, id, item_id, item_id_label FROM commissions c ";
				$query2 .= "WHERE inventoryid = '".$inventoryid."' AND rep_id = '".$comm_repid."' AND invoice_no = '".$r['invoice_no']."'; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)==0) {
					$chk = 'disabled';
					$cls = '';

					// check first for cogs that may have been generated without initializing this rep's comms
					$query2 = "SELECT cogs_avg cogs FROM sales_cogs WHERE inventoryid = '".res($inventoryid)."' ";
					$query2 .= "AND item_id = '".$I['item_id']."' AND item_id_label = '".$I['item_id_label']."'; ";
					$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
					if (mysqli_num_rows($result2)>0) {
						$r2 = mysqli_fetch_assoc($result2);
						$cogs = $r2['cogs'];
					} else {
						$comm_cls = 'info em';

/* dl 7-6-17 I think we don't want to fix the actual cost for this view
						$query2 = "SELECT actual FROM inventory_costs WHERE inventoryid = '".$inventoryid."'; ";
						$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
						if (mysqli_num_rows($result2)>0) {
							$r2 = mysqli_fetch_assoc($result2);
							$cogs = $r2['actual'];
						}
*/
					}
					$cogs = round($cogs,2);
					$profit = $I['amount']-$cogs;
					$comm_amount = $profit*($RATES[$comm_repid]/100);
					$comm_edit = '<a href="javascript:void(0);" class="calc-comm" '.
						'data-cogs="'.$cogs.'" data-invoice="'.$r['invoice_no'].'" data-invoiceitemid="'.$I['invoice_item_id'].'" data-inventoryid="'.$inventoryid.'" '.
						'data-repid="'.$comm_repid.'" data-itemid="'.$I['item_id'].'" data-itemidlabel="'.$I['item_id_label'].'">'.
						'<i class="fa fa-calculator"></i></a>';

					$results[] = array(
						'cogs'=>$cogs,
						'profit'=>$profit,
						'comm_amount'=>$comm_amount,
						'id'=>0,
					);
				}
				while ($r2 = mysqli_fetch_assoc($result2)) {
					// subtract paid amount against this commission
					$paid_amount = 0;
					$query3 = "SELECT SUM(amount) amount FROM commission_payouts WHERE commissionid = '".$r2['id']."'; ";
					$result3 = qdb($query3) OR die("Problem pulling associated commission payouts for id ".$r2['id']);
					if (mysqli_num_rows($result3)>0) {
						$r3 = mysqli_fetch_assoc($result3);
						$paid_amount = $r3['amount'];
					}
					if (! $history_date) {
						$r2['commission_amount'] -= $paid_amount;
						if ($r2['commission_amount']==0) { continue; }
					}

					$cogsid = $r2['cogsid'];
					if ($cogsid) {
						// get cogs from sales_cogs table with associated inventoryid and item_id
						$query3 = "SELECT cogs_avg cogs FROM sales_cogs WHERE id = $cogsid; ";
						$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
						if (mysqli_num_rows($result3)>0) {
							$r3 = mysqli_fetch_assoc($result3);
							$cogs = round($r3['cogs'],2);
						}
					}
					$profit = $I['amount']-$cogs;

					// add comm amount so long as it's a positive number (can't lose money on a sale), or a return
					if ($r2['item_id_label']=='return_item_id' OR $r2['commission_amount']>0) {
						$comm_amount = $r2['commission_amount'];
					}

					$results[] = array(
						'cogs'=>$cogs,
						'profit'=>$profit,
						'comm_amount'=>$comm_amount,
						'id'=>$r2['id'],
					);
				}

				foreach ($results as $C) {
					$cogs = $C['cogs'];
					$profit = $C['profit'];
					$comm_amount = $C['comm_amount'];

					$comm_amount = round($comm_amount,2);
					// sum comm amount for this rep
					if (! isset($comm_reps[$comm_repid])) { $comm_reps[$comm_repid] = 0; }
					if ($chk=='checked') { $comm_reps[$comm_repid] += $comm_amount; }

					if (! isset($pending_comms[$comm_repid])) { $pending_comms[$comm_repid] = 0; }
					$pending_comms[$comm_repid] += $comm_amount;

					$comm_rows .= '
						<tr class="'.$cls.'">
							<td class="col-md-1" style="padding:0px !important">
								<input type="checkbox" name="comm['.$C['id'].']" class="comm-item" data-repid="'.$comm_repid.'" data-amount="'.$comm_amount.'" value="'.$comm_amount.'" '.$chk.'>
							</td>
							<td class="col-md-2"> '.$part.' '.$heci.' </td>
							<td class="col-md-2"> '.$serial.' <a href="javascript:void(0);" data-id="'.$inventoryid.'" class="history_button"><i class="fa fa-history"></i></a> </td>
							<td class="col-md-1">
								'.$source_ln.'
							</td>
							<td class="col-md-1 text-right">
								'.format_price($sale_amount).'
							</td>
							<td class="col-md-1 text-right">
								<span class="'.$comm_cls.'">'.format_price($cogs).'</span>
							</td>
							<td class="col-md-1 text-right">
								<span class="'.$comm_cls.'">'.format_price($profit).'</span>
							</td>
							<td class="col-md-1"> </td>
							<td class="col-md-1 text-right">
								<span class="'.$comm_cls.'">'.format_price($comm_amount).'</span>
							</td>
							<td class="col-md-1 text-right" style="padding-right:5px !important">
								'.$comm_edit.'
							</td>
						</tr>
					';
				}
			}
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

	// only user admins have privilege to approve commissions
	if ($user_admin) {
		$form_action = 'save-commissions.php';
		$col_width = floor(11/$num_reps);
	} else {
		$form_action = 'commissions.php';
		$col_width = floor(12/$num_reps);
	}
	foreach ($comm_reps as $rep_id => $rep_amt) {
		$pending_amt = 0;
		if (isset($pending_comms[$rep_id])) { $pending_amt = $pending_comms[$rep_id]; }

		$comm_stats .= '
                <div class="col-md-'.$col_width.' col-sm-'.$col_width.' stat">
                    <div class="data" id="'.$rep_id.'">
                        <span class="number text-brown">'.format_price(round($rep_amt,2),true,'').'</span>
						<span class="info"><label><input type="checkbox" class="comm-master" checked> <span class="rep-name">'.getRep($rep_id).'</span></label></span>
                    </div>
					<span class="aux">'.format_price($pending_amt,true,'').' total pending</span>
                </div>
		';
	}
	if ($user_admin AND $comm_stats) {
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
?>

        <!-- upper main stats -->
        <div id="main-stats" style="position:fixed; width:auto; left:0px; right:0px; top:93px; z-index:1001; box-shadow: 2px 1px 2px #888888; opacity:.9">
            <div class="row stats-row">
				<?php echo $comm_stats; ?>
            </div>
        </div>
		<hr/>
        <!-- end upper main stats -->
		<form class="form-inline" method="post" action="/<?php echo $form_action; ?>" id="comm-form">
		<table class="table table-hover table-striped table-condensed" style="margin-top:60px">
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
					Order No.
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
					Total Charges
				</th>
				<th class="text-right">
					<span class="line"></span>
					Payments
				</th>
			</tr>
			<?php echo $comm_rows; ?>
		</table>
		</form>
	</div>

<?php include_once 'modal/history.php'; ?>
<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
		$(document).ready(function() {
			$(".btn-details").on("click",function() {
				alert('hi');
			});
			// calc commissions based on checked items
			$(".comm-item").on("click",function() {
				updateCommissions();
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
			$(".calc-comm").on("click",function() {
				var cogs = $(this).data("cogs");
				var invoice = $(this).data("invoice");
				var invoice_item_id = $(this).data("invoiceitemid");
				var inventoryid = $(this).data("inventoryid");
				var repid = $(this).data("repid");
				var item_id = $(this).data("itemid");
				var item_id_label = $(this).data("itemidlabel");

				// don't allow the user to edit if the cogs is known/calculated
				var field_state = ' disabled';
				var cogs_helper = '';
				if (cogs=='' || cogs=='0' || cogs=='0.00') {
					field_state = '';
					cogs_helper = '<span class="info em">adjust if necessary</span></div>';
				}
				var modal_msg = 'I can re-calculate this Commission for you, but I have to reload your page. Are you sure that\'s okay?<br/><br/>'+
					'<div class="row">'+
						'<div class="col-sm-3 text-right"><strong>COGS</strong></div>'+
						'<div class="col-sm-3"><input type="text" class="form-control input-xs" name="item_cogs_'+inventoryid+'_'+repid+'" id="item-cogs-'+inventoryid+'-'+repid+'" value="'+cogs+'" '+field_state+'/></div>'+
						'<div class="col-sm-6">'+cogs_helper+'</div>'+
					'</div>'+
					'<div class="row">'+
						'<div class="col-sm-3 text-right"><strong>Invoice</strong></div>'+
						'<div class="col-sm-9">'+invoice+'</div>'+
					'</div>'+
					'<div class="row">'+
						'<div class="col-sm-3 text-right"><strong>Invoice Item ID</strong></div>'+
						'<div class="col-sm-9">'+invoice_item_id+'</div>'+
					'</div>'+
					'<div class="row">'+
						'<div class="col-sm-3 text-right"><strong>'+item_id_label+'</strong></div>'+
						'<div class="col-sm-9">'+item_id+'</div>'+
					'</div>'+
					'<div class="row">'+
						'<div class="col-sm-3 text-right"><strong>Inventory ID</strong></div>'+
						'<div class="col-sm-9">'+inventoryid+'</div>'+
					'</div>'+
					'<div class="row">'+
						'<div class="col-sm-3 text-right"><strong>Rep ID</strong></div>'+
						'<div class="col-sm-9">'+repid+'</div>'+
					'</div>';
				modalAlertShow('<i class="fa fa-female"></i> A message from Améa...',modal_msg,true,'calcCommission',$(this));
//alert(invoice+':'+inventoryid+':'+repid);
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
		function calcCommission(e) {
			var invoice = e.data("invoice");
			var invoice_item_id = e.data("invoiceitemid");
			var inventoryid = e.data("inventoryid");
			var repid = e.data("repid");
			var item_id = e.data("itemid");
			var item_id_label = e.data("itemidlabel");
			var cogs = $("#item-cogs-"+inventoryid+"-"+repid).val();

        	console.log(window.location.origin+"/json/calc-comm.php?invoice="+invoice+"&invoice_item_id="+invoice_item_id+"&inventoryid="+inventoryid+"&repid="+repid+"&cogs="+cogs+"&item_id="+item_id+"&item_id_label="+item_id_label);
	        $.ajax({
				url: 'json/calc-comm.php',
				type: 'get',
				data: {'invoice': invoice, 'invoice_item_id': invoice_item_id, 'inventoryid': inventoryid, 'repid': repid, 'cogs': cogs, 'item_id': item_id, 'item_id_label': item_id_label},
				dataType: 'json',
				cache: false,
				success: function(json, status) {
					if (json.message=='Success') {
						location.reload();
					} else {
						alert(json.message);
					}
				},
				error: function(xhr, desc, err) {
//					console.log(xhr);
				},
			});
		}
		function updateCommissions() {
			$(".stat .data").each(function() {
				var repid = $(this).attr('id');
				if (! repid || repid.length==0) { return; }

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
