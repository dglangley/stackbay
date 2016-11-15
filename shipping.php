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
	//include_once $rootdir.'/inc/order-creation.php';
	
	$order_number = $_REQUEST['order_no'];
	$order_type = "Sales";
	
	//If no order is selected then return to shipping home
	if(empty($order_number)) {
		header("Location: /shipping_home.php");
		die();
	}
	
	$sales_order;
	
	//get the information based on the order number selected
	$query = "SELECT * FROM sales_orders WHERE so_number = ". res($order_number) .";";
	$result = qdb($query) OR die(qe());
	
	if (mysqli_num_rows($result)>0) {
		$result = mysqli_fetch_assoc($result);
		$sales_order = $result['so_number'];
	}
	
	// print_r($sales_order);
	
	function getItems($so_number = 0) {
		$sales_items = array();
		
		$query = "SELECT * FROM sales_items WHERE so_number = ". res($so_number) .";";
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
		
		$query = "SELECT * FROM inventory WHERE partid = ". res($partid) .";";
		$result = qdb($query) OR die(qe());
	
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$inventory = $result;
		}
		
		return $inventory;
	}
	
	function getLocation($locationid){
		$location;
		
		$query = "SELECT * FROM locations WHERE id = ". res($locationid) .";";
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$location = $result;
		}
		
		return $location;
	}
	
	function getWarehouse($warehouseid) {
		$warehouse;
		
		$query = "SELECT * FROM warehouses WHERE id = ". res($warehouseid) .";";
		$result = qdb($query);
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$location = $result['name'];
		}
		
		return $warehouse;
	}
	
	
	// function getAddress($addressid = 0) {
	// 	$address;
		
	// 	$query = "SELECT * FROM addresses WHERE id = ". res($addressid) .";";
	// 	$result = qdb($query) OR die(qe());
		
	// 	if (mysqli_num_rows($result)>0) {
	// 		$result = mysqli_fetch_assoc($result);
	// 		$address = $result[''];
	// 	}
		
	// 	return $address;
	// }

?>

<!DOCTYPE html>
<html>
	<head>
		<title>Shipping</title>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css" type="text/css" />

	</head>
	
	<body class="sub-nav" id = "order_body" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
	<!----------------------- Begin the header output  ----------------------->
		<?php include 'inc/navbar.php'; ?>
		<div class="loading_element">
			<!--================== Begin Left Half ===================-->
			<div id="left-side-main">
				<!-- Everything here is put out by the order creation ajax script -->
			</div>
			<!--======================= End Left half ======================-->
			
			<div class="col-sm-10 shipping-list" style="padding-top: 20px">
				<button class="btn btn-success pull-right" style="margin-top: -5px;">Update Order</button>
				<h3>Items to be Shipped</h3>
				<!--<hr style="margin-top : 10px;">-->
			
				<div class="table-responsive">
					<table class="table table-hover table-striped table-condensed" style="margin-top: 15px;">
						<thead>
							<tr>
								<th>Item</th>
								<th>Serial</th>
								<th>Qty</th>
								<th>Location</th>
								<th>Box #</th>
								<th>Condition</th>
								<th>Shipped</th>
							</tr>
						</thead>
						<?php foreach(getItems($sales_order) as $item): 
								$inventory = getInventory($item['partid']);
								$location = ($inventory['locationid'] ? getLocation($inventory['locationid']) : '');
						?>
							<tr>
								<td>
									<strong><?php echo getPartName($item['partid']); ?></strong>
								</td>
								<td>
									<?php echo $inventory['serial_no']; ?>
								</td>
								<td>
									<?php echo $inventory['qty']; ?>
								</td>
								<td>
									<?php echo (!empty($location) ? getWarehouse($location['warehouseid']) . ' ' . $location['aisle'] . ': ' . $location['shelf'] : '') ?>
								</td>
								<td>
									<?php //$inventory['item_condition'] ?>
								</td>
								<td>
									<?php $inventory['item_condition'] ?>
								</td>
								<td>
									<div class="checkbox">
										<label><input type="checkbox" data-serial="<?php echo $inventory['serial_no']; ?>" value="" <?php echo (!empty($item['delivery_date']) ? 'checked disabled' : ''); ?>> <?php echo (!empty($item['delivery_date']) ? date_format(date_create($item['delivery_date']), "m/d/Y") : ''); ?></label>
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