<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getCost.php';
	include_once $rootdir.'/inc/getCOGS.php';
	include_once $rootdir.'/inc/getDisposition.php';
//	include_once $rootdir.'/inc/calcLegacyRepairCost.php';
	include_once $rootdir.'/inc/calcRepairCost.php';
	include_once $rootdir.'/inc/order_type.php';

	function getReturns($order_number=0, $order_type='', $inventoryid=0) {
		global $dbStartDate,$dbEndDate,$order_search,$companyid;

		$returns = array();

		$query = "SELECT r.*, r.created date, ri.*, c.name, '' avg_cost, r.status ";
		$query .= "FROM returns r, return_items ri, companies c ";
		$query .= "WHERE r.rma_number = ri.rma_number ";
		if ($order_number AND $order_type) {
			$query .= "AND order_number = '".$order_number."' AND order_type = '".$order_type."' AND inventoryid = '".$inventoryid."' ";
		} else {
			if ($companyid) { $query .= "AND r.companyid = '".$companyid."' "; }
			if ($order_search) { $query .= "AND (r.rma_number = '".res($order_search)."' OR r.order_number = '".res($order_search)."') "; }
			else if ($dbStartDate) {
				$query .= "AND r.created BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
			}
		}
		$query .= "AND r.companyid = c.id; ";// AND r.status <> 'Void'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$r['order_number'] = $r['rma_number'];
			$r['order_type'] = getDisposition($r['dispositionid']);

			// repair, still with possible credit
			//$query2 = "SELECT ro_number, invid, id FROM repair_items WHERE ref_1 = '".$r['id']."' AND ref_1_label = 'return_item_id'; ";
			// don't count voided/canceled repair orders
			$query2 = "SELECT ri.ro_number, ri.invid, ri.id FROM repair_items ri, repair_orders ro ";
			$query2 .= "WHERE ri.ref_1 = '".$r['id']."' AND ri.ref_1_label = 'return_item_id' AND ri.ro_number = ro.ro_number ";
			$query2 .= "AND (ri.repair_code_id <> 8 AND ri.repair_code_id <> 9 AND ri.repair_code_id <> 18); ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)==0) {
				$r['ref'] = '';
				if ($r['dispositionid']==3) {//3==Repair so if no results, we gotta ask why
					if ($r['status']<>'Void' AND $U['id']==1) { echo $query2.'<BR>'; }
				} else if ($r['dispositionid']==1) {//Credit
					continue;
				} else {//if ($r['dispositionid']==2) {//Replace/Exchange
					// get Sales COGS of item returned on this RMA to Credit back the Sales COGS account
					$query3 = "SELECT ri.*, sc.* FROM returns r, return_items ri, inventory_history h, sales_cogs sc, sales_items si ";
					$query3 .= "WHERE ri.rma_number = '".$r['rma_number']."' ";
					$query3 .= "AND r.rma_number = ri.rma_number AND (h.field_changed = 'returns_item_id' AND h.value = ri.id) AND sc.inventoryid = h.invid ";
					$query3 .= "AND sc.item_id = si.id AND si.so_number = r.order_number AND r.order_type = 'Sale' ";
					$query3 .= "GROUP BY h.invid; ";
					$result3 = qdb($query3);
					if (mysqli_num_rows($result3)>0) {
//						echo $query3.'<BR>';
						$r3 = mysqli_fetch_assoc($result3);
						$r['avg_cost'] = $r3['cogs_avg'];
						$r['exchange_cogs'] = 0;

						// in Exchanges, we must not only offset COGS by Crediting back to stock, but also need to Debit the COGS account
						// since we're shipping an item back out
						$query4 = "SELECT * FROM sales_items si, sales_cogs sc WHERE si.id = sc.item_id AND sc.item_id_label = 'sales_item_id' ";
						$query4 .= "AND (";
						$query4 .= "(si.ref_1 = '".$r3['return_item_id']."' AND si.ref_1_label = 'return_item_id') ";
						$query4 .= "OR (si.ref_2 = '".$r3['return_item_id']."' AND si.ref_2_label = 'return_item_id') ";
						$query4 .= ") ";
						$query4 .= "AND (si.so_number = '".$r3['so_number']."'); ";
						$result4 = qdb($query4);
						if (mysqli_num_rows($result4)>0) {
							$r4 = mysqli_fetch_assoc($result4);

							$r['exchange_cogs'] = $r4['cogs_avg'];
						}
					}
				}
				$query2 = "SELECT * FROM parts WHERE id = '".$r['partid']."'; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)==0) {
					echo $query2.'<BR>';
					continue;
				}
				$r2 = mysqli_fetch_assoc($result2);
				$r['descr'] = trim($r2['part'].' '.$r2['heci']);

				$returns[] = $r;
				continue;
			}
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$query3 = "SELECT * FROM parts WHERE id = '".$r['partid']."'; ";
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
				if (mysqli_num_rows($result3)==0) {
					echo $query3.'<BR>';
				}
				$r3 = mysqli_fetch_assoc($result3);
				$r['descr'] = trim($r3['part'].' '.$r3['heci']);

				if ($r['order_type']=='Repair') {
					$r['avg_cost'] = calcRepairCost($r2['ro_number'],$r2['id'],$r2['invid'],true);
				}
				$r['ref'] = $r2['ro_number'];

				$returns[] = $r;
			}
		}

		return ($returns);
	}


	/***************/
	/*** CREDITS ***/
	/***************/
	function getCredits() {//$order_number, $order_type) {
		global $companyid,$dbStartDate,$dbEndDate,$order_search;

		$credits = array();

		$query = "SELECT c.name, sc.companyid, sci.qty, sci.amount price, ri.inventoryid, ri.partid, part, heci, ";
		$query .= "sc.id ref, sc.rma_number order_number, sc.date_created date, 'Credit' order_type, sc.order_number og_order, sc.order_type og_type, ";
		$query .= "NULL line_number, NULL item_id, NULL serial_no, NULL invoice_no, NULL packageid, 'Active' status ";
		$query .= "FROM companies c, credits sc, credit_items sci ";//, return_items ri, parts p ";
		$query .= "LEFT JOIN return_items ri ON sci.return_item_id = ri.id ";
		$query .= "LEFT JOIN parts p ON ri.partid = p.id ";
		$query .= "WHERE sc.companyid = c.id ";
//		$query .= "AND sc.order_number = '".$order_number."' AND sc.order_type = '".$order_type."' ";
		if ($companyid) { $query .= "AND sc.companyid = '".$companyid."' "; }
		if ($order_search) { $query .= "AND (sc.rma_number = '".res($order_search)."' OR sc.id = '".res($order_search)."' OR sc.order_number = '".res($order_search)."') "; }
		else if ($dbStartDate) {
			$query .= "AND sc.date_created BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
		}
//		$query .= "AND sci.return_item_id = ri.id AND ri.partid = p.id ";
		$query .= "AND sc.id = sci.cid; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$T = order_type($r['og_type']);

			$r['descr'] = trim($r['part'].' '.$r['heci']);

			// find out if original a Sale or Repair to get item id for cogs lookup from original billable order
			$query2 = "SELECT items.id FROM ".$T['items']." items, inventory_history h ";
			$query2 .= "WHERE items.".$T['order']." = '".$r['og_order']."' AND items.id = h.value AND h.field_changed = '".$T['item_label']."' ";
			$query2 .= "AND h.invid = '".$r['inventoryid']."' ";
			$query2 .= "GROUP BY h.invid; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$r['avg_cost'] = getCOGS($r['inventoryid'],$r2['id'],$T['item_label'],true);
			}

			$credits[] = $r;
		}

		return ($credits);
	}

	$PURCHASES = array();
	function getSalesRecords() {
		global $PURCHASES,$dbStartDate,$dbEndDate,$order_search,$companyid,$buckets;
		$entries = array();
		$returns = array();
		$freight_charges = array();

		$query = "SELECT ii.line_number, c.name, c.id companyid, i.invoice_no, i.invoice_no ref, i.date_invoiced date, ";
		$query .= "i.order_number, i.order_type, ii.id invoice_item_id, ii.item_id partid, ii.amount, s.packageid, ii.memo, i.freight, i.status ";
		$query .= "FROM companies c, invoices i, invoice_items ii ";
		$query .= "LEFT JOIN invoice_shipments s ON ii.id = s.invoice_item_id ";
		$query .= "WHERE c.id = i.companyid AND i.invoice_no = ii.invoice_no ";
		if ($companyid) { $query .= "AND i.companyid = '".$companyid."' "; }
		if ($order_search) { $query .= "AND (i.invoice_no = '".res($order_search)."' OR i.order_number = '".res($order_search)."') "; }
		else if ($dbStartDate) {
			$query .= "AND i.date_invoiced BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
		}
		$query .= "ORDER BY i.date_invoiced ASC, i.order_number ASC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {

			// sum freight charges for each invoice only once
			if ($r['freight'] AND ! isset($freight_charges[$r['invoice_no']])) {
				$freight = $r;
				$freight['qty'] = 1;
				$freight['invoice_item_id'] = 999999;
				$freight['line_number'] = 999999;
				$freight['partid'] = 0;
				$freight['price'] = $r['freight'];
				$freight['memo'] = '';
				$freight['descr'] = 'FREIGHT CHARGE';
				$freight['avg_cost'] = 0;
				$freight['actual_cost'] = 0;

				$entries[] = $freight;
				$freight_charges[$r['invoice_no']] = true;
			}
if ($GLOBALS['U']['id']==1 AND $r['order_type']=='Service') { continue; }

			$T = order_type($r['order_type']);
			if (! $T OR count($T)==0 OR ! $T['items']) {
				$entry = $r;
				$entry['descr'] = '- ERROR finding order information -';
				$entry['avg_cost'] = 0;
				$entry['actual_cost'] = 0;

				$entries[] = $entry;
				continue;
			}
			$r['price'] = $r['amount'];

			// a place for misc invoiced charges (COD Charges, etc)
			if (! $r['partid']) {
				$entry = $r;
				$entry['descr'] = $r['memo'];
				$entry['avg_cost'] = 0;
				$entry['actual_cost'] = 0;

				$entries[] = $entry;
				continue;
			}

			if (! $r['packageid']) {
				$query2 = "SELECT items.id item_id, i.qty, i.serial_no, i.id inventoryid, part, heci ";
				$query2 .= "FROM ".$T['items']." items, inventory_history h, inventory i, parts ";
				$query2 .= "WHERE items.".$T['order']." = '".$r['order_number']."' AND items.line_number ";
				if ($r['line_number']) { $query2 .= "= '".$r['line_number']."' "; } else { $query2 .= "IS NULL "; }
				$query2 .= "AND (h.field_changed = '".$T['item_label']."' AND h.value = items.id) ";
				$query2 .= "AND h.invid = i.id AND i.partid = parts.id ";
				if ($T['items']=='service_items') {
					$query2 .= "AND items.item_id = '".$r['partid']."' AND items.item_label = 'partid' ";
				} else {
					$query2 .= "AND items.partid = '".$r['partid']."' ";
				}
				$query2 .= "GROUP BY h.invid, h.value; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)==0) {
					$entry = $r;
					$entry['descr'] = $r['order_type'];
					$entry['avg_cost'] = 0;
					$entry['actual_cost'] = 0;

					$entries[] = $entry;
				}
				while ($r2 = mysqli_fetch_assoc($result2)) {
					$entry = $r;
					$entry['descr'] = trim($r2['part'].' '.$r2['heci']);

					//dl 8-31-17 to accommodate non-serialized qtys instead of a single-qty-per-record serial-based model
					if ($r2['qty']==0) { $r2['qty'] = 1; }
					$entry['qty'] = $r2['qty'];

					$entry['avg_cost'] = 0;
					$entry['actual_cost'] = getCost($r['partid'],'actual');

					if ($r['order_type']=='Repair') {
						$entry['avg_cost'] = calcRepairCost($r['order_number'],$r2['item_id'],$r2['inventoryid']);
					} else {
						$query3 = "SELECT cogs_avg FROM sales_cogs sc WHERE sc.inventoryid = '".$r2['inventoryid']."' ";
						$query3 .= "AND item_id = '".$r2['item_id']."' AND item_id_label = '".$T['item_label']."'; ";
						$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
						if (mysqli_num_rows($result3)>0) {
							$r3 = mysqli_fetch_assoc($result3);
							$entry['avg_cost'] = $r3['cogs_avg'];
						}
					}

					$entries[] = $entry;
/*
					$ret = getReturns($r['order_number'],$r['order_type'],$r2['inventoryid']);
					foreach ($ret as $ri) {
						$entry['order_number'] = $ri['order_number'];
						$entry['order_type'] = $ri['order_type'];
						$entry['ref'] = $ri['ref'];
						$entry['qty'] = $ri['qty'];
						$entry['avg_cost'] = $ri['avg_cost'];

						$returns[] = $entry;
					}
*/
				}
			} else {
				$pseudos = array();
				// on repairs, we use sales_items to ship the product, which ends up being a pseudo-order for repairs, so we
				// need to work backwards using the referenced line items in order to get the associated shipments
				if ($r['order_type']=='Repair') {
					$query3 = "SELECT so_number FROM sales_items si, repair_items ri ";
					$query3 .= "WHERE ri.ro_number = '".$r['order_number']."' ";
					$query3 .= "AND ((ri.id = si.ref_1 AND si.ref_1_label = 'repair_item_id') OR (ri.id = si.ref_2 AND si.ref_2_label = 'repair_item_id')); ";
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					while ($r3 = mysqli_fetch_assoc($result3)) {
						$psuedos[] = array('order_number'=>$r3['so_number'],'order_type'=>'Sale');
					}
				}

				$query2 = "SELECT items.id item_id, i.qty, i.serial_no, i.id inventoryid, part, heci ";
				$query2 .= "FROM ".$T['items']." items, packages p, package_contents pc, inventory_history h, inventory i, parts ";
				$query2 .= "WHERE items.".$T['order']." = '".$r['order_number']."' AND items.line_number ";
				if ($r['line_number']) { $query2 .= "= '".$r['line_number']."' "; } else { $query2 .= "IS NULL AND items.partid = '".$r['partid']."' "; }
				$query2 .= "AND pc.serialid = h.invid AND p.id = pc.packageid AND pc.packageid = '".$r['packageid']."' ";
				$query2 .= "AND ( (items.".$T['order']." = p.order_number AND p.order_type = '".$r['order_type']."') ";
				foreach ($pseudos as $p) {
					$query2 .= "OR (p.order_number = '".$p['order_number']."' AND p.order_type = '".$p['order_type']."') ";
				}
				$query2 .= ") AND (h.field_changed = '".$T['item_label']."' AND h.value = items.id) ";
				$query2 .= "AND h.invid = i.id AND i.partid = parts.id ";
				// exclude rma-replaced items because those will be counted in the returns section
				$query2 .= "AND (items.ref_1_label IS NULL OR items.ref_1_label <> 'sales_item_id') ";
				$query2 .= "AND (items.ref_2_label IS NULL OR items.ref_2_label <> 'sales_item_id') ";
				$query2 .= "AND items.partid = '".$r['partid']."' ";
				$query2 .= "GROUP BY h.invid, h.value; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)==0) {
					$entry = $r;
					$entry['descr'] = '- ERROR finding order information, please investigate -';
					$entry['avg_cost'] = 0;
					$entry['actual_cost'] = 0;

					$entries[] = $entry;
				}
				while ($r2 = mysqli_fetch_assoc($result2)) {
					$entry = $r;
					$entry['descr'] = trim($r2['part'].' '.$r2['heci']);

					//dl 8-31-17 to accommodate non-serialized qtys instead of a single-qty-per-record serial-based model
					if ($r2['qty']==0) { $r2['qty'] = 1; }
					$entry['qty'] = $r2['qty'];

					$entry['avg_cost'] = 0;
					$entry['actual_cost'] = getCost($r['partid'],'actual');

					if ($r['order_type']=='Repair') {
						$entry['avg_cost'] = calcRepairCost($r['order_number'],$r2['item_id'],$r2['inventoryid']);
					} else {
						$query3 = "SELECT cogs_avg FROM sales_cogs sc WHERE sc.inventoryid = '".$r2['inventoryid']."' ";
						$query3 .= "AND item_id = '".$r2['item_id']."' AND item_id_label = '".$T['item_label']."'; ";
						$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
						if (mysqli_num_rows($result3)>0) {
							$r3 = mysqli_fetch_assoc($result3);
							$entry['avg_cost'] = $r3['cogs_avg'];
						}
					}

					$entries[] = $entry;
/*
					$ret = getReturns($r['order_number'],$r['order_type'],$r2['inventoryid']);
					foreach ($ret as $ri) {
						$entry['order_number'] = $ri['order_number'];
						$entry['order_type'] = $ri['order_type'];
						$entry['ref'] = $ri['ref'];
						$entry['qty'] = $ri['qty'];
						$entry['avg_cost'] = $ri['avg_cost'];

						$returns[] = $entry;
					}
*/
				}
			}
		}

		return ($entries);//array('entries'=>$entries,'returns'=>$returns));
	}

	//=========================================================================================
	//==================================== FILTERS SECTION ====================================
	//=========================================================================================
	
	//Company Id is grabbed from the search field at the top, but only if one has been passed in
	$companyid = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) { 
		$companyid = $_REQUEST['companyid']; 
	}

	$debits = false;
	if (isset($_REQUEST['debits'])) { $debits = $_REQUEST['debits']; }
	$credits = false;
	if (isset($_REQUEST['credits'])) { $credits = $_REQUEST['credits']; }
	if (! $debits AND ! $credits) {
		$debits = true;
		$credits = true;
	}

	$order_search = '';
	if (isset($_REQUEST['order']) AND $_REQUEST['order']){
		$order_search = $_REQUEST['order'];
		$credits = true;
		$debits = true;
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

	$dbStartDate = '';
	$dbEndDate = '';
	if ($startDate) {
		$dbStartDate = format_date($startDate, 'Y-m-d').' 00:00:00';
		$dbEndDate = format_date($endDate, 'Y-m-d').' 23:59:59';
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
		.description {
			max-width:240px;
			overflow:hidden;
			white-space:nowrap;
			text-overflow:ellipsis;
		}
		.sticky-header thead tr {
			background-color:#fafafa;
			border-bottom:1px solid #eee;
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
	$buckets = array('debits'=>$debits,'credits'=>$credits);

	$cost_basis = 'average';//can toggle between average and fifo
/*
	if (isset($_REQUEST['cost_basis'])) {
		if ($_REQUEST['cost_basis']=='fifo') { $cost_basis = 'fifo'; }
		else if ($_REQUEST['cost_basis']=='qb') { $cost_basis = 'qb'; }
	}
*/
?>
		    <div class="btn-group">
		        <button class="btn btn-sm left btn-bucket<?php if ($buckets['debits']) { echo ' btn-success active'; } ?>" type="submit" data-value="debits" data-toggle="tooltip" data-placement="bottom" title="debits">
		        	<i class="fa fa-shopping-cart"></i>	
		        </button>
				<input type="radio" name="debits" id="debits" value="1" class="hidden"<?php if ($buckets['debits']) { echo ' checked'; } ?>>
				<input type="radio" name="credits" id="credits" value="1" class="hidden"<?php if ($buckets['credits']) { echo ' checked'; } ?>>
		        <button class="btn btn-sm right btn-bucket<?php if ($buckets['credits']) { echo ' btn-danger active'; } ?>" type="submit" data-value="credits" data-toggle="tooltip" data-placement="bottom" title="credits">
		        	<i class="fa fa-inbox"></i>	
		        </button>
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
			<div class="input-group">
                <input class="form-control" type="text" name="order" value="<?php echo trim($order_search); ?>" placeholder="Order #" />
            	<span class="input-group-btn">
					<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
                </span>
            </div><!-- /input-group -->
<!--
			<input type="text" name="part" class="form-control input-sm" value ='<?php echo $part?>' placeholder = 'Part/HECI' disabled />
-->
		</td>
		<td class="col-md-3">
			<div class="pull-right form-group">
			<select name="companyid" id="companyid" class="company-selector">
					<option value="">- Select a Company -</option>
				<?php 
				if ($companyid) {echo '<option value="'.$companyid.'" selected>'.(getCompany($companyid)).'</option>'.chr(10);} 
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
	
	
    <div id="pad-wrapper">

<?php
	//Establish a blank array for receiving the results from the table
	$results = array();

	$rows = '';
	$total_pcs = 0;
	$total_amt = 0;

	$results = array();
	$entries = array();//invoice / sale / qb entries
	$credit_results = array();//keep track to avoid duplicates
	$returns = array();//combined credits

/*
	$records = getSalesRecords();
	$entries = $records['entries'];
	$returns = $records['returns'];
*/
	$entries = getSalesRecords();
	$returns = getReturns();
/*
	foreach ($ret as $ri) {
		$entry['order_number'] = $ri['order_number'];
		$entry['order_type'] = $ri['order_type'];
		$entry['ref'] = $ri['ref'];
		$entry['qty'] = $ri['qty'];
		$entry['avg_cost'] = $ri['avg_cost'];

		$returns[] = $entry;
	}
*/

	//append to returns results
	$credit_results = getCredits();
	foreach ($credit_results as $c) {
		$returns[] = $c;
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
   	if ($dbStartDate) {
   		$query .= "AND je.date between CAST('".$dbStartDate."' AS DATE) and CAST('".$dbEndDate."' AS DATE) ";
	}
	$query .= "ORDER BY je.date ASC, je.id ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$r['descr'] = trim($r['part_number'].' '.$r['clei']);

		if ($r['credit_acct']=='Inventory Sale COGS') {
			$r['order_type'] = 'Credit';
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
				$r['order_type'] = 'Credit';
				$returns[] = $r2;
			} else {
				$entries[] = $r2;
			}
		}
	}
*/



	foreach ($entries as $r) {
		$key = $r['date'].'.A'.$r['order_number'].'.'.$r['partid'].'.'.$r['ref'].'.'.$r['invoice_item_id'];
		$order_ln = '';
		if ($r['order_number']) { $order_ln = '<a href="/'.strtoupper(substr($r['order_type'],0,1)).'O'.$r['order_number'].'" target="_new"><i class="fa fa-arrow-right"></i></a>'; }

		$ref = '';
		if ($r['ref']) {
			$ref = 'Invoice '.$r['ref'].' <a href="invoice.php?invoice='.$r['ref'].'" target="_new"><i class="fa fa-file-pdf-o"></i></a>';
		}
		if (! isset($results[$key])) {
			$results[$key] = array(
				'order_type'=>$r['order_type'],
				'qty'=>0,
				'cogs'=>0,
				'sale_amount'=>0,
				'status'=>$r['status'],
				'pushed'=>1,
				'push_success'=>1,
				'order'=>$r['order_number'],
				'order_ln'=>$order_ln,
				'ref'=>$ref,
				'descr'=>$r['descr'],
				'price'=>$r['price'],
				'companyid'=>$r['companyid'],
				'company'=>$r['name'],
				'actual_cost'=>$r['actual_cost'],
				'avg_cost'=>$r['avg_cost'],
				'exchange_cogs'=>0,
				'date'=>$r['date'],
				'po_number'=>$r['po_number'],
			);
		}

		$results[$key]['class'] = 'Billable';
		//dl 8-31-17 see change above for qtys from inventory
		//$results[$key]['qty']++;
		$results[$key]['qty'] += $r['qty'];
		if ($cost_basis=='average') {
			$results[$key]['cogs'] += $r['avg_cost'];
		} else {
			$results[$key]['cogs'] += $r['actual_cost'];
		}
		$results[$key]['sale_amount'] += $r['price'];
	}

	foreach ($returns as $r) {
		$key = $r['date'].'.B'.$r['item_id'].'.'.$r['order_number'].'.'.$r['ref'];
		$order_ln = '';
		if ($r['order_number']) { $order_ln = '<a href="/RMA'.$r['order_number'].'" target="_new"><i class="fa fa-arrow-right"></i></a>'; }
		$exchange_cogs = 0;

		$ref = '';
		if ($r['ref']) {
			if ($r['order_type']=='Credit') {
				$ref = 'CM '.$r['ref'].' <a href="/docs/CM'.$r['ref'].'.pdf" target="_new"><i class="fa fa-file-pdf-o"></i></a>';
			} else {
				$ref = 'Repair '.$r['ref'].' <a href="/RO'.$r['ref'].'" target="_new"><i class="fa fa-arrow-right"></i></a>';
			}
		} else if ($r['order_type']=='Exchange' AND isset($r['exchange_cogs'])) {
			$exchange_cogs = $r['exchange_cogs'];
		}
		if (! isset($results[$key])) {
			$results[$key] = array(
				'order_type'=>$r['order_type'],
				'qty'=>0,
				'cogs'=>0,
				'sale_amount'=>0,
				'status'=>$r['status'],
				'pushed'=>1,
				'push_success'=>1,
				'order'=>$r['order_number'],
				'order_ln'=>$order_ln,
				'ref'=>$ref,
				'descr'=>$r['descr'],
				'price'=>$r['price'],
				'companyid'=>$r['companyid'],
				'company'=>$r['name'],
				'actual_cost'=>$r['actual_cost'],
				'avg_cost'=>$r['avg_cost'],
				'exchange_cogs'=>0,
				'date'=>$r['date'],
				'po_number'=>$r['po_number'],
			);
		}

		$results[$key]['class'] = 'Return';
		$results[$key]['qty']++;
		if ($cost_basis=='average') {
			$results[$key]['cogs'] += $r['avg_cost'];
		} else {
			$results[$key]['cogs'] += $r['actual_cost'];
		}
		$results[$key]['exchange_cogs'] += $exchange_cogs;
		if ($r['order_type']=='Credit') {
			$results[$key]['sale_amount'] += $r['price'];
		}
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

//		if ($r['order_type']=='Sale' OR ($r['order_type']=='Repair' AND $r['price']>0) OR $r['order_type']=='IT') {
		if ($r['class']=='Billable') {
			$type = '<span class="label label-success label-box">'.$r['order_type'].'</span>';
		} else {
			$type = '<span class="label label-danger label-box">'.$r['order_type'].'</span>';
		}

		$profit = '';
		$ext_debit = '';
		$ext_credit = '';
		$cogs_credit = '';
		$cogs_debit = '';
		$cls = '';
		if ($r['status']=='Void') {
			$cls = ' class="strikeout"';
		} else {
//			if ($r['order_type']=='Sale' OR $r['order_type']=='Repair' OR $r['order_type']=='IT') {
			if ($r['class']=='Billable' OR $r['order_type']=='Repair') {
				if (! $buckets['debits']) { continue; }

				if ($r['class']<>'Billable') {
					$ext_price = 0;
				}
				$ext_debit = format_price(round($ext_price,2),true,' ');
				$sum_ext_price += $ext_price;

				$profit = ($r['sale_amount']-$r['cogs']);
				$sum_profit += $profit;
				$sum_qty += $r['qty'];
				$cogs_debit = format_price(round($r['cogs'],2),true,' ');
				$sum_cogs_debits += $r['cogs'];
			} else {
				if (! $buckets['credits']) { continue; }

				$ext_credit = format_price(-round($ext_price,2),true,' ');
				$sum_credits += $ext_price;
				$ext_price = '';

				$sum_qty -= $r['qty'];
				$cogs_credit = format_price(-round($r['cogs'],2),true,' ');//.' <sup><i class="fa fa-plus"></i></sup>';
				$sum_cogs_credits -= $r['cogs'];
				$returned_cogs = true;
				$profit = -($r['sale_amount']-$r['cogs']);

				if ($r['exchange_cogs']) {
					$cogs_debit = format_price(round($r['exchange_cogs'],2),true,' ');//.' <sup><i class="fa fa-plus"></i></sup>';
					$sum_cogs_debits += $r['exchange_cogs'];
					$profit -= $r['exchange_cogs'];
					$sum_qty += $r['qty'];
				}

				$sum_profit += $profit;
			}

			if (! $r['pushed'] OR ! $r['push_success']) {
				$cls = ' class="grayout"';
				$sum_pending_cogs += $r['cogs'];
			}
		}

		$gross_profit = format_price(round($profit,2),true,' ');
		if ($profit<0) { $gross_profit = '<span class="text-danger">'.$gross_profit.'</span>'; }

		$rows .= '
                            <!-- row -->
                            <tr'.$cls.'>
                                <td class="col-md-1">
                                    '.$type.' '.format_date($r['date'],'M j, Y').'
                                </td>
                                <td class="col-md-2">
									<div class="description"><small>'.$r['descr'].'</small></div>
                                </td>
                                <td class="col-md-1 text-right">
                                    <span class="pull-left">'.$r['qty'].'</span>
                                    <small>'.format_price($r['price'],true,' ').'</small>
                                </td>
                                <td class="col-md-1 text-right primary">
                                    '.$ext_credit.'
                                </td>
                                <td class="col-md-1 text-right primary">
                                    '.$ext_debit.'
                                </td>
                                <td class="col-md-1">
                                    <strong>'.$r['order'].'</strong> '.$r['order_ln'].'
                                </td>
                                <td class="col-md-1">
									'.$r['ref'].'
                                </td>
                                <td class="col-md-1" style="white-space:nowrap">
									<small>'.$r['company'].' <a href="/profile.php?companyid='.$r['companyid'].'" target="_new"><i class="fa fa-building"></i></a></small>
                                </td>
                                <td class="col-md-1 text-right primary">
                                    '.$cogs_credit.'
                                </td>
                                <td class="col-md-1 text-right primary">
                                    '.$cogs_debit.'
                                </td>
                                <td class="col-md-1 text-right">
									'.$gross_profit.'
                                </td>
<!--
                                <td class="col-md-1 text-right">
                                    '.format_price(round($sum_profit,2),true,' ').'
                                </td>
-->
                            </tr>
		';
	}
?>


                <div class="row">
                    <table class="table table-hover table-striped table-condensed sticky-header">
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
									<div class="row">
										<div class="col-sm-6 text-center">
											Credit
										</div>
										<div class="col-sm-6 text-center">
											Debit
										</div>
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
									<div class="row">
										<div class="col-sm-6 text-center">
											Credit
										</div>
										<div class="col-sm-6 text-center">
											Debit
										</div>
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
                </div><!-- row -->
            </div><!-- pad-wrapper -->

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
			$(".sticky-header").floatThead({
				top:94,
				zIndex:1001,
			});
			$('.btn-bucket').click(function() {
				var btnChk = ! $("#"+$(this).data('value')).attr('checked');
				$("#"+$(this).data('value')).attr('checked',btnChk);
			});
        });
    </script>

</body>
</html>
