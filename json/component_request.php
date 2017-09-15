<?php

	//Prepare the page as a JSON type
	header('Content-Type: application/json');

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
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/send_gmail.php';

	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session
	
	$components = $_REQUEST['requested_items'];
	$order_number = $_REQUEST['order_number'];
	$techid = $U['id'];
	$requested = $now;
	$repair_item_id = $_REQUEST['repair_item_id'];
	$total_pr = $_REQUEST['total_pr'];

	$notes = $_REQUEST['notes'];
	
	function saveReq($techid, $order_number, $requested, $components, $repair_item_id, $notes, $total_pr) {
		global $SEND_ERR;
		global $_SERVER;
		global $now;
		global $DEV_ENV;

		//Probably over declaring variables here
		$query;
		$result;

		$link = '';
		$message = '';

		foreach($components as $item) {

			$query = "SELECT * FROM parts WHERE id = '".res($item['part'])."'; ";
			$result = qdb($query);

			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$part = $r['part'];
			}

			$message = 'requested for Repair# ' . $order_number;

			//$link = '/order_form.php?ps=Purchase&s='.$item['part'].'&repair='.$repair_item_id;
			$link = '/purchase_requests.php';

			$query = "INSERT INTO purchase_requests (techid, ro_number, requested, partid, qty, notes) VALUES (".prep($techid).", ".prep($order_number).", ".prep($requested).", ".prep($item['part']).", ".prep($total_pr).", ".prep($notes).");";
			qdb($query) or die(qe() . ' ' . $query);

			//13 = Sam Sabedra
			$query = "INSERT INTO messages (datetime, message, userid, link, ref_1, ref_1_label, ref_2, ref_2_label) ";
			$query .= "VALUES ('".$now."', ".prep($message).", ".prep($techid).", ".prep($link).", ".prep($item['part']).", 'partid', ".prep($order_number).", 'ro_number');";

			qdb($query) or die(qe() . ' ' . $query);
			$messageid = qid();

			$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '13');";
			$result = qdb($query) or die(qe() . ' ' . $query);

			if($result && !$DEV_ENV) {
				$email_body_html = getRep($techid)." has requested <a target='_blank' href='".$_SERVER['HTTP_HOST']."/purchase_requests.php'>Part# ".getPart($item['part'])."</a> Qty ".$total_pr." on <a target='_blank' href='".$_SERVER['HTTP_HOST']."/order_form.php?ps=ro&on=".$order_number."'>Repair# ".$order_number."</a>";
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

			break;
		}

		return $result;
	}

	$result = saveReq($techid, $order_number, $requested, $components, $repair_item_id, $notes, $total_pr);
		
	echo json_encode($result);
    exit;
