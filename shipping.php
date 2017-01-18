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
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/form_handle.php';
	
	//include_once $rootdir.'/inc/order-creation.php';
	
	$order_number = $_REQUEST['on'];
	$order_type = "Sales";
	
	//If no order is selected then return to shipping home
	if(empty($order_number)) {
		header("Location: /shipping_home.php");
		die();
	}
	
	$sales_order;
	
	//get the information based on the order number selected
	$query = "SELECT * FROM sales_orders WHERE so_number = ". prep($order_number) .";";
	$result = qdb($query) OR die(qe());
	
	if (mysqli_num_rows($result)>0) {
		$result = mysqli_fetch_assoc($result);
		$sales_order = $result['so_number'];
	}
	
	function getItems($so_number = 0) {
		$sales_items = array();
		
		//First run a check just in case the sales order was changed recently and reflect the changes (E.G. qty order was increase, if qty is less than order admin may need to intervene)
		
		$query = "UPDATE sales_items SET ship_date = NULL WHERE so_number = ". res($so_number) ." AND qty_shipped < qty;";
		qdb($query);
		
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
	

?>
	

<!DOCTYPE html>
<html>
	<head>
		<title>Shipping</title>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css" type="text/css" />
		
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
		</style>

	</head>
	
	<body class="sub-nav" id = "order_body" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
	<!----------------------- Begin the header output  ----------------------->
		<?php include 'inc/navbar.php'; 
		include_once $rootdir.'/modal/package.php';
		?>
		<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color: #f7fff1">
			<div class="col-md-4">
				<a href="/order_form.php?on=<?php echo $order_number; ?>&ps=s" class="btn btn-info pull-left" style="margin-top: 10px;"><i class="fa fa-list-ul" aria-hidden="true"></i> Order Info</a>
			</div>
			<div class="col-md-4 text-center">
				<?php
				echo"<h1>";
				echo " Shipping Order";
				if ($order_number!='New'){
					echo " #$order_number";
				}
				echo"</h1>"
				?>
			</div>
			<div class="col-md-4">
				<button class="btn btn-success pull-right btn-update" id="btn_update" style="margin-top: 10px;">Update Order</button>
			</div>
		</div>
		<div class="loading_element">
			<!--================== Begin Left Half ===================-->
			<div class="left-side-main col-sm-2" style="height: 100%;">
				<!-- Everything here is put out by the order creation ajax script -->
			</div>
			<!--======================= End Left half ======================-->
			
			<div class="col-sm-10 shipping-list" style="padding-top: 20px">
				<div class = 'row-fluid'>
					<div class = 'col-sm-3'>
						<h3>Items to be Shipped</h3>
					</div>
					<div class="col-sm-9">
						<div class="btn-group" style = "padding-bottom:16px;">
							<button type="button" class="btn btn-warning box_edit" title = 'Edit Selected Box'>
								<i class="fa fa-pencil fa-4" aria-hidden="true"></i>
							</button>
							<?php
								$select = "SELECT * FROM `packages`  WHERE  `order_number` = '$order_number'";
								$results = qdb($select);
								
									$box_drop = "<div>\
			            				<select class='form-control box_drop'>";
								if (mysqli_num_rows($results) > 0){
									foreach($results as $b){
										$box_button = "<button type='button' class='btn btn-grey box_selector'";
										$box_button .= " data-width = '".$b['weight']."' data-l = '".$b['length']."' ";
										$box_button .= " data-h = '".$b['height']."' data-weight = '".$b['weight']."' ";
										$box_button .= " data-row-id = '".$b['id']."' data-tracking = '".$b['tracking']."' ";
										$box_button .= " data-row-freight = '".$b['freight-amount']."'";
										$box_button .= ">".$b['package_no']."</button>";
										echo($box_button);
			                        	
			                        	$box_drop .= "<option value='".$b['id']."'>Box ".$b['package_no']."</option>";
									}
								}
								else{
									$insert = "INSERT INTO `packages`(`order_number`,`package_no`) VALUES ($order_number, '1');";
									qdb($insert);
									echo("<button type='button' class='btn btn-grey box_selector' data-row-id = '".qid()."'>1</button>");
								}
								$box_drop .= 
			    			    		"</select>
			    	        		</div>";
								
								
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
								<th>SERIAL	(*SCAN OR PRESS ENTER ON INPUT FOR MORE)</th>
								<th>Qty to be Shipped</th>
								<!--<th>Location</th>-->
								<th>Item Condition</th>
								<th>Ship by</th>
								<th>Ship Date</th>
								<th>Box #</th>
								<th>Lot Shipment</th>
							</tr>
						</thead>
						<?php 
							$items = getItems($sales_order);
							foreach($items as $item): 
								// $inventory = getInventory($item['partid']);
								// print_r($inventory);
								// $location = ($inventory['locationid'] ? getLocation($inventory['locationid']) : '');
						?>
							<tr class="<?php echo (!empty($item['ship_date']) ? 'order-complete' : ''); ?>">
								<td class="part_id" data-partid="<?php echo $item['partid']; ?>" data-part="<?php echo getPartName($item['partid']); ?>" style="padding-top: 15px !important;">
									<strong><?php echo getPartName($item['partid']); ?></strong>
								</td>
								<td class="infiniteSerials">
									<div class="input-group" style="margin-bottom: 6px;">
									    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-saved="" <?php echo ($item['qty'] - $item['qty_shipped'] == 0 ? 'disabled' : ''); ?>>
									    <span class="input-group-addon">
									        <button class="btn btn-secondary deleteSerialRow" type="button" disabled><i class="fa fa-trash fa-4" aria-hidden="true"></i></button>
									    </span>
						            </div>
									<!--<input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-saved="" style="margin-bottom: 5px;" <?php echo (!empty($item['ship_date']) ? 'disabled' : ''); ?>>-->
								</td>
								<td class="remaining_qty">
									<input class="form-control input-sm" data-qty="" name="qty" value="<?php echo $item['qty'] - $item['qty_shipped']; ?>" readonly>
								</td>
								<td style="padding-top: 15px !important;">
									<span class="condition_field" data-condition="<?php echo $item['cond'] ?>"><?php echo $item['cond'] ?></span>
								</td>
								<td style="padding-top: 15px !important;">
									<?php echo (!empty($item['delivery_date']) ? date_format(date_create($item['delivery_date']), "m/d/Y") : ''); ?>
								</td>
								<td class="ship-date" style="padding-top: 15px !important;">
									<?php echo (!empty($item['ship_date']) ? date_format(date_create($item['ship_date']), "m/d/Y") : ''); ?>
								</td>
								<td>
									<?=$box_drop?>
								</td>
								<td>
									<div class="checkbox">
										<label><input class="lot_inventory" style="margin: 0 !important" type="checkbox" <?php echo (!empty($item['ship_date']) ? 'disabled' : ''); ?>></label>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
			</div>
		</div>
		
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js"></script>
	</body>
</html>