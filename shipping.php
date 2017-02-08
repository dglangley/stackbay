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
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/form_handle.php';
	
	//include_once $rootdir.'/inc/order-creation.php';
	
	$order_number = $_REQUEST['on'];
	$order_type = "Sales";
	
	$so_updated = $_REQUEST['success'];
	
	//If no order is selected then return to shipping home
	if(empty($order_number)) {
		header("Location: /shipping_home.php");
		die();
	}
	
	function address_out($address_id){
		//General function for handling the standard display of addresses
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
	
	$sales_order;
	$notes;
	$shipid;
	$selected_carrier;
	
	//get the information based on the order number selected
	$query = "SELECT * FROM sales_orders WHERE so_number = ". prep($order_number) .";";
	$result = qdb($query) OR die(qe());
	
	if (mysqli_num_rows($result)>0) {
		$result = mysqli_fetch_assoc($result);
		$sales_order = $result['so_number'];
		$notes = $result['public_notes'];
		$shipid = $result['ship_to_id'];
		$selected_carrier = $result['freight_carrier_id'];
	}
	
	function getItems($so_number = 0) {
		$sales_items = array();
		
		//First run a check just in case the sales order was changed recently and reflect the changes (E.G. qty order was increase, if qty is less than order admin may need to intervene)
		
		$query = "UPDATE sales_items SET ship_date = NULL WHERE so_number = ". res($so_number) ." AND qty_shipped < qty;";
		qdb($query);
		
		//Get all the items, including old items from the sales order.
		$query = "SELECT * FROM sales_items WHERE so_number = ". res($so_number) ." ORDER BY ship_date ASC;";
		$result = qdb($query) OR die(qe());
				
		while ($row = $result->fetch_assoc()) {
			$sales_items[] = $row;
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
		$inventory;
		$partid = prep($partid);
		$query = "SELECT * FROM inventory WHERE partid = $partid AND `qty` > 1;";
		$result = qdb($query) OR die(qe());
	
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$inventory = $result;
		}
		
		return $inventory;
	}
	
	function getHistory($partid) {
		global $order_number;
		$listSerials;
		
		$query = "SELECT * FROM inventory WHERE last_sale = ". res($order_number) ." AND partid = '". res($partid) ."';";
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
		global $order_number;
		$comment;
		
		$query = "SELECT * FROM inventory WHERE id = ". res($invid) .";";
		$result = qdb($query);
	    
	    if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$comment = $result['notes'];
		}
		
		return $comment;
	}
	
	function getWarranty($id) {
		$warranty;
		$id = prep($id);
		$query = "SELECT * FROM warranties WHERE id = $id";
		$result = qdb($query) OR die(qe());
	
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$warranty = $result['warranty'];
		}
		
		return $warranty;
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
	
	function format($partid){
		$parts = reset(hecidb($partid, 'id'));
	    $name = "<span class = 'descr-label'>".$parts['part']." &nbsp; ".$parts['heci'].' &nbsp; '.$parts['Manf'].' '.$parts['system'].' '.$parts['Descr']."</span>";
	    $name .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($parts['manf'])." &nbsp; ".dictionary($parts['system']).'</span> <span class="description-label">'.dictionary($parts['description']).'</span></div>';

	    return $name;
	}

	$items = getItems($sales_order);
?>
	

<!DOCTYPE html>
<html>
	<head>
		<title>Shipping</title>
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
			
			table {
			    counter-reset: rowNumber;
			}
			
			table tr > td:first-child {
			    counter-increment: rowNumber;
			}
			
			table tr td:first-child::before {
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
		</style>

	</head>
	
	<body class="sub-nav" id = "order_body" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
	<!----------------------- Begin the header output  ----------------------->
		<?php 
			include 'inc/navbar.php'; 
			include_once $rootdir.'/modal/package.php';
			include_once $rootdir.'/modal/iso.php';
		?>
		<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color: #f7fff1">
			<div class="col-md-4">
				<a href="/order_form.php?on=<?php echo $order_number; ?>&ps=s" class="btn-flat info pull-left" style="margin-top: 10px;"><i class="fa fa-list-ul" aria-hidden="true"></i> Order Info</a>
			</div>
			<div class="col-md-4 text-center">
				<?php
					echo"<h2 class='minimal shipping_header' style='padding-top: 10px;' data-so='". $order_number ."'>";
					echo " Shipping Order";
					if ($order_number!='New'){
						echo " #$order_number";
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
			    <strong>Success!</strong> <?php echo ($po_updated ? 'Purchase' : 'Sales'); ?> Order Updated.
			</div>
		<?php endif; ?>

		<div class="loading_element">
			<!--================== Begin Left Half ===================-->
			<div class="left-side-main col-sm-2">
				<!-- Everything here is put out by the order creation ajax script -->
			</div>
			<!--======================= End Left half ======================-->
			
			<div class="col-sm-10 shipping-list" style="padding-top: 20px">
				<div class = 'row'>
					<div class = 'col-sm-3'>
						<h3>Items to be Shipped</h3>
					</div>
					<div class="col-sm-9">
						<div class="btn-group box_group" style = "padding-bottom:16px;">
							<button type="button" class="btn btn-warning box_edit" title = 'Edit Selected Box'>
								<i class="fa fa-pencil fa-4" aria-hidden="true"></i>
							</button>
							<?php
								function box_drop($order_number, $associated = '', $first = '',$selected = '', $serial = ''){
									$select = "SELECT * FROM `packages`  WHERE  `order_number` = '$order_number'";
									$results = qdb($select);
									
									$drop = '';
									foreach ($results as $item) {
										//print_r($item);
										$it[$item['id']] = $item['datetime'];	
										$drop .= "<option value='".$item['id']."'";
										if ($selected == $item['id']){
											$drop .= ' selected';
										}
										$drop .= ($item['datetime'] != '' ? ' disabled': '');
										$drop .= ">Box ".$item['package_no']."</option>";
									}
									$drop .= "</select>";
									$drop .= "</div>";
									if ($first){
											$f = "<div>
				            				<select class='form-control input-sm active_box_selector' data-associated = '$associated' data-serial = '$serial'>";
										}
										else{
											$f = "<div>
				            					<select class='form-control box_drop input-sm'  data-associated = '$associated' data-serial = '$serial' ".($it[$selected] != '' ? ' disabled ': '').">";
										}
										$f .= $drop;
									return $f;
								}
								
								$select = "SELECT * FROM `packages`  WHERE  `order_number` = '$order_number'";
								$results = qdb($select);

								if (mysqli_num_rows($results) > 0){
									$init = true;
									foreach($results as $b){
										$box_button = "<button type='button' class='btn ".($b['datetime'] != '' ? 'btn-grey' : 'btn-secondary active')." box_selector'";
										$box_button .= " data-width = '".$b['weight']."' data-l = '".$b['length']."' ";
										$box_button .= " data-h = '".$b['height']."' data-weight = '".$b['weight']."' ";
										$box_button .= " data-row-id = '".$b['id']."' data-tracking = '".$b['tracking_no']."' ";
										$box_button .= " data-row-freight = '".$b['freight_amount']."'";
										$box_button .= " data-order-number='" . $order_number . "'";
										$box_button .= " data-box-shipped ='".($b['datetime'] != '' ? 'completed' : '')."'>".$b['package_no']."</button>";
										echo($box_button);
			                        	
			                        	$box_list .= "<option value='".$b['id']."'>Box ".$b['package_no']."</option>";
			                        	$init = false;
									}
								}
								else{
									$insert = "INSERT INTO `packages`(`order_number`,`package_no`) VALUES ($order_number, '1');";
									qdb($insert);
									echo("<button type='button' class='btn btn-grey box_selector' data-row-id = '".qid()."'>1</button>");
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
								<th>SERIAL</th>
								<th>Box #</th>
								<th>Components</th>
								<th>Ordered</th>
								<th>Outstanding</th>
								<th>Condition</th>
								<th>Warranty</th>
								<th>Delivery</th>
								<th></th>
							</tr>
						</thead>
						<?php
							//Grab a list of items from an associated sales order.
							foreach($items as $item): 
								$inventory = getInventory($item['partid']);
								// print_r($inventory);
						?>
							<tr class="<?php echo (!empty($item['ship_date']) ? 'order-complete' : ''); ?>" style = "padding-bottom:6px;">
								<td class="part_id col-md-3" data-partid="<?php echo $item['partid']; ?>" data-part="<?php echo getPartName($item['partid']); ?>" style="padding-top: 15px !important;">
									<?= format($item['partid']); ?>
								</td>
							
							<!-- Grab the old serial values from the database and display them-->
								<td class="infiniteSerials" style="padding-top: 10px !important;">
									<div class="input-group">
									    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-saved="" <?php echo ($item['qty'] - $item['qty_shipped'] == 0 ? 'disabled' : ''); ?>>
									    <span class="input-group-addon">
									        <button class="btn btn-secondary deleteSerialRow" type="button" disabled><i class="fa fa-trash fa-4" aria-hidden="true"></i></button>
									    </span>
						            </div>
									
								
									<?php
										$select = "SELECT DISTINCT `serial_no`, i.id, `packageid`, p.datetime FROM `inventory` AS i, `package_contents`, `packages` AS p WHERE i.id = serialid AND last_sale = ".prep($order_number)." and partid = ".prep($item['partid'])." AND p.id = packageid;";
										$serials = qdb($select);
										foreach ($serials as $serial):
									?>
									<div class="input-group">
									    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-package = "<?= $serial['packageid']; ?>" data-inv-id =<?=$serial['id']?> data-saved="<?=$serial['serial_no']?>" value='<?=$serial['serial_no']?>' <?php echo ($serial['datetime'] != '' ? 'disabled' : '');?>>
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
									<div class="checkbox">
										<label><input class="lot_inventory" style="margin: 0 !important" type="checkbox" <?php echo (!empty($item['ship_date']) ? 'disabled' : ''); ?>></label>
									</div>
									
									<div class='infiniteComments'>
									<?php
										$select = "SELECT DISTINCT `serial_no`, i.id, `packageid`, p.datetime FROM `inventory` AS i, `package_contents`, `packages` AS p WHERE i.id = serialid AND last_sale = ".prep($order_number)." and partid = ".prep($item['partid'])." AND p.id = packageid;";
										$serials = qdb($select);
										foreach ($serials as $serial):
									?>

								    <input style='margin-bottom: 10px;' class="form-control input-sm iso_comment" type="text" name="partComment" data-package = "<?= $serial['packageid']; ?>" value="<?= getComments($serial['id']); ?>" placeholder="Comments" data-serial='<?=$serial['serial_no']?>' data-inv-id='<?=$serial['id']?>' data-part="<?php echo getPartName($item['partid']); ?>" <?php echo ($serial['datetime'] != '' ? 'disabled' : '');?>>

									<?php endforeach; ?>
									</div>
									<!--<button class="btn-sm btn-flat pull-right serial-expand" data-serial='serial-<?=$part['id'] ?>' style="margin-top: -40px;"><i class="fa fa-list" aria-hidden="true"></i></button>-->
								</td>
								<td style="padding-top: 15px !important;">
									<span class="qty_field"><?php echo $item['qty'] ?></span>
								</td>
								<td class="remaining_qty" style="padding-top: 15px !important;" data-qty="<?php echo $item['qty'] - $item['qty_shipped']; ?>">
									<?php echo $item['qty'] - $item['qty_shipped']; ?>
								</td>
								<td style="padding-top: 15px !important;">
									<span class="condition_field" data-condition="<?php echo $item['cond'] ?>"><?php echo $item['cond'] ?></span>
								</td>
								<td style="padding-top: 15px !important;">
									<span class="condition_field" data-condition="<?php echo $item['warranty'] ?>"><?php echo getWarranty($item['warranty']); ?></span>
								</td>
								<td style="padding-top: 15px !important;">
									<?php echo (!empty($item['delivery_date']) ? date_format(date_create($item['delivery_date']), "m/d/Y") : ''); ?>
								</td>
								<td>
									<!--<button class="btn-sm btn-flat pull-right serial-expand" data-serial="serial-<?=$item['id'] ?>"><i class="fa fa-list" aria-hidden="true"></i></button>-->
								</td>
							</tr>
							<?php $history = getHistory($item['partid']); if($history != '') { ?>
								<!--<tr class='nested_table serial-<?=$item['id'] ?>' style='display:none;'>-->
								<!--	<td colspan='12'>-->
								<!--		<table class='table serial table-hover table-condensed'>-->
								<!--			<thead>-->
								<!--				<tr>-->
								<!--					<th>Serial Number</th>-->
								<!--					<th>Box #</th>-->
								<!--					<th>Comments</th>-->
								<!--				</tr>-->
								<!--			</thead>-->
								<!--			<tbody>-->
								<!--			<?php foreach($history as $serial): ?>-->
								<!--				<tr>-->
								<!--					<td><?= $serial['serial_no']; ?></td>-->
								<!--					<td></td>-->
								<!--					<td><?= getComments($serial['id']); ?></td>-->
								<!--				</tr>-->
								<!--			<?php endforeach; ?>-->
								<!--			</tbody>-->
								<!--		</table>-->
								<!--	</td>-->
								<!--</tr>-->
							<?php } ?>
							
						<?php endforeach; ?>
					</table>
				</div>
			</div>
		</div>
		
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
		<script>
			(function($){
				$('#item-updated-timer').delay(3000).fadeOut('fast');
			})(jQuery);
		</script>
	</body>
</html>
