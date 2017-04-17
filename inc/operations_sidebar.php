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
include_once $rootdir.'/inc/packages.php';

	$rtv_items = array();
	$rtv_array = array();
	
	if(strtolower($_REQUEST['ps']) == 'rtv'){
		$order_type = "RTV";
		$origin = $order_number;
		$order_number = "New";
		$rtv_items = $_REQUEST['partid'];
		
		//$rtv_items = array_count_values($rtv_items);
		
		foreach($rtv_items as $key => $item){
			$rtv_array[$key] = array_count_values($item);
		}
		
		 $rtv_items = $rtv_array;
		 
		 //print_r($rtv_items); die;
	}
	
	// $number = grab('on');
	// $rma = grab('rma');
	
	
	// $type = grab('type');
	// //$mode = grab('page');
	

	
	function edit($order_number,$order_type, $mode=''){
		global $rtv_items;
	//======================= DECLARE VARIABLE DEFAULTS ======================= 
		//This will have the reference order number(s) and associated files attached to it
		$associated_order = '';
		$f_carrier = '';
		$f_service = '';
		$f_account = '';
		$carrier_options = '';
		$ref_ln = '';
		$status = 'Active';
	
		//Notes: Associated to an ID, but I will simply load the text here.
		$private = '';
		$public = '';
		
		//If it is an old record 
		if ($order_number != 'New'){
			$q_form = "SELECT * FROM ";
			$q_form .= ($order_type == 'Purchase' || strtolower($order_type) == 'rtv') ? 'purchase_orders' : 'sales_orders';
			$q_form .= " WHERE ";
			$q_form .= ($order_type == 'Purchase' || strtolower($order_type) == 'rtv') ? 'po_number' : 'so_number';
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
				$status = $row['status'];
				$associated_order = ($order_type == 'Purchase') ? $row['assoc_order'] : $row['cust_ref'];
				if ($order_type == 'Purchase') {$tracking = $row['tracking_no'];}
				else { $ref_ln = $row['ref_ln']; }
			}
		}
		
		if(strtolower($order_type) == 'rtv'){
			//Overwrite the information from the purchase order based on the RTV Defaults
			$purchase_lineid;
			$data = array();
			
			$items = $rtv_items;
			foreach($items as $lineitem => $item) {
				$purchase_lineid = $lineitem;
			}
			
			$query = "SELECT * FROM purchase_items p, purchase_orders o, inventory i WHERE p.id = ".prep($purchase_lineid)." AND p.po_number = o.po_number AND i.purchase_item_id = p.id;";
			$result = qdb($query) or die(qe());
			
			if (mysqli_num_rows($result)>0) { 
				$data = mysqli_fetch_assoc($result);
			}
			
			$terms = 1;
			$associated_order = '';
			$selected_service = '';
			$selected_account = '';
			$selected_account = '';
			$private = 'RTV From PO #'.$order_number;
			$public = '';
			$contact = '';
			
			// $b_add = $data['remit_to_id'];
			// $b_name = getAddresses($b_add,'street');
			
			$s_add = '';
			$s_name = '';
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
		} else {
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
		$right .= "	<div class='row' style='padding-bottom: 10px;'>";
		if ($order_type == "Sales" || $order_type == 'RTV'){
			$right .= "		<div class='col-sm-7' id='customer_order'>";
			// Changes the label based off of creation of the order number
			if ($order_number != 'New' && $associated_order){
				$right .= "	<label for='assoc'><a href='".$ref_ln."' target='_new'>".$associated_order."</a></label>
							<input class='form-control input-sm required' id = 'assoc_order' name='assoc' type='text' placeholder = 'Order #' value='$associated_order'>";
			} else {
				$right .= "	<label for='assoc'>Customer Order</label>
							<div class='input-group'>
								<input class='form-control input-sm required' id = 'assoc_order' name='assoc' type='text' placeholder = 'Order #' value='$associated_order'>
								<span class='input-group-btn'>
									<button class='btn btn-info btn-sm btn-order-upload' type='button' for='assoc_order_upload'><i class='fa fa-paperclip'></i></button>
								</span>
							</div><!-- /input-group -->
							<input name='assoc_order_upload' type='file' id='order-upload' class='order-upload required' accept='image/*,application/pdf,application/vnd.ms-excel,application/msword,text/plain,*.htm,*.html,*.xml' />
				";
			}
			$right .= "</div>";
		}
			// $right .= "<div class='so-terms'>";
			$right .= dropdown('terms',$terms,$companyid, 'col-sm-5');
			// $right .= "</div>";
		$right .= "</div>";
	
		    	
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
	    	$right .= "</div>"; //close the immediate sidebar container
		
		
		//Output the void button to the bottom left side of the collumn
		if ($order_number != 'New' && $status == 'Active') {
			$right .= "
				<div class='row'>
					<div class='col-sm-12'>
						<div class='btn btn-sm btn-danger' id = 'order_void'>Void</div>
					</div>
				</div>
			";
		}
		
		//Output the void button to the bottom left side of the collumn
		if ($order_number != 'New' && $status == 'Void') {
			$right .= "
				<div class='row'>
					<div class='col-sm-12'>
						<div class='btn btn-sm btn-warning' id = 'order_void'>Unvoid</div>
					</div>
				</div>
			";
		}
		
		$right .= "</div>"; //Close the sidebar
		
		
		return ($right);
	}
	
	function display($order_number = '',$page = 'Purchase',$mode = ''){
		//Opens the sidebar
		// $file = basename(__FILE__);
		
		$company_name;
		$public;
		$s_carrier_name;
		
		// Aquire macro level information about the RMA Item
		if (substr($mode,0,3) == 'RMA' && $page =='RMA'){
			$rma_macro_select = "SELECT `notes`, `order_number` FROM `returns` WHERE rma_number = ".prep($order_number).";";
			$rma_macro_results = qdb($rma_macro_select);
			$rma_macro = mysqli_fetch_assoc($rma_macro_results);
			$rma_number = $order_number;
			$order_number = $rma_macro['order_number'];
			$rma_notes = $rma_macro['notes'];
		}
		
		//Navigation changer
		$right =  "	<div class='row  company_meta left-sidebar' style='height:100%; padding: 0 10px;'>";
		// if ($page == "RMA"){
		// 	$right = "";
		// }
		$right .= "		<div class='sidebar-container'>";
		
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
			
			if($page != "RMA" && $mode != 'bill'){
				$right.="
						<div class='row'>
							<div class='col-sm-12' style='padding-bottom: 10px;'>						
								<div class ='order'>
									<label for='order_selector'> ";
				// $right .= ($page == "Purchase")? "<h5>PO NAVIGATION</h5>" : "<h5>SO NAVIGATION</h5>";
				$right .= "</label>
									<select name='order_selector' id='order_selector' class='order-selector' style = 'width:100%;'>";
				
							
							if($order_number){ 
									$right.="			<option value = $order_number>$order_number - $company_name</option>";
							}
				$right.="			</select>
								</div>
							</div>
						</div>";
			}else if ($mode != 'bill'){
				$right.="
						<div class='row'>
							<div class='col-sm-12' style='padding-bottom: 10px;font-size:14pt; '>						
								Created from SO #$order_number
							</div>
						</div>";
			}
		}
		if($mode == 'bill'){
			$right.="
					<div class='row'>
						<div class='col-sm-12' style='padding-bottom: 10px;font-size:14pt; '>						
							<label for='associated_invoice'>Customer Invoice #:</label>";
							
								$right .= "<input name = 'associated_invoice' id='customer_invoice' class = 'form-control'>";
			$right .="</div>
			</div>";
		}
		if($results) {
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
			
			if($page != "RMA"){
				//Addresses
				if($page != 'Purchase') {
					$right .= "<b style='color: #526273;font-size: 14px;'>BILLING ADDRESS:</b><br>";
					$right .= "<span style='color: #aaa;'>" .address_out($b_add). "</span>";
				} else {
					$right .= "<b style='color: #526273;font-size: 14px;'>REMIT TO:</b><br>";
					//address function needs to be edited to take in remit to column insead of bill to
					$right .= "<span style='color: #aaa;'>" .address_out($b_add). "</span>";
				}
				
				$right .= "<br><br><b style='color: #526273;font-size: 14px;'>SHIPPING ADDRESS:</b><br>";
				$right .= "<span style='font-size: 14px;'>" .address_out($s_add). "</span>";
				$right .= "<br><br>";
				//Shipping inforamtions
				$right .= "<b style='color: #526273;font-size: 14px;'>SHIPPING INSTRUCTIONS:</b><br>";
				
				if($selected_carrier){
					$right .= getFreight('carrier',$selected_carrier,'','name');
				} else {
					$right .= "None";
				}
				
				if ($selected_service){
					$right .= " ".getFreight('services','',$selected_service,'method');
				}
	
				$right .= "<br><br>";
			}
			
			if($public){
				$right .= "<b style='color: #526273;font-size: 14px;'>PUBLIC NOTES";
				if($page == "RMA"){$right .= " FROM SO #$order_number";}
				$right .= ":</b><br>";
				$right .= $public;
				$right .= "<br><br>";
			}
			
			if($private){
				
				$right .= "<b style='color: #526273;font-size: 14px;'>PRIVATE NOTES";
				if($page == "RMA"){$right .= " FROM SO #$order_number";}
				$right .=":</b><br>";
				$right .= $private;
			}

			if($page == "RMA"){
				$right .= "<br><br>";
				if ($mode == "RMA_display"){
					$right .= "
						<div class='row' style='padding-bottom: 10px;'>
							<div class='col-sm-12'>
								<label for='rma_notes'>RMA Notes</label>
								<br>$rma_notes
							</div>
						</div>	
					";
				} else {
					$right .= "
						<div class='row' style='padding-bottom: 10px;'>
							<div class='col-sm-12'>
								<label for='rma_notes'>RMA Notes</label>
								<textarea id='rma_notes' class='form-control textarea' name='rma_notes' rows='3' style=''>$rma_notes</textarea>
							</div>
						</div>	
					";
				}
			}

			
			$lists = array();
			
			// $query = "SELECT DISTINCT datetime FROM packages WHERE order_number = '".res($order_number)."';";
			if($page == 'Sales') {
				// Grab the list of packages grouped by shipment datetime
				$query = "SELECT GROUP_CONCAT(package_no ORDER BY package_no ASC) boxes, datetime FROM packages WHERE order_number = ".prep($order_number)." and datetime IS NOT NULL GROUP BY datetime;";
				$result = qdb($query) OR die(qe().' '.$query);
				
				//If there are any existing packages, print them out on this list
				if (mysqli_num_rows($result) > 0){
					$right .= "<br>";
					$right .= "<b style='color: #526273;font-size: 14px;'>PACKING LIST:</b><br>";
					foreach($result as $slip) {
						//Create a date_time object
						$dateF = date_create($slip['datetime']);
						
						// Append to right the packing slip options
						$right .= '<a target="_blank" href="/packing-slip.php?on='.$order_number.'&date='.$slip['datetime'].'"><i class="fa fa-file" aria-hidden="true"></i>&nbsp';
						$right .= '<b>Box #  ' . $slip['boxes']. '</b></a> ' . date_format($dateF, "N/j/y g:ia") . '<br>';
					}
				}
			} //end of the sales specific section
			
			$right .= "<br></div></div>";
		}
		
		
		//Closing Tag (Leave Outside of any if statment)
	    $right .= "</div>
		   
		</div>";
		
		return $right;
	}

function sidebar_out($number, $type, $mode ='order'){
	// if(!empty($rma)) {
	// 	$mode = 'rma';
	// } else {
	// 	$mode = 'order';
	// }
	
	if ($mode == 'order'){
		echo edit($number,$type,$mode);
	} else {
		echo display($number,$type,$mode);
	}
}