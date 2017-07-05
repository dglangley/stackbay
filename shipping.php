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
	include_once $rootdir.'/inc/getCondition.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/operations_sidebar.php';
	include_once $rootdir.'/inc/packages.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/getOrderStatus.php';
	//include_once $rootdir.'/inc/order-creation.php';
	
	$order_number = $_REQUEST['on'];
	$order_type = "Sales";
	
	$so_updated = $_REQUEST['success'];
	
	//If no order is selected then return to shipping home
	if(empty($order_number)) {
		//header("Location: /shipping_home.php");
		//die();
	}
	
	
	$sales_order;
	$notes;
	$shipid;
	$selected_carrier;
	$selected_service;
	$selected_account = true;
	$exchange = false;

	$repair_item_id;
	$repair_order;

	//get the information based on the order number selected
	$query = "SELECT * FROM sales_orders WHERE so_number = ". prep($order_number) .";";
	$result = qdb($query) OR die(qe());
	
	if (mysqli_num_rows($result)>0) {
		$result = mysqli_fetch_assoc($result);
		$sales_order = $result['so_number'];
		$notes = $result['public_notes'];
		$shipid = $result['ship_to_id'];
		$selected_carrier = $result['freight_carrier_id'];
		$selected_service = $result['freight_services_id'];
		$selected_account = $result['freight_account_id'];
		$status = $result['status'];
	}

	function getItems($so_number = 0, $exchange) {
		$sales_items = array();
		$query;
		//First run a check just in case the sales order was changed recently and reflect the changes (E.G. qty order was increase, if qty is less than order admin may need to intervene)
		
//david 2-28-17
//		$query = "UPDATE sales_items SET ship_date = NULL WHERE so_number = ". res($so_number) ." AND qty_shipped < qty;";
//		qdb($query);
		
		//Get all the items, including old items from the sales order.
		if($so_number != 0) {
			$query = "SELECT * FROM sales_items WHERE so_number = ". prep($so_number) ." ORDER BY ship_date, ref_2 DESC;";
			$result = qdb($query) OR die(qe());
					
			while ($row = $result->fetch_assoc()) {
				$sales_items[] = $row;
			}
		}
		
		return $sales_items;
	}
	
	// print_r(getItems($sales_order));
	
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
	
	function getInventory($partid) {
		$inventory = array();
		$partid = prep($partid);
		$query = "SELECT DISTINCT place, instance FROM inventory i, locations l WHERE partid = $partid AND `qty` > 0 AND i.locationid = l.id;";
		$result = qdb($query) OR die(qe());
	
		if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$inventory[] = $row;
			}
		}
		
		return $inventory;
	}
	
	function getHistory($itemid) {
		$listSerials;
		
		$query = "SELECT * FROM inventory WHERE sales_item_id = '". res($itemid) ."';";
		$result = qdb($query);
	    
	    if($result)
	    if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$listSerials[] = $row;
			}
		}
		
		return $listSerials;
	}
	
	function getComments($invid) {
		$comment;
		
		$query = "SELECT * FROM inventory WHERE id = ". res($invid) .";";
		$result = qdb($query);
	    
	    if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$comment = $result['notes'];
		}
		
		return $comment;
	}
	
	function getDateStamp($order_number) {
		$datestamp = '';
		
		$select = "SELECT * FROM `packages`  WHERE  `order_number` = '$order_number'";
		$results = qdb($select);
		
		if (mysqli_num_rows($results)>0) {
			$results = mysqli_fetch_assoc($results);
			$datestamp = $results['datetime'];
		}
		
		return $datestamp;
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
		$serial;

		$query = "SELECT serial_no FROM inventory WHERE id = ".prep($invid).";";
		$result = qdb($query) or die(qe());
		if (mysqli_num_rows($result)){
			$row = mysqli_fetch_assoc($result);
			$serial = $row['serial_no'];
		}

		return $serial;
	}
	
	if (grab('exchange')){
		$exchange = grab('exchange');
	}
	
	$items = getItems($sales_order, $exchange);

	foreach($items as $item) {
		$repair_item_id = $item['ref_1'];
		break;
	}

	if($repair_item_id) {
		$query = "SELECT ro_number FROM repair_items WHERE id = ".prep($repair_item_id).";";
		$result = qdb($query) OR die(qe());
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$repair_order = $result['ro_number'];
		}
	}

	$RMA_history = getRMA($sales_order, 'Sale');
?>
	

<!DOCTYPE html>
<html>
	<head>
		<title>Shipping <?=($order_number != 'New' ? '#' . $order_number : '')?></title>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
		
		<style type="text/css">
			.table td {
				vertical-align: top !important;
				/*padding-top: 10px !important;*/
				/*padding-bottom: 0px !important;*/
			}
			
			.btn-secondary {
			    color: #292b2c;
			    background-color: #fff;
			    border-color: #ccc;
			}

			.infiniteSerials .btn-secondary {
				/*color: #373a3c;*/
				background-color: transparent;
				border: 0;
				padding: 0;
				line-height: 0;
			}
			
			.table .order-complete td {
				background-color: #efefef !important;
			}
			
			.infiniteSerials .input-group, .infiniteBox select {
				margin-bottom: 10px;
			}
			
			table.num {
			    counter-reset: rowNumber;
			}
			
			table.num tr > td:first-child {
			    counter-increment: rowNumber;
			}
			
			table.num tr td:first-child::before {
			    content: counter(rowNumber);
			    min-width: 1em;
			    margin-right: 0.5em;
			}
			
			table tr.nested_table td:first-child::before {
			    content: '';
			    min-width: 0em;
			    margin-right: 0em;
			}
			
			.infiniteISO .checkbox {
				margin-top: 5px;
				margin-bottom: 20px;
			}
			
			.btn:active, .btn.active {
				outline: 0;
				background-image: none;
				-webkit-box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.25);
				box-shadow: inset 0 3px 5px rgba(0, 0, 0, .25);
			}
			
			.order-exchange td {
				background-color: #f5fafc !important;
			}
			
			.master-package {
				font-weight:bold;
			}
		</style>

	</head>

	<body class="sub-nav" id = "order_body" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
		<?php 
			include 'inc/navbar.php'; 
			include_once $rootdir.'/modal/package.php';
			include_once $rootdir.'/modal/iso.php';
		?>
		<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color: #f7fff1">
			<div class="col-md-4">
				<?php if(in_array("3", $USER_ROLES) || in_array("1", $USER_ROLES)) { ?>
				<a href="/order_form.php?on=<?php echo $order_number; ?>&ps=s" class="btn-flat info pull-left" style="margin-top: 10px;"><i class="fa fa-list-ul" aria-hidden="true"></i> Manage</a>
				<?php
						$isoq = "SELECT * FROM iso WHERE so_number = ".prep($order_number).";";
						$isor = qdb($isoq) OR die(qe() . ' ' . $isoq);

						 if (mysqli_num_rows($isor)>0) {
					?>
				<a target="_blank" href="/iso-form.php?on=<?=$order_number;?>" class="btn-flat pull-left">QC</a>
				<?php }} ?>
<?php
				if(is_numeric($order_number)){
					// echo '<a class="btn-flat pull-left" target="_new"><i class="fa fa-file-pdf-o"></i></a>';
					// echo '<a class="btn-flat pull-left" href="/rma_add.php?on='.$rma_number.'">Receive</a>';
					$rma_select = 'SELECT rma_number FROM `returns` where order_type = "Sale" AND order_number = "'.$order_number.'"';
					$rows = qdb($rma_select);
						$output = '
						<div class ="btn-group">
							<button type="button" class="btn-flat dropdown-toggle" data-toggle="dropdown">
                              <i class="fa fa-question-circle-o"></i>
                              <span class="caret"></span>
                            </button>';
                        
						$output .= '<ul class="dropdown-menu">';
						// $output = "<div id = 'invoice_selector' class = 'ui-select'>";
						if(mysqli_num_rows($rows)>0){
							foreach ($rows as $rma) {
								$output .= '
									<li>
										<div class = "row rma-list-items">
											<div class = "col-md-3">
												<a href="/rma.php?rma='.$rma['rma_number'].'" class = "pull-right">
													<i class="fa fa-list"></i>
												</a>
											</div>
											<div class = "col-md-6" style="padding-left:0px;padding-right:0px;">
												<a href="/rma.php?rma='.$rma['rma_number'].'" class = "pull-right">
													RMA #'.$rma['rma_number'].'
												</a>	
											</div>
											<div class = "col-md-3 pull-left">
												<a href="/rma.php?on='.$rma['rma_number'].'" class = "pull-left">
													<i class="fa fa-truck"></i>
												</a>
											</div>
										</div>
									</li>';
							}
						}
						$output .= '<li>
										<a href="/rma.php?on='.$order_number.'">
											ADD RMA <i class ="fa fa-plus"></i>
										</a>
									</li>';
                        $output .= "</ul>";
						$output .= "</div>";
						echo $output;
				}
?>
			</div>
			
			<div class="col-md-4 text-center">
				<?php
					echo"<h2 class='minimal shipping_header' style='padding-top: 10px;' data-so='". $order_number ."'>";
					if(!$repair_order) {
						if(!$exchange) {
							echo " Shipping Order ";
						} else {
							echo " Exchange for SO ";
						}
						if ($order_number!='New'){
							echo "#$order_number";
						}
					} else {
						echo "Shipping Repair #$repair_order";
					}
					if (strtolower($status) == 'void'){
						echo ("<b><span style='color:red;'> [VOIDED]</span></b>");
					}
					echo"</h2>";
				?>
			</div>
			<div class="col-md-4">
				<button class="btn-flat success pull-right btn-update" id="iso_report" data-datestamp = "<?= getDateStamp($order_number); ?>" style="margin-top: 10px; margin-right: 10px;">Complete Order</button>
			</div>
		</div>
		
		<?php if($so_updated == 'true'): ?>
			<div id="item-updated-timer" class="alert alert-success fade in text-center" style="position: fixed; width: 100%; z-index: 9999; top: 95px;">
			    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
			    <strong>Success!</strong> <?= ($po_updated ? 'Purchase' : 'Sales'); ?> Order Updated.
			</div>
		<?php endif; ?>

		<div class="loading_element">
			<div class="row remove-margin">
				<!--================== Begin Left Half ===================-->
				<div class="left-side-main col-sm-2">
					<?=sidebar_out($order_number, $order_type,'display')?>
				</div>
				<!--======================= End Left half ======================-->
				
				<div class="col-sm-10 shipping-list" style="padding-top: 20px">
					<div class = 'row'>
						<div class = 'col-sm-3'>
<!--
							<h3>Items to be Shipped</h3>
-->
						</div>
						<div class="col-sm-9">
							<div class="btn-group box_group" data-account='<?=$selected_account?>' style = "padding-bottom:16px;">
								<button type="button" class="btn btn-warning box_edit" title = 'Edit Selected Box'>
									<i class="fa fa-pencil fa-4" aria-hidden="true"></i>
								</button>
								<?php

									$select = "SELECT * FROM `packages`  WHERE  `order_number` = '$order_number'";
									$results = qdb($select);
									
									//Check for any open items to be shipped
									$check = "SELECT * FROM `sales_items`  WHERE  `so_number` = '$order_number' AND qty_shipped < qty";
									$open_items = qdb($check);
	
									if (mysqli_num_rows($results) > 0){
										//Initialize
										$init = true;
										$package_no = 0;
										
										$masters = master_packages($order_number,"sales");
										foreach($results as $b){
											$package_no = $b['package_no'];
											$box_button = "<button type='button' class='btn ";
											
											//Build classes for the box buttons based off data-options
											$box_button .= ($b['datetime'] != '' ? 'btn-grey' : 'btn-secondary'); //If the button has been shipped
											$box_button .= (($b['datetime'] == '' && $init) ? ' active' : ''); //If the box is active, indicate that
											$box_button .= (in_array($package_no,$masters)) ? ' master-package ' : '';
											$box_button .= " box_selector'";
											
											//Add Data tags for the future population of modals
											$box_button .= " data-width = '".$b['weight']."' data-l = '".$b['length']."' ";
											$box_button .= " data-h = '".$b['height']."' data-weight = '".$b['weight']."' ";
											$box_button .= " data-row-id = '".$b['id']."' data-tracking = '".$b['tracking_no']."' ";
											$box_button .= " data-row-freight = '".$b['freight_amount']."'";
											$box_button .= " data-order-number='" . $order_number . "'";
											$box_button .= " data-box-shipped ='".($b['datetime'] ? $b['datetime'] : '')."'>".$b['package_no']."</button>";
											echo($box_button);
				                        	
				                        	$box_list .= "<option value='".$b['id']."'>Box ".$b['package_no']."</option>";
				                        	if($b['datetime'] == '' && $init)
				                        		$init = false;
										}
										
										//There are still open items to be shipped
										if (mysqli_num_rows($open_items) > 0){
											//Check for any open boxes to be shipped
											$check = "SELECT * FROM `packages`  WHERE  `order_number` = '$order_number' AND datetime IS NULL";
											$open_boxes = qdb($check);
											$package_no = $package_no + 1;
											//echo mysqli_num_rows($open_boxes);
											//There is no boxes open and there are items to be shipped
											if (mysqli_num_rows($open_boxes) == 0){
												$insert = "INSERT INTO `packages`(`order_number`,`package_no`) VALUES ($order_number, ".$package_no.");";
												qdb($insert);
												echo("<button type='button' class='btn btn-secondary active box_selector' data-row-id = '".qid()."'>$package_no</button>");
											}
										}
									} else {
										$insert = "INSERT INTO `packages`(`order_number`,`package_no`) VALUES ($order_number, '1');";
										qdb($insert);
										echo("<button type='button' class='btn btn-secondary active box_selector master-package' data-row-id = '".qid()."'>1</button>");
									}
		
								?>
								<button type="button" class="btn btn-primary box_addition" title = "">
							  		<i class="fa fa-plus fa-4" aria-hidden="true"></i>
						  		</button>
							</div>

						</div>
					</div>
				
					<div class="table-responsive">
						<table class="shipping_update table table-hover table-striped table-condensed" style="margin-top: 15px;">
							<thead>
								<tr>
									<th>Item</th>
									<th>SO Qty</th>
									<th>SERIAL</th>
									<th>Box #</th>
									<th>Locations</th>
									<th>Outstanding</th>
									<th>Condition</th>
									<th>Warranty</th>
									<th>Delivery</th>
									<th></th>
								</tr>
							</thead>
							<?php
								//Grab a list of items from an associated sales order.
								$serials = array();
								if(!empty($items))
								foreach($items as $item): 
									$inventory = getInventory($item['partid']);
									$select = "SELECT DISTINCT `serial_no`, i.id, `packageid`, p.datetime FROM `inventory` AS i, `package_contents`, `packages` AS p WHERE i.id = serialid AND sales_item_id = ".prep($item['id'])." AND p.id = packageid AND p.order_number = ".prep($order_number).";";
									$serials = qdb($select);
									//print_r($inventory);
									$parts = explode(' ',getPartName($item['partid']));
									$part = $parts[0];
							?>
								<tr class="<?= (!empty($item['ship_date']) ? 'order-complete' : ''); ?> <?= (!empty($item['ref_2']) ? 'order-exchange' : ''); ?>" style = "padding-bottom:6px;">
									<td class="part_id col-md-3" data-partid="<?php echo $item['partid']; ?>" data-part="<?php echo $part; ?>" style="padding-top: 15px !important;">
										<div class="product-img"><img class="img" src="/img/parts/<?php echo $part; ?>.jpg" alt="pic"></div>
										<div class="product-descr"><?= display_part(current(hecidb($item['partid'],'id'))) ?></div>
									</td>
									<td class="text-center" style="padding-top: 15px !important;">
										<span class="qty_field"><?php echo $item['qty'] ?></span>
									</td>
								
								<!-- Grab the old serial values from the database and display them-->
									<td class="infiniteSerials" style="padding-top: 10px !important;">
										<div class="input-group">
										    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-item-id='<?=$item['id']?>' data-saved="" <?php echo ($item['qty'] - $item['qty_shipped'] == 0 ? 'disabled' : ''); ?>>
										    <span class="input-group-addon">
										        <button class="btn btn-secondary deleteSerialRow" type="button" disabled><i class="fa fa-trash fa-4" aria-hidden="true"></i></button>
										    </span>
							            </div>
										
									
										<?php
											foreach ($serials as $serial):
										?>
										<div class="input-group check-save" data-savable="true">
										    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-package = "<?= $serial['packageid']; ?>" data-inv-id ='<?=$serial['id']?>' data-saved="<?=$serial['serial_no']?>" data-item-id='<?=$item['id']?>' value='<?=$serial['serial_no']?>' <?php echo ($serial['datetime'] != '' ? 'disabled' : '');?>>
										    <span class="input-group-addon">
										        <button class="btn btn-secondary deleteSerialRow" type="button" data-package = "<?= $serial['packageid']; ?>" <?php echo ($serial['datetime'] != '' ? 'disabled' : '');?>><i class="fa fa-trash fa-4" aria-hidden="true"></i></button>
										    </span>
							            </div>
										<?php endforeach; ?>
									</td>
									<td class="infiniteBox" style="padding-top: 10px !important;">
										<?=box_drop($order_number, '', true)?>
										<?php foreach ($serials as $serial):?>
											<?=box_drop($order_number,$serial['id'],'',$serial['packageid'], $serial['serial_no'])?>
										<?php endforeach; ?>
									</td>
									<td style="padding-top: 10px !important;">
										<div style="margin-top: 5px; margin-bottom: 15px; margin-left:5px; height: 20px; max-height: 20px; overflow: hidden;">
											<!-- <label><input class="lot_inventory" style="margin: 0 !important" type="checkbox" <?php echo (!empty($item['ship_date']) ? 'disabled' : ''); ?>></label> -->
											<?php 
												if($inventory) {
													$init = true;
													foreach ($inventory as $location) {
														if(!$init){ echo ', ';}
														echo $location['place'];
														if($location['instance']){ echo '-' . $location['instance']; }
														$init = false; 
													}
												}
											?>
										</div>
										
										<div class='infiniteComments'>
										<?php
											foreach ($serials as $serial):
										?>
	
									    <input style='margin-bottom: 10px;' class="form-control input-sm iso_comment" type="text" name="partComment" data-package = "<?= getPackageInfo($serial['packageid']); ?>" value="<?= getComments($serial['id']); ?>" placeholder="Comments" data-serial='<?=$serial['serial_no']?>' data-inv-id='<?=$serial['id']?>' data-part="<?php echo getPartName($item['partid']); ?>" <?php echo ($serial['datetime'] != '' ? 'disabled' : '');?>>
	
										<?php endforeach; ?>
										</div>
										<!--<button class="btn-sm btn-flat pull-right serial-expand" data-serial='serial-<?=$part['id'] ?>' style="margin-top: -40px;"><i class="fa fa-list" aria-hidden="true"></i></button>-->
									</td>
									<td class="remaining_qty" style="padding-top: 15px !important;" data-qty="<?php echo $item['qty'] - $item['qty_shipped']; ?>">
										<?php echo $item['qty'] - $item['qty_shipped']; ?>
									</td>
									<td style="padding-top: 15px !important;">
										<span class="condition_field" data-condition="<?php echo $item['conditionid'] ?>"><?php echo getCondition($item['conditionid']) ?></span>
									</td>
									<td style="padding-top: 15px !important;">
										<span class="condition_field" data-condition="<?php echo $item['warranty'] ?>"><?php echo getWarranty($item['warranty'],"warranty"); ?></span>
									</td>
									<td style="padding-top: 15px !important;">
										<?php echo (!empty($item['delivery_date']) ? date_format(date_create($item['delivery_date']), "m/d/Y") : ''); ?>
									</td>
									<td>
										<!--<button class="btn-sm btn-flat pull-right serial-expand" data-serial="serial-<?=$item['id'] ?>"><i class="fa fa-list" aria-hidden="true"></i></button>-->
									</td>
								</tr>
								
							<?php endforeach; ?>
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
			<!--End Row-->
			</div>
		<!--End Loading Element-->
		</div>
		
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
		<script>
			(function($){
				$('#item-updated-timer').delay(1000).fadeOut('fast');
			})(jQuery);
		</script>
	</body>
</html>
