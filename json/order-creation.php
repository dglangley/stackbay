<?php

//Prepare the page as a JSON type
header('Content-Type: application/json');

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
	
	$number = $_REQUEST['number'];
	$type = $_REQUEST['type'];
	$mode = $_REQUEST['mode'];
	
	function waterfalls(){
		//The waterfalls function expects the input of which field it is looking for, 
		//and the output of adjusted lists of parameters, based on whatever
		
		
	}
	
    function order_left($order_number,$order_type){
		 
		//Rather than do a select2 like a sane person would, I will use a method of appended
		//arrays. Each of these will allow me a greater amount of control (Perhaps)
		//to output and organize each of the results in a manner which database
		//access happens implicitly.
		
		$company = '- Select a Company -';
		
		//This will have the reference order number(s) and associated files attached to it
		$associated_order = '';
		
		//Address arrays: with output in mind, we will use the names of each of these
		//to both refer to unique records, and to format the display option. It might
		//make more sense to nest each of these values into a nested array, where
		//the text to be displayed is matched directly to an ID.
		$s_add = array(
			'id' => '0',
			'name' => 'Address',
			'line1' => '',
			'line2' => '',
			'city' => '',
			'state' => '',
			'zip' => ''
			);
		$b_add = array(
			'id' => '0',
			'name' => 'Address',
			'line1' => '',
			'line2' => '',
			'city' => '',
			'state' => '',
			'zip' => ''
			);
		
		//ID-Name Arrays: These will act similar to SELECT2 statements, by pairing
		//a dynamic output option select. However, this method will be triggered
		//by selection of other terms. Essentially, these will cascade on selections
		//made elsewhere on the page. This will be done on the waterfalls??
		$s_terms = array(
			'id' => '0',
			'name' => 'Shipping Terms');
		$b_terms = array(
			'id' => '0',
			'name'=> 'Billing Terms'
			);
		$f_carrier = 'Freight Carrier';
		$f_service = 'Freight Service';
		$f_account = 'Freight Account';
			
		//Notes: Associated to an ID, but I will simply load the text here.
		$private = '';
		$public = '';
		
		//If it is an old 
		if ($order_number != 'New'){
			$q_form = "SELECT * FROM ";
			$q_form .= ($order_type == 'p') ? 'purchase_orders' : 'sales_orders';
			$q_form .= " WHERE ";
			$q_form .= ($order_type == 'p') ? 'po_number' : 'so_number';
			$q_form .= " = '$order_number';";
			$results = qdb($q_form);
			
			//PUT LOOP HERE TO PARSE RESULTS AND REPLACE ANY EMPTY VARS ABOVE THIS
		}
		
		//Else there is a chance that I would want to make a temporary entry
		
		$right = "	
			<div class = 'row-fluid forms_section'>
				<h3>
					Company Information
				</h3>
				<div class='row-fluid'>
					<div class='col-md-6'>
						<div class = 'company'>
							<select name='companyid' id='companyid' class='company-selector'>
								<option>$company</option>
							</select>
						</div>
					</div>
					<div class='col-md-6'>
						<div class='input-group'>
							<input class='form-control' type='text' placeholder = 'Order #' value='$associated_order'>
							<span class='input-group-btn'>
						        <button class='btn btn-secondary' type='button'>
							        <i class='fa fa-paperclip' aria-hidden='true'></i>
						        </button>
			    			</span>
		    			</div>
					</div>
                </div>
            </div>
            <!------------------------ Address Section ------------------------>
            <div class = 'row-fluid forms_section'>
				<div class='col-md-6'>
					<h4>Ship to</h4>
	                    <select id='ship_to' data-ship-id=0>
	                        <option>Address</option>
	                        <option class='add_new_dropdown' data-new-field='address'>Add New</option>
	                    </select>
            		<div>".
	                	$s_add['line1']."<br>".
	                	$s_add['line2']."<br>".
	                	$s_add['city']."&nbsp;".$s_add['state']."<br>".
	                	$s_add['zip']
	            	."</div>
					<div class='ui-select forms_dropdown'>
	                    <select>
	                        <option selected=''>Shipping Terms</option>
							<option class='add_new_dropdown' data-new-field='address'>Add New</option>
	                    </select>
	                </div>
				</div>
				<div class='col-md-6'>
					<h4>Bill to</h4>

	                    <select id='bill_to' data-bill-id=0>
	                        <option selected=''>Address</option>
							<option class='add_new_dropdown' data-new-field='address'>Add New</option>
	                    </select>
	            	<div>".
	                	$b_add['line1']."<br>".
	                	$b_add['line2']."<br>".
	                	$b_add['city']."&nbsp;".$b_add['state']."<br>".
	                	$b_add['zip']
	            	."</div>
					<div class='ui-select forms_dropdown'>
	                    <select>
	                        <option selected=''>".$b_terms['name']."</option>
							<option class='add_new_dropdown' data-new-field='address'>Add New</option>
	                    </select>
	                </div>
                </div>
            </div>
            
            <!----------------------------- Terms ----------------------------->
            <div class = 'row-fluid '>
				<h3>Freight</h3>
            	<div class='col-md-4'>
					<div class='ui-select forms_dropdown'>
	                    <select id = 'freight-carrier'>
	                        <option data-carrier-id='blank' selected=''>$f_carrier</option>
							<option data-carrier-id=1>UPS</option>
							<option data-carrier-id=2>FEDEX</option>
							<option data-carrier-id=3>USPS</option>
							<option data-carrier-id=4>ONTRAC</option>
							<option data-carrier-id=5>PERSONAL</option>
	                    </select>
	                </div>
                </div>
				<div class='col-md-4'>
					<div class='ui-select forms_dropdown'>
	                    <select id = 'freight-services'>
	                        <option data-carrier-id='blank' selected=''>$f_service</option>
							<option data-carrier-id=1>UPS Overnight</option>
							<option data-carrier-id=1>UPS 2 Day</option>
							<option data-carrier-id=1>UPS 3 Day</option>
							<option data-carrier-id=1>UPS Standard</option>
							<option data-carrier-id=2>FEDEX Overnight</option>
							<option data-carrier-id=2>FEDEX 2 Day</option>
							<option data-carrier-id=2>FEDEX 3 Day</option>
							<option data-carrier-id=2>FEDEX Standard</option>
							<option data-carrier-id=3>USPS Overnight</option>
							<option data-carrier-id=3>USPS 2 Day</option>
							<option data-carrier-id=3>USPS 3 Day</option>
							<option data-carrier-id=3>USPS Standard</option>
							<option data-carrier-id=4>ONTRAC Overnight</option>
							<option data-carrier-id=4>ONTRAC 2 Day</option>
							<option data-carrier-id=4>ONTRAC 3 Day</option>
							<option data-carrier-id=4>ONTRAC Standard</option>
							<option data-carrier-id=5>It will get there when it gets there</option>
	                    </select>
	                </div>
	            </div>
            </div>
            <div class='col-md-4'>
				<div class = 'account forms_section'>
					<select name='account' id='account_select' class='account-selector'>
						<option>".$f_account."</option>
					</select>
				</div>
            </div>
        </div>
            
            <!----------------------------- Notes ----------------------------->
			<div class = 'row-fluid'>
				<div class='col-md-6'>
					<h3>Private Notes</h3>
				<textarea class='form-control' rows='4' style=''>$private</textarea>
				</div>
				<div class='col-md-6'>
					<h3>Public Notes</h3>
				<textarea class='col-md-6 form-control' rows='4' style=''>$public</textarea>
				</div>
			</div>";
			echo json_encode($right);
    }
	
	order_left($number,$type);
?>