<?php

//==============================================================================
//============================ RMA ADDITION SCREEN  ============================
//==============================================================================
//	This screen works with the handling of rma items once they have been 	   |
//	recorded as return items. Originally created by Andrew in early January '17|
//==============================================================================

	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/dictionary.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/operations_sidebar.php'; 
	include_once $rootdir.'/inc/display_part.php'; 
	include_once $rootdir.'/inc/getDisposition.php';
	include_once $rootdir.'/inc/credit_creation.php';


	//Set initials to be used throughout the page
	$order_number = grab('on');
	$order_type = "rma";
	
	//Variables used for the post save
	$rma_serial = '';
	$invid = '';
	$itemLocation = '';
	$errorHandler = '';
	$errorHandler = '';
	$place = '';
	$instance = '';
	$rmaArray = array();
	// print_r($_REQUEST);
	$ro_number = getOrderNum($order_number);
	$partid;

	//echo $ro_number;
	
	//If this is a form which sumbits upon itself
	if((grab('rmaid') || grab('invid')) && !grab('exchange_trigger')) {
		$rma_serial = strtoupper(grab('rmaid'));
		$invid = grab('invid');
		// exit($invid);

			//Get the initial Sales Item 
			$query = "SELECT serial_no, sales_item_id, returns_item_id, partid FROM inventory WHERE id = ".prep($invid).";";
			$serial_find = qdb($query) or die(qe());
			if (mysqli_num_rows($serial_find)) {
				$serial_find = mysqli_fetch_assoc($serial_find);
				$rma_serial = $serial_find['serial_no'];
				$sales_item_id = $serial_find['sales_item_id'];
				$partid = $serial_find['partid'];
			}

		
		
		$place = grab('place');
		$instance = grab('instance');
		
		$itemLocation = dropdown_processor($place,$instance);
		
		//Find the items pertaining to the RMA number and the serial searched
		$rmaArray = findRMAItems($rma_serial, $order_number);
	
		// print_r($rmaArray);
		if(empty($itemLocation)) {
			$errorHandler = "Locations can not be empty.";
		} else {
			//Check if there is 1, multiple, or none found
			if(count($rmaArray) == 1 || $invid != '') {
				$errorHandler = savetoDatabase($itemLocation, reset($rmaArray), $invid);
				if($sales_item_id){
					$si_line = "
					SELECT so_number FROM sales_items WHERE `id` = ".prep($sales_item_id).";
					";
					$si_result = qdb($si_line);
					if (mysqli_num_rows($si_result)){
						$si_result = mysqli_fetch_assoc($si_result);
						$so_number = $si_result['so_number'];
						if(all_credit_received($order_number)){
							credit_creation($so_number, "sales",$order_number);
						}
					}
				}else{
					//exit('This part was never sold');
				}
				
				//Clear values after save
				if($errorHandler == '') {
					$rma_serial = '';
					$invid = '';
					//$itemLocation = '';
				}
			} else if(count($rmaArray) > 1) {
				$errorHandler = "Multiple items found for serial: " . $rma_serial . ". Please select the correct one using the list below.";
			} else {
				$errorHandler = "No items found for serial: " . $rma_serial;
			}
			
		}
	} else if(grab('exchange_trigger')) {
		$new_so;
		
		//Insertion of the values of from the exhange parameters
		$insert = "INSERT INTO `sales_items` (`partid`, `so_number`, `line_number`, `qty`, `qty_shipped`, `price`, `delivery_date`, `ship_date`, `ref_2`, `ref_2_label`, `warranty`, `conditionid`)
		SELECT s.`partid`, s.`so_number`, s.`line_number`, 1 AS `qty`, 0 AS `qty_shipped`, 0.00 AS `price`, `delivery_date`, `ship_date`, `inventory`.`sales_item_id` AS `ref_1`, 'sales_item_id' AS `ref_1_label`, `warranty`, s.`conditionid`
		FROM `inventory`, `sales_items` s WHERE `inventory`.`id` = ".$_POST['exchange_trigger']." AND `sales_item_id` = s.`id`;";
		qdb($insert);
		$exchangeid = qid();
		
		$query = "SELECT so_number FROM sales_items WHERE id = ".res($exchangeid).";";
		
		$result = qdb($query) or die(qe());
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$new_so = $result['so_number'];
		}
		
		header("Location: /shipping.php?on=".$new_so."&exchange=true");
		//print_r($exchangeid); die;
	} else if(grab('repair_trigger')){
		$ro_number;
		$order_result = array();

		//Grab from RO data that is populated from there
		$query = "SELECT rma.*, ri.* FROM returns rma, return_items ri WHERE rma.rma_number = ri.rma_number AND rma.rma_number = ".prep($order_number).";";

		$result = qdb($query) OR die(qe() . ' ' . $query);

		if (mysqli_num_rows($result)) {

			$result = mysqli_fetch_assoc($result);
			//print_r($result);

			//If an order exists for this RMA then retrieve all the information we need for this order
			if($result['order_type'] == 'Sale' && $result['order_number']) {

				$query = "SELECT * FROM sales_orders so, sales_items si WHERE so.so_number = si.so_number AND so.so_number = ".prep($result['order_number']).";";

				$order_result = qdb($query) OR die(qe() . ' ' . $query);
				if (mysqli_num_rows($order_result)) {
					$order_result = mysqli_fetch_assoc($order_result);
				}

			} else {
				$query = "SELECT * FROM repair_orders ro, repair_items ri WHERE ro.ro_number = ri.ro_number AND ro.ro_number = ".prep($ro_number).";";

				$order_result = qdb($query) OR die(qe() . ' ' . $query);
				if (mysqli_num_rows($order_result)) {
					$order_result = mysqli_fetch_assoc($order_result);
				}
			}

			//print_r($order_result);

			$insert = "INSERT INTO repair_orders (created, created_by, sales_rep_id, companyid, contactid, cust_ref, bill_to_id, ship_to_id, freight_carrier_id, freight_services_id, freight_account_id, termsid, public_notes, private_notes, repair_code_id, status) VALUES (
				".prep($now).",
				".prep($U['id']).",
				".prep($order_result['sales_rep_id']).",
				".prep($result['companyid']).",
				".prep($result['contactid']).",
				".prep('RMA#' . $order_number).",
				".prep($order_result['bill_to_id']).",
				".prep($order_result['ship_to_id']).",
				".prep($order_result['freight_carrier_id']).",
				".prep($order_result['freight_services_id']).",
				".prep($order_result['freight_account_id']).",
				".prep('15').",
				".prep($order_result['public_notes']).",
				".prep($order_result['private_notes']).",
				NULL,
				'Active'
				);";
			//echo $insert . '<br><br>';
			qdb($insert);
			$ro_number = qid();

			$insert = "INSERT INTO repair_items (partid, ro_number, line_number, qty, price, due_date, invid, ref_1, ref_1_label, ref_2, ref_2_label, warrantyid, notes) VALUES (
				".prep($result['partid']).",
				".prep($ro_number).",
				'1',
				".prep($result['qty']).",
				".prep($order_result['price']).",
				".prep($order_result['due_date']).",
				".prep($result['inventoryid']).",";

			//If Ref_ is null we will use the label as the pointer to rma	
			if(!$result['ref_1']) {
				$insert .=	prep($result['id']).",
					".prep('return_item_id').",";
			} else {
				$insert .=	prep($result['ref_1']).",
					".prep($result['ref_1_label']).",";
			}

			//If Ref_1 was used and ref_2 is null we will use ref 2 instead else we used ref 1 already and will ignore ref 2
			if(!$result['ref_2'] && $result['ref_1']) {
				$insert .=	prep($result['id']).",
					".prep('return_item_id').",";
			} else {
				$insert .=	prep($result['ref_2']).",
					".prep($result['ref_2_label']).",";
			}

			$insert .=	prep($order_result['warranty']).",
				".prep($order_result['notes'])."
				);";

			//echo $insert;
			qdb($insert);
			$repair_item_id = qid();

			// $update = "UPDATE inventory SET repair_item_id = ".prep($repair_item_id).", status = 'in repair' WHERE id = ".prep($result['inventoryid']).";";
			// qdb($update);
		}

		header("Location: /order_form.php?ps=repair&on=" . $ro_number);
	}
	
	//Check if the page has invoked a success in saving
	$rma_updated = $_REQUEST['success'];
	
	if(empty($order_number)) {
		header( 'Location: /operations.php' ) ;
	}
	

	//Using the order number from purchase order, get all the parts being ordered and place them on the inventory add page
	function getRMAParts ($order_number) {
		
		$listPartid;
		
		//Only looking for how many parts are in the RMA, distinct as we will retrieve all the serial pertaining to the part later
		$query = "SELECT id, partid FROM return_items WHERE rma_number = ". res($order_number) ." GROUP BY partid;";
		$result = qdb($query);
	    
	    if($result)
	    if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$listPartid[] = $row;
			}
		}
		
		return $listPartid;
	}
	
	//This grabs the return specific items based on the rma_number and partid (Used to grab inventoryid for the same part only)
	function getRMAitems($partid, $order_number) {
		$listSerial;
		
		$query = "SELECT DISTINCT i.serial_no, i.locationid, r.reason, i.returns_item_id, r.inventoryid, r.dispositionid, r.id FROM return_items  as r, inventory as i WHERE r.partid = ". res($partid) ." AND i.id = r.inventoryid AND r.rma_number = ".res($order_number).";";
		$result = qdb($query);
	    
	    if($result)
	    if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$listSerial[] = $row;
			}
		}
		
		return $listSerial;
	}
	

	//This is solely used to grab the date the order was created "RMA" for sidebar usage
	function getCreated($order_number) {
		$date;
		$query = "SELECT * FROM returns WHERE rma_number = ".res($order_number).";";
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$date = $result['created'];
		}
		
		$date = date_format(date_create($date), "M j, Y");
		
		return $date;
	}
	
	
	
	//This attempts to find all the items pertaining to the Serial & PartID matching the inventory to return item table
	function findRMAItems($search, $order_number, $type = 'all'){
		$rma_search = array();
		$query = '';
		
		if($type == 'all'){
			$query = "SELECT r.id as rmaid, r.inventoryid, disposition, dispositionid FROM inventory as i, return_items as r, dispositions as d WHERE i.serial_no = '".res($search)."' AND d.id = dispositionid AND r.inventoryid = i.id AND r.rma_number = ".res($order_number).";";
		} else {
			$query = "SELECT r.id as rmaid, r.inventoryid, disposition, dispositionid FROM inventory as i, return_items as r, dispositions as d WHERE i.serial_no = '".res($search)."' AND d.id = dispositionid AND r.inventoryid = i.id AND i.returns_item_id is NULL AND r.rma_number = ".res($order_number).";";
		}
		//Query or pass back error
		$result = qdb($query) or die(qe());
		
		while ($row = $result->fetch_assoc()) {
			$rma_search[] = $row;
		}
		
		return $rma_search;
	}
	
	function savetoDatabase($locationid, $data, $invid = ''){
		global $ro_number;

		$result;
		$receive_check;
		$query;
		$id;
		
		if($invid != '') {
			$id = $invid;
		} else {
			$id = $data['inventoryid'];
		}
		
		//Check to see if the item has been received
		$query = "SELECT * FROM inventory WHERE id = '". res($id) ."' AND returns_item_id is NULL;";
		$receive_check = qdb($query);
		
		if (mysqli_num_rows($receive_check)>0) {
			if($ro_number) {
				$query = "UPDATE inventory SET returns_item_id = ". res($data['rmaid']) .", status = 'received', qty = 1, locationid = '". res($locationid) ."', conditionid = '8' WHERE id = '". res($id) ."';";
			} else {
				$query = "UPDATE inventory SET returns_item_id = ". res($data['rmaid']) .", status = 'received', qty = 1, locationid = '". res($locationid) ."', conditionid = '-1' WHERE id = '". res($id) ."';";
			}
			//$query = "UPDATE inventory SET returns_item_id = ". res($data['rmaid']) .", status = 'received', locationid = '". res($locationid) ."', conditionid = '-1' WHERE id = '". res($id) ."';";
			//Query or pass back error
			$result = (qdb($query) ? '' : qe());
		} else {
			$result = "Item has already been received.";
		}

		return $result;
	}

	function getOrderNum($number) {
		$ro_number;

		$query = "SELECT order_number FROM returns WHERE rma_number = ".prep($number).";";
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$ro_number = $result['order_number'];
		}

		return $ro_number;
	}
	
	//parameter id if left blank will pull everything else if id is specified then it will give the disposition value
	

	//Grab all parts of the RMA
	$partsListing = getRMAParts($order_number);
?>

<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<title>RMA Receive <?=($order_number != 'New' ? '#' . $order_number : '')?></title>
		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
		<style type="text/css">
			.table td {
				vertical-align: top !important;
				padding-top: 10px !important;
				padding-bottom: 0px !important;
			}
			
			.btn-secondary {
				/*color: #373a3c;*/
				background-color: transparent;
				border: 0;
				padding: 0;
				line-height: 0;
			}
			
			.table .order-complete td {
				background-color: #efefef !important;
			}
			
			.infiniteLocations select {
				margin-bottom: 5px;
    			height: 31px;
			}
			
			.truncate {
				max-width: 100%;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}
			
			.rma_add .row {
				margin: 0;
			}
			
			.container-fluid {
				height: 100%;
			}
			
			.rma_sidebar {
				background: #efefef;
				height: 100%;
			}
			
			.serialsExpected .input-group-addon {
				background-color: transparent !important;
				border: 0;
				padding: 0;
				padding-right: 15px;
			}
			
			.data-load {
				/*display: none;*/
			}
			
			.serialInput {
				text-transform: uppercase;
			}
			body.modal-open#rma-add {
				margin-right: 0;
			}
		</style>
	</head>
	
	<body class="sub-nav" id="rma-add" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
	<!----------------------- Begin the header output  ----------------------->
		<div class="container-fluid pad-wrapper data-load">
		<?php include 'inc/navbar.php'; include 'modal/package.php';?>
		<div class="row table-header" id = "order_header" style="margin: 0; width: 100%;">
			<div class="col-sm-4"><a href="/rma.php?rma=<?=$order_number;?>" class="btn-flat info pull-left" style="margin-top: 10px;"><i class="fa fa-list" aria-hidden="true"></i> Manage RMA</a></div>
			<div class="col-sm-4 text-center" style="padding-top: 5px;">
				<h2>RMA #<?= $order_number.' Receiving'; ?></h2>
			</div>
			<div class="col-sm-4">
			<!--	<button class="btn-flat gray pull-right btn-update" id="rma_complete" style="margin-top: 10px; margin-right: 10px;" disabled>Save</button>-->
			</div>
		</div>
		
		<!--Add in Error message-->
		<?php if($errorHandler != ''): ?>
			<div id="item-updated-timer" class="alert alert-danger fade in text-center" style="margin-bottom: 0px; width: 100%; z-index: 9999; top: 95px;">
			    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
			    <strong>Error!</strong> <?=$errorHandler;?>
			</div>
		<?php endif; ?>
		
			<!-------------------- $$ OUTPUT THE MACRO INFORMATION -------------------->
				<div class="col-md-2 rma_sidebar" data-page="addition" style="padding-top: 15px;">
						<?=sidebar_out($ro_number,"RMA","RMA_display")?>
			</div>
				
			<div class="col-sm-10">
				<form id="rma_add" method="post">
				<?php 
					//Grab all the parts from the specified PO #
					$init = true;
					$rma_status = true;
					if(!empty($partsListing)) {
						foreach($partsListing as $part): 
							$serials = getRMAitems($part['partid'],$order_number);

						if($init):
							foreach($serials as $item){
								if(!$item['returns_item_id']) {
									$rma_status = false;
									break;
								}

							}

				?>
				<div class = 'row' style='padding-top:10px;'>
					<div class="col-sm-12">
						<div class="btn-group box_group" style = "padding-bottom:16px;">
							<button type="button" class="btn btn-warning box_edit" title = 'Edit Selected Box'>
								<i class="fa fa-pencil fa-4" aria-hidden="true"></i>
							</button>
							<?php

								$select = "SELECT * FROM `packages`  WHERE  `order_number` = '$order_number'";
								$results = qdb($select) or die(qe()." ".$select);
								
								//Check for any open items to be shipped
								if (mysqli_num_rows($results) > 0){
									//Initialize
									$init = true;
									$package_no = 0;
									
									$masters = master_packages($order_number,$order_type);
									foreach($results as $b){
										$package_no = $b['package_no'];
										$box_button = "<button type='button' class='btn ";
										
										//Build classes for the box buttons based off data-options
										$box_button .= 'btn-grey'; //If the button has been shipped
										$box_button .= (($b['datetime'] == '' && $init) ? ' active' : ''); //If the box is active, indicate that
										$box_button .= (in_array($package_no,$masters)) ? ' master-package ' : '';
										$box_button .= " box_selector'";
										
										//Add Data tags for the future population of modals
										$box_button .= " data-width = '".$b['weight']."' data-l = '".$b['length']."' ";
										$box_button .= " data-h = '".$b['height']."' data-weight = '".$b['weight']."' ";
										$box_button .= " data-row-id = '".$b['id']."' data-tracking = '".$b['tracking_no']."' ";
										$box_button .= " data-row-freight = '".$b['freight_amount']."'";
										$box_button .= " data-order-number='" . $order_number . "'";
										$box_button .= " data-box-shipped ='".($b['datetime'] ? $b['datetime'] : '')."' >".$b['package_no']."</button>";
										echo($box_button);
			                        	
			                        	$box_list .= "<option value='".$b['id']."'>Box ".$b['package_no']."</option>";
			                        	if($b['datetime'] == '' && $init)
			                        		$init = false;
									}
									

								} else {
									$insert = "INSERT INTO `packages`(`order_number`,`order_type`,`package_no`,`datetime`) VALUES ($order_number,'$order_type','1',NOW());";
									qdb($insert) or die(qe());
									echo("<button type='button' class='btn active box_selector master-package' data-row-id = '".qid()."'>1</button>");
								}
	
							?>
							<button type="button" class="btn btn-primary box_addition" title = "">
						  		<i class="fa fa-plus fa-4" aria-hidden="true"></i>
					  		</button>
						</div>

					</div>
				</div>
					<div class="row" style="margin: 20px 0;">
						
						<?php if(!$rma_status) { ?>
						
							<div class="col-md-7" style="padding-left: 0px !important;">
								<div class="col-md-6 location">
									<div class="row">
										<div class="col-md-6" style="padding-left: 0px !important;">
											<?=loc_dropdowns('place', $itemLocation)?>
											<?=$locationid;?>
										</div>
										
										<div class="col-md-6">
											<?=loc_dropdowns('instance', $itemLocation)?>
										</div>
									</div>
								</div>
								
								<div class="col-md-6" style="padding: 0 0 0 5px;">
								    <input class="form-control input-sm serialInput auto-focus" name="rmaid" type="text" placeholder="Serial" value="<?=($rma_serial ? $rma_serial : '');?>" autofocus>
					            </div>
				            </div>

				        <?php } ?>

					</div>
				
					<div class="table-responsive">
						<table class="rma_add table table-hover table-striped table-condensed" style="table-layout:fixed;"  id="items_table">
							<thead>
						         <tr>
						            <th class="col-sm-2">
						            	PART	
						            </th>
						            <th class="text-center col-sm-1">
										RMA Serial
						        	</th>
						        	<th class="text-center col-sm-1">
										Warr Exp
						        	</th>
						        	<th class="text-center col-sm-1">
										Disposition
						        	</th>
						        	<th class="col-sm-3">
										Reason
						        	</th>
						        	<th class="text-center col-sm-2">
										Location
						        	</th>
						        	<th class="text-center col-sm-1">
										Vendor Warr Exp
						        	</th>
						        	<th class="text-right col-sm-1">
						        		Action
						        	</th>
						         </tr>
							</thead>
							
							<tbody>

						<?php endif; ?>

								<tr>
									<td>
										<?php 
											echo display_part(current(hecidb($part['partid'],'id')));
										?>
									</td>
									<td class="serialsExpected">
										<?php 
											if(!empty($serials)):
												foreach($serials as $item) { 
										?>
												<div class="row">
													<!--<div class="input-group">-->
														<span class="text-center" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=$item['serial_no'];?></span>
														<!--<span class="input-group-addon">-->
													
														<!--</span>-->
													<!--</div>-->
												</div>
										<?php 
												} 
											endif;
										?>
									</td>
									
									<td class="warranty">
										<?php 
											if(!empty($serials)):
											foreach($serials as $item) { 
										?>
											<div class="row">
												<span class="text-center" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=calcWarranty($item['inventoryid'], 'sales');?></span>
											</div>	
										<?php 
											} 
											endif;
										?>
									</td>
																		
									<td class="disposition">
										<?php 
											if(!empty($serials)):
											foreach($serials as $item) { 
										?>
											<div class="row">
												<span class="text-center" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=($item['dispositionid'] ? getDisposition($item['dispositionid']) : 'None' )?></span>
											</div>	
										<?php 
											} 
											endif;
										?>
									</td>
									
									<td class="reason">
										<?php 
											if(!empty($serials)):
												foreach($serials as $item) { 
										?>
												<div class="row">
													<span class="truncate" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=($item['reason']? $item['reason'] : 'No reason given'); ?></span>
												</div>	
										<?php 
												} 
											endif;
										?>
									</td>
									
									<td>
										<?php 
											if(!empty($serials)):
												foreach($serials as $item) { 
										?>
												<div class="row">
													<span class="text-center <?=(empty($item['last_return']) ? 'location-input' : ''); ?>" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=(empty($item['returns_item_id']) ? 'TBD' : display_location($item['locationid']) )?></span>
												</div>	
										<?php 
												} 
											endif;
										?>
									</td>
									
									<td class="vwarranty">
										<?php 
											if(!empty($serials)):
											foreach($serials as $item) { 
										?>
											<div class="row">
												<span class="text-center" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=calcWarranty($item['inventoryid'], 'purchase');?></span>
											</div>	
										<?php 
											} 
											endif;
										?>
									</td>
									
									<td>
										<?php 
											if(!empty($serials)):
											foreach($serials as $item) { 
										?>
										<div class="row text-center">
											<?php if(!$item['returns_item_id']) { ?>
												<button style="padding: 7px; margin-bottom: 5px; float: right; margin-left: 5px;" class="serial-check btn btn-flat btn-sm  <?=($item['returns_item_id'] ? 'active' : '');?>" type="submit" name='invid' value="<?=$item['inventoryid'];?>" data-toggle="tooltip" data-placement="bottom" title="Receive">
													<i class="fa fa-truck"></i>
													</button>
											<?php 
												} else if(getDisposition($item['dispositionid']) == 'Repair') { 
													$linked_ro;
													//Check and see if the repair order has already been created for this line item
													$query = "SELECT ro_number FROM repair_items WHERE (ref_1_label = 'return_item_id' OR ref_2_label = 'return_item_id') AND (ref_1 = ".prep($item['id'])." OR ref_2 = ".prep($item['id']).");";

													$ro_result = qdb($query) or die(qe());

													if (mysqli_num_rows($ro_result)) {
														$ro_result = mysqli_fetch_assoc($ro_result);
														$linked_ro = $ro_result['ro_number'];
													}

													if($linked_ro): 
											?>
												<a href="/order_form.php?ps=repair&on=<?=$linked_ro?>" style="padding: 7px; margin-bottom: 5px; float: right;" class="serial-check btn btn-flat btn-sm" name='repair_trigger' data-toggle="tooltip" data-placement="bottom" title="Repair" value="<?=$item['inventoryid'];?>"><i class="fa fa-wrench" aria-hidden="true"></i></a>		
											<?php else: ?>
											<!--</form>-->
												<button style="padding: 7px; margin-bottom: 5px; float: right;" class="serial-check btn btn-flat btn-sm" type="submit" name='repair_trigger' data-toggle="tooltip" data-placement="bottom" title="Repair" value="<?=$item['inventoryid'];?>"><i class="fa fa-wrench" aria-hidden="true"></i></button>
											
											<?php endif; }?>
											<?php if(getDisposition($item['dispositionid']) == 'Exchange') { ?>
											<!--<form action="/shipping.php" method="post" style='float: right;'>-->
												<button id="exchange" style="padding: 7px; margin-bottom: 5px; float: right;" class="serial-check btn gray btn-flat btn-sm" type="submit" name='exchange_trigger' data-toggle="tooltip" data-placement="bottom" title="Send Replacement" value="<?=$item['inventoryid'];?>"><i class="fa fa-share" aria-hidden="true"></i></button>
											<?php } ?>
											
										</div>
										<?php 
											} 
											endif;
										?>
									</td>
								</tr>
							<?php 
									endforeach;
								} 
							?>
						</tbody>
					</table>
				</div>
				</form>
			</div>
		</div> 
		
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js"></script>
		<script type="text/javascript">
			$("#exchange").click(function(event){
				if (confirm("Confirm to Send Replacement")){
					$('form#rma_add').submit();
				} else {
					event.preventDefault();
				}
			});
		</script>
	
	</body>
</html>
