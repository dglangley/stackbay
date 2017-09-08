<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/dictionary.php';

	//Declared Variables
	// Type of job (Service, Repair, etc.)
	$type = ucwords(isset($_REQUEST['type']) ? $_REQUEST['type'] : 'Repair');
	$order_number = ucwords(isset($_REQUEST['on']) ? $_REQUEST['on'] : '');
	$edit = (isset($_REQUEST['edit']) ? $_REQUEST['edit'] : false);

	// List of subcategories
	$documentation = true;
	$activity = true;
	$materials = true;
	$expenses = true;
	$closeout = true;

	// Dynamic Variables Used
	$documentation_data = array();
	$activity_data = array();
	$materials_data = array();
	$expenses_data = array();
	$closeout_data = array();

	$item_id = 0;

	// Disable the modules you want on the page here
	if($type == 'Service') {

	} else if($type == 'Build') {

	} else if($type == 'Repair') {
		// Diable Modules for Repair
		$closeout = false;

		$items = getItems($order_number);
		$activity_data = grabActivities($order_number, $item_id, $type);
		$component_data = getComponents($order_number, $item_id, $type);

		$check_status = ""; 
		$claimed = "";

		if(! empty($activities)){
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

	// Function are mostly used for Repair OR Builds
	function getItems($order_number = 0) {
		global $item_id;

		$repair_items = array();
		$query;
		
		$query = "SELECT * FROM repair_items WHERE ro_number = ". prep($order_number) .";";
		$result = qdb($query) OR die(qe());
				
		while ($row = $result->fetch_assoc()) {
			$repair_items[] = $row;
			$item_id = $row['id'];
		}
		
		return $repair_items;
	}

	function grabActivities($ro_number, $repair_item_id, $type = 'Repair'){
		$repair_activities = array();
		$invid = '';
		
		$query = "
				SELECT techid, datetime as datetime, notes FROM repair_activities WHERE ro_number = ".prep($ro_number)." 
				UNION
				SELECT '' as techid, i.date_created as datetime, CONCAT('Component Received ', `partid`, ' Qty: ', qty ) as notes FROM inventory i WHERE i.repair_item_id = ".prep($repair_item_id)." AND serial_no IS NULL
				UNION
				SELECT created_by as techid, created as datetime, CONCAT('$type Order Created') as notes FROM repair_orders WHERE ro_number = ".prep($ro_number)."
				UNION
				SELECT userid as techid, date_created as datetime, CONCAT('Received $type Serial: <b>', serial_no, '</b>') as notes FROM inventory WHERE id in (SELECT invid FROM inventory_history where field_changed = 'repair_item_id' and `value` = ".prep($repair_item_id).") AND serial_no IS NOT NULL
				UNION
				SELECT '' as techid, datetime as datetime, CONCAT('Tracking# ', IFNULL(tracking_no, 'N/A')) as notes FROM packages WHERE order_number = ".prep($ro_number)." AND order_type = 'Repair'
				UNION
				SELECT '' as techid, i.date_created as datetime, CONCAT('Component <b>', p.part, '</b> Received') FROM purchase_requests pr, purchase_items pi, parts p, inventory i WHERE pr.ro_number = ".prep($ro_number)." AND pr.po_number = pi.po_number AND pr.partid = pi.partid AND pi.qty <= pi.qty_received AND p.id = pi.partid AND i.purchase_item_id = pi.id
				ORDER BY datetime DESC;";

		$result = qdb($query) OR die(qe());
		foreach($result as $row){
			$repair_activities[] = $row;
		}

		// Aaron's way to check if an item is marked for tested or not
		$query = "SELECT invid FROM inventory_history where field_changed = 'repair_item_id' and `value` = ".prep($repair_item_id)." LIMIT 1;";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$invid = $result['invid'];
		} else {
			$query = "SELECT id FROM inventory where `repair_item_id` = ".prep($repair_item_id)." LIMIT 1;";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if (mysqli_num_rows($result)) {
				$result = mysqli_fetch_assoc($result);
				$invid = $result['invid'];
			}
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

	// This function checks to see if the item (KEY) exists in the array or not
	function in_array_r($item , $array){
	    return preg_match('/"'.$item.'"/i' , json_encode($array));
	}

	function getComponents($order_number, $item_id, $type = 'Repair') {
		$purchase_requests = array();
		$query;
		
		if($type == 'Repair') {
			$query = "SELECT *, SUM(qty) as totalOrdered FROM purchase_requests WHERE ro_number = ". prep($order_number) ." GROUP BY partid, po_number;";
			$result = qdb($query) OR die(qe());
					
			while ($row = $result->fetch_assoc()) {
				$qty = 0;
				$po_number = $row['po_number'];

				// Check to see what has been received and sum it into the total Ordered
				$query = "SELECT *, SUM(i.qty) as totalReceived FROM repair_components c, inventory i ";
				if ($po_number) { $query .= "LEFT JOIN purchase_items pi ON pi.id = i.purchase_item_id "; }
				$query .= "WHERE c.ro_number = '".res($order_number)."' AND c.invid = i.id ";
				$query .= "AND i.partid = ".prep($row['partid'])." ";
				if ($po_number) { $query .= "AND pi.po_number = '".res($po_number)."' "; }
				$query .= "; ";

				$result2 = qdb($query) OR die(qe().' '.$query);

				if (mysqli_num_rows($result2)>0) {
					$row2 = mysqli_fetch_assoc($result2);
					$qty = ($row2['totalReceived'] ? $row2['totalReceived'] : 0);
				}

				$row['totalReceived'] = $qty;

				// This piece grabs more information on the component requested such as status, price and how many ordered if PO is active (AKA created)
				$total = 0;
				if($po_number) {
					$query = "SELECT rc.qty, (c.actual/i.qty) price, po.status ";
					$query .= "FROM repair_components rc, inventory_history h, purchase_items pi, purchase_orders po, purchase_requests pr, inventory i ";
					$query .= "LEFT JOIN inventory_costs c ON i.id = c.inventoryid ";
					$query .= "WHERE po.po_number = ".prep($po_number)." AND pr.partid = ".prep($row['partid'])." ";
					$query .= "AND po.po_number = pi.po_number AND po.po_number = pr.po_number AND pr.partid = pi.partid AND pr.ro_number = " . $order_number . " ";
					$query .= "AND rc.ro_number = pr.ro_number ";
					$query .= "AND h.value = pi.id AND h.field_changed = 'purchase_item_id' AND h.invid = i.id AND i.id = rc.invid ";
					$query .= "GROUP BY i.id; ";
					$result3 = qdb($query) OR die(qe().'<BR>'.$query);

					if (mysqli_num_rows($result3)>0) {
						$query_row = mysqli_fetch_assoc($result3);
						$row['status'] = $query_row['status'];
						$row['price'] = $query_row['price'];
						if($status == 'Active') {
							$row['ordered'] = $query_row['qty'];
						} else {
							$row['ordered'] = 0;
						}
					}
					$row['ext'] = ($row['price'] * $row['ordered']);
					$total += $ext;
				}

				// Grab actual available quantity for the requested component
				$row['available'] = getAvailabled($row['partid'], $po_number);
				$row['pulled'] = getPulled($row['partid'], $po_number);

				$row['total'] = $total;

				$purchase_requests[] = $row;
			}

			// This looks like an attempt to fix bad data, most likely obselete
			$query = "SELECT *, SUM(i.qty) as totalReceived FROM repair_components c, inventory i ";
			$query .= "WHERE c.ro_number = '".res($order_number)."' AND c.invid = i.id ";
			$query .= "AND serial_no IS NULL GROUP BY i.partid; ";
			$result = qdb($query) OR die(qe()); 

			while ($row = $result->fetch_assoc()) {
				if(!in_array_r($row['partid'] , $purchase_requests)) {
					$purchase_requests[] = $row;
				}
			}
		}

		return $purchase_requests;
	}

	function getAvailabled($partid,$po_number=0) {
		$qty = 0;
		$query;
		
		$query = "SELECT SUM(i.qty) as sum FROM inventory i ";
		if ($po_number) { $query .= "LEFT JOIN purchase_items pi ON pi.id = i.purchase_item_id "; }
		$query .= "WHERE i.partid = ". prep($partid) ." AND (i.status = 'shelved' OR i.status = 'received') ";
		if ($po_number) { $query .= "AND pi.po_number = '".res($po_number)."' "; }
		$query .= "GROUP BY i.partid ";
		if ($po_number) { $query .= ", i.purchase_item_id "; }
		$query .= "; ";

		$result = qdb($query) OR die(qe());
				
		if (mysqli_num_rows($result)>0) {
			$results = mysqli_fetch_assoc($result);
			$qty = ($results['sum'] ? $results['sum'] : 0);
		}

		return $qty;
	}

	function getPulled($partid, $ro_number) {
		$qty = 0;
		
		$query = "SELECT SUM(r.qty) as sum FROM repair_components r, inventory i WHERE r.ro_number = ". prep($ro_number) ." AND r.invid = i.id AND i.partid = ".prep($partid).";";
		$result = qdb($query) OR die(qe());
				
		if (mysqli_num_rows($result)>0) {
			$results = mysqli_fetch_assoc($result);
			$qty = ($results['sum'] ? $results['sum'] : 0);
		}

		return $qty;
	}
?>

<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
			include_once $rootdir.'/modal/component_request.php';
		?>
		<title><?=$type;?></title>
		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
		<style type="text/css">
			.list {
				padding: 5px;
			}

			.list-pad span {
				margin-top: 5px;
				display: block;
			}

			.select2 {
				margin-bottom: 10px;
				width: 100% !important;
			}

			.row-title {
				padding: 5px 0;
				background-color: #fff;
			}

			.row-title-pad {
				padding: 10px 0;
			}

			hr {
				margin: 0;
			}

			.table-first {
				font-weight: bold;
				text-transform: uppercase;
			}

			section {
				margin-bottom: 15px;
			}

			.companyid, .company_address, .company_contact {
				overflow: hidden;
			}
		</style>
	</head>
	
	<body class="sub-nav" data-order-type="<?=$type?>" data-order-number="<?=$order_number?>">
	<!---------------- Begin the header output  ---------------------->
		<div class="container-fluid pad-wrapper data-load">
			<?php include 'inc/navbar.php'; include 'modal/package.php';?>
			<div class="row table-header" id = "order_header" style="margin: 0; width: 100%;">
				<div class="col-md-4">
					<?php if($type == 'Repair') { 
						$btn_value = "test_out";
						$btn_title = "Mark as Tested";
						$btn_style = " btn-flat info";http://marketmanager:8888/job_view.php?type=service&on=331026&edit=true#
					?>
						<?php if($build): ?>
							<a href="/builds_management.php?on=<?php echo $build; ?>" class="btn btn-default btn-sm"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
						<?php elseif(in_array("1", $USER_ROLES) || in_array("4", $USER_ROLES) || in_array("5", $USER_ROLES) || in_array("7", $USER_ROLES)): ?>
							<a href="/job_view.php?type=<?=$type;?>&on=<?=$order_number;?>&edit=true" class="btn btn-default btn-sm toggle-edit"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
						<?php endif; ?>

						<?php if(!$build || ($build && $ticketStatus)) { ?>
							<a href="/repair_add.php?on=<?=($build ? $build . '&build=true' : $order_number)?>" class="btn btn-default btn-sm text-warning"><i class="fa fa-qrcode"></i> Receive</a>
						<?php } ?>
						<button class="btn btn-sm btn-default <?=$btn_style;?>" type="submit" name="type" value="<?=$btn_value;?>" <?=$btn_disabled;?> title="<?=$btn_title;?>" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-terminal"></i></button>
					<?php } else { ?>
						<a href="/job_view.php?type=<?=$type;?>&on=<?=$order_number;?>&edit=true" class="btn btn-default btn-sm toggle-edit"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
					<?php } ?>
				</div>
				<div class="col-sm-4 text-center" style="padding-top: 5px;">
					<h2><?=($type == 'Service' ? 'Job' : $type ) . '# ' . $order_number;?></h2>
				</div>
				<div class="col-sm-4">
					<div class="col-md-6">
					</div>
					<div class="col-md-6" style="padding-top: 10px;">
						<!-- <div class="col-md-8"> -->
							<select name="task" class="form-control task-selector pull-right">
								<option><?=$type . '# '.$order_number;?></option>
							</select>
						<!-- </div> -->
					</div>
					<!-- <button class="btn btn-warning btn-sm pull-right btn-update" style="margin-top: 10px; margin-right: 10px;"><i class="fa fa-briefcase" aria-hidden="true"></i> Regular Pay</button>
					<button class="btn btn-default btn-sm pull-right btn-update" style="margin-top: 10px; margin-right: 10px;"><i class="fa fa-car" aria-hidden="true"></i> Travel Time</button> -->
				</div>
			</div>

			<div class="row" style="height: 100%; margin: 0;">
				<div class="col-md-2" data-page="addition" style="padding-top: 110px; background-color: #efefef; height: 100%;">
					<p style="text-transform: uppercase; font-weight: bold;">Information</p>
					<?php if(isset($edit) && $edit) { ?>
						<select name="companyid" class="form-control input-xs company-selector required"><option value="25">Ventura Telephone</option></select>

						<input class="form-control input-sm" class="bid" type="text" placeholder="Bid No." value="23429879HHADI213" style="margin-bottom: 10px;">

						<select name="contactid" class="form-control input-xs contact-selector required"><option value="David Langley">David Langley</option></select>

						<select name="addressid" class="form-control input-xs contact-selector required"><option value="25">3037 Golf Course Drive, Suite 2</option></select>

						<br>

						<p style="text-transform: uppercase; font-weight: bold;">Scope</p>
						<textarea id="scope" class="form-control" name="scope" rows="3" style="margin-bottom: 10px;" placeholder="Scope">Here is a scope of everything that is being done for the job.</textarea>

						<p style="text-transform: uppercase; font-weight: bold;">Task Details</p>

						<?php if($type == 'Repair' OR $type == 'Build') { ?>
							<span class="descr-label">ERB5 &nbsp; T3PQAGCAAC</span>
							<div class="description desc_second_line descr-label" style="color:#aaa;">ALCATEL-LUCENT &nbsp;  <span class="description-label"><abbr title="DIGITAL ACCESS AND CROSS-CONNECT SYSTEM">DACS</abbr> IV PRIMARY NON-VOLATILE M</span></div>
							<br>

							<p style="text-transform: uppercase; font-weight: bold;">Serial(s):</p>
							<p>1231ASDJLK</p>
						<?php } ?>

						<?php if($type == 'Service') { ?>
							<select name="site_contactid" class="form-control input-xs contact-selector required">
								<option value="David Langley">David Langley</option>
							</select>

							<select name="site_addressid" class="form-control input-xs contact-selector required"><option value="25">3037 Golf Course Drive, Suite 2</option></select>
						<?php } ?>
						<div class="input-group" style="margin-bottom: 10px;">
  							<span class="input-group-addon">$</span>
							<input class="form-control input-sm" class="total_charge" type="text" placeholder="Price" value="800.00">
						</div>

						<br>

						<p style="text-transform: uppercase; font-weight: bold;">Public Notes</p>
						<textarea id="public_notes" class="form-control" name="public_notes" rows="3" style="margin-bottom: 10px;" placeholder="">Here is a scope of everything that is being done for the job.</textarea>

						<br>

						<p style="text-transform: uppercase; font-weight: bold;">Internal Use Only</p>

						<!-- <p style="text-transform: uppercase; font-weight: bold;">Private Notes</p> -->
						<textarea id="private_notes" class="form-control textarea-info" name="private_notes" rows="3" style="margin-bottom: 10px;" placeholder="">These notes are only seen by us and no one else.</textarea>

					<?php } else { ?>
						<p class="companyid" data-companyid="25"><span class="company_text" style="font-weight: bold; font-size: 15px;">Ventura Telephone</span></p>

						<p class="bid">23429879HHADI213</p>
					
						<p class="company_contact" data-contactid="">David Langley</p>

						<p class="company_address" data-addressid=""><span class="line_1">3037 Golf Course Drive, Suite 2</span><br>
						Ventura, CA 93003</p>

						<br>

						<p style="text-transform: uppercase; font-weight: bold;">Scope</p>
						<p class="scope">Here is a scope of everything that is being done for the job.</p>

						<br>

						<p style="text-transform: uppercase; font-weight: bold;">Task Details</p>

						<?php if($type == 'Repair' OR $type == 'Build') { ?>
							<br>
							<span class="descr-label">ERB5 &nbsp; T3PQAGCAAC</span>
							<div class="description desc_second_line descr-label" style="color:#aaa;">ALCATEL-LUCENT &nbsp;  <span class="description-label"><abbr title="DIGITAL ACCESS AND CROSS-CONNECT SYSTEM">DACS</abbr> IV PRIMARY NON-VOLATILE M</span></div>
							<br>

							<p style="text-transform: uppercase; font-weight: bold;">Serial(s):</p>
							<p>1231ASDJLK</p>

							<p class="total_charge">$800.00</p>
						<?php } ?>

						<?php if($type == 'Service') { ?>
							<p class="site_contact">David Langley</p>

							<p class="site_address"><span class="line_1">3037 Golf Course Drive, Suite 2</span><br>
							Ventura, CA 93003<br></p>

							<p class="total_charge">$800.00</p>
						<?php } ?>

						<br>

						<p style="text-transform: uppercase; font-weight: bold;">Public Notes</p>
						<p class="public_notes">Here is a scope of everything that is being done for the job.</p>

						<br>

						<p style="text-transform: uppercase; font-weight: bold;">Internal Use Only</p>

						<!-- <p style="text-transform: uppercase; font-weight: bold;">Private Notes</p> -->
						<p class="private_notes">These notes are only seen by us and no one else.</p>
					<?php } ?>
				</div>
						
				<div class="col-sm-10" style="padding-top: 95px;">

					<br>
					<!-- Cost Dash for Management People Only -->
					<div id="main-stats">
			            <div class="row stats-row">
			                <div class="col-md-3 col-sm-3 stat">
			                    <div class="data">
			                        <span class="number text-brown">$0.00</span>
									<span class="info">Quote</span>
			                    </div>
			                </div>
			                <div class="col-md-3 col-sm-3 stat">
			                    <div class="data">
			                        <span class="number text-black">$0.00</span>
									<span class="info">Cost</span>
			                    </div>
			                </div>
			                <div class="col-md-3 col-sm-3 stat">
			                    <div class="data">
			                        <span class="number text-black">$0.00</span>
									<span class="info">Commission</span>
			                    </div>
			                </div>
			                <div class="col-md-3 col-sm-3 stat last">
			                    <div class="data">
			                        <span class="number text-success">$0.00</span>
									<span class="info">Profit</span>
			                    </div>
			                </div>
			            </div>
			        </div>

			        <br>

			        <!-- Begin all the tabs in the page -->
			        <ul class="nav nav-tabs nav-tabs-ar">
				        <li class="active"><a href="#activity" data-toggle="tab"><i class="fa fa-folder-open-o"></i> Activity</a></li>
				        <li><a href="#documentation" data-toggle="tab"><i class="fa fa-file-pdf-o"></i> Documentation</a></li>
						<li><a href="#labor" data-toggle="tab"><i class="fa fa-users"></i> Labor &nbsp; $0.00</a></li>
						<li><a href="#materials" data-toggle="tab"><i class="fa fa-list"></i> Materials &nbsp; $0.00</a></li>
						<li><a href="#expenses" data-toggle="tab"><i class="fa fa-credit-card"></i> Expenses &nbsp; $0.00</a></li>
						<li><a href="#outside" data-toggle="tab"><i class="fa fa-suitcase"></i> Outside Services &nbsp; $0.00</a></li>
						<li class="pull-right"><a href="#"><strong><i class="fa fa-shopping-cart"></i> Total &nbsp; $0.00</strong></a></li>
					</ul>

					<div class="tab-content">

						<!-- Activity pane -->
						<div class="tab-pane active" id="activity">
		                    <?php if($activity) { ?>
								<section>
									<div class="row list table-first">
										<div class="col-md-2">Date/Time</div>
										<div class="col-md-4">Tech</div>
										<div class="col-md-6">Activity</div>
									</div>

									<?php
										if($activity_data)
										foreach($activity_data as $activity_row):
									?>
										<hr>
										<div class="row list">
											<div class="col-md-2"><?=format_date($activity_row['datetime'], 'n/j/y, h:i a');?></div>
											<div class="col-md-4"><?=getContact($activity_row['techid'], 'userid');?></div>
											<div class="col-md-6"><?=$activity_row['notes'];?></div>
										</div>
									<?php endforeach; ?>
								</section>
							<?php } ?>
						</div><!-- Activity pane -->

						<!-- Documentation pane -->
						<div class="tab-pane" id="documentation">
		                    <?php if($documentation) { ?>
								<section>
									<!-- <div class="row">
										<div class="col-sm-2">
											<h4>Documentation</h4>
										</div>
									</div> -->

									<div class="row list table-first">
										<div class="col-md-3">Date/Time</div>
										<div class="col-md-3">User</div>
										<div class="col-md-3">Notes</div>
										<div class="col-md-3">Action</div>
									</div>

									<hr>

									<div class="row list">
										<div class="col-md-3">7/12/2017</div>
										<div class="col-md-3">Scott</div>
										<div class="col-md-3">MOP</div>
										<div class="col-md-3"><a href="#"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></div>
									</div>
									<hr>
									<div class="row list">
										<div class="col-md-3">7/18/2017</div>
										<div class="col-md-3">David</div>
										<div class="col-md-3">MOP</div>
										<div class="col-md-3"><a href="#"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></div>
									</div>
									<hr>
									<div class="row list">
										<div class="col-md-3">8/22/2017</div>
										<div class="col-md-3">Chris</div>
										<div class="col-md-3">MOP</div>
										<div class="col-md-3"><a href="#"><i class="fa fa-file-pdf-o" aria-hidden="true"></i></a></div>
									</div>
								</section>
							<?php } ?>

							<?php if($closeout) { ?>
								<br>
								<section>
									<div class="row">
										<div class="col-sm-12">
											<h4>Closeout</h4>
										</div>
									</div>
								</section>
							<?php } ?>
						</div><!-- Documentation pane -->
						
						<!-- Labor pane -->
						<div class="tab-pane" id="labor">
							<div class="row labor_edit" style="display: none;">
								<div class="col-sm-2"></div>
								<div class="col-sm-10 remove-pad">
									<div class="col-sm-10">
										<div class="col-sm-2 remove-pad pull-right">
							            	<input type="text" class="form-control input-sm" value="" placeholder="Rate">
							            </div>
							            <div class="col-md-1 pull-right">
							            	<span style="text-align: center; display: block; margin-top: 5px; color: #777;">x</span>
							            </div>
							            <div class="col-sm-2 remove-pad pull-right">
							            	<input type="text" class="form-control input-sm" value="" placeholder="Hours">
							            </div>
						            </div>
						            <div class="col-sm-2">
						            	<!-- <span style="display: block; margin-top: 5px; color: #777;">= Calculated Amount</span> -->
						            	<input type="text" class="form-control input-sm pull-right" value="" placeholder="0.00" style="max-width: 100px;" readonly>
						            </div>
								</div>
							</div>

		                    <table class="table table-hover table-condensed">
		                        <thead class="no-border">
		                            <tr>
		                                <th class="col-md-4">
		                                    Employee
		                                </th>
		                                <th class="col-md-4">
		                                    Total Hours Logged
		                                </th>
		                                <th class="col-md-2 text-center">
		                                    Cost
		                                </th>
		                                <th class="col-md-2 text-center">
											<div data-toggle="tooltip" data-placement="left" title="" data-original-title="Tech Complete?"><i class="fa fa-id-badge"></i></div>
		                                </th>
		                               <!--  <th class="col-md-1 text-center">
											<div data-toggle="tooltip" data-placement="left" title="" data-original-title="Admin Complete?"><i class="fa fa-briefcase"></i></div>
		                                </th> -->
		                            </tr>
		                        </thead>
		                        <tbody>
		                            <!-- row -->
		                            <tr class="first">
		                                <td colspan="1">
											<div class="progress progress-lg">
												<div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">0%</div>
											</div>
		                                </td>
		                                <td>
											$0.00 profit of $0.00 quoted Labor
		                                </td>
		                                <td>
											<strong>00:00:00 &nbsp; </strong>
		                                </td>
		                                <td class="text-right">
		                                    <strong>$ 0.00</strong>
		                                </td>
		                               <!--  <td colspan="2">
		                                </td> -->
		                            </tr>
								</tbody>
							</table>
						</div><!-- Labor pane -->

						<!-- Materials pane -->
						<div class="tab-pane" id="materials">
		                    <?php if($materials) { ?>
								<section>
									<div class="row">
										<div class="col-sm-6">
											<!-- <h4>Materials</h4> -->
										</div>
										<div class="col-sm-6">
											<button data-toggle="modal" data-target="#modal-component" class="btn btn-flat btn-sm btn-status pull-right" type="submit">
									        	<i class="fa fa-plus"></i>	
									        </button>
								        </div>
									</div>

									<div class="row list table-first">
										<div class="col-md-3">Material</div>
										<div class="col-md-1">Requested</div>
										<div class="col-md-2">SOURCE</div>
										<div class="col-md-1">Available</div>
										<div class="col-md-1">Pulled</div>
										<div class="col-md-2 text-right">Price Per Unit</div>
										<div class="col-md-2 text-right">EXT Price</div>
									</div>

									<?php $total = 0; foreach($component_data as $row){ ?>
										<hr>

										<div class="row list">
											<div class="col-md-3">
												<span class="descr-label"><?=trim(format($row['partid'], true));?></span>
											</div>
											<div class="col-md-1"><?=$row['totalOrdered'];?></div>
											<div class="col-md-2">
												<?php
													if($row['po_number']) {
														echo $row['po_number'].' <a href="/PO'.$row['po_number'].'"><i class="fa fa-arrow-right"></i></a>';
													} else if(($row['totalOrdered'] - $row['pulled'] > 0)) {
														echo "<span style='color: #8a6d3b;'>Pending</span>";
													} else if(($row['totalOrdered'] - $row['pulled'] <= 0)) {
														echo "<span style='color: #3c763d;'>Completed</span>";
													} else if($row['status'] == 'Void') {
														echo "<span style='color: #a94442;'>Canceled</span>";
													}
												?>
											</div>
											<div class="col-md-1"><?=$row['available'];?></div>
											<div class="col-md-1">
												<?=$row['pulled'];?> 
												<?php
													if(($row['available'] - $row['pulled']) > 0) {
														echo '<a style="margin-left: 10px;" href="#" class="btn btn-default btn-sm text-info"><i class="fa fa-download" aria-hidden="true"></i> Pull</a>';
													}
												?>
											</div>
											<div class="col-md-2 text-right">$0.00</div>
											<div class="col-md-2 text-right">$0.00</div>
										</div>
									<?php } ?>

									<hr>

									<div class="row list">
										<div class="col-md-3"></div>
										<div class="col-md-1"></div>
										<div class="col-md-2"></div>
										<div class="col-md-1"></div>
										<div class="col-md-1"></div>
										<div class="col-md-2"></div>
										<div class="col-md-2 text-right">Total: <?=format_price($total);?></div>
									</div>

								</section>
							<?php } ?>
						</div><!-- Materials pane -->

						<!-- Expenses pane -->
						<div class="tab-pane" id="expenses">
		                    <?php if($expenses) { ?>
								<section>
									<div class="row">
										<div class="col-sm-6">
											<?php if(isset($edit) && $edit) { ?>
												<div class="input-group" style="margin-bottom: 10px; max-width: 250px">
						  							<span class="input-group-addon">$</span>
													<input class="form-control input-sm" class="mileage_rate" type="text" placeholder="Price" value="1.00">
												</div>
											<?php } else { ?>
												<b>Mileage Rate</b>: <span class="mileage_rate">$1.25</span>
											<?php } ?>
										</div>
										<div class="col-sm-6">
											<button data-toggle="modal" data-target="#modal-component" class="btn btn-flat btn-sm btn-status pull-right" type="submit">
									        	<i class="fa fa-plus"></i>	
									        </button>
								        </div>
									</div>

									<div class="row list table-first">
										<div class="col-md-2">Date/Time</div>
										<div class="col-md-2">User</div>
										<div class="col-md-4">Notes</div>
										<div class="col-md-2 text-right">Amount</div>
										<div class="col-md-2">Action</div>
									</div>

									<hr>

									<div class="row list">
										<div class="col-md-2">8/12/2017</div>
										<div class="col-md-2">Scott</div>
										<div class="col-md-4">Wishes he could eat a delicious pastrami sandwich from Stackz</div>
										<div class="col-md-2 text-right">$12.00</div>
										<div class="col-md-2"><a href="#"><i class="fa fa-download" aria-hidden="true"></i></a></div>
									</div>
								</section>
							<?php } ?>
						</div><!-- Expenses pane -->

						<!-- Outside Services pane -->
						<div class="tab-pane" id="outside">
		                    <table class="table table-hover table-condensed">
		                        <thead class="no-border">
		                            <tr>
		                                <th class="col-md-1">
		                                    Employee
		                                </th>
		                                <th class="col-md-2">
		                                    Vendor
		                                </th>
		                                <th class="col-md-1">
		                                    Date
		                                </th>
		                                <th class="col-md-6">
		                                    Description
		                                </th>
		                                <th class="col-md-2 text-center">
		                                    Amount
		                                </th>
		                            </tr>
		                        </thead>
		                        <tbody>
									                            <!-- row -->
		                            <tr class="first">
		                                <td colspan="4">
		                                </td>
		                                <td class="text-right">
		                                    <strong>$ 0.00</strong>
		                                </td>
		                            </tr>
								</tbody>
							</table>
						</div><!-- Outside Services pane -->

					</div>
				</div>
			</div>
		</div> 
		
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script type="text/javascript" src="js/part_search.js"></script>
	</body>
</html>
