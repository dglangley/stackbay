<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/split_inventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';

	function getRepairItemId($ro_number, $partid) {
		$repair_item_id;

		$query = "SELECT id as repair_item_id FROM repair_items WHERE ro_number = ".prep($ro_number)." LIMIT 1;";
		$result = qdb($query);

		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$repair_item_id = $result['repair_item_id'];
		}

		return $repair_item_id;
	}

	function saveReq($techid, $order_number, $requested, $notes) {
		global $SEND_ERR;
		global $_SERVER;
		global $now;
		global $DEV_ENV;

		$link = '';
		$message = '';

		$repair_item_id = 0;

		foreach($requested as $partid => $qty) {

			$query = "SELECT * FROM parts WHERE id = '".res($partid)."'; ";
			$result = qdb($query);

			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$part = $r['part'];
			}

			$repair_item_id = getRepairItemId($order_number, $partid);

			$message = 'requested for Repair# ' . $order_number;

			$link = '/order_form.php?ps=Purchase&s='.$partid.'&repair='.$repair_item_id;

			$query = "INSERT INTO purchase_requests (techid, ro_number, requested, partid, qty, notes) VALUES (".prep($techid).", ".prep($order_number).", ".prep($now).", ".prep($partid).", ".prep($qty).", ".prep($notes).");";
			qdb($query) or die(qe() . ' ' . $query);

			//13 = Sam Sabedra
			$query = "INSERT INTO messages (datetime, message, userid, link, ref_1, ref_1_label, ref_2, ref_2_label) ";
			$query .= "VALUES ('".$now."', ".prep($message).", ".prep($techid).", ".prep($link).", ".prep($partid).", 'partid', ".prep($order_number).", 'ro_number');";

			qdb($query) or die(qe() . ' ' . $query);
			$messageid = qid();

			$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '13');";
			$result = qdb($query) or die(qe() . ' ' . $query);

			if($result && !$DEV_ENV) {
				$email_body_html = getRep($techid)." has requested <a target='_blank' href='".$_SERVER['HTTP_HOST']."/order_form.php?ps=Purchase&s=".$partid."&repair=".$repair_item_id."'>Part# ".getPart($partid)."</a> Qty ".$qty." on <a target='_blank' href='".$_SERVER['HTTP_HOST']."/order_form.php?ps=ro&on=".$order_number."'>Repair# ".$order_number."</a>";
				$email_subject = 'Purchase Request on Repair# '.$order_number;
				//$recipients = 'andrew@ven-tel.com';
				$recipients = 'ssabedra@ven-tel.com';
				// $bcc = 'dev@ven-tel.com';
				
				$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
				if ($send_success) {
				    // echo json_encode(array('message'=>'Success'));
				} else {
				    $this->setError(json_encode(array('message'=>$SEND_ERR)));
				}
			}
		}
	}

	function pullComponent($pulled, $order_number) {
		foreach($pulled as $invid => $pulled) {
			if($pulled > 0) {
				// Set all used variables to 0 upon each iteration
				$average = 0;
				$stock = 0;
				$partid = 0;
				$new_invid = 0;
				$removal_cost = 0;
				$inv_qty = 0;
				$total = 0;
				$new_total = 0;
				$new_average = 0;

				// Get average cost values
				// Get only the lastest and greatest values
				$query = "SELECT a.amount, i.partid, i.qty FROM inventory i, average_costs a WHERE i.id = ".res($invid)." AND a.partid = i.partid ORDER BY a.datetime DESC LIMIT 1;";
				$result = qdb($query) OR die(qe() . ' ' .$query);

				if (mysqli_num_rows($result)) {
					$result = mysqli_fetch_assoc($result);
					$average = $result['amount'];
					$partid = $result['partid'];
					$inv_qty = $result['qty'];
				}	

				//echo $query;	

				// Get the total in stock for the particular partid
				$query = "SELECT SUM(qty) as instock FROM inventory i WHERE partid = ".res($partid)." AND (status = 'received' OR status = 'shelved');";
				$result = qdb($query) OR die(qe() . ' ' .$query);

				if (mysqli_num_rows($result)) {
					$result = mysqli_fetch_assoc($result);
					$stock = $result['instock'];
				}			

				//Average cost calculations
				$total = $average * $stock;
				$removal_cost = $inv_qty * $average;

				$new_total = $total - $removal_cost;
				$new_average = $new_total / ($stock - $pulled);

				// echo $average . '<br>';
				// echo $stock . '<br>';
				// echo $inv_qty . '<br>';
				// echo $total . '<br>';
				// echo $removal_cost . '<br>';

				$query = "INSERT INTO repair_components (invid, ro_number, datetime, qty) 
							VALUES ('".res($invid)."', '".res($order_number)."', '".res($GLOBALS['now'])."', '".res($pulled)."');";
				qdb($query) OR die(qe() . ' ' .$query);

				$query = "INSERT INTO average_costs (partid, amount, datetime) 
							VALUES ('".res($partid)."', '".res($new_average)."', '".res($GLOBALS['now'])."');";
				qdb($query) OR die(qe() . ' ' .$query);
				//echo $query;

				$new_invid = split_inventory($invid, $pulled);

				$I = array('id'=>$invid,'status'=>'installed');
				setInventory($I);
			}
		}
	}

	function quoteMaterials($techid, $requested, $task){
		$order_number = 0;

		return $order_number;
	}

	$techid = $U['id'];

	$order_number = 0;
	$requested = 0;
	$notes = '';
	$type = '';
	$action = '';
	$pulled = '';
	$task = '';

	if (isset($_REQUEST['order_number'])) { $order_number = $_REQUEST['order_number']; }
	if (isset($_REQUEST['requested'])) { $requested = $_REQUEST['requested']; }
	if (isset($_REQUEST['comment'])) { $notes = $_REQUEST['comment']; }
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }
	if (isset($_REQUEST['action'])) { $action = $_REQUEST['action']; }
	if (isset($_REQUEST['pulled'])) { $pulled = $_REQUEST['pulled']; }
	if (isset($_REQUEST['task'])) { $task = $_REQUEST['task']; }

	// Identifer type determines what type of component option is being ran (Component Request or Component Pull, etc.)
	if($action == 'request'){
		saveReq($techid, $order_number, $requested, $notes);
	} else if($action == 'pull'){
		pullComponent($pulled, $order_number);
	} else if($type == 'quote' OR $type == 'create'){
		$order_number = quoteMaterials($techid, $requested);
		$type = $task;
	}

		
	// Determine the location to redirect to...
	header('Location: /task_view.php?type='.$type.'&order='.$order_number);
    exit;