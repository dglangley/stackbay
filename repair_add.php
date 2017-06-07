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
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/operations_sidebar.php'; 
	include_once $rootdir.'/inc/display_part.php'; 
	include_once $rootdir.'/inc/getDisposition.php';
	include_once $rootdir.'/inc/order_parameters.php';


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

	$query = "SELECT status, id as repair_item_id FROM repair_orders r, repair_items i WHERE r.ro_number =".prep($order_number)." AND r.ro_number = i.ro_number;";
	$result = qdb($query) or die(qe());
	if (mysqli_num_rows($result)) {
		$result = mysqli_fetch_assoc($result);
		$status = $result['status'];
		$repair_item_id = $result['repair_item_id'];
	}

	//Check to see if a sales_item record has been created for this item
	if($status == 'Completed') {
		$query = "SELECT so_number FROM sales_items WHERE ref_1_label = 'repair_item_id' AND ref_1 = ".prep($repair_item_id).";";
		$result = qdb($query) or die(qe());
		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$sales_order = $result['so_number'];
		}
	}

	
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
		WHERE id in (
			SELECT DISTINCT invid FROM inventory_history WHERE field_changed = 'repair_item_id' AND value = ".prep($line_id)."
		);";
		$result = qdb($query) or die(qe()." | $query");
		
	    if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$listSerial[] = $row;
			}
		}
		
		return $listSerial;
	}

	function process_repair_to_db(){
			
			$place = grab("place");
			$instance = grab("instance");
			$condition = prep(grab("condition"));
			$serial = grab("serial_number");
			$rlineid = grab("line_id");
			if(!$rlineid){
				return("No Line!!");
			} else {
				$rlineid = prep($rlineid);
			}
			if($serial){
				$serial = prep(strtoupper($serial));
				$line_item_sel = "SELECT * FROM repair_items where id = $rlineid;";
				$result = qdb($line_item_sel) or die(qe()." $line_item_sel");
				$row = mysqli_fetch_assoc($result);
				$partid = prep($row['partid']);
				if($place){
					$location_id = prep(dropdown_processor($place, $instance));
				} else {
					return("NO LOCATION SELECTED ");
				}
				
				$quick_check = "SELECT * FROM inventory where `serial_no` like $serial AND repair_item_id = $rlineid;";
				$res = qdb($quick_check) or die(qe()." $quick_check");
				if(!mysqli_num_rows($res)){
					$insert = "INSERT INTO `inventory`(`serial_no`, `qty`, `partid`, 
					`conditionid`, `status`, `locationid`, `repair_item_id`, `userid`, `date_created`, `notes`) 
					VALUES ($serial,1,$partid,$condition,'in repair',$location_id,$rlineid,".$GLOBALS['U']['id'].",NOW(),NULL)";
					
					qdb($insert) or die(qe()." $insert");
				} else {
					return"ALREADY SCANNED THIS PART FOR THIS RECORD";
				}
			}
			if(isset($_REQUEST['notes'])){
				foreach($_REQUEST['notes'] as $invid => $note){
					$update = "UPDATE inventory SET `notes` = ".prep($note)." WHERE id = $invid;";
					qdb($update) or die(qe()." $update");
				}
			}
		return($rlineid);
	}
	if ($_REQUEST["form_submitted"]){
		$result = process_repair_to_db();
	}
	
	$active = $_REQUEST["line_id"];
	$sel_place = $_REQUEST["place"];
	$sel_instance = $_REQUEST["instance"];
	$sel_condition = $_REQUEST["condition"];
	$partsListing = getRepairParts($order_number);
?>



<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<title>Repair Receive <?=($order_number != 'New' ? '#' . $order_number : '')?></title>
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
		</style>
	</head>
	
	<body class="sub-nav" id="rma-add" >
	<!-- Begin the header output  -->
		<div class="container-fluid pad-wrapper data-load">
		<?php include 'inc/navbar.php';?>
		<div class="row table-header" id = "order_header" style="margin: 0; width: 100%;">
			<div class="col-sm-4"><a href="/order_form.php?ps=repair&on=<?=$order_number;?>" class="btn-flat info pull-left" style="margin-top: 10px;"><i class="fa fa-list" aria-hidden="true"></i> Manage Repair</a></div>
			<div class="col-sm-4 text-center" style="padding-top: 5px;">
				<h2><?=($status == "Completed" ? "Completed" : "");?> Repair #<?= $order_number.' Receiving'; ?></h2>
			</div>
			<div class="col-sm-4">
				<?php if($status == "Completed") { ?>
					<?php if($sales_order) { ?>
						<a href="/shipping.php?on=<?=$sales_order;
						?>" class="btn-flat success pull-right" style="margin-top: 10px; margin-right: 10px;"><i class="fa fa-truck"></i> Ship</a>
					<?php } else { ?>
						<form action="repair_shipping.php" method="POST">
							<button type="submit" name="ro_number" value="<?=$order_number?>" class="btn-flat success pull-right" style="margin-top: 10px; margin-right: 10px;"><i class="fa fa-truck"></i> Ship</button>
						</form>
					<?php } ?>
				<?php } ?>
			</div>
		</div>
		
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
			
			<div class="col-sm-10">
				<form method="post">
					<div class="row" style="margin: 20px 0;">
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
										<?=dropdown('conditionid', $sel_condition, '', '',false)?>
									</div>
								</div>
							</div>
							
							<div class="col-md-5" style="padding: 0 0 0 5px;">
							    <input class="form-control input-sm serialInput auto-focus" name="serial_number" type="text" placeholder="Serial" value="<?=($rma_serial ? $rma_serial : '');?>" autofocus>
				            </div>
				            <div class="col-md-1" style="padding: 0 0 0 5px;">
								<button type='submit'>submit</button>
							</div>
						    <input class="form-control input-sm serialInput" style='display:none' name="form_submitted" type="text" value="true" autofocus>
			            </div>
					</div>
			
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
										$serials = getRepairItems($part['id']);
	
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
											<?=$part['qty'] - count($serials)?>
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
