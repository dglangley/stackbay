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
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/packages.php';
	include_once $rootdir.'/inc/setCost.php';

	include_once $rootdir.'/inc/renderOrder.php';
	include_once($rootdir."/inc/send_gmail.php");
	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	//This is a list of everything
	$partid = grab('partid');
	$conditionid = grab('conditionid');
	$serial = strtoupper(grab('serial'));
	$savedSerial = strtoupper(grab('savedSerial'));
	$po_number = grab('po_number');
	$item_id = grab('item_id');
	$place = grab('place');
	$instance = grab('instance');
	$packageid = grab('package');

	//items = ['partid', 'serial', 'qty', 'location', 'status', 'conditionid'];
	function savetoDatabase($partid, $conditionid, $serial, $po_number, $item_id, $savedSerial, $place, $instance,$packageid){
		$result = array();
		$locationid;
		$query;
		$result['partid'] = $partid;
		$result['complete'] = true;
		$inventoryid = 0;
		
		//Get the location ID based on the preset ones in the table
		if($instance != '') {
			$query = "SELECT id FROM locations WHERE place = '". res($place) ."' AND instance = '". res($instance) ."';";
		} else {
			$query = "SELECT id FROM locations WHERE place = '". res($place) ."' AND instance is NULL;";
		}
		$locationResult = qdb($query);
		if (mysqli_num_rows($locationResult)>0) {
			$locationResult = mysqli_fetch_assoc($locationResult);
			$locationid = $locationResult['id'];
		}
		
		$result['query'] = true;
		$query = "SELECT * FROM inventory WHERE partid = '" . res($partid) . "' AND serial_no = '" . res($serial) . "';";
		$check = qdb($query);
		if($check->num_rows > 0) {
			$result['query'] = false;
		} else {//== 0
			if($savedSerial == '') {// no record for this item previously, so creating a new record

				//DEPRECATED To find this later on
				$query = "UPDATE purchase_items SET qty_received = qty_received + 1 WHERE id = '".res($item_id)."'; ";//po_number = ". res($po_number) ." AND partid = ". res($partid) .";";
				qdb($query);
				
				//Insert the item into the inventory
		 		//$query  = "INSERT INTO inventory (serial_no, qty, partid, conditionid, status, locationid, purchase_item_id, sales_item_id, returns_item_id, userid, date_created, id) VALUES ('". res($serial) ."', '1','". res($partid) ."', '". res($conditionid) ."', 'received', '". res($locationid) ."', '". res($po_number) ."', NULL, NULL, ".$GLOBALS['U']['id'].", '".$GLOBALS['now']."' , NULL);";
		 		$query  = "INSERT INTO inventory (serial_no, qty, partid, conditionid, status, locationid, purchase_item_id, sales_item_id, returns_item_id, userid, date_created, id) VALUES ('". res($serial) ."', '1','". res($partid) ."', '". res($conditionid) ."', 'received', '". res($locationid) ."', '". res($item_id) ."', NULL, NULL, ".$GLOBALS['U']['id'].", '".$GLOBALS['now']."' , NULL);";
				
				//$result['test'] = $query;
				$result['query'] = qdb($query) or die(qe());
				//$result['query'] = $query;
				$inventoryid = qid();
				//Pair the package to the line item of the inventory number we changed.
				$package_query = "INSERT INTO package_contents (packageid, serialid) VALUES ('$packageid','$inventoryid');";
				$result['package'] = qdb($package_query) OR die(qe());
				$result['package_q'] = $package_query;
			} else {
				$query = "UPDATE inventory SET serial_no = '". res($serial) ."', conditionid = '". res($conditionid) . "', locationid = '". res($locationid) ."', purchase_item_id = '".res($item_id)."' ";
				$query .= "WHERE serial_no = '". res($savedSerial) ."' AND partid = '". res($partid) ."';";
				$result['query'] = qdb($query) or die(qe());
				$result['saved'] = $serial;
				$inventoryid = qid();
				
			}

			//Quick Query to check if all the line items of a PO have been met in full
			$query = "SELECT po_number FROM purchase_items WHERE po_number = ".prep($po_number)." AND qty_received < qty;";
			$receivedResult = qdb($query);

			if (mysqli_num_rows($receivedResult)) {
				$receivedResult = mysqli_fetch_assoc($receivedResult);
				$result['complete'] = false;
			}

			//Fine tune this function so it will only send one email on the initial completion of the order
			if($result['complete']) {
				//Check if it was the current entry that completed the order
				$query = "SELECT qty_received FROM purchase_items WHERE id = '".res($item_id)."' AND qty_received = qty; ";
				$receivedResult = qdb($query);

				$sales_rep = array();

				if (mysqli_num_rows($receivedResult)) {
					//Grab all users under sales
					$query = "SELECT email FROM user_roles, emails, users WHERE privilegeid = '5' AND emails.id = users.login_emailid AND user_roles.userid = users.id;";
					$salesResult = qdb($query) OR die(qe() . ' ' . $query);

					if (mysqli_num_rows($salesResult)) {
						while ($row = $salesResult->fetch_assoc()) {
							$sales_rep[] = $row['email'];
						}
					}

					$email_body_html = renderOrder($po_number, 'Purchase', true);
					$email_subject = 'PO# ' . $po_number . ' Received';
					//$recipients = 'andrew@ven-tel.com,';
					$bcc = '';

					foreach($sales_rep as $rep) {
						$recipients .= $rep . ', ';
					}

					$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);

					if ($send_success) {
					    // echo json_encode(array('message'=>'Success'));
					} else {
					    $this->setError(json_encode(array('message'=>$SEND_ERR)));
					}
				}
			}
		}
		setCost($inventoryid);
		
		return $result;
	}
	
	$result = savetoDatabase($partid, $conditionid, $serial, $po_number, $item_id, $savedSerial, $place, $instance,$packageid);
	echo json_encode($result);
    exit;
