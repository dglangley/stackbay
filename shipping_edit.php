<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/split_inventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/checkOrderQty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPackageContents.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCogs.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/calcRepairCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/shipEmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/invoice.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterialsCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getInvoiceNumber.php';
	// include_once $_SERVER["ROOT_DIR"].'/inc/getOriginalOrder.php';

	$DEBUG = 0;
	$ALERT = '';

	$COMPLETE = false;
	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function undoShipment($inventoryid) {
		global $ALERT;

		$query = "UPDATE inventory SET status = 'received' WHERE id = ".res($inventoryid).";";
		qedb($query);
	}

	function isShipped($packageid) {
		$shipped = false;

		$query = "SELECT * FROM packages WHERE id = ".res($packageid).";";
		$result = qedb($query);

		if(qnum($result)){
			$r = qrow($result);

			if($r['datetime']) {
				$shipped = true;
			}
		}

		return $shipped;
	}

	function shipInventory($line_item, $order_number, $type, $locationid, $bin, $conditionid, $partid, $serial, $qty, $packageid) {
		global $ALERT, $DEBUG, $COMPLETE;

		$T = order_type($type);

		$status = 'received';
		if (ucfirst($type)=='Repair') { $status = 'in repair'; }

		if (ucfirst($type)=='Sale' AND $line_item) {
			checkOrderQty($line_item, ucfirst($type));
		}
		

		if(! $packageid AND ucfirst($type)=='Sale') {
			// For now if it is a sales item and has no packageid then consider this an error that needs to be fixed
			$ALERT = "Error: No package selected. Please select a package and try again.";
			return 0;
		}

		if(isShipped($packageid) AND ucfirst($type)=='Sale') {
			// For now if it is a sales item and has no packageid then consider this an error that needs to be fixed
			$ALERT = "Error: Package already shipped. Please select another package and try again.";
			return 0;
		}

		$inventoryid = 0;

		$pulledInv = array();

		if($serial) {

			$inventory_qty = 0;

			// force cap serials
			$serial = strtoupper(trim($serial));

			// Using getInventory allows us to see if the serial to part already exists in the database
			// Difference being an inventory update or inventory addition
			$inv = getInventory($serial,$partid, $status);

			if(empty($inv)) {
				// are we shipping out a repaired unit? allow for 'in repair' status
				if ($type=='Sale' AND $status = 'received') {
					$status = 'in repair';

	                $inv = getInventory($serial,$partid, $status);
				}

				if (empty($inv)) {
					$ALERT = urlencode('ERROR: Serial# ' .$serial. ' is not in stock or has no record.'); 
					return 0;
				}
			}

			// line_item does not exist then attempt to find it by matching the partid for each of the serial record found
			if(! $line_item) {
				// There is more than 1 item with the same serial
				// Alert user to select the actual partid
				if(! $inv['id']) {
					$ALERT = urlencode('ERROR: Serial# ' .$serial. ' exists among multiple parts please select the correct part.'); 
					return 0;
				}
				$ORDER = getOrder($order_number, $type);

				$LINES = $ORDER['items'];

				$matching_partids = 0;
				if ($inv['partid']) {
					foreach($LINES as $line) {
						if ($inv['partid'] == $line['partid']) { $matching_partids++; }
					}

					if ($matching_partids>1) {
						$ALERT = urlencode('ERROR: Multiple items on the order match the part number for this serial. Please select an item first.');
						return 0;
					}
				}

				foreach($LINES as $line) {
					// This is if the array is non multi dimensional array
					// AKA array() vs the else array(array(),array())
					if($inv['partid']) {
						if($inv['partid'] == $line['partid']) {
							$line_item = $line['id'];
							$partid = $line['partid'];
							$inventoryid = $inv['id'];
							$inventory_qty = $inv['qty'];

							break;
						}
					} else {
						foreach($inv as $item) {
							// If the partid matches with the serial record then set the variables accordingly and break
							if($item['partid'] == $line['partid']) {
								$line_item = $line['id'];
								$partid = $line['partid'];
								$inventoryid = $item['id'];
								$inventory_qty = $item['qty'];

								break;
							}
						}
					}
				}
			} else {
				// Find the partid if no partid is found for the serial
				// Check the lines partid
				$query = "SELECT partid FROM ".$T['items']." WHERE id = ".res($line_item).";";
				$result = qedb($query);

				if(mysqli_num_rows($result)) {
					$r = mysqli_fetch_assoc($result);

					$linePartid = $r['partid'];
				}

				if($linePartid != $partid AND $partid) {
					$ALERT = urlencode('ERROR: Serial# ' .$serial. ' is the wrong part.');
					return 0;
				}

				$inv = getInventory($serial,$partid, $status);

				$inventoryid = $inv['id'];
				$inventory_qty = $inv['qty'];
			}

			// Get the inventory label # to check if this item has been received already to the order
			$item_id = $inv[$T['inventory_label']];

			// Quick and dirty fail safe to not allow user to receive the same Serial
			if($item_id == $line_item AND $inv['status'] != 'received') {
				$ALERT = urlencode('ERROR: Serial# ' .$serial. ' has already been placed on the order.' . $item_id . ' ' . $line_item); 
				return 0;
			}

			if($inventoryid) {

				if($inventory_qty > 1) {
					// If the inventory qty is greater than 1 then split it out to the total qty - 1 to leave only 1 left on the record being pulled
					$ALERT = urlencode('The inventory record with this serial has an invalid qty. Please notify an admin immediately.'); 

					return 0;
				}

				// Update only, mainly put the item_id into the inventory
				$I = array('serial_no'=>$serial,'status'=>'shipped',$T['inventory_label']=>$line_item,'id'=>$inventoryid);
				$inventoryid = setInventory($I);
			} 

		} else if($qty) {
			$spilt = 0;

			$tempQty = $qty;
			// Get all records that have a status received and blank inventory_label aka no salies_item_id attached to it yet
			$inv = getInventory('',$partid, $status);

			if(empty($inv)) {
				$ALERT = urlencode('ERROR: Non-serial part is not in stock or has no record.'); 
				return 0;
			}

			if(! is_array($inv[0])) {
				$inv = array($inv); // turn record in multidimensional
			}

			// Go through each of the iterations and mark as used until the qty is furfilled
			foreach($inv as $record) {
				// print '<pre>'.print_r($record, true).'</pre>';

				// continually deduct the qty of each record until the qty is met or a record has a larger qty then that of reamining
				$tempQty -= $record['qty'];

				$pulledInv[] = $record['id'];

				if($tempQty == 0) {
					break;
				} else if($tempQty < 0) {
					// Put qty back to how much is remaining to furfill the qty to be sent
					$tempQty += $record['qty'];

					// Set the split amount to the remaining amopunt left on the order
					$split = $tempQty;
					break;
				}
			}

			$numItems = count($pulledInv);
			$i = 0;

			foreach($pulledInv as $inventoryid) {
				// If it is the last record and split exists then split out the inventory
				if(++$i === $numItems AND $split) {
					// Split out a new record of what should still be in stock for this current inventoryid
					split_inventory($inventoryid, $split);
					// echo 'Splitting '.$split.'<BR>';
				}

				// Set the inventoryid to the status shipped and add in the sales_item_id or whatever item_id that pertains to this current shipment
				$I = array('status'=>'shipped',$T['inventory_label']=>$line_item,'id'=>$inventoryid);
				$inventoryid = setInventory($I);
				// echo 'Setting ' . $inventoryid . '<BR><BR>';
			}
		}

		if($inventoryid OR ! empty($pulledInv)) {
			if($packageid) {

				if(! empty($pulledInv)) {
					// Insert each of the pulled inventory into the package AKA all the inventoryids required to fulfill x qty
					foreach($pulledInv as $inventoryid) {
						if($inventoryid) {
							$query = "INSERT INTO package_contents (packageid, serialid) VALUES (".res($packageid).",".res($inventoryid).");";
							qedb($query);
						}
					}
				} else {
					// Add Iventory id into the currently selected package
					$query = "INSERT INTO package_contents (packageid, serialid) VALUES (".res($packageid).",".res($inventoryid).");";
					qedb($query);
				}
			}

			if($type == 'Sale') {

				$field = ($type == 'Purchase' ? 'qty_received' : 'qty_shipped');

				// Opted to keep the qty_received 2/26
				// Go into the order if type is purchase and update the qty received
				$query = "UPDATE ".$T['items']." SET ".$field." = ".$field." + ".res(($qty ? : 1))." WHERE id = ".res($line_item).";";
				qedb($query);

				// Check if the qty has been fulfilled and update accordingly for all lines on the order
				$complete = '';

				$query = "SELECT qty, ".$field." FROM ".$T['items']." WHERE ".$T['order']." = ".res($order_number).";";
				$result = qedb($query);

				while($r = mysqli_fetch_assoc($result)) {
					// If the qty order is greater than the qty received then this line is not complete
					// Once 1 is incomplete then break out of the loop as no matter what it is not complete
					if($r['qty'] > $r[$field]) {
						$complete = false;
						break;
					} else {
						$complete = true;
					}
				}
			}
		} else {
			$ALERT = urlencode('No inventory record found!');
		}

		return 0;
	}

	function returntoStock($inventoryid, $packageid, $order_number, $order_type) {
		$T = order_type($order_type);

		// Get todays date
		$today = substr($GLOBALS['now'],0,10);

		if($inventoryid) {
			$inv = getInventory($inventoryid);
			$line_item = $inv[$T['inventory_label']];

			// Query the inventory history table to check for the past set values
			$query = "SELECT * FROM inventory_history WHERE invid = ".res($inventoryid)." AND date_changed LIKE (SELECT CONCAT(date(date_changed), '%')  FROM inventory_history WHERE invid = ".res($inventoryid)." AND field_changed = ".fres($T['inventory_label'])." ORDER BY date_changed DESC LIMIT 1);";
			$result = qedb($query);

			// Get the date of change and get all the results
			// Currently based on the shipping process only 2 things are changed out.
			// Status and the inventory_label: get those 2 previous values and set them
			$status = 'received';
			$inventory_label = NULL;
			while($r = mysqli_fetch_assoc($result)) {
				$delete = false;

				if($r['field_changed'] == 'status') {
					$status = $r['changed_from'];
					$delete = true;
				}

				if($r['field_changed'] == $T['inventory_label']) {
					// Leave NULL value is nothing is actually in it
					if($r['changed_from']) {
						$inventory_label = $r['changed_from'];
					}
					$delete = true;
				}
				if($delete) {
					// DELETE THIS RECORD
					$query2 = "DELETE FROM inventory_history WHERE date_changed = ".fres($r['date_changed'])." AND field_changed = ".fres($r['field_changed'])." AND invid = ".fres($r['invid'])." AND changed_from = ".fres($r['changed_from']).";";
					qedb($query2);
				}
			}

			// Set the inventory back to in stock
			$I = array('status'=>$status,$T['inventory_label']=>$inventory_label,'id'=>$inventoryid);
			$inventoryid = setInventory($I);

			// Delete the newly generated inventory_history records
			$query = "DELETE FROM inventory_history WHERE date_changed LIKE '".$today."%"."' AND field_changed = ".fres($T['inventory_label'])." AND value ".($inventory_label?' = '.$inventory_label:' IS NULL').";";
			qedb($query);

			$query = "DELETE FROM inventory_history WHERE date_changed LIKE '".$today."%"."' AND field_changed = 'status' AND value = ".fres($status).";";
			qedb($query);

			// Remove the item from the package
			$query = "DELETE FROM package_contents WHERE packageid = ".res($packageid)." AND serialid = ".res($inventoryid).";";
			qedb($query);

			// Remove a shipped qty
			$query = "UPDATE ".$T['items']." SET qty_shipped = qty_shipped - ".res($inv['qty'])." WHERE id = ".res($line_item).";";
			qedb($query);
		} 
	}

	function isoSubmit($order_number, $type) {
		$T = order_type($type);

		// Get all the packages on the order
		$packages = getISOPackages($order_number, $type);
		$valid_packages = array();

		// print_r($packages);

		// Parse out only the ones with content to be shipped out
		foreach($packages as $package) {

			$contents = getISOPackageContents($package['id']);

			// Unique invoices based on the invetoryid and there related total cogs diff
			// array(invoice_no => cogs diff)
			$invoice_cogs = array();

			if(! empty($contents)) {
				foreach($contents as $content) {
					$cogs_info = calcCogs($order_number, $content['id']);

					foreach($cogs_info as $sales_item_id => $cogs_diff) {

						$item_invoice = getInvoiceNumber($content['id'], $sales_item_id, $type);

						if($item_invoice) {
							if(! array_key_exists($item_invoice, $invoice_cogs)) {
								$invoice_cogs[$item_invoice] = 0;
							}

							$invoice_cogs[$item_invoice] += $cogs_diff;
						}

					}
				}
				$valid_packages[] = $package['id'];
			}
		}

		foreach($valid_packages as $package) {
			$query = "UPDATE packages SET datetime = ".fres($GLOBALS['now'])." WHERE id = ".res($package).";";
			qedb($query);
		}

		// Generate Invoice and Shipment Emails
		shipEmail($order_number, $type, $GLOBALS['now']);

		// Check here if the order being shipped has any ref label that points to an outsourced
		// If no records then pass and create the invoice, otherwise skip!

		$query = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = ".res($order_number)." AND (ref_1_label = 'outsourced_item_id' OR ref_2_label = 'outsourced_item_id');";
		$result = qedb($query);

		if(qnum($result) > 0) { return; }

		$INV = create_invoice($order_number, $GLOBALS['now']);

		// either something went wrong, or this is a non-invoiceable shipment (zero-priced)
		if (! $INV['invoice']) {
			// $cogs_amount = 0;
			// $invoice_no = 0;
			// we need to create journal entry
			// Shipping out an item to debit sales cogs and credit inventory asset
			$debit_account = ($T['je_debit']?:'Inventory Sale COGS');
			$credit_account = ($T['je_credit']?:'Inventory Asset');

			foreach($invoice_cogs as $invoice_no => $diff) {
				setJournalEntry(false,$GLOBALS['now'],$debit_account,$credit_account,'COGS for Invoice #'.$invoice_no, $diff,$invoice_no,'invoice');
			}
		}
	}

	function calcCogs($order_number, $inventoryid) {
		$cogs_info = array();

		$query2 = "SELECT si.* FROM inventory i, sales_items si ";
		$query2 .= "WHERE si.so_number = '".res($order_number)."' AND i.id = '".res($inventoryid)."' AND si.id = i.sales_item_id; ";
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

				/***** GET TERMS FOR THIS REPAIR ORDER TO DETERMINE ITS TYPE - RMA OR Billable *****/
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

					if(! array_key_exists($r5['id'], $cogs_info)) {
						$cogs_info[$r5['id']] = 0;
					}

					$cogs_info[$r5['id']] += $repair_cogs;

					$cogsid = setCogs($inventoryid, $r5['id'], 'sales_item_id', $cogs);
				} else if ($r4['order_type']=='Repair') {
					$query5 = "SELECT ri.id FROM repair_items ri WHERE ri.ro_number = '".res($r4['order_number'])."' AND ri.invid = '".res($inventoryid)."'; ";
					$result5 = qedb($query5);
					if (mysqli_num_rows($result5)==0) { continue; }
					$r5 = mysqli_fetch_assoc($result5);

					// sum the new repair cogs with the existing cogs because setCogs() below will SUM and UPDATE the existing COGS record
					$existing_cogs = getCOGS($inventoryid, $r5['id'], 'repair_item_id');
					$cogs = $existing_cogs+$repair_cogs;

					if(! array_key_exists($r5['id'], $cogs_info)) {
						$cogs_info[$r5['id']] = 0;
					}

					$cogs_info[$r5['id']] += $repair_cogs;

					$cogsid = setCogs($inventoryid, $r5['id'], 'repair_item_id', $cogs);
				}

				// DAVID: we should be adding a negative commission amount (assuming there was cost added, which would negatively impact original comm)
				// setCommission($invoice,$invoice_item_id=0,$inventoryid=0);


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

				if(! array_key_exists($sales_item_id, $cogs_info)) {
					$cogs_info[$sales_item_id] = 0;
				}

				$cogs_info[$sales_item_id] += $replacement_cogs;

				$cogsid = setCogs($inventoryid, $sales_item_id, 'sales_item_id', $cogs);

				// DAVID: we should be adding a negative commission amount against the originating data in the comms table
				// setCommission($invoice,$invoice_item_id=0,$inventoryid=0);



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

		return $cogs_info;
	}

	$serial = '';
	if (isset($_REQUEST['serial'])) { $serial = trim($_REQUEST['serial']); }
	$qty = 0;
	if (isset($_REQUEST['qty'])) { $qty = trim($_REQUEST['qty']); }
	$line_item = 0;
	if (isset($_REQUEST['line_item'])) { $line_item = trim($_REQUEST['line_item']); }
	$type = '';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }
	$locationid = 0;
	if (isset($_REQUEST['locationid'])) { $locationid = trim($_REQUEST['locationid']); }
	$bin = 0;
	if (isset($_REQUEST['bin'])) { $bin = trim($_REQUEST['bin']); }
	$conditionid = 0;
	if (isset($_REQUEST['conditionid'])) { $conditionid = trim($_REQUEST['conditionid']); }
	$order_number = 0;
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	$partid = '';
	if (isset($_REQUEST['partid'])) { $partid = trim($_REQUEST['partid']); }
	$packageid = '';
	if (isset($_REQUEST['packageid'])) { $packageid = trim($_REQUEST['packageid']); }

	$delete = '';
	if (isset($_REQUEST['delete'])) { $delete = trim($_REQUEST['delete']); }

	$iso = '';
	if (isset($_REQUEST['iso'])) { $iso = true; }

	$print = '';
	if (isset($_REQUEST['print'])) { $print = true; }

	$iso_comment = '';
	if (isset($_REQUEST['iso_comment'])) { $print = $_REQUEST['iso_comment']; }

	// print_r($_REQUEST);

	// Line Item stands for the actual item id of the record being purchase_item_id / repair_item_id etc
	if($iso) {
		isoSubmit($order_number, $type);
	} else if($delete) {
		returntoStock($delete, $packageid, $order_number, $type);
	} else {
		shipInventory($line_item, $order_number, $type, $locationid, $bin, $conditionid, $partid, $serial, $qty, $packageid);
	}
	//$link = '/shipping.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . ($locationid ? '&locationid=' . $locationid : '') . ($bin ? '&bin=' . $bin : '') . ($conditionid ? '&conditionid=' . $conditionid : '') . ($partid ? '&partid=' . $partid : '') . ($ALERT ? '&ALERT=' . $ALERT : '').($print?'&print=true':'');
	$link = '/shipping.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . ($locationid ? '&locationid=' . $locationid : '') . ($bin ? '&bin=' . $bin : '') . ($conditionid ? '&conditionid=' . $conditionid : '') . ($line_item ? '&line_item=' . $line_item : '') . ($ALERT ? '&ALERT=' . $ALERT : '').($print?'&print=true':'') . ($packageid ? '&packageid='.$packageid : '');

	if($COMPLETE) {
		//header('Location: /shipping.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . '&status=complete');
		$link = '/shipping.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . '&status=complete';
	}

	// Redirect also contains the current scanned parameters to be passed back that way the user doesn't need to reselect
	//header('Location: /shipping.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . ($locationid ? '&locationid=' . $locationid : '') . ($bin ? '&bin=' . $bin : '') . ($conditionid ? '&conditionid=' . $conditionid : '') . ($partid ? '&partid=' . $partid : '')) . ($packageid ? '&packageid='.$packageid : '');

	if ($DEBUG) { exit; }

	header('Location: '.$link);
	// exit;
	?>

	<!-- Rage towards Aaron for creating a buggy renderOrder that makes it so that header redirect does not work grrrrr... -->
	<script type="text/javascript">
		window.location.href = "<?=$link?>";
	</script>
