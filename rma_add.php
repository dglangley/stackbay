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

	$order_number = isset($_REQUEST['on']) ? $_REQUEST['on'] : "";
	$order_type = "rma";
	
	if(empty($order_number)) {
		header( 'Location: /operations.php' ) ;
	}
	

	//Using the order number from purchase order, get all the parts being ordered and place them on the inventory add page
	function getRMAParts ($order_number) {
		
		$listPartid;
		
		$query = "SELECT partid FROM return_items WHERE rma_number = ". res($order_number) ." GROUP BY partid;";
		$result = qdb($query);
	    
	    if($result)
	    if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$listPartid[] = $row;
			}
		}
		
		return $listPartid;
	}
	
	function getRMASerial($order_number, $partid) {
		$listSerial;
		
		$query = "SELECT * FROM return_items WHERE rma_number = ". res($order_number) ." AND partid = ". res($partid) .";";
		$result = qdb($query);
	    
	    if($result)
	    if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$listSerial[] = $row;
			}
		}
		
		return $listSerial;
	}
	
	function getSerial($invid) {
		$serial;
		
		$query = "SELECT * FROM inventory WHERE id = ". res($invid) .";";
		$result = qdb($query);
	    
	    if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$serial = $result['serial_no'];
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
	
	function address_out($address_id){
		//General function for handling the standard display of addresses
		$address = '';
		//Address Handling
		//$row = getAddresses(1);
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
	
	function getAddress($order_number) {
		$query = "SELECT * FROM returns AS r, sales_orders AS s WHERE r.rma_number = ".res($order_number)." AND r.order_number = s.so_number;";
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$address = $result['bill_to_id'];
		}
		
		$address = address_out($address);
		
		return $address;
	}
	
	function format($partid){
		$r = reset(hecidb($partid, 'id'));
	    $display = "<span class = 'descr-label'>".$r['part']." &nbsp; ".$r['heci']."</span>";
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf'])." &nbsp; ".dictionary($r['system']).'</span> <span class="description-label">'.dictionary($r['description']).'</span></div>';

	    return $display;
	}
	
	
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
			
			.inventory_add .row {
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
		</style>
	</head>
	
	<body class="sub-nav" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
	<!----------------------- Begin the header output  ----------------------->
		<div class="container-fluid pad-wrapper data-load">
		<?php include 'inc/navbar.php';?>
		<div class="row table-header" id = "order_header" style="margin: 0; width: 100%;">
			<div class="col-sm-4"><a href="/order_form.php<?php echo ($order_number != '' ? "?on=$order_number&ps=p": '?ps=p'); ?>" class="btn-flat info pull-left" style="margin-top: 10px;"><i class="fa fa-list" aria-hidden="true"></i></a></div>
			<div class="col-sm-4 text-center" style="padding-top: 5px;">
				<h2>RMA #<?php echo $order_number.' Receiving'; ?></h2>
			</div>
			<div class="col-sm-4">
				<button class="btn-flat gray pull-right btn-update" id="rma_complete" style="margin-top: 10px; margin-right: 10px;" disabled>Save</button>
			</div>
		</div>
		
		
			<!-------------------- $$ OUTPUT THE MACRO INFORMATION -------------------->
				<div class="col-md-2 rma_sidebar" data-page="addition" style="padding-top: 15px;">
					<div class="row">
						<div class="col-md-12">
							<b style="color: #526273;font-size: 14px;">RMA Order #<?php echo $order_number; ?></b><br>
							<b style="color: #526273;font-size: 12px;"><?=getRep('1');?></b><br>
							March 31, 2017<br><br>
							
	
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
							<div class="input-group" style="margin-bottom: 6px;">
							    <input class="form-control input-sm serialInput" type="text" placeholder="Serial" data-saved="" <?php echo ($part['qty'] - $part['qty_received'] == 0 ? '' : ''); ?>>
							    <span class="input-group-addon">
							        <button class="btn btn-secondary" type="button" style='display: none;' disabled><i class="fa fa-trash fa-4" aria-hidden="true"></i></button>
							        <button class="btn btn-secondary" type="button"><i style='color: green;' class="fa fa-save fa-4" aria-hidden="true"></i></button>
							    </span>
				            </div>
			            </div>
		            </div>
				</div>
			
				<div class="table-responsive">
					<table class="inventory_add table table-hover table-striped table-condensed" style="table-layout:fixed;"  id="items_table">
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
									$item = getPartName($part['partid']);
									$serials = getRMASerial($order_number, $part['partid']);
						?>
								<tr class="<?php //echo ($part['qty'] - $part['qty_received'] <= 0 ? 'order-complete' : ''); ?>">
									<td class="part_id" data-partid="<?php echo $part['partid']; ?>" data-part="<?php echo $item['part']; ?>">
										<?php 
											echo format($part['partid']);
										?>
									</td>
									<td class="serialsExpected">
										<?php 
											if(!empty($serials)):
											foreach($serials as $item) { 
										?>
											<div class="row">
												<div class="input-group">
													<span class="text-center" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=getSerial($item['inventoryid'])?></span>
													<span class="input-group-addon">
														<input class="serial-check" data-assocSerial="<?=getSerial($item['inventoryid'])?>" style="margin: 0 !important" type="checkbox">
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
										?>
											<div class="row">
												<span class="text-center location-input" data-serial="<?=getSerial($item['inventoryid'])?>" data-location="<?=(empty($item['last_return']) ? 'TBD' : getLocation($item['locationid']) )?>" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=(empty($item['last_return']) ? 'TBD' : getLocation($item['locationid']) )?></span>
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
		<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
		
		<script>
		
			//We can import this code later on into the operations js but this code is only for this page and the features on this page
			(function($){
				$('.data-load').fadeIn();
				
				//If anything was changed on the form then enable the ability to save and complete the order
				$(document).on('change', '.serial-check', function(){
					var pastLocation = 	$('.location-input[data-serial="'+$(this).data('assocserial')+'"]').data('location');
					
					$('#rma_complete').prop('disabled', false);
					$('#rma_complete').removeClass("gray");
					$('#rma_complete').addClass("success");
					
					if(!$(this).prop("checked")) {
						$('.location-input[data-serial="'+$(this).data('assocserial')+'"]').text(pastLocation);
					}
				});
				
				$(document).on('change', '.instance, .location', function(){
					$('.serialInput').focus();
				})
				
				$(document).on('keydown', '.serialInput', function(e){
					if(e.keyCode == 13) {
						if($('.place').val() != 'null') {
							var location = $('.place').val();
							var serialVal = $(this).val();
							
							if($('.instance').val() != ''){
								location += " - " + $('.instance').val();
							}
							
							if(/^\d+$/.test(serialVal)) {
								serialVal = serialVal;
							} else {
								serialVal = serialVal.toUpperCase();
							}
							//Prevent no values
							if(serialVal != '') {
								var existing = $('.serial-check[data-assocSerial="'+serialVal+'"]').length;
								if(existing == 1) {
									$('.serial-check[data-assocSerial="'+serialVal+'"]').prop('checked', true);
									$('.location-input[data-serial="'+serialVal+'"]').text(location);
									$(this).val("").focus();
									
									$('#rma_complete').prop('disabled', false);
									$('#rma_complete').removeClass("gray");
									$('#rma_complete').addClass("success");
								} else if(existing == 0) {
									modalAlertShow("Error", "No RMA Serials found for " + serialVal, false);
								} else {
									modalAlertShow("Error", "<b>Multiple</b> Serials found for " + serialVal + "<br><br> Please select the correct serial below.", false);
								}
							}
						} else {
							modalAlertShow("Error", "Locations is missing.<br><br> Please select a location and try again.", false);
						}
					}
				});
			})(jQuery);
			
			//This overwrites the other focus for the global search
			$(window).load(function(){
				$('.serialInput').focus();	
			});
		</script>
	</body>
</html>
