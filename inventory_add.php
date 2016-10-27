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
		<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color:
		;">
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
		<div class="spacer" style = "margin:120px; width:100%"></div>
	
	<!-------------------- $$ OUTPUT THE MACRO INFORMATION -------------------->
	<div class="row-fluid" style="width:100%;">
			<div class="col-md-4">
				<div class='input-group'>
					<input class='form-control' type='text' placeholder = 'Order #' value=''>
					<span class='input-group-btn'>
				        <button class='btn btn-secondary' type='button'>
					        <i class='fa fa-paperclip' aria-hidden='true'></i>
				        </button>
	    			</span>
				</div>	
			</div>
			<div class="col-md-4">						
				<div class = 'company'>
					<select name='companyid' id='companyid' class='company-selector' style = "width:100%;">
						<option>Company</option>
					</select>
				</div>
			</div>
			<div class="col-md-4">	            	
				<div class='input-group date datetime-picker-line'>
					<input type='text' name='ni_date' class='form-control input-sm' value='<?=$TODAY?>' style = 'min-width:50px;'/>
					<span class='input-group-addon'>
						<span class='fa fa-calendar'></span>
					</span>
		    	</div>
    		</div>

	</div>
	
<!---------------------- OUTPUT THE LINE ADDITION TABLE ---------------------->
		<table class="table table-hover table-striped table-condensed table-responsive" id="items_table" style="margin-top:30px;margin:2.5%">
			<thead>
		         <tr>
		            <th class="col-md-4" style="max-width:600px;">
		            	<span class="line"></span>		
		            	PART	
		            </th>
		            <th class="col-md-2" style="max-width:600px;">
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
			<tbody id="right_side_main">
			    <tr>
		            <td id='search_collumn'>
		            	<div>
							<select class='item_search' style="width:100%;">
								<option data-search = 'Nothing at the moment'>Item</option>
							</select>
						</div>
					</td>
					<td id='serial'>
			            <input class="form-control input-sm" type="text" name = "ni_price" placeholder="UNIT PRICE">
					</td>
		            <td>
						<div class="input-group">
					    	<input type="text" class="form-control" aria-label="Text input with checkbox">
					      	<span class="input-group-addon">
					        	<input type="checkbox" aria-label="Checkbox for following text input">
							</span>
			            <span class="input-group-addon">Serialize</span>

					    </div>
				    </td>
		            <td>
                            <div class="ui-select">
                                <select>
                                    <option selected="">Warehouse 12</option>
                                    <option>Warehouse 12</option>
                                    <option>Warehouse 12</option>
                                </select>
                            </div>
                    </td>
		            <td>
                    	<button class="btn btn-success">
                    		1
                    	</button>     
                    	<button class="btn btn-danger">
                    		2
                    	</button>
                    	<button class="btn btn-primary">
                    		3
                    	</button>
                    </td>
		            <td>
			           	<button class="btn btn-success">
                    		1
                    	</button>     
                    	<button class="btn btn-danger">
                    		2
                    	</button>
                    	<button class="btn btn-primary">
                    		3
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
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js"></script>

	</body>
</html>