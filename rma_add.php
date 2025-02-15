<?php

//==============================================================================
//============================ RMA ADDITION SCREEN  ============================
//==============================================================================
//	This screen works with the handling of rma items once they have been 	   |
//	recorded as return items. Originally created by Andrew in early January '17|
//==============================================================================

	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];

	$DEBUG = 0;

	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/dictionary.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/order_type.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/setInventory.php';
	include_once $rootdir.'/inc/getInventory.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/operations_sidebar.php'; 
	include_once $rootdir.'/inc/display_part.php'; 
	include_once $rootdir.'/inc/getDisposition.php';
	include_once $rootdir.'/inc/credit_functions.php';
	include_once $rootdir.'/inc/send_gmail.php';

	// include_once $rootdir.'/inc/getOrderNumber.php';

	function getRMA($rma_number) {
		$R = array();

		$query = "SELECT * FROM returns WHERE rma_number = ".prep($rma_number).";";
		$result = qedb($query);
		if (mysqli_num_rows($result)>0) {
			$R = mysqli_fetch_assoc($result);
		}

		return $R;
	}

	//Set initials to be used throughout the page
	$rma_number = grab('on');
	
	//Variables used for the post save
	$rma_serial = '';
	$invid = '';
	$itemLocation = '';
	$errorHandler = '';
	$place = '';
	$instance = '';

	$R = getRMA($rma_number);
	$order_number = $R['order_number'];
	$order_type = $R['order_type'];

	$partid;
	$sales_item_id;

	//If this is a form which sumbits upon itself
	if((grab('rma_serial') || grab('invid')) && !grab('exchange_trigger')) {
		$rma_serial = strtoupper(grab('rma_serial'));
		$invid = grab('invid');

		//Get the initial Sales Item 
		if ($invid) {
			$query = "SELECT serial_no, sales_item_id, returns_item_id, partid FROM inventory WHERE id = ".prep($invid).";";
			$serial_find = qedb($query);
			if (mysqli_num_rows($serial_find)) {
				$serial_find = mysqli_fetch_assoc($serial_find);
				$rma_serial = $serial_find['serial_no'];
				$sales_item_id = $serial_find['sales_item_id'];
				$partid = $serial_find['partid'];
			}
		}

		$itemLocation = $_REQUEST['locationid'];

		//Find the items pertaining to the RMA number and the serial searched
		$rmaArray = findRMAItems($rma_serial, $rma_number);
	
		if(empty($itemLocation)) {
			$errorHandler = "Locations can not be empty.";
		} else {
			//Check if there is 1, multiple, or none found
			if(count($rmaArray) == 1 || $invid != '') {
				$errorHandler = savetoDatabase($itemLocation, reset($rmaArray), $invid, $rma_number);

				//Clear values after save
				if($errorHandler == '') {
					$rma_serial = '';
					$invid = '';
					//$itemLocation = '';
				}
			} else if(count($rmaArray) > 1) {
				$errorHandler = "Multiple items found for serial: " . $rma_serial . ". Please select the correct one using the list below.";
			} else {
				$errorHandler = "No items found for serial: " . $rma_serial;
			}
			
		}
	} else if(grab('exchange_trigger')) {
		$new_so;
		$return_item_id;

		$inventoryid = $_REQUEST['exchange_trigger'];

		$query = "SELECT id FROM return_items WHERE inventoryid = '".res($inventoryid)."';";
		$result = qedb($query);
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$return_item_id = $result['id'];
		}

		$I = getInventory($inventoryid);

		if (! $I['sales_item_id']) {
			header('Location: ship_order.php?taskid='.$I['repair_item_id'].'&task_label=repair_item_id');
			exit;
		}

		// has this item already been shipped out as a replacement for this customer? match sales_items with inventory record
		$query = "SELECT * FROM sales_items si, inventory i ";
		$query .= "WHERE si.ref_1 = '".res($return_item_id)."' AND si.ref_1_label = 'return_item_id' ";
		$query .= "AND i.id = '".res($inventoryid)."' AND i.sales_item_id = si.ref_2 AND ref_2_label = 'sales_item_id';";
		$result = qedb($query);
		if(qnum($result) == 0) {// no replacement has been shipped out

			// create a new sales_items record alongside the original billable sales_items record
			$insert = "INSERT INTO `sales_items` (`partid`, `so_number`, `line_number`, `qty`, `qty_shipped`, `price`, `delivery_date`, `ship_date`, ref_1, ref_1_label,`ref_2`, `ref_2_label`, `warranty`, `conditionid`)
			SELECT s.`partid`, s.`so_number`, s.`line_number`, 1 AS `qty`, 0 AS `qty_shipped`, 0.00 AS `price`, `delivery_date`, null as `ship_date`, $return_item_id AS ref_1, 'return_item_id' AS ref_1_label,`inventory`.`sales_item_id` AS `ref_2`, 'sales_item_id' AS `ref_2_label`, `warranty`, s.`conditionid`
			FROM `inventory`, `sales_items` s WHERE `inventory`.`id` = '".res($inventoryid)."' AND `sales_item_id` = s.`id`;";
			qedb($insert);
			$exchangeid = qid();

			$query = "SELECT so_number FROM sales_items WHERE id = ".res($exchangeid).";";
			
			$result = qedb($query);
			if (mysqli_num_rows($result)>0) {
				$result = mysqli_fetch_assoc($result);
				$new_so = $result['so_number'];
			}
		} else {
			$r = mysqli_fetch_assoc($result);
			$new_so = $r['so_number'];
		}

//		if ($DEBUG) { exit; }

		header("Location: /shipping.php?order_number=".$new_so."&exchange=true");
		exit;
	} else if(grab('repair_trigger')){

		//Grab from RO data that is populated from there
		$query = "SELECT rma.*, ri.* FROM returns rma, return_items ri WHERE rma.rma_number = ri.rma_number AND rma.rma_number = ".prep($rma_number)." AND inventoryid = ".res(grab('repair_trigger')).";";
		$result = qedb($query);

		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$r2 = false;

			//If an order exists for this RMA then retrieve all the information we need for this order
			if ($r['order_type'] == 'Sale' && $r['order_number']) {
				$query2 = "SELECT * FROM sales_orders so, sales_items si WHERE so.so_number = si.so_number AND so.so_number = ".prep($r['order_number']).";";
				$result2 = qedb($query2);
				if (mysqli_num_rows($result2)) {
					$r2 = mysqli_fetch_assoc($result2);
				}
			} else if ($r['order_type'] == 'Repair' && $r['order_number']) {
				$query2 = "SELECT * FROM repair_orders ro, repair_items ri WHERE ro.ro_number = ri.ro_number AND ro.ro_number = ".prep($r['order_number']).";";
				$result2 = qedb($query2);
				if (mysqli_num_rows($result2)) {
					$r2 = mysqli_fetch_assoc($result2);
				}
			}

			if (! $r2) {
				die("This feature is busted, please see Admin immediately");
			}

			$insert = "INSERT INTO repair_orders (created, created_by, sales_rep_id, companyid, contactid, cust_ref, bill_to_id, ship_to_id,
				freight_carrier_id, freight_services_id, freight_account_id, termsid, public_notes, private_notes, repair_code_id, status) VALUES (
				".prep($now).",
				".prep($U['id']).",
				".prep($r2['sales_rep_id']).",
				".prep($r['companyid']).",
				".prep($r['contactid']).",
				".prep('RMA#' . $rma_number).",
				".prep($r2['bill_to_id']).",
				".prep($r2['ship_to_id']).",
				".prep($r2['freight_carrier_id']).",
				".prep($r2['freight_services_id']).",
				".prep($r2['freight_account_id']).",
				".prep('15').",
				".prep($r2['public_notes']).",
				".prep($r2['private_notes']).",
				NULL,
				'Active'
				);";
			qedb($insert);
			$ro_number = qid();

			$insert = "INSERT INTO repair_items (partid, ro_number, line_number, qty, price, due_date, invid, ref_1, ref_1_label, ref_2, ref_2_label, warrantyid, notes) VALUES (
				".prep($r['partid']).",
				".prep($ro_number).",
				'1',
				".prep($r['qty']).",
				'0.00',
				".prep($r2['due_date']).",
				".prep(grab('repair_trigger')).",";

			//If Ref_ is null we will use the label as the pointer to rma	
			if(!$r['ref_1']) {
				$insert .=	prep($r['id']).",
					".prep('return_item_id').",";
			} else {
				$insert .=	prep($r['ref_1']).",
					".prep($r['ref_1_label']).",";
			}

			//If Ref_1 was used and ref_2 is null we will use ref 2 instead else we used ref 1 already and will ignore ref 2
			if(!$r['ref_2'] && $r['ref_1']) {
				$insert .=	prep($r['id']).",
					".prep('return_item_id').",";
			} else {
				$insert .=	prep($r['ref_2']).",
					".prep($r['ref_2_label']).",";
			}

			$insert .=	prep($r2['warranty']).",
				".prep($r2['notes'])."
				);";

			//echo $insert;
			qedb($insert);
			$repair_item_id = qid();

			// update inventory with repair item id so that the user doesn't have to re-receive the item
			$I = array('id'=>grab('repair_trigger'),'repair_item_id'=>$repair_item_id,'status'=>'in repair');
			$inventoryid = setInventory($I);
		}

		header("Location: /order.php?order_type=Repair&order_number=" . $ro_number);
	}
	
	//Check if the page has invoked a success in saving
	$rma_updated = $_REQUEST['success'];
	
	if(empty($rma_number)) {
		header( 'Location: /operations.php' ) ;
	}
	

	//Using the order number from purchase order, get all the parts being ordered and place them on the inventory add page
	function getRMAParts ($rma_number) {
		$listPartid = array();
		
		//Only looking for how many parts are in the RMA, distinct as we will retrieve all the serial pertaining to the part later
		$query = "SELECT id, partid FROM return_items WHERE rma_number = ". res($rma_number) ." GROUP BY partid;";
		$result = qedb($query);
	    if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$listPartid[] = $row;
			}
		}
		
		return $listPartid;
	}
	
	//This grabs the return specific items based on the rma_number and partid (Used to grab inventoryid for the same part only)
	function getRMAitems($partid, $rma_number) {
		$listSerial = array();
		
		$query = "SELECT DISTINCT i.serial_no, i.locationid, r.reason, i.returns_item_id, r.inventoryid, r.dispositionid, r.id FROM return_items  as r, inventory as i WHERE r.partid = ". res($partid) ." AND i.id = r.inventoryid AND r.rma_number = ".res($rma_number).";";
		$result = qedb($query);
	    if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$listSerial[] = $row;
			}
		}

		return $listSerial;
	}

	//This attempts to find all the items pertaining to the Serial & PartID matching the inventory to return item table
	function findRMAItems($search, $rma_number, $type = 'all'){
		$rma_search = array();
		$query = '';
		
		if($type == 'all'){
			$query = "SELECT r.id as rmaid, r.inventoryid, disposition, dispositionid FROM inventory as i, return_items as r, dispositions as d ";
			$query .= "WHERE i.serial_no = '".res($search)."' AND d.id = dispositionid AND r.inventoryid = i.id AND r.rma_number = ".res($rma_number).";";
		} else {
			$query = "SELECT r.id as rmaid, r.inventoryid, disposition, dispositionid FROM inventory as i, return_items as r, dispositions as d ";
			$query .= "WHERE i.serial_no = '".res($search)."' AND d.id = dispositionid AND r.inventoryid = i.id AND i.returns_item_id is NULL AND r.rma_number = ".res($rma_number).";";
		}
		//Query or pass back error
		$result = qedb($query);
		while ($row = $result->fetch_assoc()) {
			$rma_search[] = $row;
		}
		
		return $rma_search;
	}

	function generateJournal($inventoryid, $item_id, $order_type, $rma_number) {
		$T = order_type($order_type);

		$debit_account = 'Inventory Asset';
		$credit_account = 'Inventory Sale COGS';

		$og_item_id = 0;
		$og_order_number = 0;
		$invoice_no = 0;
		$cogs_amount = 0;	

		// Original cogs
		// reference a repair or sales item (RMA)
		// Inventoryid
		// Get that number and put it back as journal entry

		// line_item is also known as the item_id of the items table
		// First get the inventoryid from the return_items table
		$query = "SELECT * FROM ".$T['orders']." o, ".$T['items']." i WHERE i.id = ".res($item_id)." AND o.".$T['order']." = i.".$T['order'].";";
		$result = qedb($query);

		if(qnum($result)==0) { return false; }

		$r = qrow($result);
			
		// result gives us the returns table with order_type and order_number that is needed to run the next query to find the original item_id
		$T2 = order_type($r['order_type']);
		$query2 = "SELECT t.id FROM ".$T2['items']." t, inventory_history h WHERE ".$T2['order']." = '".$r['order_number']."' AND h.value = t.id AND h.invid = '".$inventoryid."' AND h.field_changed = '".$T2['item_label']."';";
		$result2 = qedb($query2);

		if(qnum($result2)==0) { return false; }

		$r2 = qrow($result2);
		$og_item_id = $r2['id'];

		$query3 = "SELECT so_number as order_number FROM sales_items WHERE id = ".res($og_item_id).";";
		$result3 = qedb($query3);

		if(qnum($result3)==0){ return false; }// shouldn't happen, but if it does, RUN

		$r3 = qrow($result3);
		$og_order_number = $r3['order_number'];

		// Now that we have the original item_id that this RMA is attached to
		// 1. Find the Invoice number for the order (Package Contents (serialid) => Invoice Shipments (Packageid) => Invoice Items (invoice_item_id) => invoice_no)
		// 2. Find the cogs_amount based on the sales cogs on this speicific inventoryid and item_id and label
		$query3 = "SELECT i.invoice_no FROM package_contents pc, packages p, invoice_shipments s, invoice_items i WHERE p.id = pc.packageid AND p.order_type = ".fres($T2['type'])." AND p.order_number = ".res($og_order_number)." AND pc.serialid = ".res($inventoryid)." AND s.packageid = p.id AND s.invoice_item_id = i.id;";
		$result3 = qedb($query3);

		if(qnum($result3)){
			$r3 = qrow($result3);

			$invoice_no = $r3['invoice_no'];
		}

		// Get sales COG
		$query3 = "SELECT cogs_avg FROM sales_cogs WHERE inventoryid = ".res($inventoryid)." AND taskid = ".res($og_item_id)." AND task_label = ".fres($T2['item_label']).";";
		$result3 = qedb($query3);

		if(qnum($result3)){
			$r3 = qrow($result3);

			$cogs_amount = $r3['cogs_avg'];
		}

		// no journal entries if no cogs!
		if(! $cogs_amount) {
			$cogs_amount = 0;
			return false;
		}

		setJournalEntry(false,$GLOBALS['now'],$debit_account,$credit_account,'COGS for RMA Return #'.$rma_number, $cogs_amount, $invoice_no,'invoice');
	}

	function savetoDatabase($locationid, $data, $invid = '', $rma_number){
		global $rma_number;
	
		$err_output;
		$query;
		$id;
		
		if($invid != '') {
			$id = $invid;
		} else {
			$id = $data['inventoryid'];
		}

		//Check to see if the item has been received
		$query = "SELECT * FROM inventory_history WHERE invid = '".res($id)."' AND field_changed = 'returns_item_id' AND value = '".res($data['rmaid'])."'; ";
//		$query = "SELECT * FROM inventory WHERE id = '". res($id) ."' AND returns_item_id is NULL;";
		$result = qedb($query);
		
		if (mysqli_num_rows($result)==0) {
			$I = array('returns_item_id'=>$data['rmaid'],'status'=>'received','locationid'=>$locationid,'conditionid'=>'-5','id'=>$id);
			$inventoryid = setInventory($I);

			generateJournal($inventoryid, $data['rmaid'], 'returns_item_id', $rma_number);

			// notify accounting when disposition is Credit, so that they can generate the Credit Memo
			if ($data['dispositionid']==1) {
				if (! $GLOBALS['DEV_ENV']) {
					setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

					$bcc = 'david@ven-tel.com';
					$rma_ln = 'https://www.stackbay.com/RMA'.$rma_number;
					$email_body = 'RMA '.$rma_number.' is received, and is dispositioned for Credit.<br/><br/>';

					$query = "SELECT order_number, order_type FROM returns WHERE rma_number = '".res($rma_number)."'; ";
					$result = qedb($query);
					if (mysqli_num_rows($result)>0) {
						$r = mysqli_fetch_assoc($result);
						$order_combo = strtoupper(substr($r['order_type'],0,1)).'O'.$r['order_number'];
						$order_ln = 'https://www.stackbay.com/'.$order_combo;
						$email_body .= 'To issue the Credit, go to the originating billable order: '.
							$order_combo.' (<a href="'.$order_ln.'">'.$order_ln.'</a>)<br/><br/>';
					}

					$email_body .= 'Here is a link to the RMA: <a href="'.$rma_ln.'">'.$rma_ln.'</a><br/>';

					$send_success = send_gmail($email_body,'RMA '.$rma_number.' pending credit','accounting@ven-tel.com',$bcc);

					if ($send_success) {
						// echo json_encode(array('message'=>'Success'));
					} else {
						//$this->setError(json_encode(array('message'=>$SEND_ERR)));
					}
				}
			}
		} else {
			die("Item has already been received, please go back and check your data");
			$err_output = "Item has already been received.";
		}

		return $err_output;
	}
	
	//parameter id if left blank will pull everything else if id is specified then it will give the disposition value
	

	//Grab all parts of the RMA
	$partsListing = getRMAParts($rma_number);
?>

<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<title>RMA Receive <?=($rma_number != 'New' ? '#' . $rma_number : '')?></title>
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
				padding-top:15px;
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
	
	<body class="sub-nav" id="rma-add" data-order-type="RMA" data-order-number="<?=$rma_number?>">

	<!----------------------- Begin the header output  ----------------------->
		<div class="container-fluid pad-wrapper data-load">
		<?php include 'inc/navbar.php'; include 'modal/package.php';?>
		<div class="row table-header" id = "order_header" style="margin: 0; min-height:60px; width: 100%;">
			<div class="col-sm-4"><a href="/rma.php?rma=<?=$rma_number;?>" class="btn btn-default btn-sm" style="margin-top: 10px;"><i class="fa fa-pencil" aria-hidden="true"></i> Edit</a></div>
			<div class="col-sm-4 text-center">
				<h2 class="minimal" style="margin-top:10px"><?php echo 'RMA# '.$rma_number.' Receiving'; ?></h2>
				<?php if ($R['created']) { echo '<div class="info text-center" style="font-size:14px">'.format_date($R['created'],"D n/j/y g:ia").'</div>'; } ?>
			</div>
			<div class="col-sm-4">
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
			<div class="col-md-2 rma_sidebar" data-page="addition">
<?php
	$T = order_type($order_type);
	$prefix = strtoupper(substr($T['order'],0,2));
?>
				<div class="row">
					<div class="col-sm-12">
						<h4 style="margin-top:10px"><?php echo strtoupper(getCompany($R['companyid'])); ?></h4>
						<h3><?php echo $prefix.' '.$order_number; ?> <a href="/<?php echo $prefix.$order_number; ?>"><i class="fa fa-arrow-right"></i></a></h3>
					</div>
				</div>
				<BR><BR><BR>
				<div class="row">
					<div class="col-sm-12">
						<label>RMA Notes</label>
						<div style="width:100%; height:150px; border:1px solid gray; background-color:#f5f5f5">
							<?php echo $R['notes']; ?>
						</div>
					</div>
				</div>	
			</div>
				
			<div class="col-sm-10">
				<form id="rma_add" method="post">
				<?php 
					//Grab all the parts from the specified PO #
					$init = true;
					$rma_status = true;
					if(!empty($partsListing)) {
						foreach($partsListing as $part): 
							$serials = getRMAitems($part['partid'],$rma_number);

						if($init):
							foreach($partsListing as $part) {
								foreach(getRMAitems($part['partid'],$rma_number) as $item){
									if($item['returns_item_id']<>$item['id']) {//not the same as the current return item id
										$rma_status = false;
										break;
									}

								}
							}

				?>
				<div class = 'row' style='padding-top:10px;'>
					<div class="col-sm-12">
						<div class="btn-group box_group" style = "padding-bottom:16px;">
							<button type="button" class="btn btn-warning box_edit" title = 'Edit Selected Box'>
								<i class="fa fa-pencil fa-4" aria-hidden="true"></i>
							</button>
							<?php

								$select = "SELECT * FROM `packages`  WHERE  `order_number` = '$rma_number' AND `order_type` = 'RMA'; ";
								$results = qedb($select);
								
								//Check for any open items to be shipped
								if (mysqli_num_rows($results) > 0){
									//Initialize
									$init = true;
									$package_no = 0;
									
									$masters = master_packages($rma_number,"RMA");
									foreach($results as $b){
										$package_no = $b['package_no'];
										$box_button = "<button type='button' class='btn ";
										
										//Build classes for the box buttons based off data-options
										$box_button .= 'btn-grey'; //If the button has been shipped
										$box_button .= (($b['datetime'] == '' && $init) ? ' active' : ''); //If the box is active, indicate that
										$box_button .= (in_array($package_no,$masters)) ? ' master-package ' : '';
										$box_button .= " box_selector'";
										
										//Add Data tags for the future population of modals
										$box_button .= " data-width = '".$b['weight']."' data-l = '".$b['length']."' ";
										$box_button .= " data-h = '".$b['height']."' data-weight = '".$b['weight']."' ";
										$box_button .= " data-row-id = '".$b['id']."' data-tracking = '".$b['tracking_no']."' ";
										$box_button .= " data-row-freight = '".$b['freight_amount']."'";
										$box_button .= " data-order-number='" . $rma_number . "'";
										$box_button .= " data-box-shipped ='".($b['datetime'] ? $b['datetime'] : '')."' >".$b['package_no']."</button>";
										echo($box_button);
			                        	
			                        	$box_list .= "<option value='".$b['id']."'>Box ".$b['package_no']."</option>";
			                        	if($b['datetime'] == '' && $init)
			                        		$init = false;
									}
									

								} else {
									$insert = "INSERT INTO `packages`(`order_number`,`order_type`,`package_no`,`datetime`) VALUES ($rma_number,'RMA','1',NOW());";
									qedb($insert);
									echo("<button type='button' class='btn active box_selector master-package' data-row-id = '".qid()."'>1</button>");
								}
	
							?>
							<button type="button" class="btn btn-primary box_addition" title = "">
						  		<i class="fa fa-plus fa-4" aria-hidden="true"></i>
					  		</button>
						</div>

					</div>
				</div>
					<div class="row" style="margin: 20px 0;">
						
						<?php if(!$rma_status) { ?>
						
							<div class="col-md-7" style="padding-left: 0px !important;">
								<div class="col-md-3">
								</div>
								<div class="col-md-3 location">
									<select name="locationid" size="1" class="location-selector" data-noreset="1">
									</select>
								</div>
								
								<div class="col-md-6" style="padding: 0 0 0 5px;">
									<div class="input-group">
									    <input class="form-control input-sm serialInput auto-focus" name="rma_serial" type="text" placeholder="Serial" value="<?=($rma_serial ? $rma_serial : '');?>" autofocus>
										<span class="input-group-btn">
											<button class="btn btn-success btn-sm" type="submit"><i class="fa fa-save"></i></button>
										</span>
									</div>
					            </div>
				            </div>

				        <?php } ?>

					</div>
				
					<div class="table-responsive">
						<table class="rma_add table table-hover table-striped table-condensed" style="table-layout:fixed;"  id="items_table">
							<thead>
						         <tr>
						            <th class="col-sm-3">
						            	PART	
						            </th>
						            <th class="text-center col-sm-1">
										RMA Serial
						        	</th>
						        	<th class="text-center col-sm-1">
										Warr Exp
						        	</th>
						        	<th class="text-center col-sm-1">
										Disposition
						        	</th>
						        	<th class="col-sm-2">
										Reason
						        	</th>
						        	<th class="text-center col-sm-1">
										Location
						        	</th>
						        	<th class="text-center col-sm-2">
										Vendor Warr Exp
						        	</th>
						        	<th class="text-right col-sm-1">
						        		Action
						        	</th>
						         </tr>
							</thead>
							
							<tbody>

						<?php $init = false; endif; ?>

								<tr class="valign-top">
									<td>
										<?php 
											echo display_part(current(hecidb($part['partid'],'id')));
										?>
									</td>
									<td class="serialsExpected">
										<?php 
											if(!empty($serials)):
												foreach($serials as $item) { 
										?>
												<div class="row">
													<!--<div class="input-group">-->
														<span class="text-center" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=$item['serial_no'];?></span>
														<!--<span class="input-group-addon">-->
													
														<!--</span>-->
													<!--</div>-->
												</div>
										<?php 
												} 
											endif;
										?>
									</td>
									
									<td class="warranty">
										<?php 
											if(!empty($serials)):
											foreach($serials as $item) { 
										?>
											<div class="row">
												<span class="text-center" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=calcWarranty($item['inventoryid'], 'sales');?></span>
											</div>	
										<?php 
											} 
											endif;
										?>
									</td>
																		
									<td class="disposition">
										<?php 
											if(!empty($serials)):
											foreach($serials as $item) { 
										?>
											<div class="row">
												<span class="text-center" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=($item['dispositionid'] ? getDisposition($item['dispositionid']) : 'None' )?></span>
											</div>	
										<?php 
											} 
											endif;
										?>
									</td>
									
									<td class="reason">
										<?php 
											if(!empty($serials)):
												foreach($serials as $item) { 
										?>
												<div class="row">
													<span class="truncate" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=($item['reason']? $item['reason'] : 'No reason given'); ?></span>
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
													<span class="text-center <?=(empty($item['last_return']) ? 'location-input' : ''); ?>" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=($item['returns_item_id']<>$item['id'] ? 'TBD' : display_location($item['locationid']) )?></span>
												</div>	
										<?php 
												} 
											endif;
										?>
									</td>
									
									<td class="vwarranty">
										<?php 
											if(!empty($serials)):
											foreach($serials as $item) { 
										?>
											<div class="row">
												<span class="text-center" style="display: block; padding: 7px 0; margin-bottom: 5px;"><?=calcWarranty($item['inventoryid'], 'purchase');?></span>
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
										<div class="row text-center">
											<?php if($item['returns_item_id']==$item['id'] AND getDisposition($item['dispositionid']) == 'Repair') { 
													$linked_ro = '';
													//Check and see if the repair order has already been created for this line item
													$query = "SELECT ro_number FROM repair_items WHERE (ref_1_label = 'return_item_id' OR ref_2_label = 'return_item_id') AND (ref_1 = ".prep($item['id'])." OR ref_2 = ".prep($item['id']).") AND invid = ".res($item['inventoryid']).";";
													$ro_result = qedb($query);

													if (mysqli_num_rows($ro_result)) {
														$ro_result = mysqli_fetch_assoc($ro_result);
														$linked_ro = $ro_result['ro_number'];
													}

													if($linked_ro): 
											?>
												<a href="/order.php?ps=repair&on=<?=$linked_ro?>" style="padding: 7px; margin-bottom: 5px; float: right;" class="serial-check btn btn-flat btn-sm" name='repair_trigger' data-toggle="tooltip" data-placement="bottom" title="Repair" value="<?=$item['inventoryid'];?>"><i class="fa fa-wrench" aria-hidden="true"></i></a>		
											<?php else: ?>
												<button style="padding: 7px; margin-bottom: 5px; float: right;" class="serial-check btn btn-flat btn-sm" type="submit" name='repair_trigger' data-toggle="tooltip" data-placement="bottom" title="Repair" value="<?=$item['inventoryid'];?>"><i class="fa fa-wrench" aria-hidden="true"></i></button>
											
											<?php endif; }?>

											<?php if(getDisposition($item['dispositionid']) == 'Exchange') { ?>
												<button id="exchange" style="padding: 7px; margin-bottom: 5px; float: right;" class="serial-check btn gray btn-flat btn-sm" type="submit" name='exchange_trigger' data-toggle="tooltip" data-placement="bottom" title="Send Replacement" value="<?=$item['inventoryid'];?>"><i class="fa fa-share" aria-hidden="true"></i></button>
											<?php } ?>
											
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
		<script type="text/javascript">
			$("#exchange").click(function(event){
				if (confirm("Confirm to Send Replacement")){
					$('form#rma_add').submit();
				} else {
					event.preventDefault();
				}
			});
		</script>
	
	</body>
</html>
