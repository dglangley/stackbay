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
	include_once $rootdir.'/inc/getContact.php';
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
	$order_type = "Tech";
	
	$so_updated = $_REQUEST['success'];
	
	if(empty($order_number)) {
		//header("Location: /shipping_home.php");
		//die();
	}
	
	
	$repair_order;
	$notes;
	$sales_rep_id;
	$status;
	$due_date;
	$repair_item_id;
	$exchange = false;
	
	//get the information based on the order number selected
	$query = "SELECT * FROM repair_orders WHERE ro_number = ". prep($order_number) .";";
	$result = qdb($query) OR die(qe());
	
	if (mysqli_num_rows($result)>0) {
		$result = mysqli_fetch_assoc($result);
		$repair_order = $result['ro_number'];
		$notes = $result['public_notes'];
		$sales_rep_id = $result['sales_rep_id'];
		$status = $result['status'];
	}
	
	function getItems($ro_number = 0) {
		$repair_items = array();
		$query;
		
		$query = "SELECT * FROM repair_items WHERE ro_number = ". prep($ro_number) .";";
		$result = qdb($query) OR die(qe());
				
		while ($row = $result->fetch_assoc()) {
			$repair_items[] = $row;
		}
		
		return $repair_items;
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

	function grabActivities($ro_number, $repair_item_id){
		$repair_activities = array();
		$query;
		
		$query = "SELECT techid, requested as datetime, CONCAT('Component Requested Part #', partid, ' Qty: ', qty) as notes FROM purchase_requests WHERE ro_number = ".prep($ro_number)."  
				UNION
				SELECT techid, datetime as datetime, notes FROM repair_activities WHERE ro_number = ".prep($ro_number)." 
				UNION
				SELECT userid as techid, date_created as datetime, 'Received' as notes FROM inventory WHERE id in (SELECT invid FROM inventory_history where field_changed = 'repair_item_id' and `value` = ".prep($repair_item_id).")
				UNION
				SELECT `userid` as techid, `date_changed` as datetime, CONCAT('Status changed from ', `changed_from`, ' to ', `value` ) as notes FROM `inventory_history` where `field_changed` = 'status' AND `invid` in (
					SELECT `invid` FROM `inventory_history` where `field_changed` = 'repair_item_id' and `value` = ".prep($repair_item_id)."
					)
				ORDER BY datetime DESC;";
		// $query = "SELECT techid, requested as datetime, CONCAT('Component Requested Part #', partid, ' Qty: ', qty) as notes FROM purchase_requests WHERE ro_number = ".prep($ro_number)." 
		// 		UNION
		// 		SELECT techid, datetime as datetime, notes FROM repair_activities WHERE ro_number = ".prep($ro_number)." ORDER BY datetime DESC";
		$result = qdb($query) OR die(qe());
				
		while ($row = $result->fetch_assoc()) {
			$repair_activities[] = $row;
		}

		return $repair_activities;
	}

	function getComponents($ro_number) {
		$purchase_requests = array();
		$query;
		
		$query = "SELECT * FROM purchase_requests WHERE ro_number = ". prep($ro_number) .";";
		$result = qdb($query) OR die(qe());
				
		while ($row = $result->fetch_assoc()) {
			$purchase_requests[] = $row;
		}

		return $purchase_requests;
	}

	function getQuantity($partid) {
		$qty = 0;
		$query;
		
		$query = "SELECT SUM(qty) as sum FROM inventory WHERE partid = ". prep($partid) ." GROUP BY partid;";
		$result = qdb($query) OR die(qe());
				
		if (mysqli_num_rows($result)>0) {
			$results = mysqli_fetch_assoc($result);
			$qty = $results['sum'];
		}

		return $qty;
	}
	
	$items = getItems($repair_order);

	foreach($items as $item):
		$due_date = format_date($item['due_date']);
		$repair_item_id = $item['id'];
		break;
	endforeach;

	$activities = grabActivities($repair_order, $repair_item_id);

	//print_r($U);
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
		<form action="repair_activities.php" method="post">
			<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color:#f0f4ff;">
				<div class="col-md-4">
					<?php if(in_array("3", $USER_ROLES) || in_array("1", $USER_ROLES)) { ?>
					<a href="/order_form.php?on=<?php echo $order_number; ?>&ps=ro" class="btn-flat info pull-left" style="margin-top: 10px;"><i class="fa fa-list-ul" aria-hidden="true"></i> Manage Order</a>
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
					<input type="text" name="ro_number" value="<?=$order_number;?>" class="hidden">
					<input type="text" name="techid" value="<?=$U['id'];?>" class="hidden">
					<?php if(!empty($items))
						foreach($items as $item): ?>
						<input type="text" name="repair_item_id" value="<?=$item['id'];?>" class="hidden">
					<?php endforeach; ?>

					<?php $status = 'opened'; foreach($activities as $activity): ?>
						<?php 
							if(strpos($activity['notes'], 'Checked') !== false) {
								if(strtolower($activity['notes']) == 'checked in') {
									$status = 'closed';
								} else if(strtolower($activity['notes']) == 'checked out') {
									$status = 'opened';
								}
								break;
							}
						?>
					<?php endforeach; ?>

					<?php $claimed = ""; foreach($activities as $activity): ?>
						<?php 
							if(strpos($activity['notes'], 'Claimed') !== false) {
								$claimed = "Claimed on <b>" . format_date($activity['datetime']) . "</b> by <b>". getContact($activity['techid'], 'userid') . "</b>";
								break;
							}
						?>
					<?php endforeach; ?>

					<?php if($status == 'opened') { ?>
						<!-- <input type="text" name="type" value="check_in" class="hidden"> -->
						<button class="btn-flat success pull-right btn-update" type="submit" name="type" value="check_in" data-datestamp = "<?= getDateStamp($order_number); ?>" style="margin-top: 10px; margin-right: 10px;">Check In</button>
					<?php } else { ?>
						<!-- <input type="text" name="type" value="check_out" class="hidden"> -->
						<button class="btn-flat danger pull-right btn-update" id="submit" name="type" value="check_out" data-datestamp = "<?= getDateStamp($order_number); ?>" style="margin-top: 10px; margin-right: 10px;">Check Out</button>
					<?php } ?>

					<?php if(!$claimed){ ?>
						<button class="btn-flat info pull-right btn-update" type="submit" name="type" value="claim" data-datestamp = "<?= getDateStamp($order_number); ?>" style="margin-top: 10px; margin-right: 10px;">Claim Ticket</button>	
					<?php } else { ?>
						<p class="pull-right" style="margin-top: 17px;"><?=$claimed;?></p>
					<?php } ?>		
				</div>
			</div>
			
			<?php if($ro_updated == 'true'): ?>
				<div id="item-updated-timer" class="alert alert-success fade in text-center" style="position: fixed; width: 100%; z-index: 9999; top: 95px;">
				    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
				    <!-- <strong>Success!</strong> <?= ($po_updated ? 'Purchase' : 'Sales'); ?> Order Updated. -->
				</div>
			<?php endif; ?>
		</form>	
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
										<b style="color: #526273;font-size: 14px;">Rep</b><br><?=getContact($sales_rep_id)?><br><br>
										<b style="color: #526273;font-size: 14px;">Due</b><br><?=$due_date;?><br><br>
										<b style="color: #526273;font-size: 14px;">Notes</b><br>
										<?=$notes;?>
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
												<th class="col-md-6">Description</th>
												<th class="col-md-6">SERIAL</th>
											</tr>
										</thead>
										<?php
											$serial;

											if(!empty($items))
											foreach($items as $item): 
												$query = "SELECT serial_no FROM inventory WHERE repair_item_id = ".prep($item['id']).";";
												$result = qdb($query) or die(qe() . ' ' . $query);

												if (mysqli_num_rows($result)>0) {
													$r = mysqli_fetch_assoc($result);
													$serial = $r['serial_no'];
												}
										?>
											<tr class="meta_part" data-item_id="<?=$item['id'];?>" style="padding-bottom:6px;">
												<td><?=format($item['partid'], true);?></td>
												<td><?=$serial;?></td>
											</tr>
											
										<?php endforeach; ?>
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
												<th>Activity</th>
											</tr>
										</thead>
										<tr>
											<td colspan="12">
												<!-- <div class="row"> -->
												<form action="repair_activities.php" method="POST">
													<input type="text" name="ro_number" value="<?=$order_number;?>" class="hidden">
													<input type="text" name="techid" value="<?=$U['id'];?>" class="hidden">
													<?php if(!empty($items))
														foreach($items as $item): ?>
														<input type="text" name="repair_item_id" value="<?=$item['id'];?>" class="hidden">
													<?php endforeach; ?>
													<div class="col-md-12">
														<div class="input-group">
															<input type="text" name="notes" class="form-control input-sm" placeholder="Notes...">
															<span class="input-group-btn">
																<button class="btn btn-sm btn-primary" id="submit">Log</button>
															</span>
														</div>
													</div>
												</form>
												<!-- </div> -->
											</td>
										</tr>
										<?php
										// print_r($U);
											if($activities)
											foreach($activities as $activity):
										?>
											<tr class="" style = "padding-bottom:6px;">
												<td><?=format_date($activity['datetime'], 'n/j/y, H:i:s');?></td>
												<td><?=getContact($activity['techid'], 'userid');?></td>
												<td><?=$activity['notes'];?></td>
											</tr>
										<?php endforeach; ?>
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
													<button data-toggle="modal" data-target="#modal-component" class="btn btn-flat btn-sm btn-status middle modal_component pull-right" type="submit" data-filter="complete">
											        	<i class="fa fa-plus"></i>	
											        </button>
				        						</th>
											</tr>
										</thead>
										<?php
											$components = getComponents($repair_order);
											if($components)
												foreach($components as $comp):
										?>
											<tr class="" style = "padding-bottom:6px;">
												<td><?=format($comp['partid'], true);?></td>
												<td><?=$comp['qty']?></td>
												<td><?=getQuantity($comp['partid']);?></td>
												<td><?=$comp['po_number']?></td>
											</tr>
											
										<?php endforeach; ?>
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

				$(document).on('click', '.modal_component', function(){
					$('#right_side_main').empty();
					//$('#go_find_me').focus();
				});

				$('#modal-component').on('shown.bs.modal', function() {
				    $("#go_find_me").focus();
				});

				$(document).on("keydown",".search_line_qty",function(e){
					 if (e.keyCode == 13) {
						var isValid = nonFormCase($(this), e);
						
						$(".items_label").html("").remove();
						if(isValid) {
							var qty = 0;
							console.log($(".search_lines"));
	   		    			$(".search_lines").each(function() {
								qty += populateSearchResults($(".multipart_sub"),$(this).attr("data-line-id"),$(this).find("input[name=ni_qty]").val());
							});
							$(".items_label").html("").remove();
							
							if (qty == 0){
								modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Qty is missing or invalid. <br><br>If this message appears to be in error, please contact an Admin.");
							} else {
								$(".search_lines").html("").remove();
								$("#totals_row").show();
								$(this).val("");
								$("input[name='ni_qty']").val("");
								$("#order_total").val(updateTotal());
								$('#go_find_me').focus();
							}
						} 
					}
				});
			})(jQuery);
		</script>
	</body>
</html>
