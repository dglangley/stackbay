<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/split_inventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/renderOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/checkOrderQty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';

	include_once $rootdir.'/inc/setCost.php';

	$DEBUG = 0;
	$ERR = 0;

	$COMPLETE = false;
	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function shipInventory($line_item, $order_number, $type, $locationid, $bin, $conditionid, $partid, $serial, $qty, $packageid) {
		global $ERR, $DEBUG, $COMPLETE;

		$T = order_type($type);

		$status = 'received';
		if (ucfirst($type)=='Repair') { $status = 'in repair'; }

		if (ucfirst($type)=='Sale' AND $line_item) {
			checkOrderQty($line_item, ucfirst($type));
		}

		if($serial) {

			// force cap serials
			$serial = strtoupper(trim($serial));

			// Find the partid if no partid is found for the serial

			// Using getInventory allows us to see if the serial to part already exists in the database
			// Difference being an inventory update or inventory addition
			$inv = getInventory($serial,$partid, $status);

			if(empty($inv)) {
				echo 'ERROR: Serial# ' .$serial. ' is not in stock or has no record.'; die();
			}

			// line_item does not exist then attempt to find it by matching the partid for each of the serial record found
			if(! $line_item) {
				$ORDER = getOrder($order_number, $type);
				$LINES = $ORDER['items'];

				foreach($LINES as $line) {
					foreach($inv as $item) {
						// If the partid matches with the serial record then set the variables accordingly and break
						if($item['partid'] == $line['partid']) {
							$line_item = $line['id'];
							$partid = $line['partid'];
							$inventoryid = $item['id'];

							break;
						}
					}
				}
			} else {
				$inventoryid = $inv['id'];
			}

			// Get the inventory label # to check if this item has been received already to the order
			$item_id = $inv[$T['inventory_label']];

			// Quick and dirty fail safe to not allow user to receive the same Serial
			if($item_id == $line_item) {
				echo 'ERROR: Serial# ' .$serial. ' has already been placed on the order.'; die();
			}

			if($inventoryid) {
				// Update only, mainly put the item_id into the inventory
				$I = array('serial_no'=>$serial,'status'=>'shipped',$T['inventory_label']=>$line_item,'id'=>$inventoryid);
				$inventoryid = setInventory($I);
			} 

		} else if($qty) {
			$pulledInv = array();
			$spilt = 0;

			$tempQty = $qty;
			// Get all records that have a status received and blank inventory_label aka no salies_item_id attached to it yet
			$inv = getInventory('',$partid, $status);

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

		if($inventoryid) {
			// Part was either updated or added
			// Set the cost of the inventory using David's created function
			setCost($inventoryid);

			if($packageid) {
				// Add Iventory id into the currently selected package
				$query = "INSERT INTO package_contents (packageid, serialid) VALUES (".res($packageid).",".res($inventoryid).");";
				qedb($query);
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

				// Only run this for purchase items and not repair items
				// This generates the email and completes the purchase order
				if($complete) {
					
				} 
			} else if($type == "Repair") {
				// Repair has different tables
			}
		} else {
			$ERR = 2;
		}

		return 0;

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

	// print_r($_REQUEST);

	// Line Item stands for the actual item id of the record being purchase_item_id / repair_item_id etc
	shipInventory($line_item, $order_number, $type, $locationid, $bin, $conditionid, $partid, $serial, $qty, $packageid);

	$link = '/shipping.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . ($locationid ? '&locationid=' . $locationid : '') . ($bin ? '&bin=' . $bin : '') . ($conditionid ? '&conditionid=' . $conditionid : '') . ($partid ? '&partid=' . $partid : '') . ($ERR ? '&ERR=' . $ERR : '');

	if($COMPLETE) {
		//header('Location: /shipping.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . '&status=complete');
		$link = '/shipping.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . '&status=complete';
		// exit;
	}

	// Redirect also contains the current scanned parameters to be passed back that way the user doesn't need to reselect
	//header('Location: /shipping.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . ($locationid ? '&locationid=' . $locationid : '') . ($bin ? '&bin=' . $bin : '') . ($conditionid ? '&conditionid=' . $conditionid : '') . ($partid ? '&partid=' . $partid : ''));

	// exit;

	if ($DEBUG) { exit; }

	?>

	<!-- Rage towards Aaron for creating a buggy renderOrder that makes it so that header redirect does not work grrrrr... -->
	<script type="text/javascript">
		window.location.href = "<?=$link?>";
	</script>
