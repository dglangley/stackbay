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
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getCondition.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/operations_sidebar.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/getOrderStatus.php';
	include_once $rootdir.'/inc/packages.php';
	//include_once $rootdir.'/inc/order-creation.php';
	
	$order_number = grab('on');
	$order_type = "Purchase";


	//Using the order number from purchase order, get all the parts being ordered and place them on the inventory add page
	function getPOParts () {
		global $order_number;
		
		$listParts = array();
		if($order_number){
			$query = "SELECT * FROM purchase_items WHERE po_number = ". res($order_number) ." AND qty != qty_received;";
			$result = qdb($query);
			while ($row = $result->fetch_assoc()) {
				$listParts[] = $row;
			}
		
			$query = "SELECT * FROM purchase_items WHERE po_number = ". res($order_number) ." AND qty = qty_received;";
			$result = qdb($query);
	    
			while ($row = $result->fetch_assoc()) {
				$listParts[] = $row;
			}
		}
		return $listParts;
	}
	
	function getHistory($partid, $order_number) {
		$listSerials;
		
		$query = "
			SELECT serial_no, i.id, i.qty, status, locationid, i.conditionid 
			FROM inventory_history ih, purchase_items p, inventory i 
			WHERE po_number = ". prep($order_number) ."
			AND p.partid = ". prep($partid) ." 
			AND ih.field_changed = 'purchase_item_id'
			AND ih.value = p.id AND ih.invid = i.id;
		";

		$result = qdb($query);

	    
	    if($result)
		    if (mysqli_num_rows($result)>0) {
				foreach($result as $row) {
					$listSerials[] = $row;
				}
			}
		
		return $listSerials;
	}

	$partsListing = getPOParts();
	$status = getOrderStatus($order_type,$order_number);
?>

<!DOCTYPE html>
<html>
	<head>
		<?php include_once $rootdir.'/inc/scripts.php';?>
		<title>Outstanding PO <?=($order_number != 'New' ? '#' . $order_number : '')?></title>
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
		</style>
	</head>
	
	<body class="sub-nav" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
		
	<!----------------------- Begin the header output  ----------------------->
	<div class="pad-wrapper">
		<?php 
			include 'inc/navbar.php';
			include_once $rootdir.'/modal/package.php';
		?>
		
		<form action="/order_form.php?ps=RTV&on=<?=$order_number?>" method="post" style="height: 100%;">
			
			<div class="row table-header" id = "order_header" style="margin: 0; width: 100%;">
				<div class="col-sm-4">
					<?php if(in_array("3", $USER_ROLES) || in_array("1", $USER_ROLES)) { ?>
					<a href="/order_form.php<?php echo ($order_number != '' ? "?on=$order_number&ps=p": '?ps=p'); ?>" class="btn-flat info pull-left" style="margin-top: 10px;"><i class="fa fa-list" aria-hidden="true"></i></a>
					<?php } ?>
					<button type="submit" class="btn-flat btn-sm primary pull-left" id = "rtv_button" data-validation="left-side-main" style="margin-top:10px;display:none;">RTV</button>
					
					</div>
				<div class="col-sm-4 text-center" style="padding-top: 5px;">
					<h2>
						<?php echo ($order_number != '' ? 'PO #'.$order_number.' Receiving' : 'Inventory Addition'); ?>
						<?=(strtolower($status) == 'void')?("<b><span style='color:red;'> [VOIDED]</span></b>") : "";?>
					</h2>
					
				</div>
				<div class="col-sm-4">
				</div>
			</div>
			
				<!-------------------- $$ OUTPUT THE MACRO INFORMATION -------------------->
			<div class="left-side-main col-md-2" data-page="addition" style="height: 100%;">
				<?=sidebar_out($order_number, $order_type,'display')?>
			</div>
					
			<div class="col-sm-10">
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
									
									$masters = master_packages($order_number,$o['type']);
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
									$insert = "INSERT INTO `packages`(`order_number`,`package_no`) VALUES ($order_number, '1');";
									qdb($insert);
									echo("<button type='button' class='btn active box_selector master-package' data-row-id = '".qid()."'>1</button>");
								}
	
							?>
							<button type="button" class="btn btn-primary box_addition" title = "">
						  		<i class="fa fa-plus fa-4" aria-hidden="true"></i>
					  		</button>
						</div>

					</div>
				</div>
				<div class="table-responsive">
					<table class="inventory_add table table-hover table-striped table-condensed" style="table-layout:fixed;"  id="items_table">
						<thead>
					         <tr>
					            <th class="col-sm-3">
					            	PART	
					            </th>
					            <th class="col-sm-2">
									Location
								</th>
			                    <th class="col-sm-1">
									Condition
					        	</th>
								<th class="col-sm-2">
					            	Serial
					            </th>
					            <th class="col-sm-1 text-center">
									Ordered
					        	</th>
					        	<th class="col-sm-1">
									Outstanding
					        	</th>
					        	<th class="col-sm-1 text-center">
									RTV
					        	</th>
					        	<th class="col-sm-1">
									Vendor Warr
					        	</th>
					            <th class="col-sm-1">
					            	
					        	</th>
					         </tr>
						</thead>
						
						<tbody>
						<?php 
							//Grab all the parts from the specified PO #
							if($partsListing) {
								foreach($partsListing as $part): 
									$item = getPart($part['partid'],'part');
						?>
								<tr class="<?php echo ($part['qty'] - $part['qty_received'] <= 0 ? 'order-complete' : ''); ?>">
									<td class="part_id" data-partid="<?php echo $part['partid']; ?>" data-part="<?=$item?>">
										<?=display_part(current(hecidb($part['partid'],'id')));?>
									</td>
									<td  class="infiniteLocations">
										<div class="row row-fluid">
											<div class="locations_tracker" data-serial="">
												<div class="col-md-6 locations" style="padding: 0 0 0 5px;">
													<?=loc_dropdowns('place')?>
												</div>
												<div class="col-md-6 instances" style="padding: 0 0 0 5px">
													<?=loc_dropdowns('instance')?>
												</div>
											</div>
										</div>
									</td>
									<td class="infiniteCondition">
										<select class="form-control condition_field conditionid input-sm" name="conditionid" data-serial="" style="margin-bottom: 5px; height: 31px;" <?php echo ($part['qty'] - $part['qty_received'] == 0 ? 'disabled' : ''); ?>>
											<?php
												getCondition();//init all conditions
												foreach($CONDITIONS as $conditionid => $cond):
											?>
												<option <?php echo ($conditionid == $part['conditionid'] ? 'selected' : '') ?> value="<?php echo $conditionid; ?>"><?php echo $cond; ?></option>
											<?php endforeach; ?>
										</select>
									</td>
									<td class="infiniteSerials">
										<div class="input-group" style="margin-bottom: 6px;">
										    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-saved="" data-item-id="<?php echo $part['id']; ?>" <?php echo ($part['qty'] - $part['qty_received'] == 0 ? '' : ''); ?>>
										    <span class="input-group-addon">
										        <button class="btn btn-secondary deleteSerialRow" type="button" style='display: none;' disabled><i class="fa fa-trash fa-4" aria-hidden="true"></i></button>
										        <button class="btn btn-secondary updateSerialRow" type="button"><i style='color: green;' class="fa fa-save fa-4" aria-hidden="true"></i></button>
										    </span>
							            </div>
									</td>
									<td class="text-center" style="padding-top: 15px !important;">
										<?=$part['qty'];?>
									</td>
									<td class="remaining_qty">
										<input style="margin-bottom: 6px;" class="form-control input-sm" data-qty="" name="qty" placeholder="LOT QTY" value="<?php echo($part['qty'] - $part['qty_received'] <= 0 ? 0 : $part['qty'] - $part['qty_received']); ?>" readonly>
							
										<div class='infiniteComments'>
									    	<!--<input style='margin-bottom: 10px;' class="form-control input-sm iso_comment" type="text" name="partComment" value="" placeholder="Comments" data-serial='' data-inv-id='' data-part="">-->
										</div>
									</td>
									<td>
										<input style="margin: 0 auto; display: block; margin-top: 10px;" class='RTV_check' type="checkbox" name='partid[<?=$part['id'];?>][<?=$part['qty_received']?>]' value="<?=$part['partid'];?>">
									</td>
									<td>
										<?=calcPOWarranty($part['id'], $part['warranty']);?>
									</td>
									<td>
										<button type="button" class="btn-sm btn-flat white pull-right serial-expand" data-serial='serial-<?=$part['id'] ?>' style=""><i class="fa fa-list" aria-hidden="true"></i></button>
									</td>
								</tr>
								<tr class='serial-<?=$part['id'] ?>' style='display:none;'>
									<td colspan='12'>
										<table class='table serial table-hover table-condensed'>
											<thead>
												<tr>
													<th>Serial Number</th>
													<th>qty</th>
													<th>Status</th>
													<th><span class='edit'>Location</span></th>
													<th><span class='edit'>Condition</span></th>
												</tr>
											</thead>
											<tbody>
											<?php 
												$history = getHistory($part['partid'],$order_number); 
												if($history) { 
													foreach($history as $serial): ?>
												<tr>
													<td><?= $serial['serial_no']; ?></td>
													<td><?= $serial['qty']; ?></td>
													<td><?= $serial['status']; ?></td>
													<td><?= display_location($serial['locationid']); ?></td>
													<td><?= getCondition($serial['conditionid']); ?></td>
												</tr>
											<?php 
													endforeach; 
												} 
											?>
											</tbody>
										</table>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php } else { ?>
							
						<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		</div> 
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
		<script type="text/javascript">
			$(document).on("click", ".RTV_check",function(){
				var show_rtv_button = false;
				$.each($(".RTV_check"), function(){
					if ($(this).prop("checked")){
						show_rtv_button = true;
					}
				});
				if(show_rtv_button){
					$("#rtv_button").show();
				} else {
					$("#rtv_button").hide();
				}
			});
		</script>
	</form>
	</body>
</html>
