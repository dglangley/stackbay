<?php

//=============================================================================
//======================== Order Form General Template ========================
//=============================================================================
//  This is the general output form for the sales and purchase order forms.   |
//	This will be designed to cover all general use cases for shipping forms,  |
//  so generality will be crucial. Each of the sections is to be modularized  |
//	for the sake of general accessiblilty and practicality.					  |
//																			  |
//	Aaron Morefield - October 18th, 2016									  |
//=============================================================================

	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	//include_once $rootdir.'/inc/order-creation.php';

?>

<!DOCTYPE html>
<html>
	<head>
		<title>Shipping</title>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css" type="text/css" />
	</head>
	
	<body class="sub-nav">
	<!----------------------- Begin the header output  ----------------------->
		<?php include 'inc/navbar.php';?>
		
		<div class="col-md-2 company_meta" style="padding-top: 20px">
			<div class="row">
				<div class="col-md-12" style="padding-bottom: 10px;">						
					<div class ='company'>
						<label for="companyid">Company:</label>
						<select name='companyid' id='companyid' class='company-selector' style = "width:100%;">
							<option>Company</option>
						</select>
					</div>
				</div>
			</div>
			
			<div class="row">
				<div class="col-md-12" style="padding-bottom: 10px;">	            	
					<label for="address">Address:</label>
					<input class="form-control" type="text" name="address" placeholder="Street"/>
			    </div>
			    <div class="col-md-6" style="padding-bottom: 10px;">	            	
					<label for="city">City:</label>
					<input class="form-control" type="text" name="city" placeholder="City"/>
			    </div>
			    <div class="col-md-6" style="padding-bottom: 10px;">	            	
					<label for="zip">Zip:</label>
					<input class="form-control" type="text" name="zip" placeholder="Zip"/>
			    </div>
		    </div>
		    
		    <div class="row">
				<div class="col-md-12" style="padding-bottom: 10px;">
					<label for="ni_date">Ship by:</label>	            	
					<div class='input-group date datetime-picker-line'>
						<input type='text' name='ni_date' class='form-control input-sm' value='' placeholder="1/20/2016" style = 'min-width:50px;'/>
						<span class='input-group-addon'>
							<span class='fa fa-calendar'></span>
						</span>
			    	</div>
			    </div>
		    </div>
		    
		    <div class="row">
				<div class="col-md-12" style="padding-bottom: 10px;">	            	
					<label for="freight">Freight:</label>
					<select class="form-control">
						<option>USPS</option>
						<option>UPS</option>
						<option>Fedex</option>
					</select>
			    </div>
		    </div>
		    
		    <div class="row">
				<div class="col-md-12" style="padding-bottom: 10px;">	            	
					<label for="tracking">Tracking Info:</label>
					<input class="form-control" type="text" name="tracking" placeholder="Tracking #"/>
			    </div>
		    </div>
		    
		    <div class="row">
				<div class="col-md-12" style="padding-bottom: 10px;">	            	
					<label for="warranty">Warranty:</label>
					<input class="form-control" type="text" name="zip" placeholder="Warranty"/>
			    </div>
		    </div>
		</div>
		
		<div class="col-md-10" style="padding-top: 20px">
			<button class="btn btn-success pull-right" style="margin-top: -5px;"><i class="fa fa-plus" aria-hidden="true"></i></button>
			<h3>Items Shipped</h3>
			<!--<hr style="margin-top : 10px;">-->
		
			<table class="table table-hover table-striped table-condensed table-responsive" style="margin-top: 15px;">
				<thead>
					<tr>
						<th>Item</th>
						<th>Serial</th>
						<th>Qty</th>
						<th>Location</th>
						<th>Box #</th>
						<th>Condition</th>
					</tr>
				</thead>
				<tr>
					<td>
						<strong>ERB 3</strong>
					</td>
					<td>
						1L080B50230
					</td>
					<td>
						5
					</td>
					<td>
						Warehouse A1
					</td>
					<td>
						2
					</td>
					<td>
						Refurbished
					</td>
				</tr>
				<tr>
					<td>
						<strong>Aaron Bot</strong>
					</td>
					<td>
						1L080B50232
					</td>
					<td>
						5
					</td>
					<td>
						Warehouse Rancho
					</td>
					<td>
						1
					</td>
					<td>
						Young
					</td>
				</tr>
				<tr>
					<td>
						<strong>David Bot</strong>
					</td>
					<td>
						1L080B50332
					</td>
					<td>
						5
					</td>
					<td>
						Warehouse Rancho
					</td>
					<td>
						1
					</td>
					<td>
						Old
					</td>
				</tr>
			</table>
		</div>
		
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js"></script>
		<script type="text/javascript">

		</script>

	</body>
</html>