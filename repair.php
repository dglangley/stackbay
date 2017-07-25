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
	// include_once $rootdir.'/inc/getUser.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/operations_sidebar.php';
	include_once $rootdir.'/inc/packages.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/getOrderStatus.php';
	include_once $rootdir.'/inc/getRepairCode.php';
	//include_once $rootdir.'/inc/order-creation.php';
	
	$order_number = $_REQUEST['on'];
	$build = $_REQUEST['build'];
	$build_name = '';
	$order_type = "Tech";
	
	$so_updated = $_REQUEST['success'];

	
	function getSalesUser($userid, $return = 'name'){
	    $select = "SELECT $return FROM users u, contacts c where u.contactid = c.id AND u.id = ".prep($userid).";";
	    return rsrq($select);
	}
	
	if(empty($order_number)) {
		//header("Location: /shipping_home.php");
		//die();
	}

	if($build) {
		//Get the real RO
		$build = $order_number;
		//Get the real number aka the RO number
		$query = "SELECT ro_number, name FROM builds WHERE id=".prep($order_number).";";
		$result = qdb($query) or die(qe());
		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$order_number = $result['ro_number'];
			$build_name = $result['name'];
		} 
	}
	
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
		$ticketStatus = getRepairCode($result['repair_code_id']);
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
		$select = "SELECT `datetime` FROM `packages`  WHERE  `order_number` = '$order_number' AND datetime is not null limit 1;";
		return rsrq($select);
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

	function grabActivities($ro_number, $repair_item_id, $build){
		$type;

		if($build) {
			$type = 'Build';
		} else {
			$type = 'Repair';
		}

		$repair_activities = array();
		$query;
		$invid = '';
		
		$inv_query = "SELECT invid FROM inventory_history where field_changed = 'repair_item_id' and `value` = ".prep($repair_item_id)." LIMIT 1;";
		$invid = rsrq($inv_query);
		if(!$invid){
			$inv_query = "SELECT id FROM inventory where `repair_item_id` = ".prep($repair_item_id)." LIMIT 1;";
			$invid = rsrq($inv_query);
		}
		
		$query = "
				/*SELECT techid, requested as datetime, CONCAT('Component Requested Part# <b>', parts.part, '</b> Qty: ', qty) as notes FROM purchase_requests, parts WHERE ro_number = ".prep($ro_number)." AND partid = parts.id 
				UNION*/
				SELECT techid, datetime as datetime, notes FROM repair_activities WHERE ro_number = ".prep($ro_number)." 
				UNION
				SELECT '' as techid, i.date_created as datetime, CONCAT('Component Received ', `partid`, ' Qty: ', qty ) as notes FROM inventory i WHERE i.repair_item_id = ".prep($repair_item_id)." AND serial_no IS NULL
				UNION
				SELECT created_by as techid, created as datetime, CONCAT('$type Order Created') as notes FROM repair_orders WHERE ro_number = ".prep($ro_number)."
				UNION
				SELECT userid as techid, date_created as datetime, CONCAT('Received $type Serial: <b>', serial_no, '</b>') as notes FROM inventory WHERE id in (SELECT invid FROM inventory_history where field_changed = 'repair_item_id' and `value` = ".prep($repair_item_id).") AND serial_no IS NOT NULL
				UNION
				SELECT '' as techid, datetime as datetime, CONCAT('Tracking# ', IFNULL(tracking_no, 'N/A')) as notes FROM packages WHERE order_number = ".prep($ro_number)." AND order_type = 'Repair'
				ORDER BY datetime DESC;";

		$result = qdb($query) OR die(qe());
		foreach($result as $row){
			$repair_activities[] = $row;
		}
		
		if($invid){
			$invhis = "
			SELECT DISTINCT * 
			FROM inventory_history ih, repair_activities ra 
			where field_changed = 'status' 
			and ra.datetime = ih.date_changed 
			and ra.notes is null
			and invid = ".prep($invid)."
			order by date_changed asc;
			";
			$history = qdb($invhis) or die(qe()." | $invhis");
			foreach($history as $h){
				$status = '';
				if($h['value'] == "in repair" && ($h['changed_from'] == "shelved" || $h['changed_from'] == "manifest")){
					$status = 'Checked In';	
				} else if($h['changed_from'] == "in repair" && ($h['value'] == "shelved" || $h['value'] == "manifest")){
					$status = 'Checked Out';	
				} else if($h['changed_from'] == "testing" && ($h['value'] == "shelved" || $h['value'] == "manifest")){				
					$status = 'Out of Test Lab';	
				} else if($h['value'] == "testing" && ($h['changed_from'] == "shelved" || $h['changed_from'] == "manifest")){				
					$status = 'In Test Lab';	
				}
				foreach($repair_activities as $count => $row){
					if(!$row['notes']){
						$repair_activities[$count]['notes'] = $status;
					}
				}
			}
		}
		return $repair_activities;
	}

	function in_array_r($item , $array){
	    return preg_match('/"'.$item.'"/i' , json_encode($array));
	}

	function getComponents($ro_number, $repair_item_id) {
		$purchase_requests = array();
		$query;
		
		$query = "SELECT *, SUM(qty) as totalOrdered FROM purchase_requests WHERE ro_number = ". prep($ro_number) ." GROUP BY partid;";
		$result = qdb($query) OR die(qe());
				
		while ($row = $result->fetch_assoc()) {
			$qty = 0;

			//Check to see what has been received and sum it into the total Ordered
			$query = "SELECT *, SUM(qty) as totalReceived FROM inventory WHERE repair_item_id = ". prep($repair_item_id) ." AND serial_no IS NULL AND partid = ".prep($row['partid']).";";
			$received = qdb($query) OR die(qe());

			if (mysqli_num_rows($received)>0) {
				$receivedr = mysqli_fetch_assoc($received);
				$qty = $receivedr['totalReceived'];
			}


			$row['totalReceived'] = $qty;
			$purchase_requests[] = $row;
		}

		// if(empty($purchase_requests)) {
			$query = "SELECT *, SUM(qty) as totalReceived FROM inventory WHERE repair_item_id = ". prep($repair_item_id) ." AND serial_no IS NULL GROUP BY partid;"; 
    		$result = qdb($query) OR die(qe()); 

    		while ($row = $result->fetch_assoc()) {
				if(!in_array_r($row['partid'] , $purchase_requests)) {
					$purchase_requests[] = $row;
				}
			}
		// }

		//print_r($purchase_requests);

		return $purchase_requests;
	}

	function getQuantity($partid) {
		$qty = 0;
		$query;
		
		$query = "SELECT SUM(qty) as sum FROM inventory WHERE partid = ". prep($partid) ." AND (status = 'shelved' OR status = 'received') GROUP BY partid;";
		$qty = rsrq($query);
		return $qty;
	}



	function getRepairQty($partid, $ro_number) {
		$qty = 0;
		
		$query = "SELECT SUM(r.qty) as sum FROM repair_components r, inventory i WHERE r.ro_number = ". prep($ro_number) ." AND r.invid = i.id AND i.partid = ".prep($partid).";";
		$result = qdb($query) OR die(qe());
				
		if (mysqli_num_rows($result)>0) {
			$results = mysqli_fetch_assoc($result);
			$qty = $results['sum'];
		}

		return $qty;
	}
	
	function getRepairRMA($ro_number){
		$query = "SELECT `rma_number` FROM `returns` where `order_type` = 'Repair' and `order_number` = ".prep($ro_number).";";
		return rsrq($query);
	}
	
	$items = getItems($repair_order);
	
	$rma_number = '';
	$rma_number = getRepairRMA($repair_order);
	foreach($items as $item):
		$due_date = format_date($item['due_date']);
		$repair_item_id = $item['id'];
		break;
	endforeach;

	$activities = grabActivities($repair_order, $repair_item_id, $build);

	$check_status = ""; 
	$claimed = "";
	if(isset($activities)){
		foreach($activities as $activity):
			if(strpos($activity['notes'], 'Checked') !== false && !$check_status) {
				if(strtolower($activity['notes']) == 'checked in') {
					$check_status = 'closed';
				} else if(strtolower($activity['notes']) == 'checked out') {
					$check_status = 'opened';
				}
			}

			if(strpos($activity['notes'], 'Claimed') !== false && !$claimed) {
				$claimed = "Claimed on <b>" . format_date($activity['datetime']) . "</b> by <b>". getContact($activity['techid'], 'userid') . "</b>";
			}
		endforeach; 
	}

	$serial;
	$item_row = '';
	$ref1 = '';

	if(!empty($items)){
		foreach($items as $item){
			$status;

			$query = "SELECT serial_no, id, status FROM inventory WHERE repair_item_id = ".prep($item['id'])." AND serial_no IS NOT NULL;";
			$result = qdb($query) or die(qe() . ' ' . $query);

			if (!mysqli_num_rows($result)) {
				$query = "SELECT serial_no, id, status FROM inventory WHERE id = (SELECT `invid` FROM inventory_history where field_changed = 'repair_item_id' and `value` = ".prep($item['id'])." limit 1);";
				$result = qdb($query) or die(qe() . ' ' . $query);
			}
			
			if(mysqli_num_rows($result)){
				if($build) {
					while ($row = $result->fetch_assoc()) {
						$serial[] = $row;
					}
				} else {
					$r = mysqli_fetch_assoc($result);
					$serial = $r['serial_no'];
					//$invid = $r['id']; // From what I can tell this is never used
					$status = $r['status'];
				}
			}
			//echo('<input type="text" name="repair_item_id" value="'.$item['id'].'" class="hidden">');
			$ref1 = $item['ref_1'];
			if($item['ref_1_label'] == "return_item_id"){
				if(!$rma_number){
					$select = "SELECT `rma_number` from `return_items` where id = ".prep($ref1).";";
					$rma_number = rsrq($select);
				}
				$ref1 = '
				<b style="color: #526273;font-size: 14px;">
				</b>&nbsp;<a href="/rma.php?rma='.$rma_number.'">'.$rma_number.'</a>
				<br><br>';
			}
			if(!$build) {
				$item_row .= '
				<tr class="meta_part" data-item_id="'.$item['id'].'" style="padding-bottom:6px;">
					<td>'.format($item['partid'], true).'</td>
					<td>'.$serial.'</td>
					<td>'.$ref1.'</td>
					<td>'.(($item['ref_2']) ? $item['ref_2'] : "").'</td>
					<td>'.format_price($item['price']).'</td>
					<td>
						<input type="text" name="repair_item_id" value="'.$item['id'].'" class="hidden">
						<button class="btn btn-sm btn-primary" type="submit" name="type" value="'.((strtolower($status) == 'in repair')? "test_in":"test_out").'" '.((!$serial || $ticketStatus) ? 'disabled' : '').'>'.((strtolower($status) == 'in repair')? "Send to Testing":"Mark as Tested").'</button>
					</td>
				</tr>';
			} else {
				if(!empty($serial)) {
					$init = true;

					$build_price = 0;

					$query = "SELECT price FROM builds WHERE ro_number = ".prep($order_number).";";
					$result = qdb($query) or die(qe()." | $query");
					if(mysqli_num_rows($result)){
						$result = mysqli_fetch_assoc($result);
						$build_price = $result['price'];
					}
					
					foreach ($serial as $build_item) {
						$item_row .= '
						<tr class="meta_part" data-item_id="'.$item['id'].'" style="padding-bottom:6px;">
							<td>'.($init ? format($item['partid'], true) : '').'</td>
							<td>'.($init ? format_price($build_price) : '').'</td>
							<td>'.$build_item['serial_no'].'</td>
							'.($init ? '<input type="text" name="repair_item_id" value="'.$item['id'].'" class="hidden">' : '') . '
							<td>
								<button class="btn btn-sm btn-primary" type="submit" name="build_test" value="'.$build_item['id'].'" '.((!$build_item['serial_no']) ? 'disabled' : '').'>'.((strtolower($build_item['status']) == 'shelved')? "Send to Testing":"Mark as Tested").'</button>
							</td>
						</tr>';
						$init = false;
					}
				} else {
					$item_row .= '
					<tr class="meta_part" data-item_id="'.$item['id'].'" style="padding-bottom:6px;">
						<td>'.format($item['partid'], true).'</td>
						<td>'.format_price($item['price']).'</td>
						<td></td>
						<td>
							<input type="text" name="repair_item_id" value="'.$item['id'].'" class="hidden">
							<button class="btn btn-sm btn-primary" type="submit" name="type" disabled>'.((strtolower($status) == 'in repair')? "Send to Testing":"Mark as Tested").'</button>
						</td>
					</tr>';
				}
			}
			//'.((strtolower($status) == 'in repair')? "test_in":"test_out").'
		}
	}
	//print_r($U);
?>
	

<!DOCTYPE html>
<html>
	<head>
		<title><?=($build ? 'Build':'Repair');?><?=($order_number != ' New' ? '# ' . ($build ? $build : $order_number) : '')?></title>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
		
		<style type="text/css">
			.label a {
				color:white;
			}
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

	<body class="sub-nav" id = "order_body" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
		<?php 
			include 'inc/navbar.php'; 
			include_once $rootdir.'/modal/component_request.php';
			include_once $rootdir.'/modal/component_available.php';
			// include_once $rootdir.'/modal/repair_complete.php';
		?>
		<form action="repair_activities.php" method="post">
			<?php
				include_once $rootdir.'/modal/repair_complete.php';
			?>
			<div class="row-fluid table-header" id = "order_header" style="width:100%;min-height:50px;background-color:#f0f4ff;">
				<div class="col-md-4">
					<?php if($build): ?>
						<a href="/builds_management.php?on=<?php echo $build; ?>" class="btn-flat info pull-left" style="margin-top: 10px;"><i class="fa fa-list-ul" aria-hidden="true"></i> Manage</a>
					<?php elseif(in_array("1", $USER_ROLES) || in_array("4", $USER_ROLES) || in_array("5", $USER_ROLES) || in_array("7", $USER_ROLES)): ?>
						<a href="/order_form.php?on=<?php echo $order_number; ?>&ps=ro" class="btn-flat info pull-left" style="margin-top: 10px;"><i class="fa fa-list-ul" aria-hidden="true"></i> Manage</a>
					<?php endif; ?>

					<?php if(!$build || ($build && $ticketStatus)) { ?>
						<a href="/repair_add.php?on=<?=($build ? $build . '&build=true' : $order_number)?>" class="btn-flat pull-left"><i class="fa fa-truck"></i> Receive</a>
					<?php } ?>
				</div>
				
				<div class="col-md-4 text-center">
					<?php
						echo"<h2 class='minimal shipping_header' style='padding-top: 10px;' data-so='". $order_number ."'>";

						if($build) {
							//echo "Build";
						} else {
							echo "Repair Ticket";
						}
						if ($order_number!='New'){
							echo ($build ? $build_name : "# " . $order_number);
						}
						if (strtolower($status) == 'void'){
							echo ("<b><span style='color:red;'> [VOIDED]</span></b>");
						}

						if($ticketStatus){
							echo '<br>(<span class="ticket_status_'.(strpos(strtolower($ticketStatus), 'unrepairable') !== false ? 'danger' : (strpos(strtolower($ticketStatus), 'trouble') ? 'warning' : 'success')).'">' .ucwords($ticketStatus) . '</span>) ';
						}
						echo"</h2>";
					?>
				</div>
				<div class="col-md-4">
					<input type="text" name="ro_number" value="<?=$order_number;?>" class="hidden">
					<input type="text" name="techid" value="<?=$U['id'];?>" class="hidden">
					<?php if($build) { ?>
						<input type="text" name="build" value="<?=$build?>" class="hidden">
					<?php } ?>
					<?php if(!empty($items))
						foreach($items as $item): ?>
						<input type="text" name="repair_item_id" value="<?=$item['id'];?>" class="hidden">
					<?php endforeach; ?>

					<?php if($check_status == 'opened' || !$check_status) { ?>
						<input type="text" name="check_in" value="check_in" class="hidden">
						<button class="btn-flat success pull-right btn-update" type="submit" name="type" value="check_in" data-datestamp = "<?= getDateStamp($order_number); ?>" style="margin-top: 10px; margin-right: 10px;" <?=($ticketStatus ? 'disabled' : '');?>>Check In</button>
					<?php } else { ?>
						<input type="text" name="check_in" value="check_out" class="hidden">
						<button class="btn-flat danger pull-right btn-update" id="submit" name="type" value="check_out" data-datestamp = "<?= getDateStamp($order_number); ?>" style="margin-top: 10px; margin-right: 10px;" <?=($ticketStatus ? 'disabled' : '');?>>Check Out</button>
					<?php } ?>

					<?php if(!$claimed){ ?>
						<button class="btn-flat info pull-right btn-update" type="submit" name="type" value="claim" data-datestamp = "<?= getDateStamp($order_number); ?>" style="margin-top: 10px; margin-right: 10px;" <?=($ticketStatus ? 'disabled' : '');?>>Claim Ticket</button>	
					<?php } else { ?>
						<button class="btn-sm btn btn-primary pull-right btn-update" data-toggle="modal" data-target="#modal-repair" style="margin-top: 11px; margin-right: 10px; margin-left: 10px;" <?=($ticketStatus ? 'disabled' : '');?>>
							Complete Ticket
						</button>
						<p class="pull-right" style="margin-top: 18px;"><?=$claimed;?></p>
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
<!-- 								<div class="row">
									<div class="col-sm-12" style="padding-bottom: 10px; margin-top: 15px;">						
										<div class="order">
											<?=$order_number;?>
										</div>
									</div>
								</div> -->

								<div class="row">
									<div class="col-md-12" style="padding-bottom: 10px; margin-top: 15px;">
										<b style="color: #526273;font-size: 14px;">Rep</b><br><?=getSalesUser($sales_rep_id)?><br><br>
										<b style="color: #526273;font-size: 14px;">Due</b><br><?=$due_date;?><br><br>
										<b style="color: #526273;font-size: 14px;">Notes</b><br>
										<?=$notes;?>
										<br>
										<?php if($rma_number): ?>
										<br><b style="color: #526273;font-size: 14px;">Returned on:</b>&nbsp;<a href="/rma.php?rma=<?=$rma_number?>">RMA# <?=$rma_number?></a><br><br>
										<?php endif; ?>
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
									<form action="repair_activities.php" method="post">
										<input type="text" name="ro_number" value="<?=$order_number;?>" class="hidden">
										<input type="text" name="techid" value="<?=$U['id'];?>" class="hidden">
										<?php if($build) { ?>
											<input type="text" name="build" value="<?=$build?>" class="hidden">
										<?php } ?>

										<table class="table table-hover table-striped table-condensed" style="margin-top: 15px;">
											<thead>
												<tr>
													<th class="col-md-5">DESCRIPTION</th>
													<?php if(!$build): ?>
														<th class="col-md-2">SERIAL</th>
														<th class="col-md-1">
															<?php
																$label;
																foreach($items as $item){
																	if($item['ref_1_label']){
																		if($item['ref_1_label'] == "return_item_id"){echo("RMA #");break;}
																		$label = $item['label'];
																		echo($item['ref_1_label']);
																		break;
																	}
																};
															?>
														</th>
														<th class="col-md-1">
															<?php
																foreach($items as $item){
																	if($item['ref_2_label']){
																		echo($item['ref_2_label']);
																		break;
																	}
																};
															?>
														</th>
														<th class="col-md-1">PRICE</th>
													<?php else: ?>
														<th class="col-md-2">PRICE</th>
														<th class="col-md-3">SERIAL</th>
													<?php endif; ?>
													<th class="col-md-2"></th>
												</tr>
											</thead>
											<?php
												echo($item_row);
											?>		
										</table>
									</form>
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
													<?php if($build) { ?>
														<input type="text" name="build" value="<?=$build?>" class="hidden">
													<?php } ?>
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
																<button class="btn btn-sm btn-primary" name="type" value='note_log' id="submit" <?=(($ticketStatus && !$build) ? 'disabled' : '');?>>Log</button>
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
												<td><?=format_date($activity['datetime'], 'n/j/y, h:i a');?></td>
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
												<th>Requested</th>
												<th>PO</th>
												<!-- <th>Ordered</th> -->
												<th>Available</th>
												<th>Pulled</th>
												<th>Price Per Unit</th>
												<th>EXT Price</th>
				        						<th><button data-toggle="modal" data-target="#modal-component" class="btn btn-flat btn-sm btn-status middle modal_component pull-right" type="submit" <?=($ticketStatus ? 'disabled' : '');?>>
											        	<i class="fa fa-plus"></i>	
											        </button></th>
											</tr>
										</thead>
										<?php
											$components = getComponents($repair_order, $repair_item_id);
											$total = 0.00;
											if($components)
												foreach($components as $comp):
													// echo $comp['partid'];
													// print_r($comp);
													//Get the current status of the PO
													$status;
													$ordered = 0;
													$ext = 0;
													$price = 0;

													if($comp['po_number']) {
														// $query = "
														// SELECT po.*, pi.* FROM purchase_orders po, purchase_items pi, inventory i
														// WHERE po.po_number = ".prep($comp['po_number'])." 
														// AND i.purchase_item_id = pi.id
														// AND pi.po_number = po.po_number;";
														$query = "
														SELECT price, pi.qty, po.status
														FROM purchase_requests pr, purchase_items pi, purchase_orders po
														Where pr.po_number = pi.po_number 
														and pr.partid = pi.partid 
														and po.po_number = pi.po_number
														AND po.po_number = ".prep($comp['po_number'])."
														AND pr.partid = ".prep($comp['partid']).";
														";
														$result = qdb($query) OR die(qe().'<BR>'.$query);
														
														// echo $query;

														if (mysqli_num_rows($result)>0) {
										                    $query_row = mysqli_fetch_assoc($result);
										                    $status = $query_row['status'];
										                    $price = $query_row['price'];
										                    if($status == 'Active') {
										                    	$ordered = $query_row['qty'];
										                    }
										                }
										                $ext = ($price * $ordered);
										                $total += $ext;
													}
										?>
											<tr class="" style = "padding-bottom:6px;">
												<td><?=(trim(format($comp['partid'], true)) != '' ? format($comp['partid'], true) : $comp['partid'] );?></td>
												<td><?=$comp['totalOrdered'];?></td>
												<td class=""><?=($comp['po_number'] ? '<span class="label label-success complete_label status_label" style=""><a href="/PO'.$comp['po_number'].'">'.$comp['po_number'].'</a></span>' : ($comp['totalOrdered'] - getRepairQty($comp['partid'], $order_number) > 0 && ($comp['status'] != 'Void') ? "<span class='label label-warning active_label status_label' >Pending</span>" : "<span class='label label-danger active_label status_label' >Canceled</span>"));?></td>
												<!-- <td><?=$ordered;?></td> -->
												<!-- "<span class='label label-danger active_label status_label' >Canceled</span>" -->
												<td><?=(getQuantity($comp['partid']) ? getQuantity($comp['partid']) : '0');?></td> 
												<td class=""><?=($comp['totalReceived'] ? $comp['totalReceived'] :(getRepairQty($comp['partid'], $order_number) ? getRepairQty($comp['partid'], $order_number) : '0'))?></td>
												<td><?=format_price($price)?></td>
												<td><?=format_price($ext)?></td>
												<td>
													<div class="row">
														<div class="col-md-12">
															<?php if($comp['status'] != 'Void') { ?>
																<button <?=($ticketStatus ? 'disabled' : '');?> data-toggle="modal" data-target="#modal-component-available" class="btn btn-flat info btn-sm btn-status middle modal_component_available pull-right" type="submit" data-partid="<?=$comp['partid'];?>" data-requested="<?=$comp['totalOrdered'];?>" data-received="<?=getRepairQty($comp['partid'], $order_number)?>" <?=(getQuantity($comp['partid']) > 0 ? '' : 'disabled');?>>
																	<?=(getQuantity($comp['partid']) > 0 ? 'Pull from Stock' : 'No Stock');?> 	
														        </button>
													        <?php } ?>
														</div>
									                </div>
												</td>
											</tr>
											
										<?php endforeach; ?>
										<tr>
											<td colspan=6 class="text-right"><b>Total:</b></td>
											<td class = "total-column"><?=format_price($total);?></td>
											<td>&nbsp;</td>
										</tr>
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

				//Simple jQuery to clear the last value
				$(document).on('click', '.modal_component', function(){
					$('#right_side_main').empty();
				});

				//Focus into Aaron's search bar that is created with Ajax
				$('#modal-component').on('shown.bs.modal', function() {
				    $("#go_find_me").focus();
				});

				//Alter the keydown code to submit the items within Aaron's Search Paradigm
				$(document).on("keydown",".search_line_qty",function(e){
					if (e.keyCode == 13) {
						var isValid = nonFormCase($(this), e);
						
						$(".items_label").html("").remove();
						if(isValid) {
							var qty = 0;
							console.log($(".search_lines"));
	   		    			$(".search_lines").each(function() {
								qty += populateSearchResults($(".multipart_sub"),$(this).attr("data-line-id"),$(this).find("input[name=ni_qty]").val(), $(this).find('.data_stock').data('stock'));
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

				//This uses Ajax to dynamically load in the amount available within an item
				//Pulling the location of the component requested (multiple location supported)
				//This is after component request is made and stock has finally arrived
				$(document).on("click", ".modal_component_available", function(e){
					var request = $(this).data('requested');
					var received = $(this).data('received');
					var partid = $(this).data('partid');

					$.ajax({ 
						type: "POST", 
						url: '/json/component_available.php', 
						data: { 
							"request":request,
							"received":received,
							"partid":partid,
						}, 
						dataType: 'json', 
						success: function(result) { 
							console.log(result);
							//alert('here');
							$('#stock_component_avail').empty();
							$('#stock_component_avail').append(result);
						},
						error: function(error) { 
							console.log(error);
							alert(error);
						}
					}); 
				});

				//This is similar to the above function except it is utilized within the component request 3rd tab which allows
				//the user to partial receive a request they made depending on the stock
				$(document).on("click", ".add_component", function(e){
					//Using attr instead of data because data uses the cache value while attr allows dynamic values
					//If this wasn't the case would ideally use data() for better practice
					var request = $(this).attr('data-requested');
					var received = '0';
					var partid = $(this).attr('data-partid');

					var available = $(this).attr('data-available');

					var amount;

					if(available - request >= 0 || !available) {
						amount = request;
					} else {
						amount = available;
					}


					var type = $(this).data('component');

					$('.nav-tabs a[href="#item_stock"]').tab('show');

					//alert(request + ' ' + received + ' ' + partid);

					$.ajax({ 
						type: "POST", 
						url: '/json/component_available.php', 
						data: { 
							"request":request,
							"received":received,
							"partid":partid,
							"type":type,
						}, 
						dataType: 'json', 
						success: function(result) { 
							console.log(result);
							$('#stock_component').empty();
							$('#stock_component').append(result);

							$('.inventory_pull:first').val(amount);

							if(type == 'pull') {
								$('#item_stock .component_pull_submit').attr("data-type", type);
							} else {
								$('#item_stock .component_pull_submit').attr("data-type", "");
							}

							$('#item_stock .component_pull_submit').attr("data-request", request);
						},
						error: function(error) { 
							console.log(error);
							alert(error);
						}
					}); 
				});

				//Create middle modal to show the tech avaiable components and the option to partial / request or fulfill the request straight from the inventory
				$(document).on("click", ".stock_check", function(e) {
					var qty = 0;

					$(".search_lines").each(function() {
						qty += populateSearchResults($(".multipart_sub"),$(this).attr("data-line-id"),$(this).find("input[name=ni_qty]").val(), $(this).find('.data_stock').data('stock'));
					});
					$(".items_label").html("").remove();
					
					if (qty == 0){
						// modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Qty is missing or invalid. <br><br>If this message appears to be in error, please contact an Admin.");
					} else {
						$(".search_lines").html("").remove();
						$("#totals_row").show();
						$(this).val("");
						$("input[name='ni_qty']").val("");
						$("#order_total").val(updateTotal());
						$('#go_find_me').focus();
					}
					if ($(".table_components").find("tr").length === 0) {
						alert("Please Select a Component.");
					} else {
						$('.stock_component').empty();
						var html = "";

						var init = true;

						$(".table_components .easy-output").each(function() {
							//For developement only limit to 1 component request per call
							if(init) {
							    var qty = $(this).find(".line_qty").data('qty');
							    var available = $(this).find(".line_qty").data('stock');
							    var partid = $(this).find(".line_part").data('search');
							    var cost = $(this).find(".line_price").text();
							    var pullable = 0;

							    if(available - qty < 0 && available <= 0) {
							    	// pullable = available;
							    	$('.component_request_submit_pull').show();
							    	$('.component_request_pull').hide();
							    } 

							    if(available - qty >= 0 && available <= 0) {
							    	// pullable = available;
							    	$('.component_request_pull').show();
							    	$('.component_request_submit_pull').hide();
							    } 

							    if(available <= 0) {
							    	$('.component_request_pull').hide();
							    	$('.component_request_submit_pull').hide();
							    }
							    // else {
							    // 	pullable = qty
							    // }

							    $('.add_component').attr("data-partid",$(this).find(".line_part").data('search'));
							    $('.add_component').attr("data-requested",qty);
							    $('.component_request_submit').attr("data-requested",qty);
							    $('.component_request_submit_pull').attr("data-available",available);

							    //$(this).clone().appendTo(".stock_component");
							    html += "<tr class='component'>\
							    			<td class='line_part' data-search='"+$(this).find(".line_part").data('search')+"''>"+$(this).find(".line_part").html()+"</td>\
							    			<td class='line_qty' data-qty='"+qty+"'>"+$(this).find(".line_qty").html()+"</td>\
							    			<td data-available='"+available+"'>"+available+"</td>\
							    		</tr>";
							    		//<td><input type='text' class='input-sm form-control inventory_pull' value='"+pullable+"'></td>\
							    init = false;
					    	}
						});
						$(".stock_component").append(html);

						$('.nav-tabs a[href="#stock"]').tab('show');
					}
				});

				//Request and create a purchase order for the component
				$(document).on('click', '.component_request_submit', function(e){ 
					var submit = []; 
					var order_number = $('body').data("order-number");
					var repair_item_id = $('tr.meta_part').data("item_id");
					var request = $(this).data('requested');

					//alert(request);

					var notes = $('#comment').val();

					$(this).closest("body").find("#right_side_main").find('tr').each(function(){ 
						var row = { 
							"part" : $(this).find(".line_part").attr("data-search"), 
							"qty" : $(this).find(".line_qty").attr("data-qty"), 
						}; 

						submit.push(row); 
					}); 

					console.log(submit); 

					$.ajax({ 
						type: "POST", 
						url: '/json/component_request.php', 
						data: { 
							"requested_items":submit,
							"order_number":order_number,
							"repair_item_id":repair_item_id,
							"total_pr":request,
							"notes":notes,
						}, 
						dataType: 'json', 
						success: function(result) { 
							console.log(result);
							location.reload();
						} 
					}); 
				});

				//Pull items from the shelved / inventory
				$(document).on('click', '.component_pull_submit', function(e){ 
					var submit = []; 
					var partial = []; 
					var order_number = $('body').data("order-number");
					var repair_item_id = $('tr.meta_part').data("item_id");

					var type = $(this).data('type');
					var request = $(this).data('request');
					var pulledQTY = 0;
					var partialQTY = 0;

					//alert(request);

					var totalPulled = 0;

					var notes = $('#comment').val();

					$(this).closest("body").find("#stock_component").find('tr.part').each(function(){ 
						pulledQTY = $(this).find(".inventory_pull").val();
						var row = { 
							"invid" : $(this).data("invid"), 
							"qty" : pulledQTY, 
						}; 

						var partialRow = {
							"part" : $(this).data("partid"), 
							"qty" : partialQTY, 
						}

						totalPulled += parseInt(pulledQTY);

						submit.push(row); 
						partial.push(partialRow); 
					}); 

					partialQTY = (parseInt(request) - totalPulled);

					//alert(request + ' ' + partialQTY);

					console.log(submit);

					//Create a purchase request for the remaining items
					if(type == 'pull' && (partialQTY) > 0) {
						$.ajax({ 
							type: "POST", 
							url: '/json/component_request.php', 
							data: { 
								"requested_items":partial,
								"order_number":order_number,
								"repair_item_id":repair_item_id,
								"total_pr":(request - totalPulled),
								"notes":notes
							}, 
							dataType: 'json', 
							success: function(result) { 
								console.log(result);
							} 
						}); 
					}

					$.ajax({ 
						type: "POST", 
						url: '/json/component_available.php', 
						data: { 
							"pulled_items":submit,
							"order_number":order_number,
							"repair_item_id":repair_item_id
						}, 
						dataType: 'json', 
						success: function(result) { 
							console.log(result);
							location.reload();
						},
						error: function(xhr, status, error) {
							var err = eval("(" + xhr.responseText + ")");
							console.log(err.Message);
							//alert('error');
						} 
					}); 
				});

				//Pull items from the shelved / inventory
				$(document).on('click', '.component_pull_submit_avail', function(e){ 
					var submit = []; 
					var order_number = $('body').data("order-number");
					var repair_item_id = $('tr.meta_part').data("item_id");

					var type = $(this).data('type');

					$(this).closest("body").find("#stock_component_avail").find('tr.part').each(function(){ 
						var row = { 
							"invid" : $(this).data("invid"), 
							"qty" : $(this).find(".inventory_pull").val(), 
						}; 

						submit.push(row); 
					}); 

					$.ajax({ 
						type: "POST", 
						url: '/json/component_available.php', 
						data: { 
							"pulled_items":submit,
							"order_number":order_number,
							"repair_item_id":repair_item_id,
							"type":'pull',
						}, 
						dataType: 'json', 
						success: function(result) { 
							console.log(result);
							location.reload();
						} 
					}); 
				});
			})(jQuery);
		</script>
	</body>
</html>


