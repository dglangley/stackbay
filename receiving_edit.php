<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/renderOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/checkOrderQty.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/isBuild.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/getOrderNumber.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';
	
	$DEBUG = 0;
	$ERR = '';
	$ALERT = '';

	$COMPLETE = false;
	setGoogleAccessToken(5); //5 is amea’s userid, this initializes her gmail session

	function addInventory($line_item, $order_number, $type, $locationid, $bin, $conditionid, $partid, $serial, $qty, $packageid) {
		global $ERR, $DEBUG, $COMPLETE, $ALERT;

		$T = order_type($type);

		$status = 'received';
		if (ucfirst($type)=='Repair' OR ucfirst($type)=='Outsourced') {
			$BUILD = isBuild($line_item);
			if ($BUILD) {
				$status = 'received';
			} else {
				$status = 'in repair';
			}
		}

		if (ucfirst($type)=='Purchase') {
			checkOrderQty($line_item);
		}

		if($serial) {

			// force cap serials
			$serial = strtoupper(trim($serial));

			// serial
			// Using getInventory allows us to see if the serial to part already exists in the database
			// Difference being an inventory update or inventory addition
			$inv = getInventory($serial,$partid);
			$inventoryid = $inv['id'];

			// Get the inventory label # to check if this item has been received already to the order
			$item_id = $inv[$T['inventory_label']];

			// Quick and dirty fail safe to not allow user to receive the same Serial
			if($item_id == $line_item) {
				echo 'ERROR: Serial# ' .$serial. ' has already been received on the order.'; die();
			}

			if($inventoryid) {
				// Update only
				$I = array('serial_no'=>$serial,'conditionid'=>$conditionid,'locationid'=>$locationid,$T['inventory_label']=>$line_item,'id'=>$inventoryid);
				
				if(ucfirst($type)=='Outsourced'){
					$I = array('serial_no'=>$serial,'conditionid'=>$conditionid,'locationid'=>$locationid,'status'=>$status,'id'=>$inventoryid);
				}

				$inventoryid = setInventory($I);
			} else {
				// Add to inventory
				$I = array('serial_no'=>$serial,'qty'=>1,'partid'=>$partid,'conditionid'=>$conditionid,'status'=>$status,'locationid'=>$locationid,'bin'=>$bin,$T['inventory_label']=>$line_item);
				
				if(ucfirst($type)=='Outsourced'){
					$ALERT = 'This item does not exist in our records. If purchasing, this needs to be recieved on a PO first.';
					return 0;
				}

				$inventoryid = setInventory($I);
			}

			// RMA
			if($T['type'] == 'Return' AND $inventoryid) {
				$debit_account = 'Inventory Asset';
				$credit_account = 'Inventory Sale COGS';

				$og_item_id = 0;
				$invoice_no = 0;
				$cogs_amount = 0;	

				// Original cog
				// reference a repair or sales item (RMA)
				// Inventoryid
				// Get that number and put it back as journal entry

				// line_item is also known as the item_id of the items table
				// First get the inventoryid from the return_items table
				$query = "SELECT * FROM ".$T['orders']." o, ".$T['items']." i WHERE i.id = ".res($line_item)." AND o.".$T['order']." = i.".$T['order'].";";
				$result = qedb($query);

				if(qnum($result)) {
					$r = qrow($result);
					
					// result gives us the returns table with order_type and order_number that is needed to run the next query to find the original item_id
					$T2 = order_type($r['order_type']);
					$query2 = "SELECT t.id FROM ".$T2['items']." t, inventory_history h WHERE ".$T2['order']." = '".$r['order_number']."' AND h.value = t.id AND h.invid = '".$inventoryid."' AND h.field_changed = '".$T2['item_label']."';";
					$result2 = qedb($query2);

					if(qnum($result2)) {
						$r2 = qrow($result2);
						$og_item_id = $r2['id'];

						$og_order_number = getOrderNumber($og_item_id, $T2['orders'], $T2['order']);

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
						$query3 = "SELECT cogs_avg FROM sales_cogs WHERE inventoryid = ".res($inventoryid)." AND taskid = ".res($og_item_id)." AND task_label = ".fres($T['item_label']).";";
						$result3 = qedb($query3);

						if(qnum($result3)){
							$r3 = qrow($result3);

							$cogs_amount = $r3['cogs_avg'];
						}

						setJournalEntry(false,$GLOBALS['now'],$debit_account,$credit_account,' #'.$order_number, $cogs_amount,$invoice_no,'invoice');
					}
				}
			}

		} else if($qty) {
			// get all inventory items with this partid
			$inv = getInventory('',$partid);
			$qty_received = 0;

			// If the purchase_item_id is set to the correct line_item then add qty
			if(count($inv)>0 AND $inv['id'] == $line_item) {
				$qty += $inv['qty'];
			}
			
			// qty
			// Add to inventory
				$I = array('qty'=>$qty,'partid'=>$partid,'conditionid'=>$conditionid,'status'=>$status,'locationid'=>$locationid,'bin'=>$bin,$T['inventory_label']=>$line_item);
				$inventoryid = setInventory($I);
		}

		if($inventoryid) {
			// Part was either updated or added
			// Set the cost of the inventory using David's created function
			if($type != 'Outsourced') {
				// Don't run invenotry cost on outsourced orders
				setCost($inventoryid);
			}

			if($packageid) {
				// Add Iventory id into the currently selected package
				$query = "INSERT INTO package_contents (packageid, serialid) VALUES (".res($packageid).",".res($inventoryid).");";
				qedb($query);
			}

			if($type == 'Purchase' OR $type == 'Outsourced') {

				// Opted to keep the qty_received 2/26
				// Go into the order if type is purchase and update the qty received
				$query = "UPDATE ".$T['items']." SET qty_received = qty_received + ".res(($qty ? : 1))." WHERE id = ".res($line_item).";";
				qedb($query);

				// Check if the qty has been fulfilled and update accordingly for all lines on the order
				$complete = '';

				$query = "SELECT qty, qty_received FROM ".$T['items']." WHERE ".$T['order']." = ".res($order_number).";";
				$result = qedb($query);

				while($r = mysqli_fetch_assoc($result)) {
					// If the qty order is greater than the qty received then this line is not complete
					// Once 1 is incomplete then break out of the loop as no matter what it is not complete
					if($r['qty'] > $r['qty_received']) {
						$complete = false;
						break;
					} else {
						$complete = true;
					}
				}

				// Only run this for purchase items and not repair items
				// This generates the email and completes the purchase order
				if($complete AND $type == 'Purchase') {
					// Check if the order is already in the status Complete so we don't duplicate emails for extra items being received unto the order
					$query = "SELECT * FROM purchase_orders WHERE po_number = ".res($order_number)." AND status = 'Active';";
					$result = qedb($query);

					if(mysqli_num_rows($result) > 0) {
						// PO is about to transition over to complete for the first time
						// Generate the email of the po being completed

						//Grab all users under sales and accounting, except users.id = 5 (Amea)
						$query2 = "SELECT email, users.id as userid FROM user_roles, emails, users, contacts c ";
						$query2 .= "WHERE (privilegeid = '5' OR privilegeid = '7') AND emails.id = users.login_emailid AND user_roles.userid = users.id ";
						$query2 .= "AND users.id <> 5 AND users.id <> 12 ";//no Amea and no dglangley
						$query2 .= "AND users.contactid = c.id AND c.status = 'Active' ";
						$query2 .= "GROUP BY users.id; ";
						$result2 = qedb($query2);

						while($r2 = mysqli_fetch_assoc($result2)) {
								$sales_reps[] = $r2['email'];
								$userids[] = $r2['userid'];
						}

						$email_name = "po_received";
						$recipients = getSubEmail($email_name);

						// $sales_reps = array('andrew@ven-tel.com');

						$message = 'PO# ' . $order_number . ' Received';
						$link = '/PO' . $order_number;

						// $query2 = "INSERT INTO messages (message, datetime, userid, link) ";
						// $query2 .= "VALUES ('".res($message)."','".$GLOBALS['now']."',".$GLOBALS['U']['id'].", '".res($link)."'); ";
						// qedb($query2);

						// $messageid = qid();

						// if($messageid) {
						// 	foreach ($userids as $userid) {
						// 		$query3 = "INSERT INTO notifications (messageid, userid, read_datetime, click_datetime) ";
						// 		$query3 .= "VALUES ('".$messageid."','".$userid."',NULL,NULL); ";
						// 		$result3 = qedb($query3);
						// 	}	
						// }

						$email_body_html = renderOrder($order_number, 'Purchase', true);

						$bcc = '';
						if (! $GLOBALS['DEV_ENV']) {
							$send_success = send_gmail($email_body_html,$message,$recipients,$bcc);

							if (! $send_success) {
							    $ERR = $SEND_ERR;
							}
						}
					} else if($complete AND $type == 'Repair') {
						// Generate the repair statuses needed

					}

					// Order is complete on all lines so update the status on the order level
					$query = "UPDATE purchase_orders SET status = 'Complete' WHERE po_number = ".res($order_number).";";
					qedb($query);

					$COMPLETE = true;
				} else if($type == 'Purchase') {
					// Order is not complete so make the order active
					// This somwhat addresses possible issue with the user editing the order and adding parts to the order (Only works when an item is scanned)
					$query = "UPDATE purchase_orders SET status = 'Active' WHERE po_number = ".res($order_number).";";
					qedb($query);
				}
			} else if($type == "Repair") {
				// Repair has different tables
			}
		} else {
			$ERR = 'Failed to add the part to the inventory. Please see an admin for assistance.';
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
	addInventory($line_item, $order_number, $type, $locationid, $bin, $conditionid, $partid, $serial, $qty, $packageid);
	$link = '/receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . ($locationid ? '&locationid=' . $locationid : '') . ($bin ? '&bin=' . $bin : '').($conditionid ? '&conditionid=' . $conditionid : '').($partid ? '&partid=' . $partid : '').($packageid ? '&packageid=' . $packageid : '').($ALERT?'&ALERT='.$ALERT:'');

	if($COMPLETE) {
		//header('Location: /receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . '&status=complete');
		$link = '/receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . '&status=complete'. ($ALERT?'&ALERT='.$ALERT:'');
		// exit;
	}

	// Redirect also contains the current scanned parameters to be passed back that way the user doesn't need to reselect
	//header('Location: /receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . ($locationid ? '&locationid=' . $locationid : '') . ($bin ? '&bin=' . $bin : '') . ($conditionid ? '&conditionid=' . $conditionid : '') . ($partid ? '&partid=' . $partid : ''));

	// exit;

	if ($DEBUG) { exit; }

	?>

	<script type="text/javascript">
		window.location.href = "<?=$link?>";
	</script>
