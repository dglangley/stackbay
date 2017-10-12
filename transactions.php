<?php

	$rootdir = $_SERVER['ROOT_DIR'];
	
	require_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/order_type.php';

	/***** POST DATA *****/
	$sorter = '';
	if (isset($_REQUEST['sorter'])) { $sorter = $_REQUEST['sorter']; }
	$confirmed_invoices = '';
	if (isset($_REQUEST['invoices_checkbox'])) { $confirmed_invoices = $_REQUEST['invoices_checkbox']; }
	$confirmed_bills = '';
	if (isset($_REQUEST['bills_checkbox'])) { $confirmed_bills = $_REQUEST['bills_checkbox']; }
	$confirmed_credits = '';
	if (isset($_REQUEST['credits_checkbox'])) { $confirmed_credits = $_REQUEST['credits_checkbox']; }

	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }

	if($confirmed_invoices) {
		foreach($confirmed_invoices as $invoice) {
			insertQBLog($invoice, 'Invoice');
		}
	}

	if($confirmed_bills) {
		foreach($confirmed_bills as $bill) {
			insertQBLog($bill, 'Bill');
		}
	}

	if ($confirmed_credits) {
		foreach ($confirmed_credits as $credit) {
			insertQBLog($credit, 'Credit');
		}
	}
	/***** END POST DATA *****/

	function insertQBLog($order_number, $order_type) {
		global $U;
		$order_number = prep($order_number);
		$order_type = prep($order_type);

		//Current current date and prep it
		$date_completed = date('Y-m-d H:i:s');
		$date_completed = prep($date_completed);

		//Get userid
		$userid = prep($U['id']);

		$query = "SELECT * FROM qb_log WHERE order_number = $order_number; ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)==0) {
			$query = "INSERT INTO `qb_log` (order_number, order_type, date_completed, userid";
			$query .= ") VALUES (";
			$query .= " $order_number,";
			$query .= " $order_type,";
			$query .= " $date_completed,";
			$query .= " $userid";
			$query .= ");";

			$result = qdb($query) or die(qe().": $query");
		}
	}
	
	function getTransactionInfo($number){
		$number = prep($number);
		$select = "Select * , SUM(amount) as total, 'Invoice ' as `display` FROM `invoices`, `invoice_items` WHERE invoices.invoice_no = $number AND `invoices`.`invoice_no` = `invoice_items`.invoice_no;";
		$result = qdb($select) or die(qe().": $select");
		$result = mysqli_fetch_assoc($result);
		$result['type'] = 'Invoices';
		return $result;
	}

	function transaction_date($date,$days){
		//$date = format_date($date);
	    $date = date('Y-m-d\\TH:i:s\\Z', strtotime("+".$days." days", strtotime($date)));
	    return format_date($date);
	}
	
	function get_invoiced_company_id($invoice_no){
			$select = "Select companyid FROM `invoices` WHERE invoice_no = ".prep($invoice).";";
			$result = qdb($select) or die(qe()." | ".$select);
			$result = mysqli_fetch_assoc($result);
			return $result['companyid'];
	}

	//Grab any submitted value
	if (isset($_POST['id'])){
		$ids = implode(",",$_POST['id']);
		$update = "UPDATE `journal_entries` SET `confirmed_datetime` = '".$now."', `confirmed_by` = '".$U['id']."' WHERE `id` IN ($ids)";
		qedb($update);
	}

	$startDate = '';
 	if ($_REQUEST['START_DATE']){
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}
	$endDate = format_date($today, 'm/d/Y');
 	if ($_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}
	if ($startDate) {
		$dbStartDate = format_date($startDate, 'Y-m-d').' 00:00:00';
		$dbEndDate = format_date($endDate, 'Y-m-d').' 23:59:59';
	}
		
	$company = 'NO COMPANY SELECTED THIS IS PROBABLY NOT AN ORDER';
	$order_type = '';
	$select = "SELECT * FROM `journal_entries` ";
	if ($startDate) {
		$select .= "WHERE datetime BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
	}
	$select .= " ORDER BY `journal_entries`.`id` DESC LIMIT 0,300 ";
	$select .= ";";
	$je_results = qdb($select);
	$je_open = 0;

	$journal_entries = '';
	if(mysqli_num_rows($je_results) > 0){
	    foreach($je_results as $row){

			$cls = $row['confirmed_datetime']? 'complete' : 'pending';
			$tooltip = '';
			if (! $row['confirmed_datetime']) {
				$je_open++;
				if (($row['amount']=='0.00' OR ! $row['amount']) AND $row['debit_account']=='Inventory Sale COGS') {
					$cls .= ' bg-danger';
					$tooltip = 'data-toggle="tooltip" data-placement="bottom" title="Please verify 0.00 COGS is correct before continuing!"';
				}
			}
            $company_id = get_invoiced_company_id($row['invoice_no']);
            $journal_entries .= '
			<tr class="'.$cls.'">
                <td>'.format_date($row['datetime']) .'</td>
                <td>'.$row['id'].'</td>
                <td>'.$row['debit_account'].'</td>
                <td>'.$row['credit_account'].'</td>
				<td>'.$row['memo'].'</td>
				<td> </td>
				<td> </td>
                <td class="text-right">'.format_price($row['amount']).'</td>
				<td class="text-center">
					<input type="checkbox" name=id[] value="'.$row['id'].'" '.($row['confirmed_datetime']? 'checked disabled' : '' ).' '.$tooltip.'>
				</td>
            </tr>
            ';
            //, SUM(price) as 
	    }
    } else {
        $journal_entries = '
            <tr>
                <td colspan = "9" class="text-center">
                    - No Journal Entries -
                </td>
            </tr>
        ';
    }

    //Invoices population

    $select = "SELECT * FROM `invoices` WHERE (order_type = 'Sale' OR order_type = 'Repair') ";
	if ($companyid) { $select .= "AND companyid = '".res($companyid)."' "; }
	if ($startDate) {
		$select .= "AND date_invoiced BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
	}
	$select .= "ORDER BY `invoices`.`invoice_no` DESC LIMIT 0,300; ";
	$invoices_results = qdb($select);
	$invoice_info = array();
	$invoices_open = 0;

	$invoices = '';
	if(mysqli_num_rows($invoices_results) > 0){
	    foreach($invoices_results as $row){
	    	//Grab Order Information
	    	if($row['order_type'] == 'Sale') {
				$query = "SELECT * FROM sales_orders WHERE so_number = ".prep($row['order_number'])."; ";
				$result = qdb($query) OR die(qe().' '.$query);
				if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$invoice_info = $r;
				}
	    	} else if($row['order_type'] == 'Repair') {
				$query = "SELECT * FROM repair_orders WHERE ro_number = ".prep($row['order_number'])."; ";
				$result = qdb($query) OR die(qe().' '.$query);
				if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$invoice_info = $r;
				}
			} else {
				// $query = "SELECT * FROM returns WHERE so_number = ".prep($row['order_number'])."; ";
				// $result = qdb($query) OR die(qe().' '.$query);
				// if (mysqli_num_rows($result)>0) {
				// 	$r = mysqli_fetch_assoc($result);
				// 	$invoice_info = $r;
				// }
			}

			$address = getAddresses($invoice_info['bill_to_id']);

			$term = '';
			$amount = 0.00;
			$completed = '';

			$query = "SELECT terms FROM terms WHERE id = ".prep($invoice_info['termsid']).";";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$term = $r['terms'];
			}

			$query = "SELECT SUM(amount * qty) as amount FROM invoice_items WHERE invoice_no = ".prep($row['invoice_no']).";";
			$result = qdb($query) OR die(qe().' '.$query);
			while ($r = mysqli_fetch_assoc($result)) {
				$amount += $r['amount'];
			}

			$query = "SELECT date_completed FROM qb_log WHERE order_number = ".prep($row['invoice_no'])." AND order_type = 'Invoice';";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$completed = $r['date_completed'];
				$edit_ln = '';
			} else {
				$invoices_open++;
				$edit_ln = '<a href="invoice.php?invoice='.$row['invoice_no'].'"><i class="fa fa-pencil"></i></a>';
			}

			$T = order_type($row['order_type']);

	    	$invoices .= '
	            <tr class = "'.($completed? 'complete' : 'pending' ).'">
	            	<td>
						'.$row['invoice_no'].' <a href="docs/INV'.$row['invoice_no'].'.pdf" target="_new"><i class="fa fa-file-pdf-o"></i>
					</td>
                    <td>'.getCompany($row['companyid']).'</td>
                    <td>'.$address['street'].'</td>
                    <td>'.format_date($row['date_invoiced']).'</td>
                    <td>'.$T['abbrev'].' '.$row['order_number'].' <a href="/'.$T['abbrev'].$row['order_number'].'"><i class="fa fa-arrow-right"></i></a></td>
                    <td>'.$term.'</td>
                    <td>'.format_date($row['date_invoiced'],'D n/j/y').'</td>
                    <td class="text-right">'.format_price($amount).'</td>
                    <td class="text-center">
						<input type="checkbox" name="invoices_checkbox[]" value="'.$row['invoice_no'].'" '.($completed ? 'disabled checked' : '').'> '.format_date($completed,'n/d/y').'
						'.$edit_ln.'
					</td>
	            </tr>
            ';
	    }
	} else {
	    $invoices = "
	            <tr>
	                <td colspan = '9' class='text-center'>
	                    - No Invoices -
	                </td>
	            </tr>
	    ";
	}

	//Bills population
	$bills_open = 0;
    $select = "SELECT * FROM `bills` WHERE 1 = 1 ";
	if ($companyid) { $select .= "AND companyid = '".res($companyid)."' "; }
	if ($startDate) {
		$select .= "AND date_created BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
	}
	$select .= "ORDER BY `bills`.`bill_no` DESC LIMIT 0,300; ";
	$bills_results = qdb($select);
	if(mysqli_num_rows($bills_results) > 0){
	    foreach($bills_results as $row){
	    	//Grab Order Information
			$query = "SELECT * FROM purchase_orders WHERE po_number = ".prep($row['po_number'])."; ";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$invoice_info = $r;
			}

			$address = getAddresses($invoice_info['remit_to_id']);

			$term = '';
			$amount = 0.00;
			$completed = '';

			$query = "SELECT terms FROM terms WHERE id = ".prep($invoice_info['termsid']).";";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$term = $r['terms'];
			}

			$query = "SELECT SUM(price * qty) as amount FROM purchase_items WHERE po_number = ".prep($row['po_number']).";";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$amount = $r['amount'];
			}

			$query = "SELECT date_completed FROM qb_log WHERE order_number = ".prep($row['bill_no'])." AND order_type = 'Bill';";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$completed = $r['date_completed'];
			} else {
				$bills_open++;
			}

	    	$bills .= "
	            <tr class = '".($completed? "complete" : "pending" )."'>
	            	<td><a href='bill.php?bill=".$row['bill_no']."'>".$row['bill_no']."</td>
                    <td>".getCompany($row['companyid'])."</td>
                    <td>".$address['street']."</td>
                    <td>".format_date($row['date_created'])."</td>
                    <td>".$row['invoice_no']."</td>
                    <td><a href='/PO".$row['po_number']."'>".$row['po_number']."</td>
                    <td>".$term."</td>
                    <td>".format_date($row['date_due'],'D n/j/y')."</td>
                    <td class='text-right'>".format_price($amount)."</td>
                    <td class='text-center'><input type='checkbox' name='bills_checkbox[]' value='".$row['bill_no']."' ".($completed ? 'disabled checked' : '')."> ".format_date($completed,'n/d/y')."</td>
	            </tr>
            ";
	    }
	} else {
	    $bills = "
	            <tr>
	                <td colspan = '9' class='text-center'>
	                    - No Bills -
	                </td>
	            </tr>
	    ";
	}

	$credits_rows = '';
	$credits_open = 0;
	$query = "SELECT * FROM credits sc ";
	if ($sorter=='company') { $query .= ", companies c "; }
	$query .= "WHERE 1 = 1 ";
	if ($sorter=='company') { $query .= "AND c.id = sc.companyid "; }
	if ($companyid) { $select .= "AND companyid = '".res($companyid)."' "; }
	if ($startDate) {
		$query .= "AND date_created BETWEEN CAST('".$dbStartDate."' AS DATETIME) AND CAST('".$dbEndDate."' AS DATETIME) ";
	}
	if ($sorter=='company') {
		$query .= "ORDER BY c.name ASC; ";
	} else {
		$query .= "ORDER BY sc.id DESC; ";
	}
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==0) {
	    $credits_rows = '
	            <tr>
	                <td colspan = "10" class="text-center">
	                    - No Credits -
	                </td>
	            </tr>
	    ';
	}
	while ($r = mysqli_fetch_assoc($result)) {
		if ($r['order_type']=='Sale') {
			$order_table = 'sales_orders';
			$order_field = 'so_number';
		} else if ($r['order_type']=='Repair') {
			$order_table = 'repair_orders';
			$order_field = 'ro_number';
		}

		$terms = '';
//		$address = '';
		$query2 = "SELECT created, bill_to_id, terms FROM ".$order_table." o, terms t ";
		$query2 .= "WHERE ".$order_field." = '".$r['order_number']."' AND o.termsid = t.id; ";
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);

//			$address = address_out($r2['bill_to_id'],false,', ');
			$terms = $r2['terms'];
			$order_date = $r2['created'];
		}

		$invoice = '';
		$invoice_ln = '';
		$invoice_date = '';
		$amount = 0;
		$query2 = "SELECT qty, amount, return_item_id FROM credit_items WHERE cid = '".$r['id']."'; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$amount += $r2['qty']*$r2['amount'];

			$query3 = "SELECT i.invoice_no, i.date_invoiced ";
			$query3 .= "FROM return_items ri, package_contents pc, invoice_shipments s, invoice_items ii, invoices i ";
			$query3 .= "WHERE ri.id = '".$r2['return_item_id']."' AND ri.inventoryid = pc.serialid AND pc.packageid = s.packageid ";
			$query3 .= "AND s.invoice_item_id = ii.id AND ii.invoice_no = i.invoice_no; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			if (mysqli_num_rows($result3)>0) {
				$r3 = mysqli_fetch_assoc($result3);
				$invoice = $r3['invoice_no'];
				$invoice_ln = $invoice.' <a href="/docs/INV'.$invoice.'.pdf" target="_new"><i class="fa fa-file-pdf-o"></i></a>';
				$invoice_date = format_date($r3['date_invoiced'],'n/d/y');
			}
		}

		$completed = '';
		$query2 = "SELECT date_completed FROM qb_log WHERE order_number = '".$r['id']."' AND order_type = 'Credit'; ";
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$completed = format_date($r2['date_completed'],'n/d/y');
			$cls = 'complete';
			$chk_status = ' disabled checked';
		} else {
			$credits_open++;
			$cls = 'pending';
			$chk_status = '';
		}

		$credits_rows .= '
				<tr class="'.$cls.'">
					<td>'.$r['id'].' <a href="/docs/CM'.$r['id'].'.pdf" target="_new"><i class="fa fa-file-pdf-o"></i></a></td>
					<td>'.getCompany($r['companyid']).'</td>
					<td>'.format_date($r['date_created'],'n/d/y').'</td>
					<td>'.substr($r['order_type'],0,1).'O '.$r['order_number'].' <a href="/'.substr($r['order_type'],0,1).'O'.$r['order_number'].'" target="_new"><i class="fa fa-arrow-right"></i></a></td>
					<td>'.format_date($order_date,'n/d/y').'</td>
					<td>'.$terms.'</td>
					<td>'.$r['rma_number'].'</td>
					<td>'.$invoice_ln.'</td>
					<td>'.$invoice_date.'</td>
					<td class="text-right">'.format_price($amount).'</td>
					<td class="text-center">
						<input type="checkbox" name="credits_checkbox[]" value="'.$r['id'].'"'.$chk_status.'> '.$completed.'</td>
					</td>
				</tr>
		';
	}
	
    $order_types = getEnumValue("journal_entries","trans_type");
    
    //Build the type dropdown
    $type_dropdown = "<select class = 'form-control input-sm' name ='order_type' disabled>";
    $type_dropdown .= "<option value = ''>Order Type</option>";
    foreach($order_types as $select){
        $type_dropdown .= "<option".(($select == $order_type)?" selected":"").">";
        $type_dropdown .= $select;
        $type_dropdown .= "</option>";
    }
    $type_dropdown .= "</select>";
    
?>
<!DOCTYPE html>
<html>
<head>
	<title>Transactions</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<style type="text/css">
		.sorter {
			cursor:pointer;
		}
		.sorter:after {
			content: '\f0dc';/*f15d';*/
			font-family: FontAwesome;
			font-weight: normal;
			font-style: normal;
			margin:0px 0px 0px 30px;
			text-decoration:none;
		}
	</style>
</head>
<body>
<!----------------------------------------------------------------------------->
<!------------------------- Output the navigation bar ------------------------->
<!----------------------------------------------------------------------------->

	<?php include 'inc/navbar.php'; ?>

<?php
	$tab = 'journal-entries';
	if (isset($_REQUEST['tab']) AND ($_REQUEST['tab']=='invoices' OR $_REQUEST['tab']=='bills' OR $_REQUEST['tab']=='credits')) {
		$tab = $_REQUEST['tab'];
	}
?>

    <form class="form-inline" method="get" action="/transactions.php">
	<input type="hidden" name="tab" value="<?php echo $tab; ?>" id="tab">

<!----------------------------------------------------------------------------->
<!-------------------------- Header/Filter Bar/Title -------------------------->
<!----------------------------------------------------------------------------->
	<table class="table table-header table-filter">
		<tr>
			<td class="col-md-2">
				<div class="btn-group">
					<button class="btn btn-default filter_status btn-sm left" type="button" id="complete-toggle" data-toggle="tooltip" data-placement="bottom" title="Complete" data-filter="complete"><i class="fa fa-check-square" data-filter="complete"></i></button>
					<button class="btn btn-default filter_status btn-sm middle" type="button" id="pending-toggle" data-toggle="tooltip" data-placement="bottom" title="Open/Incomplete" data-filter="active"><i class="fa fa-folder-open"></i></button>
					<button class="btn btn-info btn-sm filter_status right active" type="button" id="all-toggle" data-toggle="tooltip" data-placement="bottom" title="All" data-filter="all"><i class="fa fa-square"></i></button>
<!--
			        <button class="glow left large btn-radio"  type="button" id = "complete-toggle" data-toggle="tooltip" data-placement="bottom"  title="" data-original-title="Completed">
			        	<i class="fa fa-check-circle"></i>	
			        </button>
			        <button class="glow large btn-radio" type="button" id = "all-toggle" data-toggle="tooltip" data-placement="bottom"  title="" data-original-title="All">
			        	<i class="fa fa-globe"></i>	
			        </button>
			        <button class="glow right large btn-radio active" type="button" id = "pending-toggle" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Incomplete">
			        	<i class="fa fa-times-circle"></i>	
		        	</button>
-->
			</div>
		</td>




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
							<div class="animated fadeIn hidden" id="date-ranges" style = 'width:217px;'>
						        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="<?php echo date("m/01/Y"); ?>" data-end="<?php echo date("m/d/Y"); ?>">MTD</button>
				    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("01/01/Y"); ?>" data-end="<?php echo date("03/31/Y"); ?>">Q1</button>
								<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("04/01/Y"); ?>" data-end="<?php echo date("06/30/Y"); ?>">Q2</button>
								<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("07/01/Y"); ?>" data-end="<?php echo date("09/30/Y"); ?>">Q3</button>
								<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="<?php echo date("10/01/Y"); ?>" data-end="<?php echo date("12/31/Y"); ?>">Q4</button>
							</div><!-- animated fadeIn -->
						</div><!-- btn-range-options -->
					</div><!-- btn-group -->
				</div><!-- form-group -->
			</td>

			<!-- TITLE -->
			<td class="col-md-2 text-center">
            	<h2 class="minimal">Transactions</h2>
			</td>
			
			<!--This Handles the Search Bar-->
			
			<!--Condition Drop down Handler-->
			
			<td class="col-md-2">
				<div class="input-group">
					<input type="text" name="min" class="form-control input-sm" value ='<?php if($min_price > 0){echo format_price($min_price);}?>' placeholder = 'Min $'/>
					<span class="input-group-addon">-</span>
					<input type="text" name="max" class="form-control input-sm" value ='<?php echo format_price($max_price);?>' placeholder = 'Max $'/>
				</div>
			</td>			
			<td class="col-md-1"><input type="text" class="form-control input-sm" id="order_number_filter" placeholder="Order Number"></td>
			<td class="col-md-2">
				<div class="pull-right form-group">
					<select name='companyid' id='companyid' class='form-control input-xs company-selector' >
						<option value=''>- Select a Company -</option>
<?php if ($companyid) { echo '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'; } ?>
					</select>
					<button class="btn btn-primary btn-sm" type="submit" >
                        <i class="fa fa-filter" aria-hidden="true"></i>
                    </button>
				</div>
			</td>
		</tr>
	</table>

	</form>
<!---------------------------------------------------------------------------->
<!------------------------------ Alerts Section ------------------------------>
<!---------------------------------------------------------------------------->

	<div id="item-updated" class="alert alert-success fade in text-center" style="display: none;">
	    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
	    <strong>Success!</strong> Changes have been updated. Refresh required to re-organize data
	</div>
	
<!----------------------------------------------------------------------------->
<!---------------------------------- Body Out --------------------------------->
<!----------------------------------------------------------------------------->

<div id="pad-wrapper">

			<!-- Nav tabs -->
			<ul class="nav nav-tabs nav-tabs-ar">
				<li<?php if ($tab=='journal-entries') { echo ' class="active"'; } ?>><a href="#journal-entries" class="tab-toggle" data-toggle="tab"><i class="fa fa-square"></i>
					Journal Entries<?=($je_open>0 ? ' ('.$je_open.')' : '');?></a></li>
				<li<?php if ($tab=='invoices') { echo ' class="active"'; } ?>><a href="#invoices" class="tab-toggle" data-toggle="tab"><i class="fa fa-file-text"></i>
					Invoices<?=($invoices_open>0 ? ' ('.$invoices_open.')' : '');?></a></li>
				<li<?php if ($tab=='bills') { echo ' class="active"'; } ?>><a href="#bills" class="tab-toggle" data-toggle="tab"><i class="fa fa-file-text-o"></i>
					Bills<?=($bills_open>0 ? ' ('.$bills_open.')' : '');?></a></li>
				<li<?php if ($tab=='credits') { echo ' class="active"'; } ?>><a href="#credits" class="tab-toggle" data-toggle="tab"><i class="fa fa-inbox"></i>
					Credits<?=($credits_open>0 ? ' ('.$credits_open.')' : '');?></a></li>
				<li class="pull-right" style="padding:3px 3px 3px 0px">
					<button class="btn btn-success btn-sm" id="master-save" type="button">Save</button>
				</li>
			</ul>
 
			<!-- Tab panes -->
			<div class="tab-content">

				<!-- Journal Entries pane -->
				<div class="tab-pane<?php if ($tab=='journal-entries') { echo ' active'; } ?>" id="journal-entries">
					<form class="form-inline" action='/transactions.php' method='POST'>
					<input type="hidden" name="tab" value="<?php echo $tab; ?>" class="tab-hidden">
					<input type="hidden" name="START_DATE" value="<?php echo $startDate; ?>">
					<input type="hidden" name="END_DATE" value="<?php echo $endDate; ?>">
					<input type="hidden" name="companyid" value="<?php echo $companyid; ?>">
					<div class='table-responsive'>
						<table class='table table-hover table-striped table-condensed'>
							<tr>
								<th class = 'col-sm-1'>Date</th>
								<th class = 'col-sm-1'>Entry No.</th>
								<th class = 'col-sm-2'>Debit Account</th>
								<th class = 'col-sm-2'>Credit Account</th>
								<th class = 'col-sm-2'>Memo</th>
								<th class = 'col-sm-1 info'>Name</th>
								<th class = 'col-sm-1 info'>Billable?</th>
								<th class = 'col-sm-1 text-center'>Amount</th>
								<th class = 'col-sm-1 text-center'>Confirm</th>
							</tr>
							<?=$journal_entries?>
<?php if ($je_open) { ?>
							<tr>
								<td colspan="9" class="text-right">
									<button class="btn btn-success btn-sm" id="btn-journal-entries" type="submit">Save</button>
								</td>
							</tr>
<?php } ?>
						</table>
					</div>
					</form>
				</div><!-- journal-entries -->

				<!-- Invoices pane -->
				<div class="tab-pane<?php if ($tab=='invoices') { echo ' active'; } ?>" id="invoices">
					<form class="form-inline" action='/transactions.php' method='POST'>
					<input type="hidden" name="tab" value="<?php echo $tab; ?>" class="tab-hidden">
					<input type="hidden" name="START_DATE" value="<?php echo $startDate; ?>">
					<input type="hidden" name="END_DATE" value="<?php echo $endDate; ?>">
					<input type="hidden" name="companyid" value="<?php echo $companyid; ?>">
					<div class='table-responsive'>
						<table class='table table-hover table-striped table-condensed'>
							<tr>
								<th class = 'col-sm-1'>Invoice #</th>
								<th class = 'col-sm-2'>Customer</th>
								<th class = 'col-sm-2'>Address</th>
								<th class = 'col-sm-1'>Date</th>
								<th class = 'col-sm-1'>Order#</th>
								<th class = 'col-sm-1'>Terms</th>
								<th class = 'col-sm-1'>Bill Due</th>
								<th class = 'col-sm-1 text-right'>Amount</th>
								<th class = 'text-center'>Confirm</th>
							</tr>
							<?=$invoices?>
<?php if ($invoices_open) { ?>
							<tr>
								<td colspan="9" class="text-right">
									<button class="btn btn-success btn-sm" id="btn-invoices" type="submit">Save</button>
								</td>
							</tr>
<?php } ?>
						</table>
					</div>
					</form>
				</div><!-- invoices -->

				<!-- Bills pane -->
				<div class="tab-pane<?php if ($tab=='bills') { echo ' active'; } ?>" id="bills">
					<form class="form-inline" action='/transactions.php' method='POST'>
					<input type="hidden" name="tab" value="<?php echo $tab; ?>" class="tab-hidden">
					<input type="hidden" name="START_DATE" value="<?php echo $startDate; ?>">
					<input type="hidden" name="END_DATE" value="<?php echo $endDate; ?>">
					<input type="hidden" name="companyid" value="<?php echo $companyid; ?>">
					<div class='table-responsive'>
						<table class='table table-hover table-striped table-condensed'>
							<tr>
								<th class = 'col-sm-1'>Bill #</th>
								<th class = 'col-sm-2'>Vendor</th>
								<th class = 'col-sm-2'>Address</th>
								<th class = 'col-sm-1'>Date Created</th>
								<th class = 'col-sm-1'>Ref No</th>
								<th class = 'col-sm-1'>PO No</th>
								<th class = 'col-sm-1'>Terms</th>
								<th class = 'col-sm-1'>Date Due</th>
								<th class = 'col-sm-1 text-right'>Amount</th>
								<th class = 'col-sm-1 text-center'>Confirm</th>
							</tr>
							<?=$bills?>
<?php if ($bills_open) { ?>
							<tr>
								<td colspan="9" class="text-right">
									<button class="btn btn-success btn-sm" id="btn-bills" type="submit">Save</button>
								</td>
							</tr>
<?php } ?>
						</table>
					</div>
					</form>
				</div><!-- bills -->

				<!-- Credits pane -->
				<div class="tab-pane<?php if ($tab=='credits') { echo ' active'; } ?>" id="credits">
					<form class="form-inline" action='/transactions.php' method='POST'>
					<input type="hidden" name="tab" value="<?php echo $tab; ?>" class="tab-hidden">
					<input type="hidden" name="START_DATE" value="<?php echo $startDate; ?>">
					<input type="hidden" name="END_DATE" value="<?php echo $endDate; ?>">
					<input type="hidden" name="companyid" value="<?php echo $companyid; ?>">
					<input type="hidden" name="sorter" value="<?php echo $sorter; ?>">
					<div class='table-responsive'>
						<table class='table table-hover table-striped table-condensed'>
							<tr>
								<th class = 'col-sm-1 sorter' data-type="id">CM</th>
								<th class = 'col-sm-2 sorter' data-type="company">Customer</th>
								<th class = 'col-sm-1'>CM Date</th>
								<th class = 'col-sm-1'>Order No</th>
								<th class = 'col-sm-1'>Order Date</th>
								<th class = 'col-sm-1'>Terms</th>
								<th class = 'col-sm-1'>RMA No</th>
								<th class = 'col-sm-1'>Invoice No</th>
								<th class = 'col-sm-1'>Invoice Date</th>
								<th class = 'col-sm-1 text-right'>Amount</th>
								<th class = 'col-sm-1 text-center'>Confirm</th>
							</tr>
							<?=$credits_rows?>
<?php if ($credits_open) { ?>
							<tr>
								<td colspan="11" class="text-right">
									<button class="btn btn-success btn-sm" id="btn-credits" type="submit">Save</button>
								</td>
							</tr>
<?php } ?>
						</table>
					</div>
					</form>
				</div><!-- credits -->

			</div><!-- tab-content -->

</div>


<?php include_once 'inc/footer.php'; ?>
<script type="text/javascript">
    $(document).ready(function() {
		$("#complete-toggle").click(function(){
			$('.filter_status').removeClass('btn-warning');
			$('.filter_status').removeClass('btn-success');
			$('.filter_status').removeClass('btn-info');
			
			$('.filter_status').addClass('btn-default');
			$('.filter_status[data-filter="complete"]').addClass('btn-success');
			$(this).siblings().removeClass("active");
			$(".complete").show();
			$(".pending").hide();
			$(this).addClass("active");
		});
		$("#all-toggle").click(function(){
			$('.filter_status').removeClass('btn-warning');
			$('.filter_status').removeClass('btn-success');
			$('.filter_status').removeClass('btn-info');
			
			$('.filter_status').addClass('btn-default');
			$('.filter_status[data-filter="all"]').addClass('btn-info');
			$(this).siblings().removeClass("active");
			$(".complete").show();
			$(".pending").show();
			$(this).addClass("active");
		});
		$("#pending-toggle").click(function(){
			$('.filter_status').removeClass('btn-warning');
			$('.filter_status').removeClass('btn-success');
			$('.filter_status').removeClass('btn-info');
			
			$('.filter_status').addClass('btn-default');
			$('.filter_status[data-filter="active"]').addClass('btn-warning');
			$(this).siblings().removeClass("active");
			$(".complete").hide();
			$(".pending").show();
			$(this).addClass("active");
		});
		$(".tab-toggle").click(function() {
			var tab_id = $(this).attr('href').replace('#','');
			$("#tab").val(tab_id);
			$(".tab-hidden").each(function() {
				$(this).val(tab_id);
			});
			if (document.getElementById("btn-"+tab_id)) {
				$("#master-save").prop('disabled',false);
				$("#master-save").show();
			} else {
				$("#master-save").prop('disabled',true);
				$("#master-save").hide();
			}
		});
		$("#master-save").click(function() {
			var active_tab = $("#tab").val();
			if (! active_tab) { return; }
			var f = $("#"+active_tab).find("form");

			f.submit();
		});
		$(".sorter").click(function() {
			var sort_field = $(this).data("type");
			var form = $(this).closest("form");
			form.find("input[name='sorter']").val(sort_field);
			form.submit();
		});
	});
</script>

</body>
</html>


