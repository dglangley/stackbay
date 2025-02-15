<?php
$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/setCogs.php';
	include_once $rootdir.'/inc/getCost.php';
	include_once $rootdir.'/inc/calcRepairCost.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/invoice.php';
	include_once $rootdir.'/inc/setInventory.php';
	include_once $rootdir.'/inc/shipEmail.php';

	$DEBUG = 0;
	if (! $DEBUG) {
		header('Content-Type: application/json');
	}

	//This is a list of everything
	$productItems = $_REQUEST['items'];
	$so_number = grab('so_number');

	//items = ['partid', 'Already saved serial','serial or array of serials', 'conditionid or array', 'lot', 'qty']
	function savetoDatabase($productItems, $so_number, $date){
		$db = array(
			"timestamp" => "",
			"so_number" => "",
			"invoice" => "",
			"error" => ""
			);
		$return_arr = array();
		if($GLOBALS['DEBUG']){
			$productItems = json_decode($productItems);
		}
		//This is splitting each product from mass of items
		$item_split = array_chunk($productItems,7);
		foreach($item_split as $product) {
			//This query updates and saves the box as closed only if there are no errors in the order
			foreach($product[6] as $box) {
				// found a bug 11-16-17 where a subsequent shipment on a previously-shipped order (such as an RMA exchanged unit)
				// would submit ALL the previous boxes to this code, resulting in ALL previously-shipped inventory items getting
				// updated. WOW is this code bad or what. This is a stop-gap fix...
				$query = "SELECT * FROM packages WHERE id = '".res($box)."' AND datetime IS NULL; ";
				$result = qedb($query);
				// package has previously been shipped, don't do anything beyond this point! this code should be only for unshipped boxes
				if (mysqli_num_rows($result)==0) { continue; }

				//Check and only ship boxes that have something placed into them
				$query = "SELECT * FROM package_contents WHERE packageid = '".res($box)."';";
				$data = qedb($query);
				if (mysqli_num_rows($data)>0) {
					$query = "UPDATE packages SET datetime = '".res($date)."' WHERE id = '".res($box)."' AND datetime is NULL;";
					$check = qedb($query);
				} else {
					$db['error'] = 'no package set';
				}
				while ($r = mysqli_fetch_assoc($data)) {
					$inventoryid = $r['serialid'];

					$query2 = "SELECT si.* FROM inventory i, sales_items si ";
					$query2 .= "WHERE si.so_number = '".res($so_number)."' AND i.id = '".res($inventoryid)."' AND si.id = i.sales_item_id; ";
					$result2 = qedb($query2);// OR die(qe().'<BR>'.$query2);
					while ($r2 = mysqli_fetch_assoc($result2)) {
						$partid = $r2['partid'];

						/***** GET THE REPAIR ITEM ID AND FIND OUT WHAT TYPE OF REPAIR IT IS *****/
						if (($r2['ref_1'] AND $r2['ref_1_label']=='repair_item_id') OR ($r2['ref_2'] AND $r2['ref_2_label']=='repair_item_id')) {
							/***** REPAIR (RMA or BILLABLE) *****/
							$repair_item_id = 0;
							if ($r2['ref_1'] AND $r2['ref_1_label']=='repair_item_id') {
								$repair_item_id = $r2['ref_1'];
							} else {
								$repair_item_id = $r2['ref_2'];
							}

							// find out if the repair is a billable repair order, or a warranty replacement. if a billable repair,
							// the freight gets charged to the customer as an expense, so not claimable as COGS by freight. however, if
							// a warranty repair, then the freight gets added back to the original billable order as COGS because
							// we can't charge the customer, and it's an incurred cost to that original billable sale/repair
							$ro_number = 0;
							$query3 = "SELECT ro_number FROM repair_items WHERE id = '".$repair_item_id."'; ";
							$result3 = qedb($query3);
							if (mysqli_num_rows($result3)==0) {
								// really should never be a case this doesn't come up, but just the same...escape
								continue;
							}

							$r3 = mysqli_fetch_assoc($result3);
							$ro_number = $r3['ro_number'];

							/***** GET TERMS FOR THIS REPAIR ORDER TO DETERMINE ITS TYPE - RMA OR REPAIR *****/
							$NONBILLABLE = true;
							$query4 = "SELECT termsid FROM repair_orders ro WHERE ro.ro_number = '".res($ro_number)."'; ";
							$result4 = qedb($query4);
							// with an existing record, check the terms id for anything other than "N/A" (non-billable)
							if (mysqli_num_rows($result4)>0) {
								$r4 = mysqli_fetch_assoc($result4);
								if ($r4['termsid']<>15) {//15==N/A
									$NONBILLABLE = false;
								}
							}

							// get cost of repair order for this unit
							$repair_cogs = calcRepairCost($ro_number,$repair_item_id,$inventoryid,$NONBILLABLE);//get repair cost

							// If billable (! $NONBILLABLE), the $repair_cogs gets added to the repair itself; if NON-billable,
							// the $repair_cogs should be added to the *originating* repair, not this most direct
							// repair, since this is the warranty repair for that original billable repair
							if (! $NONBILLABLE) {//this means it's BILLABLE
								// for billable repairs, set cost of goods directly against repair item
								$cogsid = setCogs($inventoryid, $repair_item_id, 'repair_item_id', $repair_cogs);
								continue;
							}

							/***** NON-BILLABLE, GET RMA DATA TO PRODUCE ORIGINATING BILLABLE ORDER *****/
							$query4 = "SELECT ref_1, ref_1_label, ref_2, ref_2_label FROM repair_items ";
							$query4 .= "WHERE id = '".$repair_item_id."' AND (ref_1_label = 'return_item_id' OR ref_2_label = 'return_item_id'); ";
							$result4 = qedb($query4);
							if (mysqli_num_rows($result4)==0) {
								// RMA doesn't exist, which is admittedly weird and awkward, but let's get out of here
								continue;
							}
							$r4 = mysqli_fetch_assoc($result4);
							// determine which ref has return item id
							$return_item_id = 0;
							if ($r4['ref_1'] AND $r4['ref_1_label']=='return_item_id') {
								$return_item_id = $r4['ref_1'];
							} else if ($r4['ref_2'] AND $r4['ref_2_label']=='return_item_id') {
								$return_item_id = $r4['ref_2'];
							}

							// look up the RMA to determine if the originating billable order was a Sale, or Repair
							$query4 = "SELECT order_number, order_type FROM return_items ri, returns r ";
							$query4 .= "WHERE ri.id = '".res($return_item_id)."' AND ri.rma_number = r.rma_number; ";
							$result4 = qedb($query4);
							if (mysqli_num_rows($result4)==0) {
								// oops, missing record
								continue;
							}
							$r4 = mysqli_fetch_assoc($result4);

							if ($r4['order_type']=='Sale') {
								// get the sales item id to which we attach the cogs
								$query5 = "SELECT si.id FROM sales_items si, inventory_history h ";
								$query5 .= "WHERE si.so_number = '".res($r4['order_number'])."' AND h.invid = '".res($inventoryid)."' ";
								$query5 .= "AND si.id = h.value AND h.field_changed = 'sales_item_id'; ";
								$result5 = qedb($query5);
								if (mysqli_num_rows($result5)==0) { continue; }
								$r5 = mysqli_fetch_assoc($result5);

								// sum the new repair cogs with the existing cogs because setCogs() below will SUM and UPDATE the existing COGS record
								$existing_cogs = getCOGS($inventoryid, $r5['id'], 'sales_item_id');
								$cogs = $existing_cogs+$repair_cogs;

								$cogsid = setCogs($inventoryid, $r5['id'], 'sales_item_id', $cogs);
							} else if ($r4['order_type']=='Repair') {
								$query5 = "SELECT ri.id FROM repair_items ri WHERE ri.ro_number = '".res($r4['order_number'])."' AND ri.invid = '".res($inventoryid)."'; ";
								$result5 = qedb($query5);
								if (mysqli_num_rows($result5)==0) { continue; }
								$r5 = mysqli_fetch_assoc($result5);

								// sum the new repair cogs with the existing cogs because setCogs() below will SUM and UPDATE the existing COGS record
								$existing_cogs = getCOGS($inventory, $r5['id'], 'repair_item_id');
								$cogs = $existing_cogs+$repair_cogs;

								$cogsid = setCogs($inventoryid, $r5['id'], 'repair_item_id', $cogs);
							}
						} else if (($r2['ref_1'] AND $r2['ref_1_label']=='sales_item_id') OR ($r2['ref_2'] AND $r2['ref_2_label']=='sales_item_id')) {
							/***** RMA REPLACEMENT *****/
							// this is a replacement unit for a previous sale; get the avg cost of the replacement unit and apply it
							// towards the cogs of the original sale
							$replacement_cogs = getCost($partid,'average',true);//get existing avg cost at this point in time

							// We're adding the $cogs to the originating sales item id, which is what the ref1/ref2 label refers to
							if ($r2['ref_1_label']=='sales_item_id') {
								$sales_item_id = $r2['ref_1'];
							} else {/*$r2['ref_2_label']=='sales_item_id']*/
								$sales_item_id = $r2['ref_2'];
							}

							$existing_cogs = getCOGS($inventoryid,$sales_item_id,'sales_item_id');
							$cogs = $existing_cogs+$replacement_cogs;

							$cogsid = setCogs($inventoryid, $sales_item_id, 'sales_item_id', $cogs);
						} else if (($r2['ref_1'] AND $r2['ref_1_label']=='purchase_item_id') OR ($r2['ref_2'] AND $r2['ref_2_label']=='purchase_item_id')) {
							/***** RTV *****/
							$purchase_item_id = 0;
							if ($r2['ref_1_label']=='purchase_item_id') {
								$purchase_item_id = $r2['ref_1'];
							} else {/*$r2['ref_2_label']=='purchase_item_id']*/
								$purchase_item_id = $r2['ref_2'];
							}
/*DAVID, finish this out!
								// if this item is being shipped back to vendor, the ref labels will have the originating purchase item id,
								// so we treat it as a credit. find out if it was used for a sale, and walk the cogs back
								$cogs = 0;
								$query3 = "SELECT cogs_avg FROM sales_cogs WHERE inventoryid = '".res($inventoryid)."' AND item_id_label = 'sales_item_id' AND cogs_avg > 0; ";
								$result3 = qedb($query3);
								while ($r3 = mysqli_fetch_assoc($result3)) {
									$cogs += $r3['cogs_avg'];
								}

								$cogs = getCost($partid,'average',true);//get existing avg cost at this point in time
								$cogsid = setCogs($inventoryid, $r2['id'], 'sales_item_id', $cogs);
*/
						} else {
							/***** SALE ITEM *****/
							// this item is a billable sale and very straightforward; $r2['id'] is the sales item id
							$cogs = getCost($partid,'average',true);//get existing avg cost at this point in time
							$cogsid = setCogs($inventoryid, $r2['id'], 'sales_item_id', $cogs);
						}
					}

					// changes from manifest to shipped
					$I = array('id'=>$inventoryid,'status'=>'shipped');
					setInventory($I);
				}
			}/* end foreach ($product) */
		}/* end foreach ($item_split) */

		// Generate the tracking email based on all packages with tracking numbers tied to the order number
		shipEmail($so_number, 'Sale', $date);

		$db = create_invoice($so_number, $date);
/*commented this in favor of built-in verification within create_invoice() function
		$query = "SELECT * FROM invoices WHERE order_number = '".res($so_number)."' AND shipmentid = '".res($date)."'; ";
		$result = qedb($query);
		// not already invoiced
		if (mysqli_num_rows($result)==0) {
			$return_arr = create_invoice($so_number, $date);
			if($return_arr['error']){
				$db['error'] = $return_arr['error'];
			} else {
				$db['invoice'] = $return_arr['invoice_no'];
			}
		} else {
			$db['error'] = "Attempted To Create Duplicate Invoice";
		}
*/
		// append addl data to array
		$db['timestamp'] = $date;
		$db['so_number'] = $so_number;
		
		return $db;
	}

	$db = savetoDatabase($productItems, $so_number, $now);
	
	echo json_encode($db);
    exit;
