<?php

//=============================================================================
//============================= Operations Sidebar ============================
//=============================================================================
// The operations sidebar has two major functions: output the sidebar edit,   |
// and the display mode. Each gets the mode the number and the type. It then  |
// counts and outputs the row function.                                       | 
//                                                                            |
// Last update: Aaron Morefield - November 29th, 2016                         |
//=============================================================================

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


	
	$number = grab('number');
	$type = grab('type');
	$mode = grab('page');
	

	
    function edit($order_number,$order_type){
		
	//======================= DECLARE VARIABLE DEFAULTS ======================= 
		//This will have the reference order number(s) and associated files attached to it
		$associated_order = '';
		$f_carrier = '';
		$f_service = '';
		$f_account = '';
		$carrier_options = '';
		$ref_ln = '';

		
		
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
				if($order_type == 'Purchase'){
					$b_add = $row['remit_to_id'];
				}
				else{
					$b_add = $row['bill_to_id'];
				}
				$b_name = getAddresses($b_add,'street');
				$s_add = $row['ship_to_id'];
				$s_name = getAddresses($s_add,'street');
				$selected_carrier = $row['freight_carrier_id'];
				// $s_carrier_name = getFreight('carrier',$s_carrier_name)['name'];
				$selected_service = $row['freight_services_id'];
				$selected_account = $row['freight_account_id'];
				
				$public = $row['public_notes'];
				$private = $row['private_notes'];
				$terms = $row['termsid'];
				$associated_order = ($order_type == 'Purchase') ? $row['assoc_order'] : $row['cust_ref'];
				if ($order_type == 'Purchase') {$tracking = $row['tracking_no'];}
				else { $ref_ln = $row['ref_ln']; }
			}
		}
		
		//Account information (Similar to Drop Pop, but for a select2)

		
		if ($selected_account){
			$account_display = getFreight("account","",$selected_account,"account_no");
			$acct_display .= "<option selected value = '$selected_account' data-carrier-id='$selected_carrier'>$account_display</option>";
		} 
		else{
			$acct_display .= "<option selected value = 'null'>PREPAID</option>";
		}

		//THis will be split into distinct elements at some point in the next few weeks.
		$right =  "	<div class='row  company_meta left-sidebar' style='height:100%; padding: 10px;'>";
		$right .= "		<div class='sidebar-container' style='padding-top: 10px'>";

		//Company						
		$right .="
				<div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>						
						<div class ='company'>
							<label for='companyid'>Company</label>
							<select name='companyid' id='companyid' class='form-control input-xs company-selector required' style = 'width:100%;'>
								<option value = $companyid>$company_name</option>
							</select>
						</div>
					</div>
				</div>";
		if ($order_type == "Purchase"){
			$right .="
				<div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>
						<label for='bill_to'>Remit to [ <i class='address_edit fa fa-pencil' aria-hidden='true'></i> ]
						</label>
		                <select id='bill_to' class='form-control input-xs required' style='overflow:hidden;' data-ship-id='0' value='$b_add'>
							<option value = '$b_add'>$b_name</option>
		                </select>
				    </div>
			    </div>";
		}
		else{
		//Billing Address
			$right .="
				<div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>	     
						<label for='bill_to'>Bill to [ <i class='address_edit fa fa-pencil' aria-hidden='true'></i> ]
						</label>
	                    <select id='bill_to' class='form-control input-xs required' style='overflow:hidden;' data-ship-id='0' value='$b_add'>
							<option value = '$b_add'>$b_name</option>
	                    </select>
				    </div>
			    </div>";
		}
		
		//Payment Terms and warranty

		if ($order_type != "Purchase"){
			$right .= "<div class='row'>
						<div class='col-sm-7' id='customer_order' style='padding-bottom: 10px;'>";
			    	
			//Associated order module
			if ($order_number != 'New'){
				$right .= "
							<label for='assoc'><a href='".$ref_ln."' target='_new'>".$associated_order."</a></label>
				";
			} else {
				$right .= "
							<label for='assoc'>Customer Order</label>
				";
			}
			if ($order_type == "Sales") {
				if ($order_number == 'New') {
					$right .= "
								<div class='input-group'>
									<input class='form-control input-sm required' id = 'assoc_order' name='assoc' type='text' placeholder = 'Order #' value='$associated_order'>
									<span class='input-group-btn'>
										<button class='btn btn-info btn-sm btn-order-upload' type='button' for='assoc_order_upload'><i class='fa fa-paperclip'></i></button>
									</span>
								</div><!-- /input-group -->
								<input name='assoc_order_upload' type='file' id='order-upload' class='order-upload required' accept='image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,*.htm,*.html,*.xml' />
					";
				} else {
					$right .= "
								<input class='form-control input-sm required' id = 'assoc_order' name='assoc' type='text' placeholder = 'Order #' value='$associated_order'>
				";
				}
			} else {
				$right .= "
							<input class='form-control input-sm required' id = 'assoc_order' name='assoc' type='text' placeholder = 'Order #' value='$associated_order'>
			";
			}
		}
		$right .= "
	    			</div>";
	    if ($order_type != "Sales") {
    		$right .= "<div class='row po-terms'  style='padding-bottom: 10px;'>
				";
    	} else {
    		$right .= "<div class='so-terms'>";
    	}	
    	
		$right .= dropdown('terms',$terms,$companyid, 'col-sm-5');
		
		$right .= "</div>";
    	
    	if ($order_type == "Sales") {
    		$right .= "</div>
				";
    	}
		    	
		//Contact
		$right .= "
				<div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>						
						<div class ='contact'>
							<label for='contactid'>Contact [ <i class='contact-edit fa fa-pencil' aria-hidden='true'></i> ]</label>
							<select name='contactid' id='contactid' class='form-control input-xs contact-selector' style = 'width:100%;'>
								<option value = $contact>".getContact($contact)."</option>
							</select>
						</div>
					</div>
				</div>
		";
		 
		 //This feature will be added later ***Upload  	
		 //$right .= "
			// 	<div class='row'>
			// 		<div class='col-sm-12' id='customer_order' style='padding-bottom: 10px;'>
			// 			<label for='assoc'>Customer Order</label>
			// 			<div class = 'input-group'>
			// 				<input class='form-control' id = 'assoc_order' name='assoc' type='text' placeholder = 'Order #' value='$associated_order'>
			// 				<span class='input-group-btn'>
			// 			        <button class='btn btn-secondary' id = 'associate_clip' type='button'>
			// 				        <i class='fa fa-paperclip' aria-hidden='true'></i>
			// 			        </button>
			//     			</span>
		 //   			</div>
	  //  			</div>
		 //   	</div>";
		    	
		//If this is a purchase order, allow a static associated tracking field to be entered.
		// if($order_type == "Purchase"){
		// 	$right .= "
		// 			<div class='row'>
		// 				<div class='col-sm-12' id='tracking_div' style='padding-bottom: 10px;'>
		// 					<label for='tracking'>Associated Tracking #</label>
		// 					<input class='form-control required' id = 'tracking' name='tracking' type='text' placeholder = 'Tracking #' value='$tracking'>
		//     			</div>
		// 			</div>";
		// }
		
		
			    	//".dropdown('warranty','','','col-sm-6',true,'warranty_global')."
		//Shipping Address 	
		$right .= "
				<div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>	     
						<label for='ship_to' >Ship to  [ <i class='address_edit fa fa-pencil' aria-hidden='true'></i> ]";
		$right .= ($order_type == "Purchase")? "" : " &nbsp;<input id='mismo' type=checkbox></input> (Same as billing)";
		$right .=		"</label>
	                    <select id='ship_to' class='required' style='overflow:hidden;' data-ship-id='0' value='$s_add'>
							<option value = '$s_add' >$s_name</option>
	                    </select>
				    </div>
			    </div>";
		
		//Carrier and service
		
		//Sets UPS to default
		$selected_carrier = (strtolower($selected_carrier) != "null" && $selected_carrier)? $selected_carrier : '1' ;
		$selected_service = (strtolower($selected_service) != "null" && $selected_service)? $selected_service : '1' ;
		$right .= "
				<div class='row' style='padding-bottom: 10px;'>
				    ".dropdown('carrier',$selected_carrier, '', 'col-sm-4')."
				    ".dropdown('services',$selected_service,$selected_carrier,'col-sm-8')."
				    
			    </div>
			    <div class='row'>
			    <div class='col-sm-12'>
					<div class = 'account forms_section'>
						<label for='account'>Account</label>
						<select id='account_select' class='form-control input-xs'>
							$acct_display
						</select>
					</div>
				</div>
				</div>";
			    
		
		//NOTES SECTION (Band together)
		$right .= "
				<div class='row' style='padding-bottom: 10px;'>
					<div class='col-sm-12'>
						<label for='private_notes'>Private Notes</label>
						<textarea id='private_notes' class='form-control textarea-info' name='email' rows='3' style=''>$private</textarea>
					</div>
				</div>	
				<div class='row' style='padding-bottom: 10px;'>
					<div class='col-sm-12'>
						<label for='public_notes'>Public Notes / Customer Requirements</label>
						<textarea id = 'public_notes' class='form-control' rows='3' style=''>$public</textarea>
					</div>
				</div>
		";
		if ($order_number == 'New' AND $order_type == "Sales") {
			$right .= "
				<div class='row' style='padding-bottom: 10px;'>
					<div class='col-sm-12'>
						<input type='checkbox' name='email_confirmation' id = 'email_confirmation' value='1' checked />
						<label for='email_confirmation'>Send Order Confirmation</label>
						<p><strong>TO</strong> <em>Contact above already included</em></p>
						<select name='email_to' id='email_to' class='form-control input-xs contact-selector' style = 'width:100%;'>
						</select>
						<p style='margin-top:10px'><strong>CC</strong> <i class='fa fa-check-square-o'></i> shipping@ven-tel.com</p>
					</div>
				</div>
			";
		}
		//Closing Tag (Leave Outside of any if statment)
	    	$right .= "</div>
		</div>";
		
		
			echo json_encode($right);
    }
    
		function getPackages($order_number){
			$order_number = prep($order_number);
			$query = "Select * From packages WHERE order_number = $order_number;";
			$result = qdb($query);
			return $result;
		}
    
	function display($order_number = '',$page = 'Purchase'){
		//Opens the sidebar
		// $file = basename(__FILE__);
		
		$company_name;
		$public;
		$s_carrier_name;
		
		//Navigation changer
		$right =  "	<div class='row  company_meta left-sidebar' style='height:100%; padding: 0 10px;'>";
		$right .= "		<div class='sidebar-container'>";
		$right.="
				<div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>						
						<div class ='order'>
							<label for='order_selector'> ";
		// $right .= ($page == "Purchase")? "<h5>PO NAVIGATION</h5>" : "<h5>SO NAVIGATION</h5>";
		$right .= "</label>
							<select name='order_selector' id='order_selector' class='order-selector' style = 'width:100%;'>";
		
		if ($order_number) {
			
			$order = ($page == "Purchase") ? 'purchase_orders' : 'sales_orders';
			$num_type = ($page == "Purchase") ? 'po_number' : 'so_number';
			
			$query = "SELECT * FROM $order WHERE $num_type = '$order_number';";
			$results = qdb($query);
			
			foreach ($results as $row){
				$companyid = $row['companyid'];
				$orderNumber = ($page == 'Purchase') ? $row['assoc_order'] : $row['cust_ref'];
				$company_name = (!empty($companyid) ? getCompany($companyid) : '- Select a Company -');
				$contact = $row['contactid'];
				if($page == 'Purchase'){
					$b_add = $row['remit_to_id'];
				}
				else{
					$b_add = $row['bill_to_id'];
					$ref_ln = $row['ref_ln'];
				}
				$b_name = getAddresses($b_add,'name');
				$s_add = $row['ship_to_id'];
				$s_name = getAddresses($s_add,'name');
				$selected_carrier = $row['freight_carrier_id'];
				//$s_carrier_name = getFreight('carrier',$row['freight_carrier_id'])['name'];
				$selected_service = $row['freight_services_id'];
				$selected_account = $row['freight_account_id'];
				$public = $row['public_notes'];
				$private = $row['private_notes'];
				$terms = $row['termsid'];
			}
			
			if($order_number){ 
					$right.="			<option value = $order_number>$order_number - $company_name</option>";
			}
		}	

		$right.="			</select>
						</div>
					</div>
				</div>";
		if($results){
			//Contact Output
			$right .= "<div class='row'>";
			$right .= "<div class='col-md-12'>";
				// $right .= "<h5>SHIPMENT INFORMATION</h5><br>";
				$right .= "<b style='color: #526273;font-size: 14px;'>".strtoupper($company_name)."</b><br>".
							"<b style='color: #526273;font-size: 12px;'>".getContact($contact)."</b><br><br>";
				
				//Order Number
				if($ref_ln != '') {
					$right .= "<a href='/".$ref_ln."'><i class='fa fa-file fa-4' aria-hidden='true'></i></a> " . $orderNumber . "<br><br>";
				} else {
					$right .=  $orderNumber . "<br><br>";
				}
				
				//Addresses
				if($page != 'Purchase') {
					$right .= "<b style='color: #526273;font-size: 14px;'>BILLING ADDRESS:</b><br>";
					$right .= "<span style='color: #aaa;'>" .address_out($b_add). "</span>";
				} else {
					$right .= "<b style='color: #526273;font-size: 14px;'>REMIT TO:</b><br>";
					//address function needs to be edited to take in remit to column insead of bill to
					$right .= "<span style='color: #aaa;'>" .address_out($b_add). "</span>";
				}
				$right .= "<br><br>";
				$right .= "<b style='color: #526273;font-size: 14px;'>SHIPPING ADDRESS:</b><br>";
				$right .= "<span style='font-size: 14px;'>" .address_out($s_add). "</span>";
				$right .= "<br><br>";
				$right .= "<b style='color: #526273;font-size: 14px;'>SHIPPING INSTRUCTIONS:</b><br>";
				if($selected_carrier){
					$right .= getFreight('carrier',$selected_carrier,'','name');
				}
				else{
					$right .= "None";
				}
				
				if ($selected_service){
					$right .= " ".getFreight('services','',$selected_service,'method');
				}

				$right .= "<br><br>";
				if($public){
					$right .= "<b style='color: #526273;font-size: 14px;'>PUBLIC NOTES:</b><br>";
					$right .= $public;
					$right .= "<br>";
				}
				if($private){
					$right .= "<b style='color: #526273;font-size: 14px;'>PRIVATE NOTES:</b><br>";
					$right .= $private;
					$right .= "<br>";
				}
				$right .= "<br>";
				
				$lists = array();
				
				$query = "SELECT DISTINCT datetime FROM packages WHERE order_number = '".res($order_number)."';";
				$result = qdb($query) OR die(qe().' '.$query);
				if($page != 'Purchase') {
					while ($row = $result->fetch_assoc()) {
						$lists[] = $row['datetime'];
						//$right .= $row['datetime'];
					}
					
					$init = true;
					foreach($lists as $num) {
						$box = array();
						if($num != '') {
							if($init) {
								$right .= "<b style='color: #526273;font-size: 14px;'>PACKING LIST:</b><br>";
								$init = false;
							}
							
							$query =  "SELECT package_no FROM packages WHERE datetime = '$num';";
							$result = qdb($query);
							
							while ($row = $result->fetch_assoc()) {
								$box[] = $row['package_no'];
							}
							
							$boxList = implode(', ', $box);
							
							$dateF = date_create($num);
							$right .= '<a target="_blank" href="/packing-slip.php?on='.$order_number.'&date='.$num.'"><i class="fa fa-file" aria-hidden="true"></i></a> Box #  ' . $boxList . ' ' . date_format($dateF, "N/j/y g:ia") . '<br>';
						}
					}
				}
				
				$right .= "<br>";
				$right .= "</div>";
			$right .= "</div>";
			
			//Old way of doing packages in the sidebar used to be here, if I am searching for a history,
			//Go to line 330 from the version on the Morning of the  18th of December. I probably will not need this
			
		}
		
		
		//Closing Tag (Leave Outside of any if statment)
	    $right .= "</div>
		   
		</div>";
		
			echo json_encode($right);
	}
	
	
	function address_out($address_id){
		//General function for handling the standard display of addresses
		$address = '';
		//Address Handling
		$row = getAddresses($address_id);
		$name = $row['name'];
		$street = $row['street'];
		$city = $row['city'];
		$state = $row['state'];
		$zip = $row['postal_code'];
		$country = $row['country'];
		
		//Address Output
		if($name){$address .= $name."<br>";}
		if($street){$address .= $street."<br>";}
		if($city && $state){$address .= $city.", ".$state;}
		else if ($city || $state){ ($address .= $city.$state);}
		if($zip){$address .= "  $zip";}
		
		return $address;
	}
	
	if ($mode == 'order'){
		edit($number,$type);
	}
	else{
		display($number,$type);
	}
		
?>
