<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	
//==============================================================================
//============================ Function Delcaration ============================
//==============================================================================
	
	//Output Module acts as the general output for each of the dashboard sections.
	//	INPUTS: Order(p,s);  Status(Active,Complete)
	function output_module($order,$status){
		
		$status_out = ($status =="Active") ? 'Outstanding ' : "Completed ";
		$order_out = ($order =="p") ? "Purchase" : "Sales";

		echo"
			<div class='col-lg-6 pad-wrapper' style='margin: 25px 0;'>
			<div class='shipping-dash'>
				<div class='shipping_section_head' data-title='Sales Orders'>";
		echo $status_out.$order_out.' Orders';
		echo	'</div>
				<div class="table-responsive">
		            <table class="table heighthover heightstriped table-condensed">';
		            output_header($status);
		echo	'<tbody>';
            		output_rows($order,$status);
		echo '	</tbody>
		            </table>
		    	</div>
		    	<div class="col-md-12 text-center shipping_section_foot more" style="padding-bottom: 15px;">
	            	<a href="#">Show more</a>
	            </div>
            </div>
        </div>';
	}
	
	function output_header($status){
			echo'<thead>';
			echo'<tr>';
			echo'	<th class="col-md-1">';
			echo'		Date';
			echo'	</th>';
			echo'	<th class="col-md-4">';
			echo'	<span class="line"></span>';
			echo'		Company';
			echo'	</th>';
            echo'	<th class="col-md-1">';
            echo'		<span class="line"></span>';
            echo'		Order#';
            echo'	</th>';
        if($status=="Active"){
            echo'   <th class="col-md-5">';
        }
        else {
        	echo'   <th class="col-md-3">';
        }
            echo'   	<span class="line"></span>';
            echo'       Items';
            echo'	</th>';
            echo'   <th class="col-md-1">';
            echo'   	<span class="line"></span>';
            echo'   	Qty';
            echo'  	</th>';
		if($status=="Complete"){
            echo'  	<th class="col-md-1">';
            echo'   	Pending';
            echo'  	</th>';
            echo'  	<th class="col-md-1">';
            echo'  		Status';
            echo'  	</th>';
            echo'</tr>';
            echo'</thead>';
		}	
	}
	
	//Inputs expected:
	//	- Status: Completed, Active
	//	- Order: s, p
	function output_rows($order, $status){
		
		//Select a joint summary query of the order we are requesting
		$query = "SELECT * FROM ";
		if ($order == 'p'){
			$query .= "purchase_orders o, purchase_items i ";
			$query .= "WHERE o.po_number = i.po_number ";
		}
		else{
			$query .= "sales_orders o, sales_items i ";
			$query .= "WHERE o.so_number = i.so_number ";
		}
		$query .= "AND status = '$status' ";
		$query .= "LIMIT 0 , 100;";
		
		$results = qdb($query);
	
		//display only the first N rows, but output all of them
		$count = 1;
		
		//Loop through the results.
		foreach ($results as $r){
			$count++;
			if ($order == 's'){
				$purchaseOrder = $r['so_number'];
			}
			else{
				$purchaseOrder = $r['po_number'];
			}
			$date = date("m/d/Y", strtotime($r['created']));
			$company = getCompany($r['companyid']);
			$item = getPart($r['partid']);
			$qty = $r['qty'];
		
		
			if($count<10){
				echo'	<tr>';
			}
			else{
				echo'	<tr style="display:none;">';
			}
				echo'        <td>'.$date.'</td>';
				echo'        <td><a href="#">'.$company.'</a></td>';
				echo'        <td><a href="/order_form.php?on='.$purchaseOrder.'&ps='.$order.'">'.$purchaseOrder.'</a></td>';
				echo'        <td>'.$item.'</td>';
				echo'    	<td>'.$qty.'</td>';
			if($status=="Complete"){
				echo'        <td class="pending">'.$pending.'</td>';
				echo'    	<td class="status">'.$count.'</td>';
			}
			echo'	</tr>';
		}
		
		// //If there are less than ten rows, fill with blanks
		while ($count < 10){
			echo'	<tr class = "empty_row">';
				echo'        <td>&nbsp;</td>';
				echo'        <td>&nbsp;</td>';
				echo'        <td>&nbsp;</td>';
				echo'        <td>&nbsp;</td>';
				echo'    	<td>&nbsp;</td>';
			if($status=="Active" || $status=="Active"){
				echo'        <td class="pending">&nbsp;</td>';
				echo'    	<td class="status">&nbsp;</td>';
			}
			echo'	</tr>';
		 echo'	</tr>';
		 $count++;
		}
	}
?>

<!----------------------------------------------------------------------------->
<!------------------------------- HEADER OUTPUT ------------------------------->
<!----------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with  home set as title -->
<head>
	<title>VMM Shipping Home</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<link rel="stylesheet" href="../css/operations-overrides.css" type="text/css" />
	<style>
		body {
			overflow-x: hidden;
			margin-top: 10px;
		}
		
		/*.select2-container {*/
		/*    width: 90% !important;*/
		/*}*/
		
		.date-options {
			height: 30px;
			overflow: hidden;
		}
		
		.date-options button {
			display: inline;
		}
		
		.date-options {
		    height: 30px;
		    overflow: hidden;
		    position: absolute;
		    /*width: 250px;*/
		    z-index: 1;
		    background: #ddd;
		}
		
		.select2-container--default .select2-selection--single {
		    border: 1px solid #ccc;
		    border-radius: 4px 0px 0px 4px;
		    height: 30px;
		}
		
		@media screen and (max-width: 991px){
			.date-options {
				position: relative;
			}
		}
		
	</style>
</head>

<body class="sub-nav accounts-body">
	
<!----------------------------------------------------------------------------->
<!------------------------- Output the navigation bar ------------------------->
<!----------------------------------------------------------------------------->

	<?php include 'inc/navbar.php'; ?>
	
	<div class="table-header" style="width: 100%; min-height: 60px; display: none;">
		<div class="row" style="padding: 15px">
			<div class = "col-md-2">
		
			    <div class="btn-group">
			        <button class="glow left large btn-report <?php if ($report_type=='summary') { echo ' active'; } ?>" type="submit" data-value="summary">
			        	<i class="fa fa-sort-numeric-desc"></i>	
			        </button>
					<input type="radio" name="report_type" value="summary" class="hidden"<?php if ($report_type=='summary') { echo ' checked'; } ?>>
			        <button class="glow right large btn-report<?php if ($report_type=='detail') { echo ' active'; } ?>" type="submit" data-value="detail">
			        	<i class="fa fa-history"></i>	
			        </button>
			        <input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>
			    </div>
				<div class="btn-group">
			        <button class="glow left large btn-report" type="submit" data-value="Sales" id = "sales">
			        	Sales	
			        </button>
					<input type="radio" name="market_table" value="Sales" class="hidden"<?php if ($market_table=='Sales') { echo ' checked'; } ?>>
			        <button class="glow right large btn-report<?php if ($market_table=='Purchases') { echo ' active'; } ?>" id="purchases" type="submit" data-value="Purchases">
			        	Purchases
			        </button>
			        <input type="radio" name="market_table" value="Purchases" class="hidden"<?php if ($market_table=='Purchases') { echo ' checked'; } ?>>
			    </div>
			</div>
			<div class = "col-md-1">
				<div class="input-group date">
		            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>" style = "min-width:50px;"/>
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		        </div>
			</div>
			<div class = "col-md-1">
				<div class="input-group date">
			            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>" style = "min-width:50px;"/>
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
			    </div>
			</div>
			<div class = "col-md-1 btn-group cal-buttons" data-toggle="buttons" id="shortDateRanges">
				<div class="date-options">
					<div class="btn btn-default btn-sm toggle-cal-options" data-name="show">&gt;</div>
			        <button class="btn btn-sm btn-default left large btn-report" id = "MTD" type="radio">MTD</button>
	    			<button class="btn btn-sm btn-default center small btn-report" id = "Q1" type="radio">Q1</button>
					<button class="btn btn-sm btn-default center small btn-report" id = "Q2" type="radio">Q2</button>
					<button class="btn btn-sm btn-default center small btn-report" id = "Q3" type="radio">Q3</button>		
					<button class="btn btn-sm btn-default center small btn-report" id = "Q4" type="radio">Q4</button>	
					<button class="btn btn-sm btn-default right small btn-report" id = "YTD" type="radio">YTD</button>
				</div>
			</div>
			<div class = "col-md-2">
				<input type="text" name="part" class="form-control input-sm" value ='<?php echo $part?>' placeholder = 'Part/HECI'/>
			</div>
			<div class = "col-md-2">
				<div class="input-group">
					<input type="text" name="min" class="form-control input-sm" value ='<?php if($min_price > 0){echo format_price($min_price);}?>' placeholder = 'Min $'/>
					<span class="input-group-addon">-</span>
					<input type="text" name="max" class="form-control input-sm" value ='<?php echo format_price($max_price);?>' placeholder = 'Max $'/>
				</div>
			</div>
			<div class = "col-md-3">
				<!--<div class="pull-right form-inline">-->
					<div class="input-group" style="width: 100%">
						<select name="companyid" id="companyid" class="company-selector">
						<option value="">- Select a Company -</option>
						<?php 
						if ($company_filter) {echo '<option value="'.$company_filter.'" selected>'.(getCompany($company_filter)).'</option>'.chr(10);} 
						else {echo '<option value="">- Select a Company -</option>'.chr(10);} 
						?>
						</select>
			            <span class="input-group-btn">
							<button class="btn btn-primary btn-sm" type="submit" >
								<i class="fa fa-filter" aria-hidden="true"></i>
							</button>
						</span>
					</div>
				<!--</div>-->
			</div>
		</div>
	</div>
	
	<table class="" style="display:none;">
		<tr id = "filterTableOutput">
			<td class = "col-md-2">
	
			    <div class="btn-group">
			        <button class="glow left large btn-report <?php if ($report_type=='summary') { echo ' active'; } ?>" type="submit" data-value="summary">
			        	<i class="fa fa-sort-numeric-desc"></i>	
			        </button>
					<input type="radio" name="report_type" value="summary" class="hidden"<?php if ($report_type=='summary') { echo ' checked'; } ?>>
			        <button class="glow right large btn-report<?php if ($report_type=='detail') { echo ' active'; } ?>" type="submit" data-value="detail">
			        	<i class="fa fa-history"></i>	
			        </button>
			        <input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>
			    </div>
				<div class="btn-group">
			        <button class="glow left large btn-report" type="submit" data-value="Sales" id = "sales">
			        	Sales	
			        </button>
					<input type="radio" name="market_table" value="Sales" class="hidden"<?php if ($market_table=='Sales') { echo ' checked'; } ?>>
			        <button class="glow right large btn-report<?php if ($market_table=='Purchases') { echo ' active'; } ?>" id="purchases" type="submit" data-value="Purchases">
			        	Purchases
			        </button>
			        <input type="radio" name="market_table" value="Purchases" class="hidden"<?php if ($market_table=='Purchases') { echo ' checked'; } ?>>
			    </div>
			</td>
			<td class = "col-md-1">
				<div class="input-group date datetime-picker-filter">
		            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>" style = "min-width:50px;"/>
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		        </div>
			</td>
			<td class = "col-md-1">
				<div class="input-group date datetime-picker-filter">
			            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>" style = "min-width:50px;"/>
			            <span class="input-group-addon">
			                <span class="fa fa-calendar"></span>
			            </span>
			    </div>
			</td>
			<td class = "col-md-1 btn-group" data-toggle="buttons" id="shortDateRanges">
				<div class="date-options">
					<div class="btn btn-default btn-sm">&gt;</div>
			        <button class="btn btn-sm btn-default left large btn-report" id = "MTD" type="radio">MTD</button>
	    			<button class="btn btn-sm btn-default center small btn-report" id = "Q1" type="radio">Q1</button>
					<button class="btn btn-sm btn-default center small btn-report" id = "Q2" type="radio">Q2</button>
					<button class="btn btn-sm btn-default center small btn-report" id = "Q3" type="radio">Q3</button>		
					<button class="btn btn-sm btn-default center small btn-report" id = "Q4" type="radio">Q4</button>	
					<button class="btn btn-sm btn-default right small btn-report" id = "YTD" type="radio">YTD</button>
				</div>
			</td>
			<td class = "col-md-2">
				<input type="text" name="part" class="form-control input-sm" value ='<?php echo $part?>' placeholder = 'Part/HECI'/>
			</td>
			<td class = "col-md-2">
				<div class="input-group">
					<input type="text" name="min" class="form-control input-sm" value ='<?php if($min_price > 0){echo format_price($min_price);}?>' placeholder = 'Min $'/>
					<span class="input-group-addon">-</span>
					<input type="text" name="max" class="form-control input-sm" value ='<?php echo format_price($max_price);?>' placeholder = 'Max $'/>
				</div>
			</td>
			<td class = "col-md-3">
				<div class="pull-right form-inline">
					<div class="input-group">
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
			</td>
		</tr>
	</table>
	<div class="row head text-center" id = "view-head" style="display:none;">
    	<div class="col-md-12">
        	<h2 id="view-head-text"></h2>
        </div>
    </div>
	<div class="row">
		<?php 
			output_module("p","Active");
			output_module("s","Active");
		?>
    </div>
	<div class="row">
		<?php 
			output_module("p","Complete");
			output_module("s","Complete");
		?>
    </div>    



<?php include_once 'inc/footer.php'; ?>
<script src="js/operations.js"></script>


</body>
</html>
