<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/dictionary.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getUser.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getRepairCode.php';

	//Declared Variables
	// Type of job (Service, Repair, etc.)
	$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';
	$order_number_details = (isset($_REQUEST['order']) ? $_REQUEST['order'] : '');
	$edit = (isset($_REQUEST['edit']) ? $_REQUEST['edit'] : false);
	$task = (isset($_REQUEST['task']) ? $_REQUEST['task'] : '');
	$tab = (isset($_REQUEST['tab']) ? $_REQUEST['tab'] : '');

	preg_match_all("/\d+/", $order_number_details, $order_number_split);

	$order_number_split = reset($order_number_split);

	$order_number = ($order_number_split[0] ? $order_number_split[0] : '');
	$task_number = ($order_number_split[1] ? $order_number_split[1] : 1);

	// Contains the id of the line item (purchase_item_id, repair_item_id ....)
	$item_id = getItemID($order_number, $task_number, 'repair_items', 'ro_number');

	// List of subcategories
	$documentation = true;
	$labor = true;
	$activity = true;
	$materials = true;
	$expenses = true;
	$closeout = true;
	$outside = true;

	$quote = false;

	// Trigger point for a quote or new order based on if order number is present or not
	if(empty($order_number)) {
		$quote = true;
		$type = 'quote';
	}

	// Dynamic Variables Used
	$documentation_data = array();
	$activity_data = array();
	$materials_data = array();
	$expenses_data = array();
	$closeout_data = array();
	$ticketStatus = '';

	// Get the details of the order for the sidebar
	$ORDER = getOrder($order_number, $type);

	if($ORDER['repair_code_id']) {
		$ticketStatus = getRepairCode($ORDER['repair_code_id']);
	}

	// Disable the modules you want on the page here
	if($type == 'service') {

	} else if($type == 'build') {

	} else if($type == 'repair') {
		// Diable Modules for Repair
		$documentation = false;
		$closeout = false;
		$expenses = false;
		$outside = false;

		// getItems uses $item_id as a global to change the value of $item_id from 0
		$items = getItems($order_number);

		$serials = getSerials($item_id);

		$activity_data = grabActivities($order_number, $item_id, $type);
		$component_data = getComponents($order_number, $item_id, $type);

	} else if($quote){
		// Create the option for the user to create a quote or create an order
		$activity = false;
		$documentation = false;
		$edit = true; 
	}

	// Get the current users access
	// Big or if the user is management then give them access no matter what assignment they possess
	if((! accessControl($GLOBALS['U']['id'], $item_id) && ! $quote) && ! in_array("4", $USER_ROLES)){
		echo "<script type='text/javascript'>alert('You do not have access to this task. Please contact management to get access.');</script>";
		echo "<script>location.href='/';</script>";
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

	function getSerials($itemid) {
		$serials = array();

		$query = "SELECT serial_no FROM inventory WHERE repair_item_id = ".res($itemid).";";
		$result = qdb($query) OR die(qe());

		while ($row = $result->fetch_assoc()) {
			$serials[] = $row['serial_no'];
		}

		return $serials;
	}

	function getItems($order_number = 0, $table = 'repair_items', $field = 'ro_number') {
		// global $item_id;

		$items = array();

		$query = "SELECT * FROM $table WHERE $field = ". res($order_number) .";";
		$result = qdb($query) OR die(qe());
				
		while ($row = $result->fetch_assoc()) {
			$items[] = $row;
			//$item_id = $row['id'];
		}
		
		return $items;
	}

	function getItemID($order_number, $line_number, $table = 'repair_items', $field = 'ro_number'){
		$item_id = 0;

		$query = "SELECT id FROM $table WHERE $field = ".res($order_number).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		if(mysqli_num_rows($result) == 1){
			$r = mysqli_fetch_assoc($result);
			$item_id = $r['id'];
		} else if(mysqli_num_rows($result) > 1) {
			$query = "SELECT id FROM $table WHERE line_number = ".res($line_number)." AND $field = ".res($order_number).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if(mysqli_num_rows($result)){
				$r = mysqli_fetch_assoc($result);
				$item_id = $r['id'];
			}
		}

		return $item_id;
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
				SELECT '' as techid, datetime as datetime, CONCAT('<b>', part, '</b> pulled to Order') as notes FROM repair_components, inventory, parts WHERE ro_number = ".prep($ro_number)." AND inventory.id = repair_components.invid AND parts.id = inventory.partid
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

	function getComponents($order_number, $item_id, $type = 'repair') {
		$purchase_requests = array();
		$query;
		
		if($type == 'repair') {
			$query = "SELECT *, SUM(qty) as totalOrdered FROM purchase_requests WHERE ro_number = ". prep($order_number) ." GROUP BY partid, po_number ORDER BY requested DESC;";
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
				$row['available'] = getAvailable($row['partid'], $item_id);
				$row['pulled'] = getPulled($row['partid'], $po_number);

				$row['total'] = $total;

				$purchase_requests[] = $row;
			}

			// Also grab elements that were fulfilled by the in stock
			$query = "SELECT *, SUM(i.qty) as totalReceived FROM repair_components c, inventory i ";
			$query .= "WHERE c.ro_number = '".res($order_number)."' AND c.invid = i.id ";
			$query .= "AND serial_no IS NULL GROUP BY i.partid; ";
			$result = qdb($query) OR die(qe()); 

			while ($row = $result->fetch_assoc()) {
				//if(!in_array_r($row['partid'] , $purchase_requests)) {
				$purchase_requests[] = $row;
				//}
			}
		}

		return $purchase_requests;
	}

	function getAvailable($partid,$itemid=0) {
		$qty = 0;

		$query = "SELECT SUM(i.qty) as sum, i.id FROM purchase_items pi, inventory i WHERE pi.ref_1_label = 'repair_item_id' AND pi.ref_1='".res($itemid)."' AND pi.id = i.purchase_item_id AND i.partid = '".res($partid)."' AND i.qty > 0 AND (status = 'shelved' OR status = 'received')
                UNION
           SELECT SUM(i.qty) as sum, i.id FROM purchase_items pi, inventory i WHERE i.partid = '".res($partid)."' AND i.purchase_item_id = pi.id AND (pi.ref_1_label <> 'repair_item_id' OR pi.ref_1_label IS NULL) AND i.qty > 0 AND (status = 'shelved' OR status = 'received');";

		$result = qdb($query) OR die(qe());
				
		 while ($row = $result->fetch_assoc()) {
			$qty += ($row['sum'] ? $row['sum'] : 0);
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

	function getOrder($order, $type) {
		$results = array();

		if(strtolower($type) == 'repair' AND $order) {
			$query = "SELECT * FROM repair_orders ro, repair_items ri WHERE ro.ro_number = ".res($order)." AND ro.ro_number = ri.ro_number;";
			$result = qdb($query) OR die(qe());

			if (mysqli_num_rows($result)>0) {
				$results = mysqli_fetch_assoc($result);
			}
		}

		return $results;
	}

	// Creating an array for the current task based on total time spent per unique userid on the task
	function getLaborTime($item_id, $type){
		$totalSeconds = array();

		$query = "SELECT * FROM timesheets WHERE taskid = ".res($item_id)." AND task_label = '".res(strtolower($type))."' AND clockin IS NOT NULL AND clockout IS NOT NULL;";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		while($r = mysqli_fetch_assoc($result)){
			$totalSeconds[$r['userid']] += strtotime($r['clockout']) - strtotime($r['clockin']);
		}

		// Also pull assigned users and set them to 0 hours worked
		$query = "SELECT * FROM service_assignments WHERE service_item_id = ".res($item_id).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		while($r = mysqli_fetch_assoc($result)){
			$totalSeconds[$r['userid']] += 0;
		}

		return $totalSeconds;
	}

	function toTime($secs) {
		// given $secs seconds, what is the time g:i:s format?
		$hours = floor($secs/3600);

		// what are the remainder of seconds after taking out hours above?
		$secs -= ($hours*3600);

		$mins = floor($secs/60);

		$secs -= ($mins*60);

		return (str_pad($hours,2,0,STR_PAD_LEFT).':'.str_pad($mins,2,0,STR_PAD_LEFT).':'.str_pad($secs,2,0,STR_PAD_LEFT));
	}

	function timeToStr($time) {
		$t = explode(':',$time);
		$hours = $t[0];
		$mins = $t[1];
		if (! $mins) { $mins = 0; }
		$secs = $t[2];
		if (! $secs) { $secs = 0; }

		//$days = floor($hours/24);
		//$hours -= ($days*24);

		$str = '';
		//if ($days>0) { $str .= $days.'d, '; }
		if ($hours>0 OR $str) { $str .= (int)$hours.'h, '; }
		if ($mins>0 OR $str) { $str .= (int)$mins.'m, '; }
		if ($secs>0 OR $str) { $str .= (int)$secs.'s'; }

		return ($str);
	}

	function accessControl($userid, $item_id){
		// Guilty until proven innocent
		$access = false;

		$query = "SELECT * FROM service_assignments WHERE service_item_id = ".res($item_id)." AND userid = ".res($userid).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);


		if(mysqli_num_rows($result)) {
			$access = true;
		}

		return $access;
	}
?>

<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
			include_once $rootdir.'/modal/materials_request.php';
			include_once $rootdir.'/modal/service_complete.php';
		?>

		<title><?=ucwords($type);?></title>
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

			.sidebar .select2 {
			    width: 100% !important;
			    overflow: hidden;
			}

			.alert-success {
			    background-color: #dff0d8 !important;
			    border-color: #d6e9c6 !important;
			    color: #468847 !important;
			}

			#main-stats {
				height: 55px;
			}

			.table td {
			     vertical-align: top !important; 
			}

			.market-table {
			    max-height: 82px;
			    min-height: 82px;
			    margin-bottom: 0;
			    overflow: hidden;
			    position: relative;
			}

			.market-table:hover {
			    max-height: 82px;
			    min-height: 82px;
			    margin-bottom: 0;
			    overflow: visible;
			    position: initial;
			}

			.found_parts_quote td {
				max-height: 100px;
				height: 100px;
				overflow: hidden;
			}

			.market-table .bg-availability:hover .market-results, .market-table .bg-demand:hover .market-results {
				max-width: 260px;
				max-height: 300px;
			}

			.market-price {
				display: inline;
			}

			.market-table .market-results {
				width: 260px;
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
	
	<body class="sub-nav" data-order-type="<?=$type?>" data-order-number="<?=$order_number?>" data-taskid="<?=$item_id;?>" data-techid="<?=$GLOBALS['U']['id'];?>">
		<div class="container-fluid data-load full-height">
			<?php include 'inc/navbar.php'; include 'modal/package.php'; include '/modal/image.php';?>
			<div class="row table-header full-screen" id = "order_header">
				<div class="col-md-4">
					<?php if(!$build && ! $quote) { ?>
							<?php if(!$edit) { ?>
								<a href="/task_view.php?type=<?=$type;?>&order=<?=$order_number;?>&edit=true" class="btn btn-default btn-sm toggle-edit"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a>
							<?php } else { ?>
								<a href="#" class="text-success btn btn-default btn-sm toggle-save"><i class="fa fa-pencil" aria-hidden="true"></i> Save</a>
							<?php } ?>

							<a href="/repair_add.php?on=<?=($build ? $build . '&build=true' : $order_number)?>" class="btn btn-default btn-sm text-warning">
								<i class="fa fa-qrcode"></i> Receive
							</a>

						<button class="btn btn-sm btn-default btn-flat info" type="submit" name="type" value="test_out" title="Mark as Tested" data-toggle="tooltip" data-placement="bottom"><i class="fa fa-terminal"></i></button>
					<?php } ?>
				</div>
				<div class="col-sm-4 text-center" style="padding-top: 5px;">
					<h2><?=($type == 'service' ? 'Job' : '') . ((! $quote) ? ucwords($type) . '# ' : ucwords($task)) . $order_number . '-' . $task_number;?></h2>
				</div>
				<div class="col-sm-4">
					<div class="col-md-6">
					</div>
					<div class="col-md-6">
						<!-- <div class="col-md-8"> -->
							<?php if(! $quote){ ?>
								<div class="col-md-9" style="padding-top: 10px;">
									<select name="task" class="form-control repair-task-selector task_selection pull-right">
										<option><?=ucwords($type) . '# '.$order_number;?> - <?=getCompany($ORDER['companyid']);?></option>
									</select>
								</div>

								<div class="col-md-3 remove-pad">
									<button class="btn btn-success btn-sm btn-update" data-toggle="modal" data-target="#modal-complete">
										<i class="fa fa-save"></i> Complete
									</button>
								</div>
							<?php } else { ?>
								<button type="button" class="btn btn-sm btn-success success pull-right" id="save_button" data-validation="left-side-main">
									<i class="fa fa-save"></i> Create
								</button>
								<a href="#" class="text-primary btn btn-default btn-sm toggle-save pull-right"><i class="fa fa-pencil" aria-hidden="true"></i> Quote</a>
							<?php } ?>
						<!-- </div> -->
					</div>
					<!-- <button class="btn btn-warning btn-sm pull-right btn-update" style="margin-top: 10px; margin-right: 10px;"><i class="fa fa-briefcase" aria-hidden="true"></i> Regular Pay</button>
					<button class="btn btn-default btn-sm pull-right btn-update" style="margin-top: 10px; margin-right: 10px;"><i class="fa fa-car" aria-hidden="true"></i> Travel Time</button> -->
				</div>
			</div>

			<form id="save_form" action="/task_edit.php" method="post">
				<input type="hidden" name="item_id" value="<?=$item_id;?>">
				<div class="row" style="height: 100%; margin: 0;">
					
					<?php include 'sidebar.php'; ?>

					<div id="pad-wrapper" >

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

						<?php if(in_array("4", $USER_ROLES)){ ?>
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
				        <?php } ?>

				        <br>

				        <!-- Begin all the tabs in the page -->
				        <ul class="nav nav-tabs nav-tabs-ar">
				        	<?php if($activity) {
					        	echo '<li class="'.(($tab == 'activity' OR ($activity && empty($tab))) ? 'active' : '').'"><a href="#activity" data-toggle="tab"><i class="fa fa-folder-open-o"></i> Activity</a></li>';
				        	}  
				        	if($documentation) { 
					        	echo '<li class="'.($tab == 'documentation' ? 'active' : '').'"><a href="#documentation" data-toggle="tab"><i class="fa fa-file-pdf-o"></i> Documentation</a></li>';
					        } 
					        if($labor) {
								echo '<li class="'.(($tab == 'labor' OR (! $activity && empty($tab))) ? 'active' : '').'"><a href="#labor" data-toggle="tab"><i class="fa fa-users"></i> Labor '.((in_array("4", $USER_ROLES)) ?'&nbsp; $0.00':'').'</a></li>';
							} 
							if($materials) { 
								echo '<li class="'.($tab == 'materials' ? 'active' : '').'"><a href="#materials" data-toggle="tab"><i class="fa fa-list"></i> Materials &nbsp; $0.00</a></li>';
							} 
							if($expenses) {
								echo '<li class="'.($tab == 'expenses' ? 'active' : '').'"><a href="#expenses" data-toggle="tab"><i class="fa fa-credit-card"></i> Expenses &nbsp; $0.00</a></li>';
							} 
							if($outside) {
								echo '<li class="'.($tab == 'outside' ? 'active' : '').'"><a href="#outside" data-toggle="tab"><i class="fa fa-suitcase"></i> Outside Services &nbsp; $0.00</a></li>';
							} ?>
							<?php if(in_array("4", $USER_ROLES)){ ?>
								<li class="pull-right"><a href="#"><strong><i class="fa fa-shopping-cart"></i> Total &nbsp; $0.00</strong></a></li>
							<?php } ?>
						</ul>

						<div class="tab-content">

							<!-- Activity pane -->
							<?php if($activity) { ?>
								<div class="tab-pane <?=(($tab == 'activity' OR ($activity && empty($tab))) ? 'active' : '');?>" id="activity">
									<section>
										<div class="row list table-first">
											<div class="col-md-2">Date/Time</div>
											<div class="col-md-4">Tech</div>
											<div class="col-md-6">Activity</div>
										</div>

										<?php
											if($activity_data) {
											foreach($activity_data as $activity_row):
										?>
											<hr>
											<div class="row list">
												<div class="col-md-2"><?=format_date($activity_row['datetime'], 'n/j/y, h:i a');?></div>
												<div class="col-md-4"><?=getContact($activity_row['techid'], 'userid');?></div>
												<div class="col-md-6"><?=$activity_row['notes'];?></div>
											</div>
										<?php endforeach; } ?>
									</section>
								</div><!-- Activity pane -->
							<?php } ?>

							<?php if($documentation) { ?>
								<!-- Documentation pane -->
								<div class="tab-pane <?=($tab == 'documentation' ? 'active' : '');?>" id="documentation">
									<section>

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
							<?php } ?>
							
							<?php if($labor) { ?>
								<!-- Labor pane -->
								<div class="tab-pane <?=(($tab == 'labor' OR (! $activity && empty($tab))) ? 'active' : '');?>" id="labor">
									<?php if($edit){ ?>
										<div class="row labor_edit">

											<div class="col-md-12">
												<div class="input-group pull-left" style="margin-bottom: 10px; margin-right: 15px; max-width: 200px">
						  							<!-- <span class="input-group-addon">$</span> -->
													<input class="form-control input-sm" class="quote_hourly" type="text" placeholder="Hours" value="">
												</div>
												<div class="input-group" style="margin-bottom: 10px; max-width: 200px">
						  							<span class="input-group-addon">$</span>
													<input class="form-control input-sm" class="quote_price" type="text" placeholder="Rate" value="">
												</div>
											</div>
										</div>
									<?php } ?>

				                    <table class="table table-hover table-condensed">
				                        <thead class="no-border">
				                            <tr>
				                                <th class="col-md-4">
				                                    Employee
				                                </th>
				                                <th class="col-md-4">
				                                    Total Hours Logged
				                                </th>
				                                <?php if(in_array("4", $USER_ROLES)){ ?>
					                                <th class="col-md-2 text-right">
					                                    Cost
					                                </th>
				                                <?php } ?>
				                                <th class="col-md-2 text-center">
													<div data-toggle="tooltip" data-placement="left" title="" data-original-title="Tech Complete?"><i class="fa fa-id-badge"></i></div>
				                                </th>
				                               <!--  <th class="col-md-1 text-center">
													<div data-toggle="tooltip" data-placement="left" title="" data-original-title="Admin Complete?"><i class="fa fa-briefcase"></i></div>
				                                </th> -->
				                            </tr>
				                        </thead>
				                        <tbody>
				                        	<?php $totalSeconds = 0; foreach(getLaborTime($item_id, $type) as $user => $labor_seconds) { 
				                        			$hours_worked = ($labor_seconds / 3600);
				                        			$totalSeconds += $labor_seconds;
				                        			$rate = 0;

				                        			$query = "SELECT hourly_rate FROM users WHERE id=".res($user).";";
				                        			$result = qdb($query) OR die(qe() . ' ' . $query);

				                        			if (mysqli_num_rows($result)) {
														$result = mysqli_fetch_assoc($result);
														$rate = $result['hourly_rate'];
													}

													$cost = round($rate * $hours_worked, 2);
				                        	?>
					                        	<tr class="valign-top">
					                                <td>
														<?=getUser($user);?>
					                                </td>
					                                <td>
														<?=toTime($labor_seconds);?><br> &nbsp; <span class="info"><?=timeToStr(toTime($labor_seconds));?></span>
					                                </td>
					                                <td class="text-right">
					                                	<?php if(in_array("4", $USER_ROLES)){ ?>
															<?=format_price($cost);?>
														<?php } ?>
					                                </td>
					                                <td class="text-center">
					                                	<?php if(in_array("4", $USER_ROLES)){ ?>
						                                	<button type="submit" class="btn btn-primary btn-sm pull-right" name="tech_status" value="<?=$user;?>">
													        	<i class="fa fa-trash" aria-hidden="true"></i>
													        </button>
												        <?php } ?>
					                                </td>
					                            </tr>
				                            <?php } ?>

				                            <?php if(in_array("4", $USER_ROLES)){ ?>
					                            <tr>
					                            	<td>
					                            		<select name="techid" class="form-control input-xs tech-selector required"></select>
				                            		</td>
					                            	<td>
					                            		<button type="submit" class="btn btn-primary btn-sm add_tech" <?=($quote ? 'disabled' : '');?>>
												        	<i class="fa fa-plus"></i>	
												        </button>
												    </td>
					                            	<td></td>
					                            	<td></td>
					                            </tr>
				                            <?php } ?>
				                            <!-- row -->
				                            <?php if(in_array("4", $USER_ROLES)){ ?>
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
														<strong><?=toTime($totalSeconds);?> &nbsp; </strong>
					                                </td>
					                                <td class="text-right">
					                                    <strong>$ 0.00</strong>
					                                </td>
					                               <!--  <td colspan="2">
					                                </td> -->
					                            </tr>
				                            <?php } ?>
										</tbody>
									</table>
								</div><!-- Labor pane -->
							<?php } ?>

							<!-- Materials pane -->
							<?php if($materials) { ?>
								<div class="tab-pane <?=($tab == 'materials' ? 'active' : '');?>" id="materials">
									<section>
										<div class="row">
											<div class="col-sm-6">
									
											</div>
											<div class="col-sm-6">
												<?php if(! $quote) { ?>
													<button style="margin-bottom: 10px;" data-toggle="modal" type="button" data-target="#modal-component" class="btn btn-primary btn-sm pull-right modal_request">
											        	<i class="fa fa-plus"></i>	
											        </button>
										        <?php } ?>
									        </div>
										</div>

										<table class="table table-striped">
											<thead class="table-first">
												<tr>
													<?php if($quote){ ?>
														<th class="col-md-3">Material</th>
														<th class="col-md-2">Amount</th>
														<th class="col-md-1">Supply</th>
														<th class="col-md-3">Leadtime</th>
														<th>Profit %</th>
														<th>Quote</th>
														<th></th>
													<?php } else { ?>
														<th class="col-md-3">Material</th>
														<th class="col-md-1">Requested</th>
														<th class="col-md-2">SOURCE</th>
														<th class="col-md-1">Available</th>
														<th class="col-md-1">Pulled</th>
														<th class="col-md-2 text-right">Price Per Unit</th>
														<th class="col-md-2 text-right">EXT Price</th>
													<?php } ?>
												</tr>
											</thead>

											<tbody <?=($quote ? 'id="quote_body"' : '');?>>

												<?php $total = 0; foreach($component_data as $row){ ?>
													<tr class="list">
														<td class="col-md-3">
															<span class="descr-label part_description" data-request="<?=$row['totalOrdered'];?>"><?=trim(format($row['partid'], true));?></span>
														</td>
														<td class="col-md-1"><?=$row['totalOrdered'];?></td>
														<td class="col-md-2">
															<?php
																if($row['po_number']) {
																	echo $row['po_number'].' <a href="/PO'.$row['po_number'].'"><i class="fa fa-arrow-right"></i></a>';
																} else if ($row['status'] == 'Void') {
																	echo "<span style='color: red;'>Canceled</span>";
																} else if(($row['totalOrdered'] - $row['pulled'] > 0)) {
																	echo "<span style='color: #8a6d3b;'>Pending</span> <a target='_blank' href='/purchase_requests.php'><i class='fa fa-arrow-right'></i></a>";
																} else if(($row['totalOrdered'] - $row['pulled'] <= 0)) {
																	echo "<span style='color: #3c763d;'>Pulled from Stock</span>";
																} else if($row['status'] == 'Void') {
																	echo "<span style='color: #a94442;'>Canceled</span>";
																}
															?>
														</td>
														<td class="col-md-1"><?=$row['available'];?></td>
														<td class="col-md-1">
															<?=$row['totalReceived'];?> 
															<?php
																if(($row['totalOrdered'] - $row['totalReceived']) > 0 && $row['available']) {
																	echo '&emsp;<a style="margin-left: 10px;" href="#" class="btn btn-default btn-sm text-info pull_part" data-type="'.$_REQUEST['type'].'" data-itemid="'.$item_id.'" data-partid="'.$row['partid'].'"><i class="fa fa-download" aria-hidden="true"></i> Pull</a>';
																}
															?>
														</td>
														<td class="col-md-2 text-right">$0.00</td>
														<td class="col-md-2 text-right">$0.00</td>
													</tr>
												<?php } ?>

												<tr id='quote_input'>
													<?php if($quote) { ?>
														<td colspan="5">
															<div class='input-group' style="width: 100%;">
			                                                    <input type='text' class='form-control input-sm' id='partSearch' placeholder='SEARCH FOR MATERIAL...'>
			                                                    <span class='input-group-btn'>
			                                                        <button class='btn btn-sm btn-primary li_search_button'><i class='fa fa-search'></i></button>              
			                                                    </span>
			                                                </div>
			                                            </td>
			                                            <!-- <td colspan="2"></td> -->
		                                            <?php } else { ?>
		                                            	<td colspan="6"></td>
		                                            <?php } ?>
													<td class="text-right" <?=($quote ? 'colspan="2"' : '');?>><?=($quote ? 'Quote' : '');?> Total: <?=format_price($total);?></td>
												</tr>
											</tbody>
										</table>

									</section>
								</div><!-- Materials pane -->
							<?php } ?>

							<!-- Expenses pane -->
							<?php if($expenses) { ?>
								<div class="tab-pane <?=($tab == 'expenses' ? 'active' : '');?>" id="expenses">
									<section>
										<div class="row">
											<div class="col-sm-6">
												<?php if(isset($edit) && $edit) { ?>
													<div class="input-group" style="margin-bottom: 10px; max-width: 250px">
							  							<span class="input-group-addon">$</span>
														<input class="form-control input-sm" class="mileage_rate" type="text" placeholder="Price" value="1.25">
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
								</div><!-- Expenses pane -->
							<?php } ?>

							<?php if($outside) { ?>
								<!-- Outside Services pane -->
								<div class="tab-pane <?=($tab == 'outside' ? 'active' : '');?>" id="outside">
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
							<?php } ?>
						</div>
					</div>
				</div>
			</div> 
		</form>
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script type="text/javascript" src="js/part_search.js"></script>
		<script type="text/javascript" src="js/lici.js"></script>

		<script type="text/javascript">
			(function($) {

			})(jQuery);
		</script>
	</body>
</html>
