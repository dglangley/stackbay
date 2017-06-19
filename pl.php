<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	include_once $rootdir.'/inc/calcLegacyRepairCost.php';

	$PURCHASES = array();
	function getSalesRecords($item_id,$startDate='',$endDate='') {
		global $PURCHASES;
		$entries = array();

		$query = "SELECT serial_no, si.partid, si.qty, price, purchase_item_id, sales_item_id, returns_item_id, ";
		$query .= "si.so_number, so.created date, i.id invid, part, heci, c.name, '' ref ";
		$query .= "FROM inventory i, inventory_history h, parts p, sales_items si, sales_orders so, companies c ";
		$query .= "WHERE i.partid = p.id AND h.invid = i.id AND h.value = si.id AND h.field_changed = 'sales_item_id' ";
		$query .= "AND si.so_number = so.so_number AND so.companyid = c.id ";
		if ($startDate) {
			$dbStartDate = format_date($startDate, 'Y-m-d');
			$dbEndDate = format_date($endDate, 'Y-m-d');
			$query .= "AND so.created between CAST('".$dbStartDate."' AS DATE) AND CAST('".$dbEndDate."' AS DATE) ";
		}
		$query .= "ORDER BY so.created ASC, si.so_number ASC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$r['descr'] = trim($r['part'].' '.$r['heci']);
			$r['order_id'] = $r['so_number'];

			$r['price'] = $r['price'];
			$r['actual_cost'] = 0;
			$r['avg_cost'] = 0;

			if (isset($PURCHASES[$r['invid']])) {
				$r['actual_cost'] = $PURCHASES[$r['invid']]['actual_cost'];
				$r['avg_cost'] = $PURCHASES[$r['invid']]['avg_cost'];
			} else {
				$query2 = "SELECT SUM(actual) actual_cost, SUM(average) avg_cost ";
				$query2 .= "FROM inventory_costs c ";
				$query2 .= "WHERE c.inventoryid = '".$r['invid']."'; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$r['actual_cost'] = $r2['actual_cost'];
					$r['avg_cost'] = $r2['avg_cost'];
					$PURCHASES[$r['invid']] = array('actual'=>$r['actual_cost'],'average'=>$r['avg_cost']);
				}
			}

			$entries[] = $r;
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
	<form class="form-inline" method="get" action="/profit_loss.php">

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
					<div class="btn-group" id="dateRanges">
						<div id="btn-range-options">
							<button class="btn btn-default btn-sm">&gt;</button>
							<div class="animated fadeIn hidden" id="date-ranges">
						        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="<?php echo date("m/01/Y"); ?>" data-end="<?php echo date("m/d/Y"); ?>">MTD</button>
				    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("01/01/Y"); ?>" data-end="<?php echo date("03/31/Y"); ?>">Q1</button>
								<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("04/01/Y"); ?>" data-end="<?php echo date("06/30/Y"); ?>">Q2</button>
								<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("07/01/Y"); ?>" data-end="<?php echo date("09/30/Y"); ?>">Q3</button>		
								<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("10/01/Y"); ?>" data-end="<?php echo date("12/31/Y"); ?>">Q4</button>	
<?php
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
	$oldid = 0;
//	echo getCompany($company_filter,'id','oldid');
//	echo('Value Passed in: '.$company_filter);
	//If there is a company id, translate it to the old identifier
	if($company_filter != 0){$oldid = dbTranslate($company_filter, false);}
//	echo '<br>The value of this company in the old database is: '.$oldid;

	$rows = '';
	$total_pcs = 0;
	$total_amt = 0;

	$results = array();
	$entries = array();//invoice / sale / qb entries
	$credits = array();//keep track to avoid duplicates
	$returns = array();//combined credits

	$entries = getSalesRecords(0,$startDate,$endDate);

	if (1 == 2) {
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

/*
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
*/


	/***************/
	/*** CREDITS ***/
	/***************/

/*
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
*/


	foreach ($entries as $r) {
		$key = $r['date'].'.A'.preg_replace('/^(RO|SO)[[:space:]]([0-9]+)$/','$2',$r['order_id']).'.'.$r['partid'].'.'.$r['ref'];
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
