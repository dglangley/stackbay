<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/split_inventory.php';

	$DEBUG = 0;
	$ALERT = '';

	function getPR_PO($taskid, $partid) {
		global $T;

		$purchase_item_ids = array();

		$query = "SELECT *, i.id as purchase_item_id FROM purchase_requests pr, purchase_items i ";
		$query .= "WHERE item_id = ".res($taskid)." AND item_id_label = ".fres($T['item_label'])." ";
		$query .= " AND pr.po_number = i.po_number AND i.partid = ".res($partid).";";
		$result = qedb($query);

		while($r = qrow($result)) {
			// print '<pre>' . print_r($r, true) . '</pre>';

			$purchase_item_ids[] = $r['purchase_item_id'];
		}

		return $purchase_item_ids;
	}

	// This function checks the purchase for any attachments to a PR and if the PR is open or closed
	function checkPO_PR($purchase_item_id) {
		$status = 'closed';

		$query = "SELECT * FROM purchase_requests pr, purchase_items i WHERE i.id = ".res($purchase_item_id)." AND pr.po_number = i.po_number AND pr.partid = i.partid;";
		$result = qedb($query);

		return $status;
	}

	function cancelRequest($purchase_request_ids) {
		global $ALERT, $T;

		if($T['type'] == 'service_quote') {
			$partid = '';
			$quoteid = '';

			// If it is a quote then delete the request and delete the bom
			$query = "SELECT * FROM service_quote_materials WHERE id = ".res($purchase_request_id).";";
			$result = qedb($query);

			if(qnum($result)) {
				$r = qrow($result);

				$quoteid = $r['quote_item_id'];
				$partid = $r['partid'];
			}
			
			$query = "DELETE FROM service_quote_materials WHERE id = ".res($purchase_request_id).";";
			qedb($query);

			$query = "DELETE FROM purchase_requests WHERE item_id_label = 'quote_item_id' AND item_id = ".res($quoteid)." AND partid = ".fres($partid).";";
			qedb($query);

			return 0;
		}

		$parse_pr = explode(',', $purchase_request_ids);

		// If there is my splitting being done then it is a singular pr_id
		// so create a single array record for the for loop
		if (sizeof($parse_pr) < 1) {
			$parse_pr[] = $purchase_request_ids;
		}

		// Go through all the PR's that the user is trying to complete that is the same partid
		// from the materials table and complete it out
		foreach($parse_pr as $pr_id) {
			// First check if the purchase request has a po attached to it yet
			$query = "SELECT * FROM purchase_requests WHERE id = ".res($pr_id)." AND po_number IS NULL;";
			$result = qedb($query);

			if(mysqli_num_rows($result) > 0) {
				$query2 = "DELETE FROM purchase_requests WHERE id = ".res($pr_id).";";
				qedb($query2);
			} else {
				$ALERT = "Attempting to cancel a request that has been purchased! Permission denied.";

				return 0;
			}
		}
	}

	function completeRequest($taskid, $purchase_request_ids) {
		global $ALERT, $T;

		$parse_pr = explode(',', $purchase_request_ids);

		// If there is my splitting being done then it is a singular pr_id
		// so create a single array record for the for loop
		if (sizeof($parse_pr) < 1) {
			$parse_pr[] = $purchase_request_ids;
		}
		
		// Go through all the PR's that the user is trying to complete that is the same partid
		// from the materials table and complete it out
		foreach($parse_pr as $pr_id) {
			$query = "UPDATE purchase_requests SET status = 'Closed' WHERE id = ".res($pr_id).";";
			qedb($query);
		}
	}

	function sourceMaterials($taskid, $partid, $qty, $locationid = '', $conditionid = '', $serial = '') {
		global $T, $ALERT;
		// Within each part is the partid and the value of how the user plans to pull and all the specifics aka conditionid locationid etc
		// User selects the exact option as grouped on the task view
		// locationid / conditionid / if serial or not

		// Array that holds all the inventory ids that will be pulled into the order
		$pulledInv = array();

		if($serial) {
			// force cap serials
			$serial = strtoupper(trim($serial));
		}

		// print_r($serial . ' ' . $qty);

		if($qty) {
			$status = 'received';

			// Set the qty to requested
			$requested = $qty;

			// Get all records that have a status received and blank inventory_label aka no salies_item_id attached to it yet
			if($serial) {
				$inv = getInventory($serial,$partid, $status);
			} else {
				// breaking on qty for a serialized item
				$query = "SELECT * FROM inventory WHERE partid = ".res($partid)." AND status = ".fres($status).";";
				$result = qedb($query);

				// echo $query;

				while($r = qrow($result)) {
					$inv[] = $r;
				}
				// $inv = getInventory('',$partid, $status);
			}

			// print_r($inv);
			// die();

			if(empty($inv)) {
				$ALERT = urlencode('ERROR: '.($serial?'Serial':'Non-serial').' part is not in stock or has no record.'); 
				return 0;
			}

			if(! is_array($inv[0])) {
				$inv = array($inv); // turn record in multidimensional
			}

			$purchase_item_ids = getPR_PO($taskid, $partid);

			// Go through each of the iterations and mark as used until the qty is fulfilled
			// Add location condition serial filter if they exist 

			// Add extra check here to check and make sure to pull what is ordered on the order before grabbing whatever
			foreach($purchase_item_ids as $purchase_item_id) {
				$n = array_search($purchase_item_id, array_column($inv, 'purchase_item_id'));

				if($n) {
					$new_value = $inv[$n];
					unset($inv[$n]);
					array_unshift($inv, $new_value);
				}
			}

			foreach($inv as $record) {

				// If locationid and the lcoationid is not what is wanted then continue
				if($locationid AND $record['locationid'] != $locationid) {
					continue;
				}

				if($conditionid AND $record['conditionid'] != $conditionid) {
					continue;
				}

				if($serial AND $record['serial_no'] != $serial) {
					continue;
				}

				// continually deduct the qty of each record until the qty is met or a record has a larger qty then that of reamining
				$requested -= $record['qty'];

				$pulledInv[] = $record['id'];

				if($requested == 0) {
					break;
				} else if($requested < 0) {
					// Put qty back to how much is remaining to fulfill the qty to be sent
					$requested += $record['qty'];

					// Set the split amount to the remaining amount left on the order
					$split = $requested;
					break;
				}
			}

			$numItems = count($pulledInv);
			$i = 0;

			foreach($pulledInv as $inventoryid) {
				
				// If it is the last record and split exists then split out the inventory
				if(++$i === $numItems AND $split) {
					// Before splitting we need to check if this split pull is a full inventory cost (AKA items was purchase specifically for this order)
					// OR is not and needs to have the cost calculated and the remaining stock recalculated
					$flag = false;

					// Look into purchase_items table and find if any ref labels match this task and type
					// Then check the inventoryid and see if any of the purchase_item_id matches in the stamped inventory record
					$query = "SELECT * FROM purchase_items p, inventory i ";
					$query .= "WHERE ((p.ref_1_label = ".fres($T['item_label'])." AND p.ref_1 = ".res($taskid).") OR (p.ref_2_label = ".fres($T['item_label'])." AND p.ref_2 = ".res($taskid).")) ";
					$query .= "AND i.purchase_item_id = p.id AND i.id = ".res($inventoryid).";";
					$result = qedb($query);

					if(qnum($result) == 0) {
						// If there are NO results then there is no inventory record that matches a purchase item that is bought for this taskid
						// set the flag as true to have the cost recalc
						$flag = true;
					}

					// else it has been requested and purchased for this task so flag it as false

					// Split out a new record of what should still be in stock for this current inventoryid
					split_inventory($inventoryid, $split, $flag);

					// print_r($inventoryid . ' ' . $split . ' ' . $flag);
					// die();
					// echo 'Splitting '.$split.'<BR>';
				}

				$I = array('status'=>'installed','id'=>$inventoryid);

				// if($T['type'] == 'Repair') {
				// 	$I = array('status'=>'installed','id'=>$inventoryid);
				// } 

				$inventoryid = setInventory($I);
				// echo 'Setting ' . $inventoryid . '<BR><BR>';
				// Currently there is no way to tell how much qty there is in the inventory so query the qty of the inventoryid
				$query = "SELECT qty FROM inventory WHERE id = ".res($inventoryid).";";
				$result = qedb($query);

				$inv_qty = 0;

				if(mysqli_num_rows($result) > 0) {
					$r = mysqli_fetch_assoc($result);

					$inv_qty = $r['qty'];
				}

				if(! $inv_qty) {
					$ALERT = 'Inventory issue.';
					return 0;
				}

				// After setting the inventory either put it into the repair_components or service_materials table
				if($T['type'] == 'Repair') {
					$query = "INSERT INTO repair_components (invid, item_id, item_id_label, datetime, qty) ";
					$query .= "VALUES (".res($inventoryid).", ".res($taskid).", 'repair_item_id', ".fres($GLOBALS['now']).", ".res($inv_qty).");";

					qedb($query);
				} else {
					$query = "INSERT INTO service_materials (inventoryid, service_item_id, datetime, qty) ";
					$query .= "VALUES (".res($inventoryid).", ".res($taskid).", ".fres($GLOBALS['now']).", ".res($inv_qty).");";
					
					qedb($query);
				}
			}
		}
	}
	
	function installMaterials($partids, $taskid) {
		global $T, $ALERT;

		foreach($partids as $partid => $info) {
			$qty = 0;
			$locationid = '';
			$conditionid = '';
			$serial = '';

			if(is_array($info)) {
				// if it is an array then find the locationid / conditionid / and if possible the serial if user is pulling a serial
				foreach($info as $locationid => $conditions) {
					foreach($conditions as $conditionid => $value) {
						// If there is another array then this is considered the serial
						if(is_array($value)) {
							foreach($value as $serial => $amount) {
								// serial should always be 1, force qty to 1 to prevent any bugs
								$qty = 1;

								sourceMaterials($taskid, $partid, $qty, $locationid, $conditionid, $serial);
							}
						} else {
							$qty = $value;
							// At this point we have the 3 special options declared, use the finder to retrieve and fulfill this installation request
							sourceMaterials($taskid, $partid, $qty, $locationid, $conditionid);
						}
					}
				}
			} else {
				// Get the one option and start fulfilling the qty
				$qty = $info;

				sourceMaterials($taskid, $partid, $qty);
			}
		}
	}

	// Order details
	$taskid = '';
	if (isset($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }
	$type = '';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }
	$order_number = '';
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }

	// Form submit values
	$partids = array();
	if (isset($_REQUEST['partids'])) { $partids = $_REQUEST['partids']; }

	$cancel = '';
	if (isset($_REQUEST['cancel'])) { $cancel = $_REQUEST['cancel']; }

	$complete = '';
	if (isset($_REQUEST['complete'])) { $complete = $_REQUEST['complete']; }

	$T = order_type($type);

	// print_r($partids); die();

	if($cancel) {
		cancelRequest($cancel);
	} else if($complete) {
		completeRequest($taskid, $complete);
	} else {
		installMaterials($partids, $taskid);
	}

	// print_r($partids);

	// Responsive Testing
	$responsive = false;
	if (isset($_REQUEST['responsive'])) { $responsive = trim($_REQUEST['responsive']); }

	$link = '/service.php';

	if($T['type'] == 'service_quote') {
		$link = '/quoteNEW.php';

		header('Location: '.$link.'?taskid=' . $taskid . '&tab=materials' . ($ALERT?'&ALERT='.$ALERT:''));

		exit;
	}

	if($responsive) {
		$link = '/responsive_task.php';
	} 

	header('Location: '.$link.'?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=materials' . ($ALERT?'&ALERT='.$ALERT:''));

	exit;
