<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/order_type.php';
	include_once '../inc/send_gmail.php';
	include_once '../inc/getPart.php';
	include_once '../inc/getRep.php';
	include_once '../inc/jsonDie.php';
	header("Content-Type: application/json", true);

	$techid = $U['id'];
	$taskid = 0;
	$task_label = '';
	$partid = 0;
	$qty = 0;
	$notes = '';

	if (isset($_REQUEST['taskid']) AND is_numeric($_REQUEST['taskid'])) { $taskid = $_REQUEST['taskid']; }
	if (isset($_REQUEST['task_label'])) { $task_label = trim($_REQUEST['task_label']); }
	if (isset($_REQUEST['partid']) AND is_numeric($_REQUEST['partid'])) { $partid = $_REQUEST['partid']; }
	if (isset($_REQUEST['qty']) AND is_numeric($_REQUEST['qty'])) { $qty = $_REQUEST['qty']; }
	if (isset($_REQUEST['notes'])) { $notes = trim($_REQUEST['notes']); }

	// first check if an open request already exists, then update; otherwise create new request
	$sum_qty = 0;
	$query = "SELECT qty, notes, id FROM purchase_requests ";
	$query .= "WHERE item_id = '".res($taskid)."' AND item_id_label = '".res($task_label)."' AND partid = '".res($partid)."' ";
	$query .= "AND po_number IS NULL; ";
	$result = qedb($query);
	while ($r = qrow($result)) {

		// append existing notes to $notes
		if ($r['notes']) {
			if ($notes) { $notes .= chr(10).chr(10); }
			$notes .= $r['notes'];
		}
		$sum_qty += $r['qty'];

		// delete this request in deference to request created below
		$query2 = "DELETE FROM purchase_requests WHERE id = '".$r['id']."'; ";
		$result2 = qdb($query2) OR jsonDie('deletion failure');
	}
	if ($sum_qty>$qty) { $qty = $sum_qty; }

	$T = order_type($task_label);

	$query = "INSERT INTO purchase_requests (techid, item_id, item_id_label, requested, partid, qty, notes) ";
	$query .= "VALUES ('".res($techid)."','".res($taskid)."','".res($T['item_label'])."','".$now."',";
	$query .= "'".res($partid)."','".res($qty)."',".fres($notes)."); ";
	$result = qdb($query) OR jsonDie('purchase request failure');

	// get corresponding order#
	$query = "SELECT line_number, ".$T['order']." order_number FROM ".$T['items']." WHERE id = '".res($taskid)."'; ";
	$result = qedb($query);
	$r = qrow($result);
	$orderLn = $r['order_number'];
	if ($r['line_number']) { $orderLn .= '-'.$r['line_number']; }

	$message = 'requested for '.$T['abbrev'].' '.$orderLn;
	$link = '/purchase_requests.php';

	$messageid = 0;
	$query = "INSERT INTO messages (datetime, message, userid, link, ref_1, ref_1_label, ref_2, ref_2_label) ";
	$query .= "VALUES ('".$GLOBALS['now']."', '".res($message)."', '".$U['id']."', '".res($link)."', '".res($partid)."', 'partid', '".res($orderLn)."', '".$T['order']."');";
	$result = qdb($query) OR jsonDie('message failure');
	$messageid = qid();

	//13 = Sam Sabedra, 15 = Joe
	if ($messageid) {
		$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '13');";
		$result = qdb($query) OR jsonDie('notification failure');

		$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '15');";
		$result = qdb($query) OR jsonDie('notification failure');
	}

	//Generate the list of parts being requested here for email
	$part_list = 'Qty '.$qty.'- '.getPart($partid).'<br/>';

	// Generate the email after all the notifications have been generated and the quoted materials imported
	$email_body_html = getRep($U['id']).' is requesting procurement for '.
		'<a target="_blank" href="https://www.stackbay.com/service.php?order_type='.$T['type'].'&taskid='.$taskid.'&tab=materials">'.$T['abbrev'].' '.$orderLn.'</a>:<br/><br/>'.
		$part_list.'<br/>';
	if ($notes) { $email_body_html .= str_replace(chr(10),'<br/>',$notes).'<br/><br/>'; }

	$email_body_html .= '<a target="_blank" href="https://www.stackbay.com/purchase_requests.php">View Purchase Requests</a>';

	$email_subject = $T['abbrev'].' '.$orderLn.' Purchase Request';
	$recipients = array(
		0 => array('ssabedra@ven-tel.com','Sam Sabedra'),
		1 => array('joe@ven-tel.com','Joe Velasquez'),
	);
//	$recipients = 'david@ven-tel.com';

	if (! $DEV_ENV) {
		setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

		$send_success = send_gmail($email_body_html,$email_subject,$recipients);
		if ($send_success) {
			// echo json_encode(array('message'=>'Success'));
		} else {
			// $this->setError(json_encode(array('message'=>$SEND_ERR)));
		}
	}

	echo json_encode(array('message'=>'Success'));
	exit;
?>
