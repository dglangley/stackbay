<?php 
    header('Content-Type: application/json');

//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getPartId.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/getManf.php';
	include_once $rootdir.'/inc/getSerials.php';
	include_once $rootdir.'/inc/dropPop.php';
	
	// Get Pages function determines how many pages the inventory should output.
	function getPages($show_num = '5') {
		//Find out what page number we are on.
		global $page;
		
		//Set a null counter for the number of rows
		$rows = 0;
		
		//Static query which gets all the parts from the inventory screen
		$query  = "SELECT COUNT(*) as rows FROM (SELECT DISTINCT  `partid` FROM  `inventory`) AS t1;";
		$result = mysqli_fetch_assoc(qdb($query));
		$rows = $result['rows'];
		$pages = ceil($rows / $show_num);
		
		for($i = 1; $i <= $pages; $i++) {
			//echo $page;
			echo '<li class="' .($page == $i || ($page == '' && $i == 1) ? 'active':''). '"><a href="?page=' .$i. '">'.$i.'</a></li>';
		}
	}
	

	function getItemHistory($invid = 0) {
		$partHistory_array = array(); 
		
		$query  = "SELECT * FROM inventory_history WHERE invid =" . res($invid) . ";";
		$result = qdb($query);
		
		while ($row = $result->fetch_assoc()) {
			$partHistory_array[] = $row;
		}
		
		return $partHistory_array;
	}
	
	function getStatusStock($stock = '', $partid = 0) {
		$stockNumber;
		
		if($stock == 'pending') {
			$query  = "SELECT SUM(qty) FROM inventory WHERE partid =" . res($partid) . " AND (status = 'ordered');";
		} else if($stock == 'instock') {
			$query  = "SELECT SUM(qty) FROM inventory WHERE partid =" . res($partid) . " AND (status = 'received' OR status = 'shelved');";
		}
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$stockNumber = $result['SUM(qty)'];
		}
		
		// while ($row = $result->fetch_assoc()) {
		// 	$stockNumber= $row['serial_no'];
		// }
		if(!$stockNumber) {
			$stockNumber = 0;
		}
		
		return $stockNumber;
	}
	
	function getVendor($order_type,$partid = 0) {
		$company;
		
		//Determine which order type to look for in this system
		$order_table = $order_type == 'po' ? 'purchase_items' : 'sales_items';
		$selector = $order_type == 'po' ? 'po_number' : 'so_number';
		
		//Order by the lastest order and limit the order # to 3 at a time
		$query = "SELECT ". res($selector) ." FROM " . res($order_table) . " WHERE partid = ". res($partid) ." LIMIT 1;";
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$po = $result[$selector];
		}
		
		$query = "SELECT * FROM purchase_orders WHERE po_number = '".res($po)."';";
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$company = $result['companyid'];
		}
		
		return $company;
	}
	
	//Get the past Purchase/Sales Order for each part in the inventory
	function getOrder($order_type,$partid = 0) {
		$order_array = array();
		$order_message = "";
		
		//Determine which order type to look for in this system
		$order_table = $order_type == 'po' ? 'purchase_items' : 'sales_items';
		$selector = $order_type == 'po' ? 'po_number' : 'so_number';
		
		//Order by the lastest order and limit the order # to 3 at a time
		$query = "SELECT ". res($selector) ." FROM " . res($order_table) . " WHERE partid = ". res($partid) ." ORDER BY ". res($selector) ." DESC LIMIT 5;";
		$result = qdb($query) or die(qe());
		
		while ($row = $result->fetch_assoc()) {
			$order_array[] = $row;
		}
		
		if(!empty($order_array)){
			//$order_message = strtoupper($order_type) . " Number Found";
			foreach($order_array as $item) {
				$order_message .= "<a href='order_form.php?on=$item[$selector]&ps=" . ($order_type == 'po' ? 'p' : 's') . "'>#" .$item[$selector] . "</a> ";
			}
		} else {
			//Else there is no record order number matched to this inventory addition
			//Assuming that in item was added manually
			$order_message = "No " . strtoupper($order_type) . " Orders Found";
		}
		
		return $order_message;
	}

	function getAvgPrice($partid) {
		$order_array = array();
		$avg = 0;
		$counter = 0;
		$partid = prep($partid);
		
		$query = "SELECT AVG(price) average FROM sales_items WHERE partid = $partid group by `partid`;";
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result) > 0){
			$row = mysqli_fetch_assoc($result);
			return (format_price($row['average']));
		}
		else{
			return "Never Sold";
		}
	}
	
	function getStock($stock = '', $partid = 0) {
		$stockNumber;
		
		$query  = "SELECT SUM(qty) FROM inventory WHERE partid =" . res($partid) . " AND item_condition = '" . res($stock) . "';";
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$stockNumber = $result['SUM(qty)'];
		}
		
		// while ($row = $result->fetch_assoc()) {
		// 	$stockNumber= $row['serial_no'];
		// }
		if(!$stockNumber) {
			$stockNumber = '0';
		}

		return $stockNumber;
	}
	
	function inventory_arr_out($id,$info){
		//Pass in a HECIDB Result
			$p = array();
			$s = array();
			$c = array();
		
		//Grab any filtered amounts
			
			
			$i++;
			// Macro portion of the part container
			$p['id'] = $id;
			
			//Eventually, there will need to be a photo-get Parameter passed here as well, but for now it will not be passed.
			
			$p['system'] = $info['Sys'];
			$p['part'] = $info['part'];
			$p['Manf'] = $info["Manf"];
			$p['po_history'] = getOrder('po', $id);
			$p['so_history'] = getOrder('so', $id);
			$p['conditions'] = array();
			
			$p['conditions']['new'] = getStock('new', $id);
			$p['conditions']['used'] = getStock('used', $id);
			$p['conditions']['refurbished'] = getStock('refurbished', $id);
			
			$p['company'] = getOrder('so', $id);
			
			$p['in_stock'] = getStatusStock('instock',$id);
			$p['pending'] = getStatusStock('pending', $id);
			$p['price_avg'] = getAvgPrice($id);
			$p['serials'] = array();
			
			$serials = getPartSerials($id);
			$element = 0; $page_no = 1; 
			foreach($serials as $serial){
				(($element % 5) == 0 && $element != 0 ? $page_no++ : ''); 
				$element++; 
				$s['page'] = $page_no;
				$s['id'] = $serial['id'];
				$s['serial_no'] = $serial['serial_no'];
				$s['date'] = date_format(date_create($serial['date_created']), 'm/d/Y');
				
				$p['date'] = date_format(date_create($serial['date_created']), 'm/d/Y');
				
				$s['location'] = $serial['locationid'];
				$s['qty'] = $serial['qty'];
				$s['condition'] = $serial['item_condition'];
				$s['status'] = $serial['status'];
				$s['history'] = array();
				foreach(getItemHistory($serial['id']) as $history){
					$h['date'] = date_format(date_create($history['date_changed']), 'm/d/Y');
					$h['repid'] = getRep($history['repid']);
					$h['field_changed'] = $history['field_changed'];
					$h['changed_from'] = $history['changed_from'];
					$s['history'][] = $h;
				}
				$p['serials'][] = $s;
			}
		return($p);
		}
// function inventory_out($id,$info){
	// 	$i = 0;
	// 	//Pass in a HECIDB Result
	// 		$output = '';
	// 		$serials = '';
			
	// 		$i++;
	// 		// Macro portion of the part container
			
				
	// 		//The description and image portion of the page
	// 		$output .=	"<div class='part-container' data-partid='".$id."'>";
		 
	// 		$output .= "
	// 					<div class='row partDescription' style='margin: 35px 0 0 0;'>
	// 						<div class='col-md-2 col-sm-2'>
	// 							<div class='row' style='margin: 0'>
	// 								<div class='col-md-2 col-sm-2 col-xs-2'>
	// 									<button class='btn btn-success buttonAdd' style='margin-top: 24px;'><i class='fa fa-plus' aria-hidden='true'></i></button>
	// 								</div>
	// 								<div class='col-md-10 col-sm-10 col-xs-10'>
	// 									<img class='img-responsive' src='http://placehold.it/350x150'>
	// 								</div>
	// 							</div>
	// 						</div>
	// 				";
	// 		//Outputting the information into the system name
	// 		$output .="
	// 						<div class='col-md-3 col-sm-3'>
	// 							<strong>".$info['Sys']." - ".$info['part']."</strong>
						
	// 							<hr>
	// 							Description".$info["Manf"]."<br><br>
	// 							<i>Alias: </i>
	// 						</div>
	// 		";
			
	// 		//Order History Portion
	// 		$output .= " 
	// 						<div class='col-md-2 col-sm-2'>
	// 							<strong>Order History</strong>
	// 							<hr>
	// 							<span title='Purchase Order' style='text-decoration: underline;'>PO</span>:".getOrder('po', $id)."<br>
	// 							<span title='Sales Order' style='text-decoration: underline;'>SO</span>: ".getOrder('so', $id)."
	// 						</div>
	// 		";
			
	// 		$output .= "
			
	// 						<div class='col-md-1 col-sm-1'>
	// 							<strong>Status</strong>
	// 							<hr>
	// 							<p>In Stock: <span style=;'color: #5cb85c;;'>".getStatusStock('instock', $id)."</span></p>
	// 							<p>Incoming: <span style=;'color: #f0ad4e;;'>".getStatusStock('pending', $id)."</span></p>
	// 						</div>
	// 		";
			
	// 		$output .= "
	// 						<div class='col-md-2 col-sm-2'>
	// 							<strong>Condition</strong>
	// 							<hr>
	// 							<button title='New' class='btn btn-success new_stock'>".getStock('new', $id)."</button>
	// 							<button title='Used' class='btn btn-warning used_stock'>".getStock('used', $id)."</button>
	// 							<button title='Refurbished' class='btn btn-danger refurb_stock'>".getStock('refurbished', $id)."</button>
	// 						</div>
	// 		";
			
	// 		$output .= "
	// 						<div class='col-md-2 col-sm-2'>
	// 							<strong>Cost Avg. (SO Based)</strong>
	// 							<hr>
	// 							\$1,000 - 1,500
	// 						</div>
	// 		";
			
	// 		$output .=	"</div>";
			
	// 		$output .= "
					
	// 					<div class='row addItem' style='margin-top: 60px; margin-left: 0; margin-right: 0; border: 1px solid #E7E7E7; padding: 20px; display: none;'>
	// 						<div class='row'>
	// 							<div class='col-md-12 col-sm-12'>
	// 								<button class='btn btn-success buttonAddRows btn-sm add pull-right' style='margin-right: 5px;'><i class='fa fa-plus' aria-hidden='true'></i></button>
	// 								<button class='btn btn-warning btn-sm add pull-right updateAll' style='margin-right: 5px;' disabled>Save Changes</button>
	// 								<h3>".$info['Sys']." - ". $info['part']."</h3>
	// 								<p style=''>Description Manufacture <i>Alias: David, Aaron, Andrew</i></p>
	// 							</div>
	// 						</div>
	// 		";
			
		
	// 		$output .= '
	// 						<hr>
	// 						<div class="addRows">
	// 		';				
	// 		$serials = getPartSerials($id); 
	// 		$element = 0; $page_no = 1; 
	// 		foreach($serials as $serial){
	// 			(($element % 5) == 0 && $element != 0 ? $page_no++ : ''); 
	// 			$element++; 
				
	// 		$output .= "
	// 							<div class='product-rows serial-page page-$page_no' style='padding-bottom: 10px;' data-id='".$serial['id']."'>
	// 								<div class='row'>";
	// 		$output .= "
	// 									<div class='col-md-2 col-sm-2'>
	// 										<label for='serial'>Serial/Lot Number</label>
	// 										<input class='form-control serial' type='text' name='serial' placeholder='#123' value='".$serial['serial_no']."'/>
	// 										<div class='form-text'></div>
	// 									</div>
	// 		";
			
	// 		$output .= "
	// 									<div class='col-md-2 col-sm-2'>
	// 										<label for='date'>Date</label>
	// 										<input class='form-control date' type='text' name='date' placeholder='00/00/0000' value='".date_format(date_create($serial['date_created']), 'm/d/Y')."'/>
	// 										<div class='form-text'></div>
	// 									</div>
	// 		";
			
	// 		$output .= "
	// 									<div class='col-md-2 col-sm-2'>
	// 										<label for='date'>Location</label>
	// 										<input class='form-control location' type='text' name='date' placeholder='Warehouse Location' value='".$serial['locationid']."'/>
	// 										<div class='form-text'></div>
	// 									</div>
	// 		";
			
	// 		$output .= "
	// 									<div class='col-md-1 col-sm-1'>
	// 										<label for='qty'>Qty</label>
	// 										<input class='form-control qty' type='text' name='qty' placeholder='Quantity' value='".$serial['qty']."'/>
	// 										<div class='form-text'></div>
	// 									</div>
	// 		";
			
	// 		$output .= "
	// 									<div class='col-md-2 col-sm-2'>
	// 										".dropdown("condition")."
	// 										<div class='form-text'></div>
	// 									</div>
	// 		";
			
	// 		$output .= "
	// 									<div class='col-md-1 col-sm-1'>
	// 										<div class='form-group'>
	// 											<label for='status'>status</label>
	// 											<select class='form-control status' name='status'>";
	// 												foreach(getEnumValue('inventory', 'status') as $status){
	// 													$output .= "<option ". ($status == $serial['status'] ? 'selected' : '').">$status</option>";
	// 												}
	// 		$output .=							"</select>
	// 										</div>
	// 										<div class='form-text'></div>
	// 									</div>
	// 		";
			
	// 		$output .= "
	// 									<div class='col-md-2 col-sm-2'>
	// 										<div class='row'>
	// 											<div class='col-md-7 col-sm-7'>
	// 												<label for='price'>Cost</label>
	// 												<input class='form-control cost' type='text' name='price' placeholder='$' value=''/>
	// 												<div class='form-text'></div>
	// 											</div>
	// 											<div class='col-md-5 col-sm-5'>
	// 												<label for='add-delete'>&nbsp;</label>
	// 												<div class='btn-group' role='group' name='add-delete' style='display: block;'>
	// 													<button class='btn btn-primary btn-sm update' disabled><i class='fa fa-check' aria-hidden='true'></i></button>
	// 													<button class='btn btn-danger delete btn-sm' disabled><i class='fa fa-minus' aria-hidden='true'></i></button>
	// 												</div>
	// 											</div>
	// 										</div>
	// 									</div>
	// 		";
			
	// 		$output .= "		</div>";
	// 		$output .= "
	// 									<div class='row'>
	// 										<div class='col-sm-12'>
	// 											<a href='#' class='show_history'>Show History +</a>
												
	// 											<div class='row history_listing' style='display: none;'>
	// 												<div class='col-md-12'>
	// 													<div class='table-responsive'>
	// 														<table class='table table-striped'>
	// 															<thead>
	// 																<tr>
	// 																	<th>Date</th>
	// 																	<th>Rep</th>
	// 																	<th>Field Changed</th>
	// 																	<th>History</th>
	// 																</tr>
	// 															</thead>
	// 															<tbody>";
	// 																foreach(getItemHistory($serial['id']) as $history){
	// 																	$output .= "
	// 																		<tr>
	// 																			<th>".date_format(date_create($history['date_changed']), 'm/d/Y')."</th>
	// 																			<td>".getRep($history['repid'])."</td>
	// 																			<td>".$history['field_changed']."</td>
	// 																			<td>".$history['changed_from']."</td>
	// 																		</tr>
	// 																	";
	// 																}
	// 		$output .="
	// 															</tbody>
	// 														</table>
	// 													</div>
	// 												</div>
	// 											</div>
	// 										</div>
	// 									</div>
	// 								</div>
	// 			";
	// 	}		
	// 		$output .="
	// 					<div class='col-md-12 text-center'><a class='show-more' href='#'>Show More</a></div>
	// 					</div>
	// 				</div>";
	// 	return($output);
	// 	}
	
	function search($search = '',$serial=''){
		$return = array();
		
		if ($search){
			$in = '(';
			//Get all the parts from the search
			$initial = hecidb($search);
			foreach($initial as $id =>$info){
				$in .= "'$id', ";
			}
			$in = trim($in, ", ");
			$in .= ")";
			$query  = "SELECT DISTINCT partid FROM inventory";
			$query .= " WHERE partid IN $in;";
			$result = qdb($query);
			
			if(mysqli_num_rows($result)>0){
				//Loop through the results
				foreach($result as $inv){
					$parts[$inv['partid']] = $initial[$inv['partid']];
				}
			}
			
			
		}
		else if($serial){
			$parts = array();
			$serial = prep("%".$serial."%");
			$query  = "SELECT DISTINCT `partid` FROM inventory where serial_no LIKE $serial";
			$result = qdb($query);
			foreach ($result as $part){
		    	$p = hecidb($part['partid'],'id');
		    	foreach($p as $id => $info){
		        	$parts[$id] = $info;
		    	}
			}
		}
		else{
		    $parts = array();
			$query  = "SELECT DISTINCT partid FROM inventory;";
			$result = qdb($query);
			foreach ($result as $part){
		    	$p = hecidb($part['partid'],'id');
		    	foreach($p as $id => $info){
		        	$parts[$id] = $info;
		    	}
			}
		}

		if(isset($parts)){
			foreach($parts as $id => $info){
				$return[] = inventory_arr_out($id, $info);
			}
		}
		return ($return);
	}
	
	
	// $parts_arr = array();


$search = grab('search');
$serial = grab('serial');


$return = (search($search,$serial));
// print_r($return);
echo json_encode($return);
?>