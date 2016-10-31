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
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css" type="text/css" />
	</head>
	
	<body class="sub-nav">
	<!----------------------- Begin the header output  ----------------------->
		<div class="container-fluid pad-wrapper">
		<?php include 'inc/navbar.php';?>
		<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;">
			<div class="col-md-4"></div>
			<div class="col-md-4 text-center">
				<h1>Inventory Addition</h1>
			</div>
			<div class="col-md-4">
				<button class="btn-flat pull-right" id = "save_button" style="margin-top:2%;margin-bottom:2%;">
					Complete
				</button>
			</div>
		</div>

			<!-------------------- $$ OUTPUT THE MACRO INFORMATION -------------------->
			<div class="row-fluid cmbar">
				<div class="company_meta col-md-2">
					<div class="row-fluid" style="width:100%; padding-top:10px;">
						Order Info
					</div>
					<div class="row-fluid" style="width:100%; padding-top:10px;">
							<div class="col-md-12">
								<div class='input-group'>
									<input class='form-control' type='text' placeholder = 'Order #' value=''>
									<span class='input-group-btn'>
								        <button class='btn btn-secondary' type='button'>
									        <i class='fa fa-paperclip' aria-hidden='true'></i>
								        </button>
					    			</span>
								</div>	
					
						</div>
					</div>
					<div class="row-fluid" style="width:100%; padding-top:10px;">
						<div class="col-md-12">						
								<div class = 'company'>
									<select name='companyid' id='companyid' class='company-selector' style = "width:100%;">
										<option>Company</option>
									</select>
								</div>
			
						</div>
					</div>
					<div class="row-fluid" style="width:100%; padding-top:10px;">
						<div class="col-md-12">	            	
								<div class='input-group date datetime-picker-line'>
									<input type='text' name='ni_date' class='form-control input-sm' value='<?=$TODAY?>' style = 'min-width:50px;'/>
									<span class='input-group-addon'>
										<span class='fa fa-calendar'></span>
									</span>
						    	</div>
				    </div>
   					</div>
				</div>
			<!---------------------- OUTPUT THE LINE ADDITION TABLE ---------------------->
				<div class="inventory_lines col-md-10">
					<table class="table table-hover table-striped table-condensed table-responsive" id="items_table" style="table-layout: fixed;margin-top:30px;">
							<thead>
						         <tr>
						            <th class="col-md-4">
						            	<span class="line"></span>		
						            	PART	
						            </th>
						            <th class="col-md-2">
						            	<span class="line"></span>		
						            	Serial	
						            </th>
						            <th class="col-md-2">   	
						            	<span class="line"></span>   	
						            	Qty
						            </th>
						            <th class="col-md-1">
										<span class="line"></span>   	
										Location	
									</th>
						        	<th class="col-md-1">
						            	Status
						        	</th>
						            <th class="col-md-1">
						            	<span class="line"></span>
										Condition
						        	</th>
						            <th class="col-md-1">
						            	<span class="line"></span>
						            	&nbsp;
						        	</th>
				
						         </tr>
						      </thead>
							<tbody id="">
							    <tr class = "addRecord">
						            <td id='search_collumn'>
						            	<div style="max-width:inherit;">
											<select class='item_search' style="max-width:inherit;overflow:hidden;">
												<option data-search = 'Nothing at the moment'>Item</option>
											</select>
										</div>
									</td>
									<td id='serial'>
							            <input class="form-control input-sm" type="text" name = "Newitem" placeholder="Serial">
									</td>
						            <td>
										<div class="input-group">
									    	<input type="text" class="form-control" aria-label="Text input with checkbox">
							            <span class="input-group-addon">Serialize Each?</span>
									      	<span class="input-group-addon">
									        	<input type="checkbox" aria-label="Checkbox for following text input">
											</span>
				
									    </div>
								    </td>
						            <td>
				                            <div class="ui-select" style="width:100%;">
				                                <select>
				                                    <option selected="">Warehouse 12</option>
				                                    <option>Warehouse 12</option>
				                                    <option>Warehouse 12</option>
				                                </select>
				                            </div>
				                    </td>
						            <td>
				                    	<button class="btn btn-success">
				                    		Rec
				                    	</button>     
				                    	<button class="btn btn-danger">
				                    		Ret 	
				                    	</button>

				                    </td>
						            <td>
							           	<button class="btn btn-success">
				                    		N
				                    	</button>     
				                    	<button class="btn btn-danger">
				                    		U
				                    	</button>
				                    	<button class="btn btn-primary">
				                    		R
				                    	</button>
				                    </td>
				                    <td>
				                    	<button class="btn-flat">
				                    		ADD RECORD
				                    	</button>
				                    </td>
							    </tr>
				
					        </tbody>
				
						   </table>
						
				</div>
			</div>
		</div>
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js"></script>
		<script type="text/javascript">

		</script>

	</body>
</html>