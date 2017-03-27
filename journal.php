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
	
	function displayTransactionInfo($associated_trans, $type){
        $on = prep($associated_trans);
        $select = '';
	    switch(trim($type)){
	        case "Invoices":
	            $select = "Select companyid, 'Invoice ' as `display` FROM `invoices` WHERE invoice_no = $on;";
	            break;
            case "Bills":
                $select = "Select '1' as companyid, 'Bill ' as `display`;";
                break;
            case "Payments":
                $select = "Select '1' as companyid, 'Payments ' as `display`;";
                break;
            default:
                $select = "SELECT '1' as companyid, 'None' as `display`;";
	    }
	  
	    $result = qdb($select) OR die(qe().": Tell Aaron He Messed Up");
	    return mysqli_fetch_assoc($result);
	}
	//Grab any submitted value

	$company = 'NO COMPANY SELECTED THIS IS PROBABLY NOT AN ORDER';
	$order_type = '';
	$associated_trans = grab('associated_trans');
	if ($associated_trans){
		$meta_info = getTransactionInfo($associated_trans);
		$company = $meta_info['companyid'];
		$order_type = $meta_info['type'];
		$amount = $meta_info['total'];
	    $quickbook_id = grab('quickbook_id');
		if($quickbook_id){
	        $quickbook_id = prep(strtoupper($quickbook_id));
	        $trans_number = prep($associated_trans);
			$associated_trans = '';
	        // $amount = prep(grab('amount'));
	        $order_type = prep($order_type);
	        $insert = "INSERT INTO `journal_entries`(`qbid`, `trans_number`, `trans_type`) 
	        VALUES ($quickbook_id,$trans_number,$order_type);";
	        qdb($insert) or die(qe().": $insert");
		}
	}
	$select = "
	SELECT * FROM `journal_entries` ";
	//Room for eventual filters
	$select .= " ORDER BY `journal_entries`.`id` DESC ";
	$select .= ";";
	$journal_entries = qdb($select);
	
	$rows_string = '';
	if(mysqli_num_rows($journal_entries) > 0){
	    foreach($journal_entries as $row){
            $order = displayTransactionInfo($row['trans_number'],$row['trans_type']);
            $rows_string .=
            "<tr>
                <td>".format_date($row['date_recorded']) ."</td>
                <td>".$row['qbid']."</td>
                <td>".(($order['companyid'])? getCompany($order['companyid']) : "None")."</td>
                <td>".(($order['display'])? $order['display']." ".$row['trans_number'] : "None")."</td>
				
            </tr>
            ";
            //, SUM(price) as 
	    }
	    
    } else {
        $rows_string = "
            <tr>
                <td colspan = '5' class='text-center'>
                    Nothing Found
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


<html>
<!-- Declaration of the standard head with Accounts home set as title -->
<head>
	<title>VMM Journal</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
	<style>
		hr {
			margin-top: 0;
			margin-bottom: 10px;
		}
		
		tbody th {
			border-top-color: #edf2f7 !important;
		}
		
		.product-rows-edited .btn-primary {
		    /*color: #ffffff;*/
		    /*background-color: #5cb85c;*/
		    /*border-color: #4cae4c;*/
		}
		
		#item-updated, #item-failed {
			position: fixed;
		    width: 100%;
		    z-index: 1;
		}
		
		table.serial {
			width: 95%;
			margin: 0 auto;
		}
		
		.pointer {
			cursor: pointer;
		}
		
		.serial-page {
			display: none;
		}
		
		.page-1 {
			display: block;
		}
		
		.addRows label {
			display: none;
		}
		
		.edit {
			display: none;
		}
		
		.addRows .product-rows:first-child label {
			display: block;
		}
		
		.nopadding {
		   padding: 0 !important;
		   margin: 0 !important;
		}
		
		.table-head .input-group.datepicker-date {
			width: auto;
			min-width: auto;
			max-width: 100%;
		}
		
		@media screen and (max-width: 767px){
			.addRows label {
				display: block;
			}
		}
		#modalHistoryBody div:nth-child(even){
			background-color:#f7f7f7;
		}
	</style>

</head>

<body class="sub-nav">
	
<!----------------------------------------------------------------------------->
<!------------------------- Output the navigation bar ------------------------->
<!----------------------------------------------------------------------------->

	<?php include 'inc/navbar.php'; ?>
	
<!----------------------------------------------------------------------------->
<!-------------------------- Header/Filter Bar/Title -------------------------->
<!----------------------------------------------------------------------------->
	<div class="table-header" style="width: 100%; min-height: 48px;">
		<div class="row" style="padding: 8px;" id = "filterBar">

			<div class="col-md-2 col-sm-2">
				<div class="input-group">
					<input type="text" name="min" class="form-control input-sm" value ='<?php if($min_price > 0){echo format_price($min_price);}?>' placeholder = 'Min $'/>
					<span class="input-group-addon">-</span>
					<input type="text" name="max" class="form-control input-sm" value ='<?php echo format_price($max_price);?>' placeholder = 'Max $'/>
				</div>
			</div>
				
			<div class = "col-md-3">
				<div class="form-group col-md-4 nopadding">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
			            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
			        </div>
				</div>
				<div class="form-group col-md-4 nopadding">
					<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>">
			            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>">
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
				    </div>
				</div>
				<div class="form-group col-md-4 nopadding">
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
			</div>
			<div class="col-md-2 col-sm-2 text-center">
            	<h2 class="minimal">Journal Entries</h2>
			</div>
			
			<!--This Handles the Search Bar-->
			
			<!--Condition Drop down Handler-->
			<div class="col-md-1 col-sm-1">
			    <?=$type_dropdown?>
			</div>
			<div class="col-md-1 col-sm-1"><input type="text" class="form-control input-sm" id="order_number_filter" placeholder="Order Number"></div>
			
			<div class="col-md-1 col-sm-1"></div>
			<div class="col-md-2 col-sm-2">
				<div class="company input-group">
					<select name='companyid' id='companyid' class='form-control input-xs company-selector required' >
						<option value=''>Select a Company</option>
					</select>
					<span class="input-group-btn">
						<button class="btn btn-sm btn-primary part_filter"><i class="fa fa-filter"></i></button>   
					</span>
				</div>
			</div>
		</div>
	</div>
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


	<div class ='loading_element_listing'>
	    <form action='/journal.php' method='POST'>
	    <div class="col-md-12">
	        <div class='row' style ='margin:10px;'>
    	    	<div class='col-sm-2'></div>
		        <div class='col-sm-1'><?=$type_dropdown?></div>
		        <div class='col-sm-2'>
		        	<div class="input-group input-group-sm">
		        		<input class='form-control input-sm <?=(($associated_trans)?"":"auto-focus")?>' type="text" name="associated_trans" placeholder ="Transaction Number" value='<?=$associated_trans?>'/>
		        		<span class='input-group-btn'>
		        			<button class="btn <?=($associated_trans)?"":"btn-primary"?>">
		        				<i class='fa fa-search'></i>
	        				</button>
        				</span>
		        	</div>
		        </div>
				<div class='col-sm-2 text-center' id='Company' style='line-height:15px;'>
		        	<?=($associated_trans && $company)?$meta_info['display']." Company: <br><b>".getCompany($company)."</b>":''?>
            	</div>
                <div class='col-sm-2' id='quickbook'>	
        			<div class="input-group input-group-sm">
        				<input class='form-control input-sm <?=(($associated_trans)?"auto-focus":"")?>' style = 'text-transform:uppercase;' type="text" name="quickbook_id" placeholder ='QB ID' <?=(($associated_trans)?"":"readonly")?>/>
	        			<span class='input-group-btn'>
		        			<button class="btn <?=($associated_trans)?"btn-primary":'disabled'?>">
		        				Save
	        				</button>
        				</span>
		        	</div>
                </div>
                <div class='col-sm-1 text-center' id='amount' style='line-height:15px;'>
		        	<?=($associated_trans && $amount)?$meta_info['display']." Amount: <br><b>".format_price($amount)."</b>":''?>
            	</div>
            	<div class="col-sm-2"></div>
			</div>
	    	</form>
	    </div>

		<div class="col-md-12">
			<div class='table-responsive'>
				<table class='table table-hover table-striped table-condensed' style='margin-top: 15px;'>
					<thead class = 'headers'>
						<th class = 'col-sm-2'>Date Entered</th>
						<th class = 'col-sm-4'>Quickbooks ID</th>
						<th class = 'col-sm-3'>Company</th>
						<th class = 'col-sm-3'>Associated Order</th>
						<!--<th class = 'col-sm-2'>Amount</th>-->
					</thead>
					<tbody class='journal_records'>
                        <?=$rows_string?>
					</tbody>
				</table>
			</div>
		</div>
		
	</div>
<?php include_once 'inc/footer.php'; ?>
<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
</body>
</html>



