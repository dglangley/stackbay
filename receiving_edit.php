<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/renderOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/checkOrderQty.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/isBuild.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';

	$DEBUG = 0;
	$ERR = '';

	$COMPLETE = false;
	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function addInventory($line_item, $order_number, $type, $locationid, $bin, $conditionid, $partid, $serial, $qty, $packageid) {
		global $ERR, $DEBUG, $COMPLETE;

		$T = order_type($type);

		$status = 'received';
		if (ucfirst($type)=='Repair') {
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
				$inventoryid = setInventory($I);
			} else {
				// Add to inventory
				$I = array('serial_no'=>$serial,'qty'=>1,'partid'=>$partid,'conditionid'=>$conditionid,'status'=>$status,'locationid'=>$locationid,'bin'=>$bin,$T['inventory_label']=>$line_item);
				$inventoryid = setInventory($I);
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
			setCost($inventoryid);

			if($packageid) {
				// Add Iventory id into the currently selected package
				$query = "INSERT INTO package_contents (packageid, serialid) VALUES (".res($packageid).",".res($inventoryid).");";
				qedb($query);
			}

			if($type == 'Purchase') {

				// Opted to keep the qty_received 2/26
				// Go into the order if type is purchase and update the qty received
				$query = "UPDATE purchase_items SET qty_received = qty_received + ".res(($qty ? : 1))." WHERE id = ".res($line_item).";";
				qedb($query);

				// Check if the qty has been fulfilled and update accordingly for all lines on the order
				$complete = '';

				$query = "SELECT qty, qty_received FROM purchase_items WHERE po_number = ".res($order_number).";";
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
				if($complete) {
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
				} else {
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
	$link = '/receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . ($locationid ? '&locationid=' . $locationid : '') . ($bin ? '&bin=' . $bin : '') . ($conditionid ? '&conditionid=' . $conditionid : '') . ($partid ? '&partid=' . $partid : '');
	if($COMPLETE) {
		//header('Location: /receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . '&status=complete');
		$link = '/receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . '&status=complete';
		// exit;
	}

	// Redirect also contains the current scanned parameters to be passed back that way the user doesn't need to reselect
	//header('Location: /receiving.php?order_type='.ucwords($type).($order_number ? '&order_number=' . $order_number : '&taskid=' . $line_item) . ($locationid ? '&locationid=' . $locationid : '') . ($bin ? '&bin=' . $bin : '') . ($conditionid ? '&conditionid=' . $conditionid : '') . ($partid ? '&partid=' . $partid : ''));

	// exit;

	if ($DEBUG) { exit; }

	?>

	<!-- Rage towards Aaron for creating a buggy renderOrder that makes it so that header redirect does not work grrrrr... -->
	<script type="text/javascript">
		window.location.href = "<?=$link?>";
	</script>
