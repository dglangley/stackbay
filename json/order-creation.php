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
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/form_handle.php';


	
	$number = $_REQUEST['number'];
	$type = $_REQUEST['type'];
	$mode = $_REQUEST['mode'];
	
    function order_left($order_number,$order_type){
		
	//======================= DECLARE VARIABLE DEFAULTS ======================= 
		$company = '- Select a Company -';
		
		//This will have the reference order number(s) and associated files attached to it
		$associated_order = '';
		
		//Address arrays: with output in mind, we will use the names of each of these
		//to both refer to unique records, and to format the display option. It might
		//make more sense to nest each of these values into a nested array, where
		//the text to be displayed is matched directly to an ID.
		$s_add = array(
			'id' => '0',
			'name' => 'Address'
			);
		$b_add = array(
			'id' => '0',
			'name' => 'Address'
			);
		
		//ID-Name Arrays: These will act similar to SELECT2 statements, by pairing
		//a dynamic output option select. However, this method will be triggered
		//by selection of other terms. Essentially, these will cascade on selections
		//made elsewhere on the page. This will be done on the waterfalls??
		// $s_terms = array(
		// 	'id' => '0',
		// 	'name' => 'Shipping Terms'); 
		// $b_terms = array(
		// 	'id' => '0',
		// 	'name'=> 'Billing Terms'
		// 	);
		$f_carrier = 'Carrier';
		$f_service = 'Service';
		$f_account = 'Account';
		$carrier_options = '';
		
		//Notes: Associated to an ID, but I will simply load the text here.
		$private = '';
		$public = '';
		
		//If it is an old record 
		if ($order_number != 'New'){
			$q_form = "SELECT * FROM ";
			$q_form .= ($order_type == 'Purchase') ? 'purchase_orders' : 'sales_orders';
			$q_form .= " WHERE ";
			$q_form .= ($order_type == 'Purchase') ? 'po_number' : 'so_number';
			$q_form .= " = '$order_number';";
			
			$results = qdb($q_form);
			
			foreach ($results as $row){
				$company = (isset($row['companyid'])) ? getCompany($row['companyid']) : '- Select a Company -';
				$contact = getRep($row['contactid']);
				$b_add = $row['bill_to_id'];
				$b_name = getAddresses($b_add,'name');
				$s_add = $row['ship_to_id'];
				$s_name = getAddresses($s_add,'name');
				$f_carrier = $row['freight_carrier_id'];
				$f_service = $row['freight_services_id'];
				$f_account = $row['freight_account_id'];
				$public = $row['public_notes'];
				$private = $row['private_notes'];
			}
		}
		
		
		$carrier = getFreight('carrier');

		
		if ($carrier){
			foreach ($carrier as $c){
				$carrier_options .= "<option data-carrier-id=".$c['id'].">".$c['name']."</option>";
			}
		}
		
		$account = '';
		$freight = array();
		$freight = getFreight('max');
		if ($freight){
			foreach ($freight as $f){
				if(isset($f['method'])){
					$account .= "<option data-carrier-id=".$f['freight_co_id'].$f['id'].">".$f['method']."</option>";
				}
				// $f['account_no'];
				// $freight .= '';
			}
		}
		
		// foreach($freight as $f){
		// 	$f[''];
		// }
		
		//Else there is a chance that I would want to make a temporary entry
		$right = "
			<div class='col-sm-2  company_meta left-sidebar'>
			<div class='sidebar-container' style='padding-top: 20px'>
				
				<div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>						
						<div class ='company'>
							<label for='companyid'>Company:</label>
							<select name='companyid' id='companyid' class='company-selector' style = 'width:100%;'>
								<option>$company</option>
							</select>
						</div>
					</div>
				</div>
				<div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>						
						<div class ='contact'>
							<label for='contactid'>Contact:</label>
							<select name='contactid' id='contactid' class='contact-selector' style = 'width:100%;'>
								<option>$contact</option>
							</select>
						</div>
					</div>
				</div>
				<div class='row'>
					<div class='col-sm-12' id='assoc_order' style='padding-bottom: 10px;'>
					<label for='assoc'>Customer Order:</label>
						<div class = 'input-group'>
							<input class='form-control' name='assoc' type='text' placeholder = 'Order #' value='$associated_order'>
							<span class='input-group-btn'>
						        <button class='btn btn-secondary' type='button'>
							        <i class='fa fa-paperclip' aria-hidden='true'></i>
						        </button>
			    			</span>
		    			</div>
	    			</div>
		    	</div>
		    	
				<div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>	     
						<label for='ship_to' >Ship to:</label>
	                    <select id='ship_to' style='overflow:hidden;' data-ship-id='0' value='$s_add'>
							<option value = '$s_add' >$s_name</option>
	                    </select>
				    </div>
			    </div>
				<div class='row'>
					<div class='col-sm-6'>
					11953 Walnut St.<br>Bloomington, CA 92316
					</div>
					<div class='col-sm-6'>
					</div>
				</div>
				<div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>	     
						<label for='bill_to'>Bill to:
							<input id='mismo' type=checkbox></input> (Same as shipping)
						</label>
	                    <select id='bill_to' style='overflow:hidden;' data-ship-id='0' value='$b_add'>
							<option value = '$b_add'>$b_name</option>
	                    </select>
				    </div>
			    </div>

			    
			    <div class='row'>
					<div class='col-sm-6' style='padding-bottom: 10px;'>	            	
						<label for='terms'>Payment Terms:</label>
						<select id ='terms' class='form-control'>
							$account
						</select>
				    </div>
					<div class='col-sm-6' style='padding-bottom: 10px;'>	            	
						<label for='freight'>Carrier:</label>
						<select id = 'carrier' class='form-control'>
							$carrier_options
						</select>
				    </div>
			    </div>
			    <div class='row'>
					<div class='col-sm-12'>
						<div class = 'account forms_section'>
							<label for='account'>Account:</label>
							<select name='account' id='account_select'>
								<option>".$f_account."</option>
							</select>
						</div>
				</div>
				</div>
			    <div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>	            	
						<label for='warranty'>Warranty:</label>
						<input class='form-control' type='text' name='zip' placeholder='Warranty'/>
				    </div>
			    </div>
				<div class = 'row'>
					<div class='col-sm-12'>
						<label for='private_notes'>Private Notes:</label>
						<textarea id='private_notes' class='form-control' rows='4' style=''>$private</textarea>
					</div>
				</div>	
				<div class = 'row'>
					<div class='col-sm-12'>
						<label for='public_notes'>Public Notes:</label>
						<textarea id = 'public_notes' class='form-control' rows='4' style=''>$public</textarea>
					</div>
				</div>
	    	</div>
		    <div class='arrow click_me'>   
		    	<i class='icon-button fa fa-chevron-left' aria-hidden='true'></i>
        	</div>
        	
        	<i class='fa fa-chevron-up shoot_me icon-button-mobile' aria-hidden='true' style='color: #000; position: absolute; bottom: -15px; left: 49%; z-index: 1;'></i>
		</div>";
		
		
			echo json_encode($right);
    }
	
	order_left($number,$type);
?>