<?php

//=============================================================================
//============================= Operations Sidebar ============================
//=============================================================================
// The operations sidebar has two major functions: output the sidebar edit,   |
// and the display mode. Each gets the mode the number and the type. It then  |
// counts and outputs the row function.                                       | 
//                                                                            |
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
	

	function display($order_number = '',$page = 'Purchase'){
		//Opens the sidebar
		// $file = basename(__FILE__);
		$right =  "	<div class='row  company_meta left-sidebar' style='height:100%; padding: 0 10px;'>";
		$right .= "		<div class='sidebar-container' style='padding-top: 20px'>";
		$right.="
				<div class='row'>
					<div class='col-sm-12' style='padding-bottom: 10px;'>						
						<div class ='order'>
							<label for='order_selector'>Associated ";
		$right .= ($page == "Purchase")? "PO" : "SO";
		$right .= ":</label>
							<select name='order_selector' id='order_selector' class='order-selector' style = 'width:100%;'>";
		
		if ($order_number) {
			
			$order = ($page == "Purchase") ? '`purchase_orders`' : '`sales_orders`';
			$num_type = ($page == "Purchase") ? '`po_number`' : '`so_number`';
			
			$query = "SELECT * FROM $order WHERE $num_type = '$order_number';";
			$results = qdb($query);
			
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
			$right .= "<b>Contact:</b> ".getContact($contact)."<br>";
			
			//Addresses
			$right .= "<b>Billing Address:</b><br>";
			$right .= address_out($b_add);
			$right .= "<br>";
			$right .= "<b>Shipping Address:</b><br>";
			$right .= address_out($s_add);
			$right .= "<br>";
		}
		//Closing Tag (Leave Outside of any if statment)
	    $right .= "</div>
		    <div class='arrow click_me'>   
		    	<i class='icon-button fa fa-chevron-left' aria-hidden='true'></i>
        	</div>

        	<i class='fa fa-chevron-up shoot_me icon-button-mobile' aria-hidden='true' style='color: #000; position: absolute; bottom: -15px; left: 49%; z-index: 1;'></i>
		</div>";
		
			echo json_encode($right);
	}
	function address_out($address_id){
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
	
	display($number,$type);

?>