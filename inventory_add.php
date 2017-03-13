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
	
	//include_once $rootdir.'/inc/order-creation.php';
	
	$order_number = isset($_REQUEST['on']) ? $_REQUEST['on'] : "";
	$order_type = "Purchase";


	//Using the order number from purchase order, get all the parts being ordered and place them on the inventory add page
	function getPOParts () {
		global $order_number;
		
		$listParts = array();
		
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
		
		return $listParts;
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
	
	function getHistory($partid) {
		global $order_number;
		$listSerials;
		
		$query = "SELECT serial_no, i.id, i.qty, status, locationid, i.conditionid FROM inventory i, purchase_items p WHERE po_number = ". res($order_number) ." AND p.partid = '". res($partid) ."' AND i.purchase_item_id = p.id;";
		$result = qdb($query);
	    
	    if($result)
		    if (mysqli_num_rows($result)>0) {
				while ($row = $result->fetch_assoc()) {
					$listSerials[] = $row;
				}
			}
		
		return $listSerials;
	}
	
	function format($partid){
		$r = reset(hecidb($partid, 'id'));
	    $display = "<span class = 'descr-label'>".$r['part']." &nbsp; ".$r['heci']."</span>";
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf'])." &nbsp; ".dictionary($r['system']).'</span> <span class="description-label">'.dictionary($r['description']).'</span></div>';

	    return $display;
	}
	
	
	$partsListing = getPOParts();
?>

<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
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
		<?php include 'inc/navbar.php';?>
		
		<form action="/order_form.php?ps=RTV&on=<?=$order_number?>" method="post" style="height: 100%;">
			
			<div class="row table-header" id = "order_header" style="margin: 0; width: 100%;">
				<div class="col-sm-4">
					<a href="/order_form.php<?php echo ($order_number != '' ? "?on=$order_number&ps=p": '?ps=p'); ?>" class="btn-flat info pull-left" style="margin-top: 10px;"><i class="fa fa-list" aria-hidden="true"></i></a>
					<button type="submit" class="btn-flat btn-sm primary pull-left" id = "rtv_button" data-validation="left-side-main" style="margin-top:10px;display:none;">RTV</button>
					
					</div>
				<div class="col-sm-4 text-center" style="padding-top: 5px;">
					<h2><?php echo ($order_number != '' ? 'PO #'.$order_number.' Receiving' : 'Inventory Addition'); ?></h2>
				</div>
				<div class="col-sm-4">
				</div>
			</div>
			
				<!-------------------- $$ OUTPUT THE MACRO INFORMATION -------------------->
				<?php if($order_number != '') { ?>
					<div class="left-side-main col-md-2" data-page="addition" style="height: 100%;">
						<?=sidebar_out($order_number, $order_type,'display')?>
					</div>
					
					<div class="col-sm-10">
				<?php } else { ?>
					<div class="col-sm-12">
				<?php } ?>
				
				
				<div class="table-responsive">
					<table class="inventory_add table table-hover table-striped table-condensed" style="table-layout:fixed;"  id="items_table">
						<thead>
					         <tr>
					            <th class="col-sm-3">
					            	PART	
					            </th>
					            <th class="col-sm-3">
									Location
								</th>
			                    <th class="col-sm-1">
									Condition
					        	</th>
								<th class="col-sm-3">
					            	Serial	(*Scan or Press Enter on Input for More)
					            </th>
					            <th class="col-sm-1">
									Remaining Qty
					        	</th>
					        	<th class="col-sm-1">
									RTV
					        	</th>
					        	<th class="col-sm-1">
									Vendor Warr
					        	</th>
					            <th class="col-sm-1">
					            	<!--Lot Inventory (No Serial)-->
					        	</th>
					         </tr>
						</thead>
						
						<tbody>
						<?php 
							//Grab all the parts from the specified PO #
							if($partsListing) {
								foreach($partsListing as $part): 
									$item = getPartName($part['partid']);
						?>
								<tr class="<?php echo ($part['qty'] - $part['qty_received'] <= 0 ? 'order-complete' : ''); ?>">
									<td class="part_id" data-partid="<?php echo $part['partid']; ?>" data-part="<?php echo $item['part']; ?>">
										<?php 
											echo format($part['partid']);
										?>
									</td>
									<td  class="infiniteLocations">
										<div class="row-fluid locations_tracker" data-serial="">
											<div class="col-md-6 location" style="padding: 0 0 0 5px;">
												<?=loc_dropdowns('place')?>
											</div>
											<div class="col-md-6 instance" style="padding: 0 0 0 5px">
												<?=loc_dropdowns('instance')?>
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
									
									<td class="remaining_qty">
										<input style="margin-bottom: 6px;" class="form-control input-sm" data-qty="" name="qty" placeholder="LOT QTY" value="<?php echo($part['qty'] - $part['qty_received'] <= 0 ? 0 : $part['qty'] - $part['qty_received']); ?>" readonly>
										<div class='infiniteComments'>
									    	<!--<input style='margin-bottom: 10px;' class="form-control input-sm iso_comment" type="text" name="partComment" value="" placeholder="Comments" data-serial='' data-inv-id='' data-part="">-->
										</div>
									</td>
									<td>
										<input class='RTV_check' type="checkbox" name='partid[<?=$part['id'];?>][<?=$part['qty_received']?>]' value="<?=$part['partid'];?>">
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
											<?php $history = getHistory($part['partid']); if($history != '') { foreach($history as $serial): ?>
												<tr>
													<td><?= $serial['serial_no']; ?></td>
													<td><?= $serial['qty']; ?></td>
													<td><?= $serial['status']; ?></td>
													<td><?= display_location($serial['locationid']); ?></td>
													<td><?= getCondition($serial['conditionid']); ?></td>
												</tr>
											<?php endforeach; } ?>
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
