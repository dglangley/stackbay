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
	
	//The output rows funciton will be the part which echos within the sections on the 
	
	function output_module($section){
		
	}
	function output_header($section){
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
        if($section=="comp_po" || $section=="comp_so"){
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
		if($section=="out_po" || $section=="out_so"){
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
	function output_rows($section,$count){
		$date = "6/19/16";
		$company = "ICBS";
		$purchaseOrder = "19678";
		$items = "D90-311670 &nbsp; T1S1CKUAAA";
		$quantity = "2";
		$pending = "2";
		$status = "Pending";
		
		if($count<10){
			echo'	<tr>';
		}
		else{
			echo'	<tr class="overview" style="display:none;">';
		}
			echo'        <td>'.$date.'</td>';
			echo'        <td><a href="#">'.$company.'</a></td>';
			echo'        <td><a href="#">'.$purchaseOrder.'</a></td>';
			echo'        <td>'.$items.'</td>';
			echo'    	<td>'.$quantity.'</td>';
		if($section=="out_po" || $section=="out_so"){
			echo'        <td class="pending">'.$pending.'</td>';
			echo'    	<td class="status">'.$count.'</td>';
		}
			echo'	</tr>';
	}
?>

<!----------------------------------------------------------------------------->
<!------------------------------- HEADER OUTPUT ------------------------------->
<!----------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with Accounts home set as title -->
<head>
	<title>VMM Shipping Home</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
	<link rel="stylesheet" href="../css/operations-overrides.css" type="text/css" />

</head>

<body class="sub-nav accounts-body">
	
<!----------------------------------------------------------------------------->
<!------------------------- Output the navigation bar ------------------------->
<!----------------------------------------------------------------------------->

	<?php include 'inc/navbar.php'; ?>
	<table class="table table-header" style="display:none;">
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
	<div class="row head text-center" id = "view-head" style="display:none;padding-top:120px;">
    	<div class="col-md-12">
        	<h2 id="view-head-text"></h2>
        </div>
    </div>
	<div class="row">
		<div class="col-lg-6 pad-wrapper" style="margin: 25px 0;">
			<div class="shipping-dash">
				<div class="shipping_section_head" data-title="Sales Orders">
					Outstanding Sales Orders
				</div>
				<div class="table-responsive">
		            <table class="table heighthover heightstriped table-condensed">
						<?php
							output_header("out_so");
						?>
		                <tbody>
		                	<?php
								for($i=0;$i<20;$i++){
									output_rows("out_po",$i);
								}
		                	?>
		                </tbody>
		            </table>
		    	</div>
		    	<div class="col-md-12 text-center shipping_section_foot more" style="padding-bottom: 20px;">
	            	<a href="#">Show more</a>
	            </div>
            </div>
        </div>
		<div class="col-lg-6 pad-wrapper" style="margin: 25px 0;">
			<div class="shipping-dash">
				<div class="shipping_section_head" data-title="Purchase Orders">Outstanding Purchase Orders</div>
				<div class="table-responsive">
		            <table class="table table-hover table-striped table-condensed">
		                	<?php
								output_header("out_po");
							?>
		                <tbody>
		                	<?php
								for($i=0;$i<10;$i++){
									output_rows("out_po",$i);
								}
		                	?>
		                </tbody>
		            </table>
	            </div>
	            <div class="col-md-12 text-center shipping_section_foot more" style="padding-bottom: 20px;">
	            	<a href="#">Show more</a>
	            </div>
			</div>
        </div>
    </div>
    <div class="row">
		<div class="col-lg-6 pad-wrapper" style="margin: 0 0 25px 0;">
			<div class="shipping-dash">
				<div class="shipping_section_head" data-title="Sales Orders">
					Outstanding Sales Orders
				</div>
				<div class="table-responsive">
		            <table class="table heighthover heightstriped table-condensed">
						<?php
							output_header("out_so");
						?>
		                <tbody>
		                	<?php
								for($i=0;$i<20;$i++){
									output_rows("out_po",$i);
								}
		                	?>
		                </tbody>
		            </table>
	            </div>
	            <div class="col-md-12 text-center shipping_section_foot more" style="padding-bottom: 20px;">
	            	<a href="#">Show more</a>
	            </div>
            </div>
        </div>
		<div class="col-lg-6 pad-wrapper" style="margin: 0 0 25px 0;">
			<div class="shipping-dash">
				<div class="shipping_section_head" data-title="Purchase Orders">Outstanding Purchase Orders</div>
				<div class="table-responsive">
		            <table class="table table-hover table-striped table-condensed">
		                	<?php
								output_header("out_po");
							?>
		                <tbody>
		                	<?php
								for($i=0;$i<10;$i++){
									output_rows("out_po",$i);
								}
		                	?>
		                </tbody>
		            </table>
	            </div>
	            <div class="col-md-12 text-center shipping_section_foot more" style="padding-bottom: 20px;">
	            	<a href="#">Show more</a>
	            </div>
            </div>
        </div>
    </div>
    



<?php include_once 'inc/footer.php'; ?>
<script src="js/operations.js"></script>

</body>
</html>
