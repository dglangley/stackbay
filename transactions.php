<?php

	$rootdir = $_SERVER['ROOT_DIR'];
	
	require_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getTerms.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/locations.php';

	$completed_Invoice = $_REQUEST['invoice_checkbox'];
	$completed_Bills = $_REQUEST['bills_checkbox'];

	// if (isset($_REQUEST['s']) AND $_REQUEST['s']) {
	// 	$keyword = $_REQUEST['s'];
	// }

	if($completed_Invoice) {
		foreach($completed_Invoice as $invoice) {
			insertQBLog($invoice, 'Invoice');
		}
	}

	if($completed_Bills) {
		foreach($completed_Bills as $bill) {
			insertQBLog($bill, 'Bill');
		}
	}

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
		
	$company = 'NO COMPANY SELECTED THIS IS PROBABLY NOT AN ORDER';
	$order_type = '';
	$select = "
	SELECT * FROM `journal_entries` ";
	$select .= " ORDER BY `journal_entries`.`id` DESC LIMIT 0,300 ";
	$select .= ";";
	$je_results = qdb($select);
	
	$journal_entries = '';
	if(mysqli_num_rows($je_results) > 0){
	    foreach($je_results as $row){
            $company_id = get_invoiced_company_id($row['invoice_no']);
            $journal_entries .=
            "<tr class = '".($row['confirmed_datetime']? "complete" : "pending" )."'>
                <td>".format_date($row['datetime']) ."</td>
                <td>".$row['id']."</td>
                <td>".$row['debit_account']."</td>
                <td>".$row['credit_account']."</td>
				<td>".$row['memo']."</td>
				<td> </td>
				<td> </td>
                <td class='text-right'>".format_price($row['amount'])."</td>
				<td class='text-center'>"."<input type='checkbox' name = id[] value ='".$row['id']."' 
				".($row['confirmed_datetime']? "checked disabled" : "" )."></td>
            </tr>
            ";
            //, SUM(price) as 
	    }
	    $journal_entries .="
		    <tr>
		    	<td colspan='8'>
		    	</td>
		    	<td class='text-center' style='padding-top:40px'>
		    		<button class = 'btn btn-success btn-sm' type='submit'>Save</button>
		    	</td>
		    </tr>
	    ";
    } else {
        $journal_entries = "
            <tr>
                <td colspan = '9' class='text-center'>
                    - No Journal Entries -
                </td>
            </tr>
        ";
    }

    //Invoices population
 //    $select = "
	// SELECT i.order_number, i.companyid, i.date_invoiced, i.invoice_no, q.date_completed FROM `invoices` as i, `qb_log` as q WHERE i.order_type = 'Sale' AND i.invoice_no = q.order_number";
	// $select .= " ORDER BY i.invoice_no DESC LIMIT 0,50 ";
	// $select .= ";";

    $select = "
	SELECT * FROM `invoices` WHERE order_type = 'Sale'";
	$select .= " ORDER BY `invoices`.`invoice_no` DESC LIMIT 0,300 ";
	$select .= ";";
	$invoices_results = qdb($select);

	$invoice_info = array();

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

			$query = "SELECT SUM(price) * qty as amount FROM sales_items WHERE so_number = ".prep($row['order_number']).";";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$amount = $r['amount'];
			}

			$query = "SELECT date_completed FROM qb_log WHERE order_number = ".prep($row['invoice_no']).";";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$completed = $r['date_completed'];
			}

	    	$invoices .= "
	            <tr class = '".($completed? "complete" : "pending" )."'>
	            	<td><a href='docs/INV".$row['invoice_no'].".pdf'>".$row['invoice_no']."</td>
                    <td>".getCompany($row['companyid'])."</td>
                    <td>".$address['street']."</td>
                    <td>".format_date($row['date_invoiced'])."</td>
                    <td><a href='/SO".$row['order_number']."'>".$row['order_number']."</a></td>
                    <td>".$term."</td>
                    <td>".$row['notes']."</td>
                    <td>".summarize_date($row['date_invoiced'], '30')."</td>
                    <td class='text-right'>".format_price($amount)."</td>
                    <td class='text-center'><input type='checkbox' style='margin-right: 10px;' name='invoice_checkbox[]' value='".$row['invoice_no']."' ".($completed ? 'disabled checked' : '').">".format_date($completed)."</td>
	            </tr>
            ";
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
    $select = "
	SELECT * FROM `bills`";
	$select .= " ORDER BY `bills`.`bill_no` DESC LIMIT 0,300 ";
	$select .= ";";
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

			$query = "SELECT SUM(price) * qty as amount FROM purchase_items WHERE po_number = ".prep($row['po_number']).";";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$amount = $r['amount'];
			}

			$query = "SELECT date_completed FROM qb_log WHERE order_number = ".prep($row['bill_no']).";";
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$completed = $r['date_completed'];
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
                    <td>".$row['notes']."</td>
                    <td>".summarize_date($row['date_due'])."</td>
                    <td class='text-right'>".format_price($amount)."</td>
                    <td class='text-center'><input type='checkbox' style='margin-right: 10px;' name='bills_checkbox[]' value='".$row['bill_no']."' ".($completed ? 'disabled checked' : '').">".format_date($completed)."</td>
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
</head>
<body>
<!----------------------------------------------------------------------------->
<!------------------------- Output the navigation bar ------------------------->
<!----------------------------------------------------------------------------->

	<?php include 'inc/navbar.php'; ?>

    <form class="form-inline" method="get" action="/transactions.php">

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
						<option value=''>Select a Company</option>
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
				<li class="active"><a href="#journal-entries" data-toggle="tab"><i class="fa fa-square"></i> Journal Entries (<?=mysqli_num_rows($je_results)?>)</a></li>
<!--
				<li><a href="#customers" data-toggle="tab"><i class="fa fa-book"></i> Customers</a></li>
-->
				<li><a href="#invoices" data-toggle="tab"><i class="fa fa-file-text"></i> Invoices</a></li>
<!--
				<li><a href="#vendors" data-toggle="tab"><i class="fa fa-book"></i> Vendors</a></li>
-->
				<li><a href="#bills" data-toggle="tab"><i class="fa fa-file-text-o"></i> Bills</a></li>
			</ul>
 
			<!-- Tab panes -->
			<div class="tab-content">

				<!-- Journal Entries pane -->
				<div class="tab-pane active" id="journal-entries">
					<form class="form-inline" action='/transactions.php' method='POST'>
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
						</table>
					</div>
					</form>
				</div><!-- journal-entries -->

				<!-- Invoices pane -->
				<div class="tab-pane" id="invoices">
					<form class="form-inline" action='/transactions.php' method='POST'>
					<button class="btn-flat success pull-right btn-update" type='submit' style="margin-bottom: 15px;">Save</button>
					<div class='table-responsive'>
						<table class='table table-hover table-striped table-condensed'>
							<tr>
								<th class = 'col-sm-1'>Invoice #</th>
								<th class = 'col-sm-2'>Customer</th>
								<th class = 'col-sm-2'>Address</th>
								<th class = 'col-sm-1'>Date</th>
								<th class = 'col-sm-1'>SO No</th>
								<th class = 'col-sm-1'>Terms</th>
								<th class = 'col-sm-2'>Memo</th>
								<th class = 'col-sm-1'>Bill Due</th>
								<th class = 'col-sm-1 text-right'>Amount</th>
								<th class = 'text-center'>Confirm</th>
							</tr>
							<?=$invoices?>
						</table>
					</div>
					</form>
				</div><!-- invoices -->

				<!-- Bills pane -->
				<div class="tab-pane" id="bills">
					<form class="form-inline" action='/transactions.php' method='POST'>
					<button class="btn-flat success pull-right btn-update" type='submit' style="margin-bottom: 15px;">Save</button>
					<div class='table-responsive'>
						<table class='table table-hover table-striped table-condensed'>
							<tr>
								<th class = 'col-sm-1'>Bill #</th>
								<th class = 'col-sm-2'>Vendor</th>
								<th class = 'col-sm-2'>Address</th>
								<th class = 'col-sm-1'>Date Created</th>
								<th class = 'col-sm-1'>Ref No</th>
								<th class = 'col-sm-1'>PO NO</th>
								<th class = 'col-sm-1'>Terms</th>
								<th class = 'col-sm-2'>Memo</th>
								<th class = 'col-sm-1'>Date Due</th>
								<th class = 'col-sm-1 text-right'>Amount</th>
								<th class = 'col-sm-1 text-center'>Confirm</th>
							</tr>
							<?=$bills?>
						</table>
					</div>
					</form>
				</div><!-- bills -->

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
	});
</script>

</body>
</html>


