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
		<style>
			.left-sidebar{
				/*position: absolute;*/
				left: 0;
				bottom: 0;
			}
			
			.arrow:after, .arrow:before {
				left: 100%;
				top: 50%;
				border: solid transparent;
				content: " ";
				height: 0;
				width: 0;
				position: absolute;
				pointer-events: none;
			}
			
			.arrow:after {
				border-color: rgba(239, 239, 239, 0);
				border-left-color: #efefef;
				border-width: 30px;
				margin-top: -30px;
			}
			.arrow:before {
				border-color: rgba(0, 0, 0, 0);
				/*border-left-color: #;*/
				border-width: 36px;
				margin-top: -36px;
			}
			
			.arrow {
				display: block;
			    position: absolute;
			    top: 50%;
			    right: 0;
				cursor: pointer;
			}
			
			.arrow i {
				left: 4px;
			    position: absolute;
			    z-index: 9;
			    top: -5px;
			}
			
			@media only screen and (max-width: 767px) {
				.company_meta:after, .company_meta:before {
					top: 100%;
					left: 50%;
					border: solid transparent;
					content: " ";
					height: 0;
					width: 0;
					position: absolute;
					pointer-events: none;
				}
				
				.company_meta:after {
					border-color: rgba(239, 239, 239, 0);
					border-top-color: #efefef;
					border-width: 30px;
					margin-left: -30px;
				}
				.company_meta:before {
					border-color: rgba(0, 0, 0, 0);
					/*border-top-color: #;*/
					border-width: 36px;
					margin-left: -36px;
				}
			}
		</style>
	</head>
	
	<body class="sub-nav">
	<!----------------------- Begin the header output  ----------------------->
		<?php include 'inc/navbar.php';?>
		
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
			(function($){
				$('.click_me').click(function() {
					var $marginLefty = $('.left-sidebar');
					var check = parseInt($marginLefty.css('marginLeft'),10) == 0 ? 'collapsed' : 'not-collapsed';
					$marginLefty.animate({
						marginLeft: (check == 'collapsed') ? -$marginLefty.outerWidth() : 0
						},{
						complete: function() {
							if(check == 'collapsed') {
								//$('.company_meta .row').hide();
							}
						}
					});
					
					if(check == 'collapsed') {
						//$('.shipping-list').animate({width: '90%'}, 550);
						$('.icon-button').addClass('fa-chevron-right');
						$('.icon-button').removeClass('fa-chevron-left');
						//$('.company_meta .row').hide();
					} 
					
					if(check != 'collapsed') {
						//$('.shipping-list').animate({width: '83.33333333333334%'}, 300);
						$('.icon-button').addClass('fa-chevron-left');
						$('.icon-button').removeClass('fa-chevron-right');
						//$('.company_meta .row').show();
					}
				});
				
					$('.shoot_me').click(function() {
						$('.left-sidebar .sidebar-container').slideToggle(function(){
							if($('.left-sidebar .sidebar-container').is(':visible')){
					            $('.icon-button-mobile').addClass('fa-chevron-up');
								$('.icon-button-mobile').removeClass('fa-chevron-down');
					        }else{
					            $('.icon-button-mobile').addClass('fa-chevron-down');
								$('.icon-button-mobile').removeClass('fa-chevron-up');
					        }
						});
					});
			})(jQuery);
		</script>

	</body>
</html>