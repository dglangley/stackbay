<?php

//=============================================================================
//======================== Order Form General Template ========================
//=============================================================================
//  																		  |
//																			  |
//=============================================================================

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
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/locations.php';

	//Set initials to be used throughout the page
	$order_number = isset($_REQUEST['on']) ? $_REQUEST['on'] : "";
	$order_type = "rma";
	
	//Check if the page has invoked a success in saving
	$rma_updated = $_REQUEST['success'];
	
	if(empty($order_number)) {
		header( 'Location: /operations.php' ) ;
	}
	

	//Using the order number from purchase order, get all the parts being ordered and place them on the inventory add page
	function getRMAParts ($order_number) {
		
		$listPartid;
		
		//Only looking for how many parts are in the RMA, distinct as we will retrieve all the serial pertaining to the part later
		$query = "SELECT id FROM return_items WHERE rma_number = ". res($order_number) .";";
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
	function getRMAitem($rid) {
		$listSerial;
		
		$query = "SELECT * FROM return_items WHERE id = ". res($rid) .";";
		$result = qdb($query);
	    
	    if($result)
	    if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$listSerial[] = $row;
			}
		}
		
		return $listSerial;
	}
	
	//Using inventory ID this function grabs the serial_no, locationid, invid and last_return values to be used in the tables
	function getSerial($invid) {
		$serial;
		
		$query = "SELECT locationid, serial_no, returns_item_id, id FROM inventory WHERE id = ". res($invid) .";";
		$result = qdb($query);
	    
	    if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$serial = $result;
		}
		
		return $serial;
	}
	
	//Get the part name from the part id
	function getPartName($partid) {
		$part;
		
		$query = "SELECT parts.part, parts.heci, parts.description, systems.system FROM parts LEFT JOIN systems ON systems.id = parts.systemid WHERE parts.id = ". res($partid) .";";
		$result = qdb($query) OR die(qe());
	
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$part[] = $result;
		}
	
		return $part[0];
	}
	
	//This with conjunction with address out creates the standard format for printing addresses in the sidebar
	function getAddress($order_number) {
		$address;
		$query = "SELECT * FROM returns AS r, sales_orders AS s WHERE r.rma_number = ".res($order_number)." AND r.order_number = s.so_number;";
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$address = $result['bill_to_id'];
		}
		
		$address = address_out($address);
		
		return $address;
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
	
	//This formats the part information to the standard form of part heci desc with dictionary
	function format($partid){
		$r = reset(hecidb($partid, 'id'));
	    $display = "<span class = 'descr-label'>".$r['part']." &nbsp; ".$r['heci']."</span>";
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf'])." &nbsp; ".dictionary($r['system']).'</span> <span class="description-label">'.dictionary($r['description']).'</span></div>';

	    return $display;
	}
	
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
				display: none;
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
		<?php include 'inc/navbar.php';?>
		<div class="row table-header" id = "order_header" style="margin: 0; width: 100%;">
			<div class="col-sm-4"><a href="/rma.php<?php echo ($order_number != '' ? "?on=$order_number&ps=p": '?ps=p'); ?>" class="btn-flat info pull-left" style="margin-top: 10px;"><i class="fa fa-list" aria-hidden="true"></i></a></div>
			<div class="col-sm-4 text-center" style="padding-top: 5px;">
				<h2>RMA #<?php echo $order_number.' Receiving'; ?></h2>
			</div>
			<div class="col-sm-4">
				<button class="btn-flat gray pull-right btn-update" id="rma_complete" style="margin-top: 10px; margin-right: 10px;" disabled>Save</button>
			</div>
		</div>
		
		<!--Add in success message-->
		<?php if($rma_updated == 'true'): ?>
			<div id="item-updated-timer" class="alert alert-success fade in text-center" style="position: fixed; width: 100%; z-index: 9999; top: 95px;">
			    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
			    <strong>Success!</strong> <?php echo ($po_updated ? 'Purchase' : 'Sales'); ?> Order Updated.
			</div>
		<?php endif; ?>
		
		
			<!-------------------- $$ OUTPUT THE MACRO INFORMATION -------------------->
				<div class="col-md-2 rma_sidebar" data-page="addition" style="padding-top: 15px;">
					<div class="row">
						<div class="col-md-12">
							<b style="color: #526273;font-size: 14px;">RMA Order #<?php echo $order_number; ?></b><br>
							<b style="color: #526273;font-size: 12px;"><?=getRep('1');?></b><br>
							<?=getCreated($order_number);?><br><br>
							
	
							<b style="color: #526273;font-size: 14px;">CUSTOMER:</b><br>
							<span style="color: #aaa;"><?=getAddress($order_number);?></span><br><br>
							
							<b style="color: #526273;font-size: 14px;">SHIPPING ADDRESS:</b><br>
							<span style="font-size: 14px;">Ventura Telephone<br>3037 Golf Course Drive <br>
                        		Unit 2 <br>
                       		 	Ventura, CA 93003
                       		</span><br><br>
							
							<b style="color: #526273;font-size: 14px;">SHIPPING INSTRUCTIONS:</b><br>UPS Ground<br><br>
						</div>
					</div>
				</div>
				
				<div class="col-sm-10">
			
				<div class="row" style="margin: 20px 0;">
					
					<div class="col-md-7" style="padding-left: 0px !important;">
						<div class="col-md-6 location">
							<div class="row">
								<div class="col-md-6" style="padding-left: 0px !important;">
									<?=loc_dropdowns('place')?>
								</div>
								
								<div class="col-md-6">
									<?=loc_dropdowns('instance')?>
								</div>
							</div>
						</div>
						
						<div class="col-md-6" style="padding: 0 0 0 5px;">
						    <input class="form-control input-sm serialInput" type="text" placeholder="Serial" data-saved="" <?php echo ($part['qty'] - $part['qty_received'] == 0 ? '' : ''); ?>>
			            </div>
		            </div>
				</div>
			
				<div class="table-responsive">
					<table class="rma_add table table-hover table-striped table-condensed" style="table-layout:fixed;"  id="items_table">
						<thead>
					         <tr>
					            <th class="col-sm-2">
					            	PART	
					            </th>
					            <th class="text-center col-sm-2">
									RMA Serial
					        	</th>
					        	<th class="col-sm-4">
									Reason
					        	</th>
					        	<th class="text-center col-sm-2">
									Disposition
					        	</th>
					        	<th class="text-center col-sm-2">
									Location
					        	</th>
					         </tr>
						</thead>
						
						<tbody>
						<?php 
							//Grab all the parts from the specified PO #
							if(!empty($partsListing)) {
								foreach($partsListing as $part): 
									$serials = getRMAitem($part['id']);
						?>
								<tr>
									<td>
										<?php 
											echo format($part['partid']);
										?>
									</td>
									<td class="serialsExpected">
										<?php 
											if(!empty($serials)):
											foreach($serials as $item) { 
												$serialData = getSerial($item['inventoryid']);
												
										?>
											<div class="row">
												<div class="input-group serial-<?=$serialData['serial_no'];?>">
													<span class="text-center" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=$serialData['serial_no'];?></span>
													<span class="input-group-addon">
														<input class="serial-check" data-rmaid="<?=$item['id'];?>" data-invid="<?=$serialData['id'];?>" data-locationid="<?=$serialData['locationid'];?>" data-place="" data-instance="" data-assocSerial="<?=$serialData['serial_no'];?>" data-partid="<?=$part['partid'];?>" style="margin: 0 !important" type="checkbox" <?=($order_number == $serialData['last_return'] ? 'checked disabled' : '');?>>
													</span>
												</div>
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
												<span class="truncate" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=$item['reason']?></span>
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
												<span class="text-center" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=($item['dispositionid'] ? 'Add Disposition Here' : 'None' )?></span>
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
												$serialData = getSerial($item['inventoryid']);
										?>
											<div class="row">
												<span class="text-center <?=(empty($serialData['last_return']) ? 'location-input' : ''); ?>" data-location="<?=(empty($serialData['last_return']) ? 'TBD' : display_location($serialData['locationid']) )?>" data-serial="<?=$serialData['serial_no'];?>" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=(empty($serialData['last_return']) ? 'TBD' : display_location($serialData['locationid']) )?></span>
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
			</div>
		</div> 
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js"></script>
		
		<script>
			

			//If the order has been updated allow the success message to show for a shrot time
			$('#item-updated-timer').delay(1000).fadeOut('fast');
			
			//Fade in the page after the load has happened.... Avoids elements jumping around upon loading in
			$('.data-load').fadeIn();
			

		</script>
	</body>
</html>
