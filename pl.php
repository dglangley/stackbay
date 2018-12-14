<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/datepickers.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	include_once $rootdir.'/inc/calcLegacyRepairCost.php';

	function getSalesRecords($item_id,$startDate='',$endDate='') {
		$entries = array();

		$query = "SELECT si.serial, si.cost actual_cost, i.id, si.so_id order_id, si.price, si.rep_id, si.freight_cost, si.po, si.avg_cost, ";
		//$query .= "si.invoice_id, si.orig_cost, so.po_number, so.complete, i.part_number, i.clei, inv.date, si.oq_id item_id, inv.id ref, so.freight_charge ";
		//$query .= "si.invoice_id, si.orig_cost, so.po_number, so.complete, i.part_number, i.clei, so.so_date date, si.oq_id item_id, inv.id ref, so.freight_charge ";
		$query .= "si.invoice_id, si.orig_cost, so.po_number, so.complete, i.part_number, i.clei, inv.date, si.oq_id item_id, inv.id ref, so.freight_charge ";
		if (! $item_id) { $query .= ", c.name "; }
		$query .= "FROM inventory_solditem si, inventory_salesorder so, inventory_inventory i, ";
		if (! $item_id) { $query .= "inventory_outgoing_quote oq, inventory_company c, "; }
		$query .= "inventory_outgoing_quote_invoice oqi, inventory_invoice inv, inventory_invoiceli ili, inventory_salesinvoice salesinvoice ";
		$query .= "WHERE so.quote_ptr_id = si.so_id AND si.inventory_id = i.id ";
		if (! $item_id) { $query .= "AND si.oq_id = oq.id AND c.id = company_id AND oqi.oq_id = si.oq_id "; }
		$query .= "AND oqi.id = ili.oqi_id AND ili.invoice_id = inv.id ";
		$query .= "AND oqi.sales_invoice_id = salesinvoice.id AND salesinvoice.so_id = si.so_id AND si.invoice_id = inv.id ";
		if ($item_id) { $query .= "AND si.id = '".$item_id."' "; }
   		if ($startDate) {
   			$dbStartDate = format_date($startDate, 'Y-m-d');
   			$dbEndDate = format_date($endDate, 'Y-m-d');
   			//$query .= "AND so.so_date between CAST('".$dbStartDate."' AS DATE) AND CAST('".$dbEndDate."' AS DATE) ";
   			$query .= "AND inv.date between CAST('".$dbStartDate."' AS DATE) AND CAST('".$dbEndDate."' AS DATE) ";
		}
		//$query .= "ORDER BY so.so_date ASC, si.so_id ASC; ";
		$query .= "ORDER BY inv.date ASC, si.so_id ASC; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$r['descr'] = trim($r['part_number'].' '.$r['clei']);

			$entries[] = $r;

/* this really shouldn't be included for outgoing freight
			if ($r['freight_charge']>0) {
				$r['descr'] = 'Freight Charge';
				$r['price'] = $r['freight_charge'];
				$r['avg_cost'] = 0;
				$r['actual_cost'] = 0;
				$r['item_id'] = 'freight';

				$entries[$r['id'].'.freight'] = $r;
			}
*/
		}

		return ($entries);
	}

	//=========================================================================================
	//==================================== FILTERS SECTION ====================================
	//=========================================================================================
	
	//Company Id is grabbed from the search field at the top, but only if one has been passed in
	$company_filter = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) { 
		$company_filter = $_REQUEST['companyid']; 
	}
	
	//This is saved as a cookie in order to cache the results of the button function within the same window
//	setcookie('report_type',$report_type);
	
	$order = '';
	if (isset($_REQUEST['order']) AND $_REQUEST['order']){
		$report_type = 'detail';
		$order = $_REQUEST['order'];
	}
	
	$part = '';
	$part_string = '';
	if (isset($_REQUEST['part']) AND $_REQUEST['part']){
    	$part = $_REQUEST['part'];

    	$part_list = getPipeIds($part);
    	foreach ($part_list as $id => $array) {
    	    $part_string .= $id.',';
    	}
    	$part_string = rtrim($part_string, ",");
    }
	
	$startDate = '';
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}
	// if no start date passed in, or invalid, set to beginning of quarter by default
	if (! $startDate) {
		$year = date('Y');
		$m = date('m');
		$q = (ceil($m/3)*3)-2;
		if (strlen($q)==1) { $q = '0'.$q; }
		$startDate = $q.'/01/'.$year;
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
<!-- Declaration of the standard head with P&L home set as title -->
<head>
	<title>Profit and Loss Report</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<style type="text/css">
		.table td {
			vertical-align:top !important;
		}
	</style>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>

	<!-- Wraps the entire page into a form for the sake of php trickery -->
	<form class="form-inline" method="get" action="/pl.php">

    <table class="table table-header table-filter">
		<tr>
		<td class = "col-md-2">
<?php
	$cost_basis = 'average';//can toggle between average and fifo
	if (isset($_REQUEST['cost_basis'])) {
		if ($_REQUEST['cost_basis']=='fifo') { $cost_basis = 'fifo'; }
		else if ($_REQUEST['cost_basis']=='qb') { $cost_basis = 'qb'; }
	}
?>
		    <div class="btn-group">
		        <button class="glow left large btn-radio <?php if ($cost_basis=='average') { echo ' active'; } ?>" type="submit" data-value="average" data-toggle="tooltip" data-placement="bottom" title="average cost">
		        	<i class="fa fa-random"></i>	
		        </button>
				<input type="radio" name="cost_basis" value="average" class="hidden"<?php if ($cost_basis=='average') { echo ' checked'; } ?>>
		        <button class="glow middle large btn-radio<?php if ($cost_basis=='fifo') { echo ' active'; } ?>" type="submit" data-value="fifo" data-toggle="tooltip" data-placement="bottom" title="fifo cost">
		        	<i class="fa fa-exchange"></i>	
		        </button>
		        <input type="radio" name="cost_basis" value="fifo" class="hidden"<?php if ($cost_basis=='fifo') { echo ' checked'; } ?>>
		        <button class="glow right large btn-radio<?php if ($cost_basis=='qb') { echo ' active'; } ?>" type="submit" data-value="qb" data-toggle="tooltip" data-placement="bottom" title="qb entry">
		        	<i class="fa fa-file-text"></i>	
		        </button>
		        <input type="radio" name="cost_basis" value="qb" class="hidden"<?php if ($cost_basis=='qb') { echo ' checked'; } ?>>
		    </div>
		</td>

		<td class = "col-md-3">
			<?=datepickers($startDate,$endDate);?>
		</td>
		<td class="col-md-2 text-center">
            <h2 class="minimal">Profit & Loss</h2>
		</td>
		
		<td class="col-md-2 text-center">
			<input type="text" name="order" class="form-control input-sm" value ='<?php echo $order?>' placeholder = "Order #" disabled />
<!--
			<input type="text" name="part" class="form-control input-sm" value ='<?php echo $part?>' placeholder = 'Part/HECI' disabled />
-->
		</td>
		<td class="col-md-3">
			<div class="pull-right form-group">
			<select name="companyid" id="companyid" class="company-selector" disabled >
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
	<!-- If the summary button is pressed, inform the page and depress the button -->
	
	
<!------------------------------------------------------------------------------------>
<!---------------------------------- FILTERS OUTPUT ---------------------------------->
<!------------------------------------------------------------------------------------>
    <div id="pad-wrapper">
		<div class="row filter-block">

            <!-- orders table -->
            <div class="table-wrapper">

			<!-- If the summary button is pressed, inform the page and depress the button -->


<!--================================================================================-->
<!--=============================   PRINT TABLE ROWS   =============================-->
<!--================================================================================-->
<?php
	//Establish a blank array for receiving the results from the table
	$results = array();

	$rows = '';
	$total_pcs = 0;
	$total_amt = 0;

	$groups = array();
	$query = "SELECT * FROM inventory_qbgroup; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$groups[$r['cogs']] = $r;
	}

	$results = array();
	$entries = array();//invoice / sale / qb entries
	$credits = array();//keep track to avoid duplicates
	$returns = array();//combined credits

	if ($cost_basis=='qb') {
		$query = "SELECT i.memo order_id, je.id item_id, i.memo part_number, je.memo clei, je.date, i.amount price, ";
		$query .= "je.amount actual_cost, je.amount avg_cost, c.name, i.id po_number, i.pushed, i.push_success, ";
		$query .= "je.pushed je_pushed, je.push_success je_push_success, i.voided, je.credit_acct, i.id ref ";
		$query .= "FROM inventory_invoice i, inventory_company c, inventory_journalentry je ";
		$query .= "WHERE i.customer_id = c.id AND i.id = je.invoice_id AND i.memo NOT LIKE 'RO #%' ";//no repairs
		$query .= "AND (je.debit_acct = 'Inventory Sale COGS' OR je.credit_acct = 'Inventory Sale COGS') ";
   		if ($startDate) {
   			$dbStartDate = format_date($startDate, 'Y-m-d');
   			$dbEndDate = format_date($endDate, 'Y-m-d');
   			$query .= "AND je.date between CAST('".$dbStartDate."' AS DATE) and CAST('".$dbEndDate."' AS DATE) ";
		}
		$query .= "ORDER BY je.date ASC, je.id ASC; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			// if the journal entry failed even if the invoice succeeded, mark the error as priority
			if (! $r['je_pushed'] AND $r['pushed']) { $r['pushed'] = $r['je_pushed']; }
			if (! $r['je_push_success'] AND $r['push_success']) { $r['push_success'] = $r['je_push_success']; }

			$r['order_id'] = preg_replace('/^(SO|RO)([[:space:]#]){2}([0-9]{4,})([\s\S]*)/i','$1 $3',$r['order_id']);

			if ($r['credit_acct']=='Inventory Sale COGS') {
				$query2 = "SELECT amount, item FROM inventory_invoiceli WHERE invoice_id = '".$r['ref']."'; ";
				$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$r['price'] = $r2['amount'];
					$r['ref'] = str_replace('COGS for RMA Return #','',$r['clei']);
					$r['part_number'] = $r2['item'];
					$r['clei'] = '';

					$query3 = "SELECT item_id, part_number, clei FROM inventory_rmaticket rt, inventory_solditem si, inventory_inventory i ";
					$query3 .= "WHERE rt.id = '".$r['ref']."' AND rt.item_id = si.id AND si.inventory_id = i.id; ";
					$result3 = qdb($query3,'PIPE') OR die(qe('PIPE').'<BR>'.$query3);
					if (mysqli_num_rows($result3)>0) {
						$r3 = mysqli_fetch_assoc($result3);
						$credits[$r3['item_id']] = true;
						$r['part_number'] = $r3['part_number'];
						$r['clei'] = $r3['clei'];
					}
				}
				$r['type'] = 'Credit';
				$returns[] = $r;
			} else {
				$entries[] = $r;
			}
		}
	} else {
		$entries = getSalesRecords(0,$startDate,$endDate);

		$query = "SELECT si.serial, rt.repair_id, si.inventory_id, item_id id, si.cost actual_cost, rt.id ref, si.so_id order_id, ";
		$query .= "si.price, si.rep_id, si.freight_cost, si.po, si.avg_cost, si.invoice_id, si.orig_cost, i.part_number, i.clei, ";
		$query .= "c.name, rt.received_date date, rt.id item_id, rt.new_item_id, rt.shipped_date ";
		$query .= "FROM inventory_rmaticket rt, inventory_solditem si, inventory_inventory i, inventory_rmaticketmaster rtm, inventory_company c ";
		$query .= "WHERE rt.item_id = si.id AND si.so_id IS NOT NULL ";//must be a sale, not a repair (at least for our purposes right now)
		$query .= "AND rt.received_date IS NOT NULL ";//must be received back to count as a returned unit
		$query .= "AND si.inventory_id = i.id AND rt.master_id = rtm.id AND rtm.company_id = c.id ";
   		if ($startDate) {
   			$dbStartDate = format_date($startDate, 'Y-m-d');
   			$dbEndDate = format_date($endDate, 'Y-m-d');
   			$query .= "AND rt.received_date between CAST('".$dbStartDate."' AS DATE) AND CAST('".$dbEndDate."' AS DATE) ";
		}
		$query .= "ORDER BY rt.received_date ASC, si.id ASC; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$r['type'] = 'Return';

			$r['po_number'] = '';
			$r['complete'] = '';

			// if repaired, calc repair cost
			if ($r['repair_id']) {
				$repair_cost = calcLegacyRepairCost($r);
				// if item was not replaced and only repaired, and so long as the item has been shipped back,
				// then we count only the repair cost against us
				if (! $r['new_item_id']) {
					if ($r['shipped_date']) {
						$r['type'] = 'Repair';

						$r['descr'] = trim($r['part_number'].' '.$r['clei']);
						$r['price'] = 0;//$repair_cost;
						$r['actual_cost'] = $repair_cost;
						$r['avg_cost'] = $repair_cost;
					} else {
						//now that I have the below returns categorized differently, I don't want to exclude credit memos below
						unset($credits[$r['id']]);
						$r['type'] = 'Pending';

						$r['price'] = $repair_cost;
						$r['actual_cost'] = $repair_cost;
						$r['avg_cost'] = $repair_cost;
					}

					$r['ref'] = $r['repair_id'];
				}
			} else if ($r['new_item_id']) {
continue;//dl 8-23-17
				$r['type'] = 'Replace';

				$r['price'] = 0;
				$r['actual_cost'] = 0;
				$r['avg_cost'] = 0;
			}

			$query2 = "SELECT * FROM inventory_creditmemo WHERE rma_id = '".$r['ref']."'; ";
			$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
			// only if this rma is NOT also accounted for in credit memos do we include it in $returns[]
			if (mysqli_num_rows($result2)==0) {
				$credits[$r['id']] = true;

				if ($r['type']=='Repair') {
					$entries[] = $r;
				} else {
					$returns[] = $r;
				}
			}
		}
	}


	/*******************************************************
	*** SPECIAL PROJECTS AND OTHER NON-INVENTORY ENTRIES ***
	*******************************************************/

	$query = "SELECT '' order_id, je.id item_id, je.debit_acct part_number, je.memo clei, je.date, '0.00' price, '' ref, ";
	$query .= "je.amount actual_cost, je.amount avg_cost, '' name, je.id po_number, je.pushed, je.push_success, '0' voided, je.credit_acct ";
	$query .= "FROM inventory_journalentry je, inventory_qbgroup qbg ";
	$query .= "WHERE invoice_id IS NULL AND qbg.cogs = je.debit_acct ";
	$query .= "AND (je.debit_acct = 'Inventory Sale COGS' OR je.credit_acct = 'Inventory Sale COGS') ";
   	if ($startDate) {
   		$dbStartDate = format_date($startDate, 'Y-m-d');
   		$dbEndDate = format_date($endDate, 'Y-m-d');
   		$query .= "AND je.date between CAST('".$dbStartDate."' AS DATE) and CAST('".$dbEndDate."' AS DATE) ";
	}
	$query .= "ORDER BY je.date ASC, je.id ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$r['descr'] = trim($r['part_number'].' '.$r['clei']);

		if ($r['credit_acct']=='Inventory Sale COGS') {
			$r['type'] = 'Credit';
			$returns[] = $r;
		} else {
			$entries[] = $r;
		}

		$query2 = "SELECT '' order_id, je_id item_id, jeli.debit_acct part_number, jeli.memo clei, '".$r['date']."' date, ";
		$query2 .= "'0.00' price, jeli.amount actual_cost, jeli.amount avg_cost, '' name, je_id po_number, ";
		$query2 .= "'".$r['pushed']."' pushed, '".$r['push_success']."' push_success, '0' voided, '' ref  ";
		$query2 .= "FROM inventory_journalentryli jeli, inventory_qbgroup qbg ";
		$query2 .= "WHERE je_id = '".$r['item_id']."' AND qbg.cogs = jeli.debit_acct; ";
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			if ($r['credit_acct']=='Inventory Sale COGS') {
				$r['type'] = 'Credit';
				$returns[] = $r2;
			} else {
				$entries[] = $r2;
			}
		}
	}


	/***************/
	/*** CREDITS ***/
	/***************/

//	if ($cost_basis<>'qb') {
		$query = "SELECT rt.id item_id, si.id si_item_id, si.so_id order_id, cm.date date, cm.ref_no, cm.po_number, cm.rma_id ref, ";
		//$query .= "li.desc part_number, '' clei, li.quantity qty, li.amount price, c.name, si.cost actual_cost, si.avg_cost ";
		//$query .= "li.desc descr, li.quantity qty, li.amount price, c.name, '0.00' actual_cost, '0.00' avg_cost, cm.voided ";
		$query .= "i.part_number, i.clei, li.quantity qty, li.amount price, c.name, si.cost actual_cost, si.avg_cost ";
		$query .= "FROM inventory_creditmemo cm, inventory_creditmemoli li, inventory_rmaticket rt, inventory_solditem si, inventory_company c, inventory_inventory i ";
		$query .= "WHERE li.cm_id = cm.id AND customer_id = c.id AND cm.voided = 0 AND cm.rma_id = rt.id AND rt.item_id = si.id AND si.inventory_id = i.id ";
   		if ($startDate) {
   			$dbStartDate = format_date($startDate, 'Y-m-d');
   			$dbEndDate = format_date($endDate, 'Y-m-d');
   			$query .= "AND cm.date between CAST('".$dbStartDate."' AS DATE) and CAST('".$dbEndDate."' AS DATE) ";
		}
		$query .= "ORDER BY li.cm_id ASC, cm.date ASC; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			if (isset($credits[$r['si_item_id']])) { continue; }

			$r['type'] = 'Credit';
			$returns[] = $r;
		}
//	}



	foreach ($entries as $r) {
		$key = $r['date'].'.A'.preg_replace('/^(RO|SO)[[:space:]]([0-9]+)$/','$2',$r['order_id']).'.'.$r['item_id'].'.'.$r['ref'];
		if ($r['order_id']) {
			if (substr($r['order_id'],0,2)=='RO') {
				$ro = str_replace('RO ','',$r['order_id']);
				$r['order_id'] = '<a href="https://db.ven-tel.com:10086/ventel/repair/edit/'.$ro.'" target="_new">Repair '.$ro.'</a>';
			} else {
				$so = str_replace('SO ','',$r['order_id']);
				$r['order_id'] = '<a href="https://db.ven-tel.com:10086/ventel/company/view_so/'.$so.'" target="_new">SO '.$so.'</a>';
			}
		}
		$ref = '';
		if ($r['ref']) {
			if ($r['type']=='Repair' OR $r['type']=='Pending') {
				$ref = '<a href="https://db.ven-tel.com:10086/ventel/repair/edit/'.$r['ref'].'" target="_new">RMA Repair '.$r['ref'].'</a>';
			} else {
				$ref = '<a href="https://db.ven-tel.com:10086/ventel/company/edit_invoice/'.$r['ref'].'" target="_new">Invoice '.$r['ref'].'</a>';
			}
		}
		if (! isset($results[$key])) {
			$results[$key] = array(
				'type'=>'Sale',
				'qty'=>0,
				'cogs'=>0,
				'sale_amount'=>0,
				'voided'=>0,
				'pushed'=>1,
				'push_success'=>1,
				'order'=>$r['order_id'],
				'ref'=>$ref,
				'descr'=>$r['descr'],
				'price'=>$r['price'],
				'company'=>$r['name'],
				'actual_cost'=>$r['actual_cost'],
				'avg_cost'=>$r['avg_cost'],
				'date'=>$r['date'],
				'po_number'=>$r['po_number'],
			);
		}
		$results[$key]['qty']++;
		if ($cost_basis=='average') {
			$results[$key]['cogs'] += $r['avg_cost'];
		} else {
			$results[$key]['cogs'] += $r['actual_cost'];
		}
		$results[$key]['sale_amount'] += $r['price'];
	}

	foreach ($returns as $r) {
		$r['descr'] = trim($r['part_number'].' '.$r['clei']);

		$key = $r['date'].'.B'.$r['item_id'].'.'.preg_replace('/^(RO|SO)[[:space:]]([0-9]+)$/','$2',$r['order_id']);//$r['order_id'];
		if ($r['order_id']) {
			if (substr($r['order_id'],0,2)=='RO') {
				$ro = str_replace('RO ','',$r['order_id']);
				$r['order_id'] = '<a href="https://db.ven-tel.com:10086/ventel/repair/edit/'.$ro.'" target="_new">Repair '.$ro.'</a>';
			} else {
				$so = str_replace('SO ','',$r['order_id']);
				$r['order_id'] = '<a href="https://db.ven-tel.com:10086/ventel/company/view_so/'.$so.'" target="_new">SO '.$so.'</a>';
			}
		}
		$ref = '';
		if ($r['ref']) {
			if ($r['type']=='Repair' OR $r['type']=='Pending') {
				$ref = '<a href="https://db.ven-tel.com:10086/ventel/repair/edit/'.$r['ref'].'" target="_new">RMA Repair '.$r['ref'].'</a>';
			} else {
				$ref = '<a href="https://db.ven-tel.com:10086/ventel/company/rma_edit/'.$r['ref'].'" target="_new">RMA '.$r['ref'].'</a>';
			}
		}
		if (! isset($results[$key])) {
			$results[$key] = array(
				'type'=>$r['type'],
				'qty'=>0,
				'cogs'=>0,
				'sale_amount'=>0,
				'voided'=>$r['voided'],
				'pushed'=>1,
				'push_success'=>1,
				'order'=>$r['order_id'],
				'ref'=>$ref,
				'descr'=>$r['descr'],
				'price'=>$r['price'],
				'company'=>$r['name'],
				'actual_cost'=>$r['actual_cost'],
				'avg_cost'=>$r['avg_cost'],
				'date'=>$r['date'],
				'po_number'=>$r['po_number'],
			);
		}
		$results[$key]['qty']++;
		if ($cost_basis=='average') {
			$results[$key]['cogs'] += $r['avg_cost'];
		} else {
			$results[$key]['cogs'] += $r['actual_cost'];
		}
		$results[$key]['sale_amount'] += $r['price'];
	}

	ksort($results);

	$returned_cogs = false;
	$sum_qty = 0;
	$sum_ext_price = 0;
	$sum_cogs_credits = 0;
	$sum_cogs_debits = 0;
	$sum_pending_cogs = 0;
	$sum_profit = 0;
	$sum_credits = 0;
	foreach ($results as $r) {
		if ($r['cogs']=='') { $r['cogs'] = '0.00'; }
		$ext_price = ($r['qty']*$r['price']);

		if ($r['type']=='Sale') {
			$type = '<span class="label label-success label-box">Sale</span>';
		} else {
			$type = '<span class="label label-danger label-box">'.$r['type'].'</span>';
		}

		$ext_debit = '';
		$ext_credit = '';
		$cogs_credit = '';
		$cogs_debit = '';
		$cls = '';
		if ($r['voided']) {
			$cls = ' class="strikeout"';
		} else {
			if ($r['type']=='Sale' OR $r['type']=='Repair') {
				$ext_debit = format_price(round($ext_price,2),true,' ');
				$profit = ($r['sale_amount']-$r['cogs']);
				$sum_profit += $profit;
				$sum_qty += $r['qty'];
				$sum_ext_price += $ext_price;
				$cogs_debit = format_price(round($r['cogs'],2),true,' ');
				$sum_cogs_debits += $r['cogs'];
			} else {
				$ext_credit = format_price(-round($ext_price,2),true,' ');
				$sum_credits += $ext_price;
				$ext_price = '';

				$sum_qty -= $r['qty'];
				$cogs_credit = format_price(-round($r['cogs'],2),true,' ');//.' <sup><i class="fa fa-plus"></i></sup>';
				$returned_cogs = true;
				$sum_cogs_credits -= $r['cogs'];
				$profit = -($r['sale_amount']-$r['cogs']);
				$sum_profit += $profit;
			}

			if (! $r['pushed'] OR ! $r['push_success']) {
				$cls = ' class="grayout"';
				$sum_pending_cogs += $r['cogs'];
			}
		}

		$rows .= '
                            <!-- row -->
                            <tr'.$cls.'>
                                <td>
                                    '.$type.' '.format_date($r['date'],'M j, Y').'
                                </td>
                                <td>
                                    <small>'.$r['descr'].'</small>
                                </td>
                                <td class="text-right">
                                    <span class="pull-left">'.$r['qty'].'</span>
                                    <small>'.format_price($r['price'],true,' ').'</small>
                                </td>
                                <td class="text-right primary">
                                    '.$ext_credit.'
                                </td>
                                <td class="text-right primary">
                                    '.$ext_debit.'
                                </td>
                                <td>
                                    <strong>'.$r['order'].'</strong>
                                </td>
                                <td>
									'.$r['ref'].'
                                </td>
                                <td>
	                                <small><a href="#">'.$r['company'].'</a></small>
                                </td>
                                <td class="text-right primary">
                                    '.$cogs_credit.'
                                </td>
                                <td class="text-right primary">
                                    '.$cogs_debit.'
                                </td>
                                <td class="text-right">
                                    '.format_price(round($profit,2),true,' ').'
                                </td>
<!--
                                <td class="text-right">
                                    '.format_price(round($sum_profit,2),true,' ').'
                                </td>
-->
                            </tr>
		';
	}
?>


	<!-- Declare the class/rows dynamically by the type of information requested (could be transitioned to jQuery) -->
                <div class="row">
                    <table class="table table-hover table-striped table-condensed">
                        <thead>
                            <tr>
                                <th class="col-md-1">
                                    Date 
                                </th>
                                <th class="col-md-2">
                                    <span class="line"></span>
                                    Description
                                </th>
                                <th class="col-md-1 text-right">
                                    <span class="line"></span>
                                    <span class="pull-left">Qty</span>
                                    Price (ea)&nbsp;
                                </th>
                                <th class="col-md-2 text-center primary" colspan="2">
                                    <span class="line"></span>
									Revenue
									<br/>
									<div class="col-sm-6 text-center">
                                    	Credit
									</div>
									<div class="col-sm-6 text-center">
                                    	Debit
									</div>
                                </th>
                                <th class="col-md-1">
                                    <span class="line"></span>
                                    Order#
                                </th>
                                <th class="col-md-1">
                                    <span class="line"></span>
                                    Reference
                                </th>
                                <th class="col-md-1">
                                    <span class="line"></span>
                                    Company</small>
                                </th>
                                <th class="col-md-2 text-center primary" colspan="2">
                                    <span class="line"></span>
									<?php if ($cost_basis=='average') { echo 'Avg'; } else { echo 'Actual'; } ?> COGS
									<br/>
									<div class="col-sm-6 text-center">
                                    	Credit
									</div>
									<div class="col-sm-6 text-center">
                                    	Debit
									</div>
                                </th>
                                <th class="col-md-1 text-center">
                                    <span class="line"></span>
                                    Gross Profit
                                </th>
<!--
                                <th class="col-md-1 text-center">
                                    <span class="line"></span>
                                    Balance
                                </th>
-->
                            </tr>
                        </thead>
                        <tbody>
                        	<?php echo $rows; ?>
							<tr class="first">
								<td colspan="2"> </td>
								<td><strong><?php echo $sum_qty; ?></strong></td>
								<td class="text-right">
                                    <strong><?php echo format_price(-round($sum_credits,2),true,' '); ?></strong><br/>
									Credits
								</td>
                                <td class="text-right">
                                    <strong><?php echo format_price(round($sum_ext_price,2),true,' '); ?></strong><br/>
									<?php if ($cost_basis=='qb') { echo 'Invoiced'; } else { echo 'Income'; } ?>
                                </td>
								<td class="text-right">
<?php if ($cost_basis=='qb' AND $sum_pending_cogs>0) { ?>
									<strong><?php echo format_price(round($sum_pending_cogs,2),true,' '); ?> <sup><i class="fa fa-asterisk"></i></sup></strong><br/>
									Pending COGS
<?php } ?>
								</td>
								<td colspan="2"> </td>
                                <td class="text-right">
                                    <strong><?php echo format_price(round($sum_cogs_credits,2),true,' '); ?></strong><br/>
									Returns
                                </td>
                                <td class="text-right">
                                    <strong><?php echo format_price(round($sum_cogs_debits,2),true,' '); ?></strong><br/>
									COGS
                                </td>
                                <td class="text-right">
                                    <strong><?php echo format_price(round($sum_profit,2),true,' '); ?></strong><br/>
									Profit
                                </td>
<!--
                                <td class="text-right">
                                </td>
-->
							</tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- end orders table -->

<?php if ($cost_basis=='qb' AND $sum_pending_cogs>0) { ?>
<i class="fa fa-asterisk"></i> Pending COGS indicates unsynchronized record(s) between the DB and QB<br/>
<?php } ?>
<?php if ($returned_cogs) { ?>
<!--
<i class="fa fa-plus"></i> COGS of Returned Items are subtracted from Total COGS since they are returning to inventory<br/>
-->
<?php } ?>

<BR><BR><BR><BR><BR>
<BR><BR><BR><BR><BR>

	</div>
	</form>
<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">

        $(document).ready(function() {
			$('.btn-report').click(function() {
				var btnValue = $(this).data('value');
				$(this).closest("div").find("input[type=radio]").each(function() {
					if ($(this).val()==btnValue) { $(this).attr('checked',true); }
				});
			});
        });
    </script>

</body>
</html>
