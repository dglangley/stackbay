<?php

//Prepare the page as a JSON type
header('Content-Type: application/json');

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
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/setCogs.php';
	include_once $rootdir.'/inc/getCost.php';
	include_once $rootdir.'/inc/calcRepairCost.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/invoice.php';

	//This is a list of everything
	$productItems = $_REQUEST['items'];
	$so_number = grab('so_number');
	

	//items = ['partid', 'Already saved serial','serial or array of serials', 'conditionid or array', 'lot', 'qty']
	function savetoDatabase($productItems, $so_number, $date){
		$result = [];
		
		//This is splitting each product from mass of items
		$item_split = array_chunk($productItems,7);
		
		foreach($item_split as $product) {
			//This query updates and saves the box as closed only if there are no errors in the order
				foreach($product[6] as $box) {
					$check;
					//Check and only ship boxes that have something placed into them
					$query = "SELECT * FROM package_contents WHERE packageid = '".res($box)."';";
					$data = qdb($query);
					if (mysqli_num_rows($data)>0) {
						$query = "UPDATE packages SET datetime ='".res($date)."' WHERE id = '".res($box)."' AND datetime is NULL;";
						$check = qdb($query);
					}
					// added by david 6-21-17 to set profits and cogs on each unit being shipped
					while ($r = mysqli_fetch_assoc($result)) {
						$inventoryid = $r['serialid'];
						$query2 = "SELECT si.* FROM inventory i, sales_items si ";
						$query2 .= "WHERE si.so_number = '".res($so_number)."' AND i.id = '".res($inventoryid)."' AND si.id = i.sales_item_id; ";
						$result2 = qdb($query2);// OR die(qe().'<BR>'.$query2);
						while ($r2 = mysqli_fetch_assoc($result2)) {
							$partid = $r2['partid'];

							if (($r2['ref_1'] AND $r2['ref_1_label']=='repair_item_id') OR ($r2['ref_2'] AND $r2['ref_2_label']=='repair_item_id'])) {
								/***** REPAIR (RMA or BILLABLE) *****/
								$repair_item_id = 0;
								if ($r2['ref_1'] AND $r2['ref_1_label']=='repair_item_id']) {
									$repair_item_id = $r2['ref_1'];
								} else {
									$repair_item_id = $r2['ref_2'];
								}
								$ro_number = 0;
								$query3 = "SELECT ro_number FROM repair_items WHERE id = '".$repair_item_id."'; ";
								$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
								if (mysqli_num_rows($result3)>0) {
									$r3 = mysqli_fetch_assoc($result3);
									$ro_number = $r3['ro_number'];

									// find out if the repair is a billable repair order, or a warranty replacement. if a billable repair,
									// the freight gets charged to the customer as an expense, so not claimable as COGS by freight. however, if
									// a warranty repair, then the freight gets added back to the original billable order as COGS because
									// we can't charge the customer, and it's an incurred cost to that original billable sale/repair
									$NONBILLABLE = true;
									$query4 = "SELECT termsid FROM repair_orders ro WHERE ro.ro_number = '".res($ro_number)."'; ";
									$result4 = qdb($query4) OR die(qe().'<BR>'.$query4);
									// with an existing record, check the terms id for anything other than "N/A" (non-billable)
									if (mysqli_num_rows($result4)>0) {
										$r4 = mysqli_fetch_assoc($result4);
										if ($r4['termsid']<>15) {//15==N/A
											$NONBILLABLE = false;
										}
									}

									// get cost of repair order for this unit
									$cogs = calcRepairCost($ro_number,$repair_item_id,$inventoryid,$NONBILLABLE);//get repair cost

									// If non-billable, the $cogs should be added to the *originating* repair, not this most direct
									// repair, since this is the warranty repair for that original billable repair
									if ($NONBILLABLE) {
										$query4 = "SELECT ref_1, ref_1_label, ref_2, ref_2_label FROM repair_items ";
										$query4 .= "WHERE id = '".$repair_item_id."' AND (ref_1_label = 'return_item_id' OR ref_2_label = 'return_item_id'); ";
										$result4 = qdb($query4) OR die(qe().'<BR>'.$query4);
										if (mysqli_num_rows($result4)>0) {
											$r4 = mysqli_fetch_assoc($result4);
											$return_item_id = 0;
											if ($r4['ref_1'] AND $r4['ref_1_label']=='return_item_id') {
												$return_item_id = $r4['ref_1'];
											} else if ($r4['ref_2'] AND $r4['ref_2_label']=='return_item_id') {
												$return_item_id = $r4['ref_2'];
											}

											if ($return_item_id) {
												$profitid = setCogs($inventoryid, $repair_item_id, 'repair_item_id', $cogs);
											}
										}
									} else {
										// for billable repairs, set cost of goods directly against repair item
										$profitid = setCogs($inventoryid, $repair_item_id, 'repair_item_id', $cogs);
									}
								}
							} else if (($r2['ref_1'] AND $r2['ref_1_label']=='sales_item_id') OR ($r2['ref_2'] AND $r2['ref_2_label']=='sales_item_id'])) {
								/***** RMA REPLACEMENT *****/
								// this is a replacement unit for a previous sale; get the avg cost of the replacement unit and apply it
								// towards the cogs of the original sale
								$cogs = getCost($partid);//get existing avg cost at this point in time

								// We're adding the $cogs to the originating sales item id, which is what the ref1/ref2 label refers to
								if ($r2['ref_1_label']=='sales_item_id']) {
									$profitid = setCogs($inventoryid, $r2['ref_1'], $cogs);
								} else {/*$r2['ref_2_label']=='sales_item_id']*/
									$profitid = setCogs($inventoryid, $r2['ref_2'], $cogs);
								}

								// if we're getting credit on the returned unit, credit back cogs from the original shipment

							} else {
								/***** SALE ITEM *****/
								// this item is a billable sale and very straightforward; $r2['id'] is the sales item id
								$cogs = getCost($partid);//get existing avg cost at this point in time
								$profitid = setCogs($inventoryid, $r2['id'], $cogs);
							}
						}
					}
				}
				
				$result['timestamp'] = $date;
				$result['on'] = $so_number;
				
				//Invoice Creation based off shipping
		}
		
		create_invoice($so_number, $date, "Sale");
		
		return $result;
	}
	
	$result = savetoDatabase($productItems, $so_number, $now);
	
	echo json_encode($result);
    exit;
