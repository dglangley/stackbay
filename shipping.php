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
	
	$order_no = $_REQUEST['order_no'];
	
	//If no order is selected then return to shipping home
	if(empty($order_no)) {
		header("Location: /shipping_home.php");
		die();
	}
	
	$sales_order;
	
	//get the information based on the order number selected
	$query = "SELECT * FROM sales_orders WHERE so_number = ". res($order_no) .";";
	$result = qdb($query) OR die(qe());
	
	if (mysqli_num_rows($result)>0) {
		$result = mysqli_fetch_assoc($result);
		$sales_order = $result;
	}
	
	print_r($sales_order);
	
	function getItems($so_number) {
		$sales_items = array();
		
		$query = "SELECT * FROM sales_items WHERE so_number = ". res($so_number) .";";
		
		while ($row = $result->fetch_assoc()) {
			$sales_items[] = $row;
		}
		
		return $sales_items;
	}
	
	function getLocation($locationid = 0){
		$location;
		
		$query = "SELECT * FROM locations WHERE id = ". res($locationid) .";";
		$result = qdb($query) OR die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$location = $result[''];
		}
		
		return $location;
	}
	
	
	function getAddress($addressid = 0) {
		$address;
		
		$query = "SELECT * FROM addresses WHERE id = ". res($addressid) .";";
		$result = qdb($query) OR die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$address = $result[''];
		}
		
		return $address;
	}

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
		<?php include 'inc/navbar.php'; ?>
		<div class="loading_element">
			<div class="col-sm-2  company_meta left-sidebar">
				<div class="sidebar-container" style="padding-top: 20px">
					<div class="row">
						<div class="col-sm-12" style="padding-bottom: 10px;">						
							<div class ='company'>
								<label for="companyid">Company:</label>
								<select name='companyid' id='companyid' class='company-selector' style = "width:100%;">
									<option>Company</option>
								</select>
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="col-sm-12" style="padding-bottom: 10px;">	            	
							<label for="address">Address:</label>
							<input class="form-control" type="text" name="address" placeholder="Street"/>
					    </div>
					    <div class="col-sm-6" style="padding-bottom: 10px;">	            	
							<label for="city">City:</label>
							<input class="form-control" type="text" name="city" placeholder="City"/>
					    </div>
					    <div class="col-sm-6" style="padding-bottom: 10px;">	            	
							<label for="zip">Zip:</label>
							<input class="form-control" type="text" name="zip" placeholder="Zip"/>
					    </div>
				    </div>
				    
				    <div class="row">
						<div class="col-sm-12" style="padding-bottom: 10px;">
							<label for="ni_date">Ship on:</label>	            	
							<div class='input-group date datetime-picker-line'>
								<input type='text' name='ni_date' class='form-control input-sm' value='' placeholder="1/20/2016" style = 'min-width:50px;'/>
								<span class='input-group-addon'>
									<span class='fa fa-calendar'></span>
								</span>
					    	</div>
					    </div>
				    </div>
				    
				    <div class="row">
						<div class="col-sm-12" style="padding-bottom: 10px;">	            	
							<label for="freight">Freight:</label>
							<select class="form-control">
								<option>USPS</option>
								<option>UPS</option>
								<option>Fedex</option>
							</select>
					    </div>
				    </div>
				    
				    <div class="row">
						<div class="col-sm-12" style="padding-bottom: 10px;">	            	
							<label for="tracking">Tracking Info:</label>
							<input class="form-control" type="text" name="tracking" placeholder="Tracking #"/>
					    </div>
				    </div>
				    
				    <div class="row">
						<div class="col-sm-12" style="padding-bottom: 10px;">	            	
							<label for="warranty">Warranty:</label>
							<input class="form-control" type="text" name="zip" placeholder="Warranty"/>
					    </div>
				    </div>
			    </div>
			    <div class="arrow click_me">   
			    	<i class="icon-button fa fa-chevron-left" aria-hidden="true"></i>
	        	</div>
	        	
	        	<i class="fa fa-chevron-up shoot_me icon-button-mobile" aria-hidden="true" style="color: #000; position: absolute; bottom: -15px; left: 49%; z-index: 1;"></i>
			</div>
			
			<div class="col-sm-10 shipping-list" style="padding-top: 20px">
				<button class="btn btn-success pull-right" style="margin-top: -5px;">Update Order</button>
				<h3>Items to be Shipped</h3>
				<!--<hr style="margin-top : 10px;">-->
			
				<div class="table-responsive">
					<table class="table table-hover table-striped table-condensed" style="margin-top: 15px;">
						<thead>
							<tr>
								<th>Item</th>
								<th>Serial</th>
								<th>Qty</th>
								<th>Location</th>
								<th>Box #</th>
								<th>Condition</th>
								<th>Shipped</th>
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
							<td>
								<div class="checkbox">
									<label><input type="checkbox" value=""></label>
								</div>
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
							<td>
								<div class="checkbox">
									<label><input type="checkbox" value=""></label>
								</div>
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
							<td>
								<div class="checkbox">
									<label><input type="checkbox" value=""></label>
								</div>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js"></script>

	</body>
</html>