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
	$selected_account;
	$exchange = false;
	
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

	function format($partid, $desc = true){
		$r = reset(hecidb($partid, 'id'));
		$parts = explode(' ',$r['part']);
	    $display = "<span class = 'descr-label'>".$parts[0]." &nbsp; ".$r['heci']."</span>";
	    if($desc)
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf']).' &nbsp; '.dictionary($r['system']).
				'</span> <span class="description-label">'.dictionary(substr($r['description'],0,30)).'</span></div>';

	    return $display;
	}
	
	if (grab('exchange')){
		$exchange = grab('exchange');
	}
	
	$items = getItems($sales_order, $exchange);
?>
	

<!DOCTYPE html>
<html>
	<head>
		<title>Repair <?=($order_number != 'New' ? '#' . $order_number : '')?></title>
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
			include_once $rootdir.'/modal/component_request.php';
		?>
		<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color:#f0f4ff;">
			<div class="col-md-4">
				<?php if(in_array("3", $USER_ROLES) || in_array("1", $USER_ROLES)) { ?>
				<a href="/order_form.php?on=<?php echo $order_number; ?>&ps=s" class="btn-flat info pull-left" style="margin-top: 10px;"><i class="fa fa-list-ul" aria-hidden="true"></i> Manage Order</a>
				<?php } ?>
			</div>
			
			<div class="col-md-4 text-center">
				<?php
					echo"<h2 class='minimal shipping_header' style='padding-top: 10px;' data-so='". $order_number ."'>";
						echo "Repair Ticket ";
					if ($order_number!='New'){
						echo "#$order_number";
					}
					if (strtolower($status) == 'void'){
						echo ("<b><span style='color:red;'> [VOIDED]</span></b>");
					}
					echo"</h2>";
				?>
			</div>
			<div class="col-md-4">
				<!-- <button class="btn-flat success pull-right btn-update" id="iso_report" data-datestamp = "<?= getDateStamp($order_number); ?>" style="margin-top: 10px; margin-right: 10px;">Check In</button> -->
				<button class="btn-flat danger pull-right btn-update" id="iso_report" data-datestamp = "<?= getDateStamp($order_number); ?>" style="margin-top: 10px; margin-right: 10px;">Check Out</button>
				<button class="btn-flat info pull-right btn-update" id="iso_report" data-datestamp = "<?= getDateStamp($order_number); ?>" style="margin-top: 10px; margin-right: 10px;">Claim Ticket</button>
			</div>
		</div>
		
		<?php if($ro_updated == 'true'): ?>
			<div id="item-updated-timer" class="alert alert-success fade in text-center" style="position: fixed; width: 100%; z-index: 9999; top: 95px;">
			    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
			    <!-- <strong>Success!</strong> <?= ($po_updated ? 'Purchase' : 'Sales'); ?> Order Updated. -->
			</div>
		<?php endif; ?>

		<div class="loading_element">
			<div class="row remove-margin">
				<!--================== Begin Left Half ===================-->
				<div class="left-side-main col-sm-2">
					<div class="row company_meta left-sidebar" style="height:100%; padding: 0 10px;">		
						<div class="sidebar-container">
							<div class="row">
								<div class="col-sm-12" style="padding-bottom: 10px; margin-top: 15px;">						
									<div class="order">
										<?=$order_number;?>
									</div>
								</div>
							</div>

							<div class="row">
								<div class="col-md-12">
									<b style="color: #526273;font-size: 14px;">Rep</b><br><br>
									<b style="color: #526273;font-size: 14px;">Due</b><br><br>
									<b style="color: #526273;font-size: 14px;">Notes</b><br>
									Lorem ipsum dolor sit amet, bonorum imperdiet duo ne, homero legere in quo, ea ridens audiam dissentiunt sed.
									<br>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!--======================= End Left half ======================-->
				
				<div class="col-sm-10 shipping-list" style="padding-top: 20px">
					<div class="row">
						<div class="col-md-6">
							<div class="table-responsive">
								<table class="table table-hover table-striped table-condensed" style="margin-top: 15px;">
									<thead>
										<tr>
											<th class="col-md-6">Part Number</th>
											<th class="col-md-6">SERIAL</th>
										</tr>
									</thead>
									<?php
										//Grab a list of items from an associated sales order.
										// $serials = array();
										// if(!empty($items))
										// foreach($items as $item): 
										// 	$inventory = getInventory($item['partid']);
										// 	$select = "SELECT DISTINCT `serial_no`, i.id, `packageid`, p.datetime FROM `inventory` AS i, `package_contents`, `packages` AS p WHERE i.id = serialid AND sales_item_id = ".prep($item['id'])." AND p.id = packageid AND p.order_number = ".prep($order_number).";";
										// 	$serials = qdb($select);
										// 	//print_r($inventory);
										// 	$parts = explode(' ',getPartName($item['partid']));
										// 	$part = $parts[0];
									?>
										<tr class="" style = "padding-bottom:6px;">
											<td><?=format('311173', true);?></td>
											<td>9AC4DCSDGT</td>
										</tr>
										
									<?php //endforeach; ?>
								</table>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-6">	
							<div class="table-responsive">
								<table class="table table-hover table-striped table-condensed" style="margin-top: 15px;">
									<thead>
										<tr>
											<th>Date / Time</th>
											<th>Tech</th>
											<th>Activity 
												
										    </th>
										</tr>
									</thead>
									<?php
										//Grab a list of items from an associated sales order.
										// $serials = array();
										// if(!empty($items))
										// foreach($items as $item): 
										// 	$inventory = getInventory($item['partid']);
										// 	$select = "SELECT DISTINCT `serial_no`, i.id, `packageid`, p.datetime FROM `inventory` AS i, `package_contents`, `packages` AS p WHERE i.id = serialid AND sales_item_id = ".prep($item['id'])." AND p.id = packageid AND p.order_number = ".prep($order_number).";";
										// 	$serials = qdb($select);
										// 	//print_r($inventory);
										// 	$parts = explode(' ',getPartName($item['partid']));
										// 	$part = $parts[0];
									?>
										<tr class="" style = "padding-bottom:6px;">
											<td><?=format_date($now, 'n/j/y, H:i:s');?></td>
											<td>Aaron</td>
											<td>Broke Everything</td>
										</tr>

										<tr class="" style = "padding-bottom:6px;">
											<td><?=format_date($now, 'n/j/y, H:i:s');?></td>
											<td>David</td>
											<td>Fixed Everything</td>
										</tr>

										<tr class="" style = "padding-bottom:6px;">
											<td><?=format_date($now, 'n/j/y, H:i:s');?></td>
											<td>Andrew</td>
											<td>Broke it again</td>
										</tr>
										
									<?php //endforeach; ?>
								</table>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-12">	
							<div class="table-responsive">
								<table class="table table-hover table-striped table-condensed" style="margin-top: 15px;">
									<thead>
										<tr>
											<th>Component</th>
											<th>Order Qty</th>
											<th>Available Qty</th>
											<th>PO
												<button data-toggle="modal" data-target="#modal-component" class="btn btn-flat btn-sm btn-status middle filter_status pull-right" type="submit" data-filter="complete">
										        	<i class="fa fa-plus"></i>	
										        </button>
			        						</th>
										</tr>
									</thead>
									<?php
										//Grab a list of items from an associated sales order.
										// $serials = array();
										// if(!empty($items))
										// foreach($items as $item): 
										// 	$inventory = getInventory($item['partid']);
										// 	$select = "SELECT DISTINCT `serial_no`, i.id, `packageid`, p.datetime FROM `inventory` AS i, `package_contents`, `packages` AS p WHERE i.id = serialid AND sales_item_id = ".prep($item['id'])." AND p.id = packageid AND p.order_number = ".prep($order_number).";";
										// 	$serials = qdb($select);
										// 	//print_r($inventory);
										// 	$parts = explode(' ',getPartName($item['partid']));
										// 	$part = $parts[0];
									?>
										<tr class="" style = "padding-bottom:6px;">
											<td><?=format('311173', true);?></td>
											<td></td>
											<td></td>
											<td></td>
										</tr>
										
									<?php //endforeach; ?>
								</table>
							</div>
						</div>
					</div>
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
