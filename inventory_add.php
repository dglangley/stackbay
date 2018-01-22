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
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/getDisposition.php';
	include_once $rootdir.'/inc/getPart.php';
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
	function getPOParts ($order_number) {		
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

	function getClassification($partid) {
		$query = "SELECT classification FROM parts WHERE id = ".prep($partid).";";
		return rsrq($query);
	}
	
	function getPartName($partid) {
		$part;
		
		$query = "SELECT part FROM parts WHERE id = ". res($partid) .";";
		$result = qdb($query) OR die(qe());
	
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$part = $result['part'];
		}
	
		return $part;
	}

	function getHistory($partid, $order_number) {
		$listSerials = array();
		
		$query = "
			SELECT serial_no, i.id, i.qty, status, locationid, i.conditionid 
			FROM inventory_history ih, purchase_items p, inventory i 
			WHERE po_number = ". prep($order_number) ."
			AND p.partid = ". prep($partid) ." 
			AND ih.field_changed = 'purchase_item_id'
			AND ih.value = p.id AND ih.invid = i.id;
		";

		$result = qdb($query) or die(qe());

	    
	    if($result)
		    if (mysqli_num_rows($result)>0) {
				foreach($result as $row) {
					$listSerials[] = $row;
				}
			}
		
		return $listSerials;
	}

	function getRMA($order_number, $type){
		$RMA = array();

		$query = "SELECT * FROM returns as r, return_items as i WHERE r.order_number = ".prep($order_number)." AND r.order_type = ".prep($type)." AND r.rma_number = i.rma_number;";
		$result = qdb($query) OR die(qe());

		while ($row = $result->fetch_assoc()) {
			$RMA[] = $row;
		}

		return $RMA;
	}

	function getSerial($invid) {
		$query = "SELECT serial_no FROM inventory WHERE id = ".prep($invid).";";
		return rsrq($query);
	}

	$partsListing = getPOParts($order_number);
	$status = getOrderStatus($order_type,$order_number);
	$RMA_history = getRMA($order_number, 'Purchase');

	$first = reset($partsListing);
	$classification = '';

	if($first['ref_1_label'] == 'repair_item_id') {
		$classification = 'component';
	}

	if($partsListing && !$classification) {
		foreach($partsListing as $part): 
			$classification = getClassification($part['partid']);
			break;
		endforeach;
	}
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
		
	<!-- Begin the header output  -->
	<div class="pad-wrapper">
		<?php 
			include 'inc/navbar.php';
			include_once $rootdir.'/modal/package.php';
			include_once $rootdir. '/modal/image.php';
			include_once $rootdir. '/inc/getOrder.php';

			$ORDER = getOrder($order_number,$order_type);
		?>
		
		<?php if($classification != 'component' AND $classification != 'material') { ?>
			<form action="/order_form.php?ps=RTV&on=<?=$order_number?>" method="post" style="height: 100%;">
		<?php } else { ?>
			<form action="/component_add.php" method="post" style="height: 100%;">
		<?php } ?>

			<div class="row table-header" id = "order_header" style="margin: 0; width: 100%;">
				<div class="col-sm-3">
					<?php if(in_array("1", $USER_ROLES) || in_array("4", $USER_ROLES) || in_array("5", $USER_ROLES) || in_array("7", $USER_ROLES)) { ?>
					<a href="/edit_order.php<?php echo ($order_number != '' ? "?on=$order_number&ps=p": '?ps=p'); ?>" class="btn btn-default btn-sm pull-left"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
					<?php } ?>
					<button type="submit" class="btn btn-default btn-sm pull-left" id = "rtv_button" data-validation="left-side-main" style="margin-top:10px;display:none;">RTV</button>
					
				</div>
				<div class="col-sm-1">
					<h4><?=getRep($ORDER['sales_rep_id']);?></h4>
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
								$num_packages = mysqli_num_rows($results);
								
								//Check for any open items to be shipped
								if ($num_packages > 0){
									//Initialize
									$init = true;
									$package_no = 0;
									
									$masters = master_packages($order_number,$order_type);
									foreach($results as $b){
										$package_no = $b['package_no'];
										$box_button = "<button type='button' class='btn ";
										
										//Build classes for the box buttons based off data-options
										$box_button .= 'btn-grey'; //If the button has been shipped
										$box_button .= (($num_packages == 1 OR ($b['datetime'] == '' && $init)) ? ' active' : ''); //If the box is active, indicate that
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
									$insert = "INSERT INTO `packages`(`order_number`,`order_type`,`package_no`,`datetime`) VALUES ($order_number,'$order_type','1','".$GLOBALS['now']."');";
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
					            	Serial / Component
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
									$classification = getClassification($part['partid']);

									$part_string = explode(' ',getPartName($part['partid']));
									$part_name = $part_string[0];
						?>
								<tr class="<?php echo ($part['qty'] - $part['qty_received'] <= 0 ? 'order-complete' : ''); ?>">
									<td class="part_id" data-partid="<?php echo $part['partid']; ?>" data-part="<?=$item?>">
										<div class="product-img"><img class="img" src="/img/parts/<?=$part_name;?>.jpg" alt="pic" data-part="<?=$part_name;?>"></div>
										<div class="product-descr"><?=display_part(current(hecidb($part['partid'],'id')));?></div>
										<?php if($classification == 'component') { ?>
											<input class="hidden" type="text" name="order_num" value="<?=$order_number;?>" readonly>
											<input class="hidden" type="text" name="partid" value="<?=$part['partid'];?>" readonly>
											<input class="hidden" type="text" name="purchase_item_id" value="<?=$part['id'];?>" readonly>
											<input class="hidden" type="text" name="repair_item_id" value="<?=$part['ref_1'];?>" readonly>
										<?php } ?>
									</td>
									<td  class="infiniteLocations">
										<div class="row row-fluid">
											<div class="locations_tracker" data-serial="">
												<div class="col-md-<?=($classification != 'component' ? '6' : '4');?> locations" style="padding: 0 0 0 5px;">
													<?=loc_dropdowns('place'); ?>
												</div>
												<div class="col-md-<?=($classification != 'component' ? '6' : '4');?> instances" style="padding: 0 0 0 5px">
													<?=loc_dropdowns('instance')?>
												</div>
												<?php if($classification == 'component') { ?>
													<div class="col-md-4 bin" style="padding: 0 0 0 5px">
														<?=loc_dropdowns('bin')?>
													</div>
												<?php } ?>
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
									<?php if($classification != 'component') { ?>
										<td class="infiniteSerials">
											<div class="input-group" style="margin-bottom: 6px;">
											    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-saved="" data-item-id="<?php echo $part['id']; ?>" <?php echo ($part['qty'] - $part['qty_received'] == 0 ? '' : ''); ?>>
											    <span class="input-group-addon">
											        <button class="btn btn-secondary deleteSerialRow" type="button" style='display: none;' disabled><i class="fa fa-trash fa-4" aria-hidden="true"></i></button>
											        <button class="btn btn-secondary updateSerialRow" type="button"><i style='color: green;' class="fa fa-save fa-4" aria-hidden="true"></i></button>
											    </span>
								            </div>
										</td>
									<?php } else { ?>
										<td class="componentColumn">
											<div class="input-group" style="margin-bottom: 6px;">
											    <input class="form-control input-sm" type="text" name="componentQTY[<?=$part['id'];?>]" placeholder="QTY">
											    <span class="input-group-addon">
											        <button class="btn btn-secondary" type="submit"><i style='color: green;' class="fa fa-save fa-4" aria-hidden="true"></i></button>
											    </span>
								            </div>
										</td>
									<?php } ?>
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
										<input style="margin: 0 auto; display: block; margin-top: 10px;" class='RTV_check' type="checkbox" name='partid[<?=$part['id'];?>][<?=$part['qty_received']?>]' value="<?=$part['partid'];?>" <?=($classification != 'component' ? '' : 'disabled')?>>
									</td>
									<td>
										<?=calcPOWarranty($part['id'], $part['warranty']);?>
									</td>
									<td>
										<button type="button" class="btn btn-default btn-sm white pull-right serial-expand" data-serial='serial-<?=$part['id'] ?>' style=""><i class="fa fa-chevron-down" aria-hidden="true"></i></button>
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

				<?php if($RMA_history): ?>
					<div class="table-responsive">
						<table class="table table-hover table-striped table-condensed">
							<thead>
								<th>RMA #</th>
								<th>Description</th>
								<th>Date</th>
								<th>Serial</th>
								<th>Disposition</th>
								<th>Reason</th>
							</thead>

							<tbody>
								<?php foreach($RMA_history as $history): ?>
									<tr>
										<td><?=$history['rma_number']?></td>
										<td><?=display_part(current(hecidb($history['partid'], 'id')));?></td>
										<td><?=format_date($history['created']);?></td>
										<td><?=getSerial($history['inventoryid']);?></td>
										<td><?=getDisposition($history['dispositionid']);?></td>
										<td><?=$history['reason']?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
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


