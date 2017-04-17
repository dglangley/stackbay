<?php

	$rootdir = $_SERVER['ROOT_DIR'];
	
	require_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/locations.php';
	
	function getTransactionInfo($number){
		$number = prep($number);
		$select = "Select * , SUM(price) as total, 'Invoice ' as `display` FROM `invoices`, `invoice_items` WHERE invoices.invoice_no = $number AND `invoices`.`invoice_no` = `invoice_items`.invoice_no;";
		$result = qdb($select) or die(qe().": $select");
		$result = mysqli_fetch_assoc($result);
		$result['type'] = 'Invoices';
		return $result;
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
	$journal_entries = qdb($select);
	
	$rows_string = '';
	if(mysqli_num_rows($journal_entries) > 0){
	    foreach($journal_entries as $row){
            $company_id = get_invoiced_company_id($row['invoice_no']);
            $rows_string .=
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
	    $rows_string .="
		    <tr>
		    	<td colspan='8'>
		    	</td>
		    	<td class='text-center' style='padding-top:40px'>
		    		<button class = 'btn btn-success btn-sm' type='submit'>Save</button>
		    	</td>
		    </tr>
	    ";
    } else {
        $rows_string = "
            <tr>
                <td colspan = '9' class='text-center'>
                    - No Journal Entries -
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
	<title>VMM Journal</title>
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

    <form class="form-inline" method="get" action="/journal.php">

<!----------------------------------------------------------------------------->
<!-------------------------- Header/Filter Bar/Title -------------------------->
<!----------------------------------------------------------------------------->
	<table class="table table-header table-filter">
		<tr>
			<td class="col-md-2">
				<div class="btn-group">
					<button class="btn btn-success btn-sm left" type="button" id="complete-toggle" data-toggle="tooltip" data-placement="bottom" title="Complete"><i class="fa fa-check-square"></i></button>
					<button class="btn btn-warning btn-sm middle" type="button" id="pending-toggle" data-toggle="tooltip" data-placement="bottom" title="Open/Incomplete"><i class="fa fa-folder-open"></i></button>
					<button class="btn btn-info btn-sm right active" type="button" id="all-toggle" data-toggle="tooltip" data-placement="bottom" title="All"><i class="fa fa-square"></i></button>
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
            	<h2 class="minimal">Journal Entries</h2>
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

	<form class="form-inline" action='/journal.php' method='POST'>
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
           	<?=$rows_string?>
		</table>
	</div>
	</form>

</div>


<?php include_once 'inc/footer.php'; ?>
<script type="text/javascript">
    $(document).ready(function() {
		$("#complete-toggle").click(function(){
			$(this).siblings().removeClass("active");
			$(".complete").show();
			$(".pending").hide();
			$(this).addClass("active");
		});
		$("#all-toggle").click(function(){
			$(this).siblings().removeClass("active");
			$(".complete").show();
			$(".pending").show();
			$(this).addClass("active");
		});
		$("#pending-toggle").click(function(){
			$(this).siblings().removeClass("active");
			$(".complete").hide();
			$(".pending").show();
			$(this).addClass("active");
		});
	});
</script>

</body>
</html>