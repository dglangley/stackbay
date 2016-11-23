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
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';


	
	$number = $_REQUEST['number'];
	$type = $_REQUEST['type'];
	$mode = $_REQUEST['mode'];
	
    function order_left($order_number,$order_type){
		
	//======================= DECLARE VARIABLE DEFAULTS ======================= 
		//This will have the reference order number(s) and associated files attached to it
		$associated_order = '';
		$f_carrier = '';
		$f_service = '';
		$f_account = '';
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
				$companyid = $row['companyid'];
				$company_name = (isset($companyid) ? getCompany($companyid) : '- Select a Company -');
				$contact = $row['contactid'];
				$b_add = $row['bill_to_id'];
				$b_name = getAddresses($b_add,'name');
				$s_add = $row['ship_to_id'];
				$s_name = getAddresses($s_add,'name');
				$selected_carrier = $row['freight_carrier_id'];
				// $s_carrier_name = getFreight('carrier',$s_carrier_name)['name'];
				$selected_service = $row['freight_services_id'];
				$selected_account = $row['freight_account_id'];
				$public = $row['public_notes'];
				$private = $row['private_notes'];
				$terms = $row['termsid'];
				
			}
		}
		
		//Account information
		$account = '';
		$freight = array();
		$freight = getFreight('max');
		if ($freight){
			foreach ($freight as $f){
				if(isset($f['method'])){
					$account .= "<option data-carrier-id=".$f['freight_co_id'].$f['id'].">".$f['account_no']."</option>";
				}
			}
		}
		
		// foreach($freight as $f){
		// 	$f[''];
		// }
		
		//THis will be split into distinct elements at some point in the next few weeks.
		$right = "
			<div class='col-sm-2  company_meta left-sidebar'>
			<div class='sidebar-container' style='padding-top: 20px'>
				
				<div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>						
						<div class ='company'>
							<label for='companyid'>Company:</label>
							<select name='companyid' id='companyid' class='company-selector' style = 'width:100%;'>
								<option value = $companyid>$company_name</option>
							</select>
						</div>
					</div>
				</div>
				<div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>						
						<div class ='contact'>
							<label for='contactid'>Contact:</label>
							<select name='contactid' id='contactid' class='contact-selector' style = 'width:100%;'>
								<option value = $contact>".getContact($contact)."</option>
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
						Ship to:
						<div id='ship_display'>11953 Walnut St.<br>Bloomington, CA 92316</div>
					</div>
					<div class='col-sm-6'>
						Bill to:
						<div id='bill_display'>11953 Walnut St.<br>Bloomington, CA 92316</div>
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

			    
			    <div class='row' style='padding-bottom: 10px;'>
					".dropdown('terms',$terms,$companyid)."
			    	".dropdown('warranty','','','col-sm-6')."
			    </div>
			    <div class='row' style='padding-bottom: 10px;'>
				    ".dropdown('carrier',$selected_carrier)."
			    	".dropdown('services',$services)."
			    </div>
			    <div class='row'>
					<div class='col-sm-12'>
						<div class = 'account forms_section'>
							<label for='account'>Account:</label>
							<select id='account_select'>
								$account
							</select>
						</div>
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