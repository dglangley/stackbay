<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/split_inventory.php';

	$DEBUG = 0;
	$ALERT = '';

	function cancelRequest($purchase_request_id) {
		global $ALERT;
		// First check if the purchase request has a po attached to it yet
		$query = "SELECT * FROM purchase_requests WHERE id = ".res($purchase_request_id)." AND po_number IS NULL;";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$query2 = "DELETE FROM purchase_requests WHERE id = ".res($purchase_request_id).";";
			qedb($query2);
		} else {
			$ALERT = "Attempting to cancel a request that has been purchased! Permission denied.";
		}
	}

	function sourceMaterials($taskid, $partid, $qty, $locationid = '', $conditionid = '', $serial = '') {
		global $T, $ALERT;
		// Within each part is the partid and the value of how the user plans to pull and all the specifics aka conditionid locationid etc
		// User selects the exact option as grouped on the task view
		// locationid / conditionid / if serial or not

		// echo $partid . "<BR>";
		// echo $qty . "<BR>";
		// echo $locationid . "<BR>";
		// echo $conditionid . "<BR>";
		// echo $serial . "<BR>";
		// echo "<BR><BR>";

		// Array that holds all the inventory ids that will be pulled into the order
		$pulledInv = array();

		if($qty) {
			$status = 'received';

			// Set the qty to requested
			$requested = $qty;
			// Get all records that have a status received and blank inventory_label aka no salies_item_id attached to it yet
			$inv = getInventory('',$partid, $status);

			if(empty($inv)) {
				$ALERT = urlencode('ERROR: Non-serial part is not in stock or has no record.'); 
				return 0;
			}

			if(! is_array($inv[0])) {
				$inv = array($inv); // turn record in multidimensional
			}

			// print_r($inv);

			// Go through each of the iterations and mark as used until the qty is furfilled
			// Add location condition serial filter if they exist 
			foreach($inv as $record) {
				// print '<pre>'.print_r($record, true).'</pre>';

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
					// Split out a new record of what should still be in stock for this current inventoryid
					split_inventory($inventoryid, $split);
					// echo 'Splitting '.$split.'<BR>';
				}

				// Set the inventoryid to the status shipped and add in the sales_item_id or whatever item_id that pertains to this current shipment
				$I = array('status'=>'installed','id'=>$inventoryid);

				if($T['type'] == 'Repair') {
					$I = array('status'=>'installed','repair_item_id'=>$taskid,'id'=>$inventoryid);
				} 

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
							}
						} else {
							$qty = $value;
						}

						// At this point we have the 3 special options declared, use the finder to retrieve and fulfill this installation request
						sourceMaterials($taskid, $partid, $qty, $locationid, $conditionid, $serial);
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

	$T = order_type($type);

	// print_r($partids); die();

	if($cancel) {
		cancelRequest($cancel);
	} else {
		installMaterials($partids, $taskid);
	}

	// print_r($partids);

	header('Location: /serviceNEW.php?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=materials' . ($ALERT?'&ALERT='.$ALERT:''));

	exit;
