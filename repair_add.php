<?php

//==============================================================================
//============================ RMA ADDITION SCREEN  ============================
//==============================================================================
//	This screen works with the handling of rma items once they have been 	   |
//	recorded as return items. Originally created by Andrew in early January '17|
//==============================================================================

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
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/getOrderStatus.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/setInventory.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/operations_sidebar.php'; 
	include_once $rootdir.'/inc/display_part.php'; 
	include_once $rootdir.'/inc/getDisposition.php';
	include_once $rootdir.'/inc/order_parameters.php';
	include_once $rootdir.'/inc/getRepairCode.php';
	include_once $rootdir.'/inc/setCostsLog.php';


	//Set initials to be used throughout the page
	$order_number = grab('on');
	$o = o_params("ro");
	
	//Variables used for the post save
	$rma_serial = '';
	$invid = '';
	$itemLocation = '';
	$errorHandler = '';
	$errorHandler = '';
	$place = '';
	$instance = '';
	$rmaArray = array();

	$status = "Active";
	$repair_item_id;
	$sales_order;
	$tracking;
	$received_inventory;
	$ticketStatus;

	$build = $_REQUEST['build'];

	if($build) {
		//get the build # for usage
		$build = $order_number;
		//Get the real number aka the RO number
		$query = "SELECT ro_number FROM builds WHERE id=".prep($order_number).";";
		$result = qdb($query) or die(qe());
		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$order_number = $result['ro_number'];
		} else {
			$query = "SELECT id FROM builds WHERE ro_number=".prep($order_number).";";

			$result = qdb($query) or die(qe());
			if (mysqli_num_rows($result)) {
				$result = mysqli_fetch_assoc($result);
				$build = $result['id'];
			}
		}

		$o = o_params("bo");
	}

	$query = "SELECT status, id as repair_item_id, repair_code_id FROM repair_orders r, repair_items i WHERE r.ro_number =".prep($order_number)." AND r.ro_number = i.ro_number;";
	//echo $query;
	$result = qdb($query) or die(qe());
	if (mysqli_num_rows($result)) {
		$result = mysqli_fetch_assoc($result);
		$status = $result['status'];
		$repair_item_id = $result['repair_item_id'];
		$ticketStatus = getRepairCode($result['repair_code_id']);
	}

	//Check to see if a sales_item record has been created for this item
	//if($status != 'Active') {
		$query = "SELECT so_number FROM sales_items WHERE ref_1_label = 'repair_item_id' AND ref_1 = ".prep($repair_item_id).";";
		$result = qdb($query) or die(qe());
		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$sales_order = $result['so_number'];
		} else {
			$query = "SELECT tracking_no FROM packages WHERE order_type = 'Repair' AND order_number = ".prep($order_number).";";
			$result = qdb($query) or die(qe());
			if (mysqli_num_rows($result)) {
				$result = mysqli_fetch_assoc($result);
				$tracking = ($result['tracking_no'] ? $result['tracking_no'] : 'N/A');
			} else {
				$query = "SELECT id inventoryid FROM inventory WHERE repair_item_id = ".prep($repair_item_id)." AND status <> 'in repair';";
				$result = qdb($query) or die(qe());

				if (mysqli_num_rows($result)) {
					$result = mysqli_fetch_assoc($result);
					$received_inventory = $result['inventoryid'];
				}
			}
		}
		//echo 'hi';
	//}

	//Using the order number from purchase order, get all the parts being ordered and place them on the inventory add page
	function getRepairParts ($order_number) {		
		$listPartid;
		//Only looking for how many parts are in the RMA, distinct as we will retrieve all the serial pertaining to the part later
		$query = "SELECT id, partid, notes, qty FROM repair_items WHERE ro_number = ".prep($order_number)." GROUP BY partid;";
		$result = qdb($query) or die(qe()." $query");
	    
	    if($result)
	    if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$listPartid[] = $row;
			}
		}
		
		return $listPartid;
	}
	

	//This grabs the return specific items based on the rma_number and partid (Used to grab inventoryid for the same part only)
	function getRepairItems($line_id) {

		$query = "
		SELECT i.serial_no, i.locationid, i.repair_item_id, i.conditionid, i.notes, i.id
		FROM inventory i 
		WHERE i.repair_item_id = ".prep($line_id)." AND i.serial_no IS NOT NULL;";
		$result = qdb($query) or die(qe()." | $query");
		
	    if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$listSerial[] = $row;
			}
		}
		
		return $listSerial;
	}

	function process_repair_to_db(){
		global $build, $order_number, $now;

		$place = grab("place");
		$instance = grab("instance");
		$conditionid = grab("condition");
		$serial = strtoupper(trim(grab("serial_number")));
		$repair_item_id = grab("line_id");
		if (! $repair_item_id){ return("No Line!!"); }

		$prep_rid = prep($repair_item_id);
		if($serial){
			$prep_serial = prep($serial);
			$line_item_sel = "SELECT * FROM repair_items WHERE id = $prep_rid;";
			$result = qdb($line_item_sel) or die(qe()." $line_item_sel");
			$row = mysqli_fetch_assoc($result);
			$prep_partid = prep($row['partid']);
			$locationid = 0;
			if($place){
				$locationid = dropdown_processor($place, $instance);
			} else {
				return("NO LOCATION SELECTED ");
			}

			$I = getInventory($serial,$partid);

			// there's no matching inventory record so create one
			if (count($I)==0) {
				$status = 'in repair';
				if (isset($_REQUEST['build']) AND $_REQUEST['build']) { $status = 'received'; }

				$I = array('serial_no'=>$serial,'qty'=>1,'partid'=>$partid,'conditionid'=>$conditionid,'status'=>$status,'locationid'=>$locationid,'repair_item_id'=>$repair_item_id);
				$invid = setInventory($I);
			} else {
				// update inventory with repair item id
				$I = array('id'=>$I['id'],'repair_item_id'=>$repair_item_id);
				$invid = setInventory($I);
			}

			$query = "UPDATE repair_items SET invid = $invid WHERE id = $prep_rid;";
			qdb($query) or die(qe()." $query");

			// DL: moved this from within the INSERT INTO inventory section above, thinking that even if this is an
			// unlikely scenario where a build is taking an existing inventory item into its build, it's potentially
			// still relevant and it's not hurting anything by being here...

			//If this is a build then insert into inventory cost log and inventory cost
			if($build) {
				setCost($invid);

/*
				$query = "SELECT price FROM builds WHERE ro_number = ".prep($order_number).";";
				$result = qdb($query) or die(qe()." | $query");
				if(mysqli_num_rows($result)){
					$result = mysqli_fetch_assoc($result);
					$build_price = $result['price'];
				}
*/
			}
		}
		if(isset($_REQUEST['notes'])){
			foreach($_REQUEST['notes'] as $invid => $note){
				$I = array('id'=>$invid,'notes'=>$note);
				$id = setInventory($I);
			}
		}

		return($repair_item_id);
	}

	if ($_REQUEST["form_submitted"]){
		$result = process_repair_to_db();
	}
	
	$active = $_REQUEST["line_id"];
	$sel_place = $_REQUEST["place"];
	$sel_instance = $_REQUEST["instance"];
	$sel_condition = $_REQUEST["condition"];
	$partsListing = getRepairParts($order_number);

	$outstanding = 0;
	$status = getOrderStatus($o['type'],$order_number);

	if(!empty($partsListing)) {
		$results = count($partsListing);
		foreach($partsListing as $part): 
			$serials = getRepairItems($repair_item_id);
		 	$outstanding = $part['qty'] - count($serials);
			break;
		endforeach;
	}
?>



<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<title><?=($build?'Build':'Repair')?> Receive <?=($order_number != 'New' ? '#' . ($build?$build:$order_number) : '')?></title>
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
				/*display: none;*/
			}
			
			.serialInput {
				text-transform: uppercase;
			}
			body.modal-open#rma-add {
				margin-right: 0;
			}

			.ticket_status_danger {
				color: #a94442;
			}
			.ticket_status_success {
				color: #3c763d;
			}
			.ticket_status_warning {
				color: #8a6d3b;
			}
		</style>
	</head>
	
	<?php include_once $rootdir.'/modal/repair_receive.php'; ?>

	<body class="sub-nav" id="rma-add" >
	<!-- Begin the header output  -->
		<div class="container-fluid pad-wrapper data-load">
		<?php include 'inc/navbar.php';?>
		<div class="row table-header" id = "order_header" style="margin: 0; width: 100%;">
			<div class="col-sm-4 text-left">
				<?php if(in_array("1", $USER_ROLES) || in_array("4", $USER_ROLES) || in_array("5", $USER_ROLES) || in_array("7", $USER_ROLES)) { ?>
					<?php if($build): ?>
						<a href="/builds_management.php?on=<?php echo $build; ?>" class="btn btn-default btn-sm"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
					<?php else : ?>
						<a href="/order_form.php<?='?ps=repair&on='.$order_number;?>" class="btn btn-default btn-sm""><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
					<?php endif; ?>
				<?php } ?>

				<?php if((strtolower($status) != 'voided' && strpos(strtolower($status), 'canceled') === false) && $status) { 
						if($sales_order && $o['type'] == 'Repair') { ?>
					<div class ="btn-group">
						<button type="button" class="btn btn-default text-success btn-sm dropdown-toggle" data-toggle="dropdown">
							<i class="fa fa-truck"></i> Ship
							<span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                        	<li>
								<a href="/shipping.php?on=<?=$sales_order;?>"><i class="fa fa-truck"></i> Ship</a>
							</li>
						</ul>
					</div>
				<?php } else if($tracking && $o['type'] == 'Repair') { ?>
					<div class ="btn-group">
						<button type="button" class="btn btn-default text-success btn-sm dropdown-toggle" data-toggle="dropdown">
							<i class="fa fa-truck"></i> Ship
							<span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                        	<li>
								<small><span style="padding: 3px 10px;">Trk# <?=$tracking;?></span></small>
							</li>
						</ul>
					</div>
				<?php } else if($received_inventory && $o['type'] == 'Repair') { ?>
					<div class ="btn-group">
						<button type="button" class="btn btn-default text-success btn-sm dropdown-toggle" data-toggle="dropdown">
							<i class="fa fa-truck"></i> Ship
							<span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                        	<li>
								<span style="padding: 3px 20px;">Returned to Stock</span>
							</li>
						</ul>
					</div>
				<?php } else if($o['type'] == 'Repair' && $order_number!='New') { ?>
					<form id="repair_ship" action="repair_shipping.php" method="POST" style="display:inline">
						<div class ="btn-group">
							<button type="button" class="btn btn-default text-success btn-sm dropdown-toggle" data-toggle="dropdown">
								<i class="fa fa-truck"></i> Ship
								<span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                            	<li>
									<a class="ship" data-ship="ship" type="submit" name="ro_number" value="<?=$order_number?>" href="#"><i class="fa fa-truck"></i> Ship</a>
								</li>
								<li>
									<a class="ship" data-ship="stock" id="stock_order" type="submit" name="ro_number" value="<?=$order_number?>" href="#"><i class="fa fa-list"></i> Return to Stock</a>
								</li>
							</ul>
						</div>
					</form>
				<?php } } ?>
				<a href="/repair.php?on=<?=($build ? $build . '&build=true' : $order_number);?>" class="btn btn-primary btn-sm"><i class="fa fa-wrench"></i> Tech View</a>
			</div>
			<div class="col-sm-4 text-center" style="padding-top: 5px;">
				<h2><?=($build ? 'Build':'Repair');?> #<?= ($build?$build:$order_number).' Receiving'; ?></h2>
			</div>
			<div class="col-sm-4">
			</div>
		</div>

<?php
		if ($ticketStatus) {
			echo '
				<div class="alert alert-default" style="padding:5px; margin:0px">
					<h3 class="text-center">
						<span class="ticket_status_'.(strpos(strtolower($ticketStatus), 'unrepairable') !== false || strpos(strtolower($ticketStatus), 'voided') !== false || strpos(strtolower($ticketStatus), 'canceled') !== false ? 'danger' : (strpos(strtolower($ticketStatus), 'trouble') ? 'warning' : 'success')).'">' .ucwords($ticketStatus) . '</span>
					</h3>
				</div>
			';
		}
?>
		
		<!--Add in Error message-->
		<?php if($errorHandler != ''): ?>
			<div id="item-updated-timer" class="alert alert-danger fade in text-center" style="margin-bottom: 0px; width: 100%; z-index: 9999; top: 95px;">
			    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
			    <strong>Error!</strong> <?=$errorHandler;?>
			</div>
		<?php endif; ?>
		
			<!-------------------- $$ OUTPUT THE MACRO INFORMATION -------------------->
			<div class="col-md-2 rma_sidebar" data-page="addition" style="padding-top: 15px;">
				<?=sidebar_out($order_number,$o['type'],"display")?>
				<!--<div class="row">-->
				<!--	<div class="col-sm-12" style="padding-bottom: 10px;font-size:14pt; ">						-->
				<!--		Repair Ticket #12451-->
				<!--	</div>-->
				<!--</div>-->
			</div>
			
			<div class="col-sm-10"  style="margin-top: 20px;">
				<form method="post">
					<?php if($outstanding ): ?>
						<div class="row" style="margin-bottom: 20px; margin-left: 0;">
							<div class="col-md-7" style="padding-left: 0px !important;">
								<div class="col-md-6 location">
									<div class="row">
										<div class="col-md-4" style="padding-left: 0px !important;">
											<?=loc_dropdowns('place', $sel_place)?>
										</div>
										
										<div class="col-md-3">
											<?=loc_dropdowns('instance', $sel_instance, $sel_place)?>
										</div>

										<div class="col-md-5">
											<?=dropdown('conditionid', ($build ? '2' : '-5'), ($build ? '' : 'repair'), '',false)?>
										</div>
									</div>
								</div>
								
								<div class="col-md-5" style="padding: 0 0 0 5px;">
								    <input class="form-control input-sm serialInput auto-focus" name="serial_number" type="text" placeholder="Serial" value="<?=($rma_serial ? $rma_serial : '');?>" autofocus <?=($outstanding  ? '' : 'disabled');?>>
					            </div>
					            <div class="col-md-1" style="padding: 0 0 0 5px;">
									<button class="btn btn-sm btn-primary" type='submit' <?=($outstanding || $build ? '' : 'disabled');?>>Submit</button>
								</div>
							    <input class="form-control input-sm serialInput" style='display:none' name="form_submitted" type="text" value="true" autofocus>
				            </div>
						</div>
					<?php endif; ?>
			
					<div class="table-responsive">
						<table class="rma_add table table-hover table-striped table-condensed" style="table-layout:fixed;"  id="items_table">
							<thead>
						         <tr>
						            <th class="col-sm-3">
						            	PART	
						            </th>
						            <th class="text-left col-sm-1">
										Remaining Qty
						        	</th>
						        	<th class="text-center col-sm-1">
										Serial
						        	</th>
						        	<th class="text-center col-sm-2">
										Location
						        	</th>
						        	<th class="text-center col-sm-1">
						        		Condition
						        	</th>
						        	<th class="col-sm-3">
										Notes
						        	</th>
						         </tr>
							</thead>
							
							<tbody>
							<?php 
								//Grab all the parts from the specified PO #
								if(!empty($partsListing)) {
									$results = count($partsListing);
									foreach($partsListing as $part): 
										$serials = getRepairItems($repair_item_id);
	
							?>
									<tr>
	
										<td>
											<input type="radio" name="line_id" class="pull-left" style="margin-right: 10px;margin-top:12px;" value = '<?=$part['id']?>' <?=(($results == 1 || $active == $part['id']) ? 'checked' : "")?>>
											<div class="product-img pull-left"><img class="img" src="/img/parts/<?php echo $part; ?>.jpg" alt="pic"></div>
											<div>
											<?=display_part(current(hecidb($part['partid'],'id')));?>
											</div>
											<div class="meta_notes">
												<?=(($part['notes'])?"<br>Notes: ".$part['notes']:"")?>
											</div>
										</td>
										<td>
											<?=$part['qty'] - count($serials);?>
										</td>
										<td class="serials_col">
											<?php 
												if(!empty($serials)):
													foreach($serials as $item) { 
											?>
													<div class="row">
															<span class="text-center" style="display: block; padding: 7px 0; margin-bottom:4px;"><?=$item['serial_no'];?></span>
													</div>
											<?php 
													} 
												endif;
											?>
										</td>
										<td class="location_col">
											<?php 
												if(!empty($serials)):
													foreach($serials as $item) { 
											?>
													<div class="row">
														<!--<div class="input-group">-->
															<span class="text-center" style="display: block; padding: 7px 0; margin-bottom: 4px;"><?=display_location($item['locationid']);?></span>
															<!--<span class="input-group-addon">-->
														
															<!--</span>-->
														<!--</div>-->
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
															<span class="text-center" style="display: block; padding: 7px 0; margin-bottom: 4px;"><?=getCondition($item['conditionid'])?></span>
													</div>
											<?php 
													} 
												endif;
											?>	
										</td>
										<td class="notes_col">
											<?php 
												if(!empty($serials)):
													foreach($serials as $item) { 
											?>
													<div class="row">
														<input type="text" class = 'form-control' name="notes['<?=$item['id']?>']" value = "<?=$item['notes']?>"/>
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
				</form>
			</div>
		</div> 
		
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js"></script>
	
	</body>
</html>
