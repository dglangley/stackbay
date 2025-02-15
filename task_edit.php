<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/saveFiles.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/file_zipper.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRep.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/completeTask.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';

	$DEBUG = 0;
	$ALERT = '';
	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function editTask($so_number, $line_number, $qty, $amount, $item_id, $item_label, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $service_item_id){
		global $LINE_NUMBER;

		$id = 0;

		// Set line number automatically
		// Search for the largest line_number for current service order
		// Does not set another line_number if one is inserted in
		if(empty($line_number)) {
			$query = "SELECT line_number FROM service_items WHERE so_number = ".res($service_item_id)." ORDER BY line_number DESC LIMIT 1;";
			$result = qedb($query);

			// Get the largest line_number if exists and increment by 1
			if(mysqli_num_rows($result)){
				$r = mysqli_fetch_assoc($result);

				$line_number = $r['line_number'] + 1;
			}
		}

		$query = "REPLACE INTO service_items (so_number, line_number, qty, amount, item_id, item_label, description, ref_1, ref_1_label, ref_2, ref_2_label";
		if($service_item_id) { $query .= " ,id"; }
		$query .= ") VALUES (".fres($so_number).", ".fres($line_number).", ".fres($qty).", ".fres($amount).", ".fres('').", ".fres($item_label).", ";
		$query .= fres($description).", ".fres($ref_1).", ".fres($ref_1_label).", ".fres($ref_2).", ".fres($ref_2_label);
		if($service_item_id) { $query .= ", " . fres($service_item_id); }
		$query .= ");";

		qedb($query);
		$id = qid();

		$LINE_NUMBER = $line_number;

		return $id;
	}

	function quoteTask($quoteid, $line_number, $qty, $amount, $item_id, $item_label, $description, $labor_hours, $labor_rate, $expenses, $search, $search_type, $quote_item_id){
		global $LINE_NUMBER;

		// print_r($search);

		// Currently only 1 id associated per item
		$search = $search[0];
		$search_type = $search_type[0];

		$ref_1 = '';
		$ref_1_label = '';
		$ref_2 = '';
		$ref_2_label = '';

		$id = 0;

		$query = "SELECT * FROM service_quote_items WHERE quoteid = ".res($quoteid)."; ";// LIMIT 1;";
		$result = qedb($query);

		$max_ln = 0;
		// Get the largest line_number if exists and increment by 1
		while ($r = mysqli_fetch_assoc($result)) {
			if ($quote_item_id==$r['id']) {
				$ref_1 = $r['ref_1'];
				$ref_1_label = $r['ref_1_label'];
				$ref_2 = $r['ref_2'];
				$ref_2_label = $r['ref_2_label'];
			}

			if ($r['line_number']>$max_ln) { $max_ln = $r['line_number']; }
		}
		$ln = $max_ln+1;

		// Set line number automatically
		// Search for the largest line_number for current quoteid
		if(empty($line_number) && ! $quote_item_id) { $line_number = $ln; }

		$query = "REPLACE INTO service_quote_items (quoteid, line_number, qty, amount, item_id, item_label, description, ref_1, ref_1_label, ref_2, ref_2_label, labor_hours, labor_rate, expenses";
		if($quote_item_id) { $query .= " ,id"; }
		$query .= ") VALUES (".fres($quoteid).", ".fres($line_number).", ".fres(($qty?:1)).", ".fres($amount).", ".fres($search).", ".fres(($search_type == 'Site' ? 'addressid' : 'partid')).", ";
		$query .= fres($description).", ".fres($ref_1).", ".fres($ref_1_label).", ".fres($ref_2).", ".fres($ref_2_label).", ".fres($labor_hours).", ".fres($labor_rate).", ".fres($expenses);
		if($quote_item_id) { $query .= ", " . fres($quote_item_id); }
		$query .= ");";

		// echo $query;

		qedb($query);
		$id = qid();

		$LINE_NUMBER = $line_number;

		return $id;
	}

	function editMaterials($materials, $quoteid, $table, $field){
		// Get the ID of all the currently / existing quoted items
		$quotedItems = array();

		if($field == 'create') {
			$item_id_label = 'service_item_id';
		} else if ($field == 'bom') {
			$item_id_label = 'item_id_label';
		} else {
			$item_id_label = 'quote_item_id';
		}

		if(! empty($materials)){
			foreach($materials as $partid => $data) {
				foreach($data as $line) {

					$query = "REPLACE INTO $table (";
					if ($field=='bom') { $query .= "item_id, "; }
					$query .= "$item_id_label, partid, qty, amount, ";
					if ($field<>'bom') { $query .= "leadtime, leadtime_span, quote, "; }
					else { $query .= "charge, "; }
					$query .= "profit_pct";
					if($line['quoteid'] && $field != 'create') { $query .= " ,id"; }
					$query .= ") VALUES (";
					$query .= fres($quoteid).", ";
					if ($field=='bom') { $query .= "'service_item_id', "; }
					$query .= fres($partid).", ".fres($line['qty']).", ".fres($line['amount']).", ";
					if ($field<>'bom') { $query .= fres($line['leadtime']).", ".fres(ucwords($line['lead_span'])).", "; }
					$query .= fres($line['quote']).", ".fres(str_replace('Infinity','',$line['profit']));
					if($line['quoteid'] && $field != 'create') { $query .= ", " . fres($line['quoteid']); }
					$query .= ");";
					//echo $query;
					qedb($query);

					if (! $GLOBALS['DEBUG']) { $quotedItems[] = qid(); }
				}
			}

			// DELETE all records that did not get pushed back up into the update
			if (count($quotedItems)>0) {
				$query = "DELETE FROM $table WHERE id NOT IN ('".join("','", $quotedItems)."') AND $item_id_label = ".fres($quoteid).";";
				qedb($query);
			}
		}
	}

	function editOutsource($outsourced, $quoteid, $table, $field){
		// Get the ID of all the currently / existing quoted items
		$quotedItems = array();

		if(! empty($outsourced)){
			foreach($outsourced as $line) {
				//foreach($line_item as $line) {
					if($line['companyid']) {
						$query = "REPLACE INTO $table (quote_item_id, companyid, description, amount, quote";
						if($line['quoteid'] && $field != 'create') { $query .= " ,id"; }
						$query .= ") VALUES (".fres($quoteid).", ".fres($line['companyid']).", ".fres($line['description']).", ".fres($line['amount']).", ".fres($line['quote']);
						if($line['quoteid'] && $field != 'create') { $query .= ", " . fres($line['quoteid']); }
						$query .= ");";
						//echo $query;
						qedb($query);

						$quotedItems[] = qid();
					}
				//}
			}

			// DELETE all records that did not get pushed back up into the update
			$query = "DELETE FROM service_quote_outsourced WHERE id NOT IN ('".join("','", $quotedItems)."') AND quote_item_id = ".fres($quoteid).";";
			qedb($query);
		}
	}

	function quotedOutsideServices($partid, $qty, $price, $quoteid){
		$now = $GLOBALS['now'];
		
		$query = ";";
		qedb($query);
	}

	function editTech($techid, $status, $item_id, $item_id_label = 'service_item_id', $order_number, $start_datetime='', $end_datetime='') {
		if (! $techid) { die("You did not select a user first"); }

		if(! empty($status)) {
			$query = "DELETE FROM service_assignments WHERE userid = ".res($techid)." AND item_id = ".res($item_id)." AND item_id_label = ".fres($item_id_label).";";
			qedb($query);
		} else {
			// Check first if the user has already been assigned to this job
			$query = "SELECT * FROM service_assignments WHERE item_id = ".res($item_id)." AND item_id_label = ".fres($item_id_label)." AND userid = ".res($techid).";";
			$result = qedb($query);

			if(mysqli_num_rows($result) > 0) {
				$query = "DELETE FROM service_assignments WHERE userid = ".res($techid)." AND item_id = ".res($item_id)." AND item_id_label = ".fres($item_id_label).";";
				qedb($query);
			}

			if ($start_datetime) { $start_datetime = date("Y-m-d H:i:s", strtotime($start_datetime)); }
			if ($end_datetime) { $end_datetime = date("Y-m-d H:i:s", strtotime($end_datetime)); }

			$query = "REPLACE INTO service_assignments (item_id, item_id_label, userid, start_datetime, end_datetime) ";
			$query .= "VALUES (".res($item_id).", ".fres($item_id_label).", ".res($techid).", ".fres($start_datetime).", ".fres($end_datetime).");";
			qedb($query);

			$message = $item_id_label;

			if($item_id_label == 'repair_item_id') {
				$title = 'RO# ' . $order_number;
				//$issue = 'Issue: ' . $activity;
				$link = '/service.php?order_type=Repair&order_number=' . $order_number;
			} else if($item_id_label == 'service_item_id') {
				$title = 'SO# ' . $order_number;
				//$issue = 'Issue: ' . $activity;
				$link = '/service.php?order_type=Service&order_number=' . $order_number;
			}
			$message = $title . ' Assigned';
			if ($start_datetime) { $message .= ' for '.format_date($start_datetime,'D M j, y'); }
			if ($end_datetime) { $message .= ' up to '.format_date($end_datetime,'D M j, y'); }

			$query = "INSERT INTO messages (datetime, message, userid, link, ref_1, ref_1_label, ref_2, ref_2_label) ";
				$query .= "VALUES ('".$GLOBALS['now']."', ".prep($message).", ".prep($GLOBALS['U']['id']).", ".prep($link).", NULL, NULL, ".prep($order_number).", '".($item_id_label == 'repair_item_id' ? 'ro_number' : 'so_number')."');";
			qedb($query);

			$messageid = qid();

			$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', ".res($techid).");";
			$result = qedb($query);
		}
	}

	function addNotes($notes, $order_number, $item_id, $label) {
		//$query = "INSERT INTO activity_log (ro_number, repair_item_id, datetime, techid, notes) VALUES (".fres($order_number).", ".fres($repair_item_id).", ".fres($GLOBALS['now']).", ".fres($GLOBALS['U']['id']).", ".fres($notes).")";
		$query = "INSERT INTO activity_log (item_id, item_id_label, datetime, userid, notes) VALUES (".fres($item_id).", ".fres($label).", ".fres($GLOBALS['now']).", ".fres($GLOBALS['U']['id']).", ".fres($notes).")";
		//echo $query;
		qedb($query);
	}

	function deleteNotes($noteid) {
		$query = "DELETE FROM activity_log WHERE id = ".fres($noteid)." AND userid = ".fres($GLOBALS['U']['id']).";";
		//echo $query;
		qedb($query);
	}

	function addDocs($documents, $item_id, $item_label) {
		//foreach($documents as $doc) {
			// $query = "INSERT INTO service_docs (filename, notes, datetime, userid, type, item_label, item_id) VALUES (NULL, ".fres($documents['notes']).", ".fres($GLOBALS['now']).", ".fres($GLOBALS['U']['id']).", ".fres($documents['type']).", ".fres($item_label).", ".fres($item_id).");";

			// qedb($query);
			// $docid = qid();

			if(! empty($_FILES)) {
				if(! $_FILES['files']['error']) {

					$BUCKET = 'ventel.stackbay.com-docs';

					$name = $_FILES['files']['name'];
					$temp_name = $_FILES['files']['tmp_name'];
					$file = array('name'=>str_replace($TEMP_DIR,'',$name),'tmp_name'=>$temp_name);
					$file_url = saveFile($file);

					$query = "INSERT INTO service_docs (filename, notes, datetime, userid, type, item_label, item_id) VALUES (".fres($file_url).", ".fres($documents['notes']).", ".fres($GLOBALS['now']).", ".fres($GLOBALS['U']['id']).", ".fres($documents['type']).", ".fres($item_label).", ".fres($item_id).");";
					qedb($query);

					// $query = "UPDATE service_docs SET filename = ".fres($file_url)." WHERE id = ".res($docid).";";

					// qedb($query);
				}

				if(! $_FILES['filesImage']['error']) {

					$BUCKET = 'ventel.stackbay.com-docs';

					$name = $_FILES['filesImage']['name'];
					$temp_name = $_FILES['filesImage']['tmp_name'];
					$file = array('name'=>str_replace($TEMP_DIR,'',$name),'tmp_name'=>$temp_name);
					$file_url = saveFile($file);

					$query = "INSERT INTO service_docs (filename, notes, datetime, userid, type, item_label, item_id) VALUES (".fres($file_url).", ".fres($documents['notes']).", ".fres($GLOBALS['now']).", ".fres($GLOBALS['U']['id']).", ".fres($documents['type']).", ".fres($item_label).", ".fres($item_id).");";
					qedb($query);
				}
			} 
			// else {
			// 	// No Files added delete the doc
			// 	$query = "DELETE FROM service_docs WHERE id = ".res($docid).";";
			// 	qedb($query);
			// }
		//}
	}

	function addExpenses($expense, $label = 'service_item_id', $item_id) {
			$units = 1;
			if ($expense['units']>0) { $units = $expense['units']; }
			$amount = $expense['amount'];

			$reimburse = 0;
			if ($expense['reimbursement']>0) { $reimburse = $expense['reimbursement']; }

			$date = date('Y-m-d', strtotime($expense['date']));

			$categoryid = $expense['categoryid'];

			if ($categoryid==91) {
				$table = 'service_items';

				if($label == 'repair_item_id') { $table = 'repair_items'; }

				$query = "SELECT mileage_rate FROM $table WHERE id = ".res($item_id).";";
				$result = qedb($query);

				if(mysqli_num_rows($result)) {
					$r = mysqli_fetch_assoc($result);

					$amount = $r['mileage_rate'];
				}
			}

			$companyid = setCompany('expense','companyid');

			$query = "INSERT INTO expenses (item_id, item_id_label, companyid, expense_date, description, categoryid, ";
			$query .= "units, amount, file, userid, datetime, reimbursement, financeid) ";
			$query .= "VALUES ('".res($item_id)."', ".fres($label).", ".fres($companyid).", ";
			$query .= fres($date).", ".fres($expense['description']).", ".fres($expense['categoryid']).", ";
			$query .= "'".res($units)."', ".fres($amount).", '', ".res($expense['techid']).", '".res($GLOBALS['now'])."', '".res($reimburse)."', ".fres($expense['financeid']).");";

			qedb($query);
			$expenseid = qid();

			if(! empty($_FILES)) {
				if(! $_FILES['expense_files']['error']) {
					$BUCKET = 'ventel.stackbay.com-receipts';

					$name = $_FILES['expense_files']['name'];
					$temp_name = $_FILES['expense_files']['tmp_name'];

					$file = array('name'=>str_replace($TEMP_DIR,'',$name),'tmp_name'=>$temp_name);
					$file_url = saveFile($file);

					$query = "UPDATE expenses SET file = ".fres($file_url)." WHERE id = ".res($expenseid).";";

					qedb($query);
				}
			}
	}

	function createQuote($companyid, $contactid, $classid, $bill_to_id, $public, $private) {
		$quoteid = 0;

		$query = "INSERT INTO service_quotes (classid, companyid, contactid, datetime, userid, public_notes, private_notes) VALUES (".fres($classid).",".fres($companyid).",".fres($contactid).",".fres($GLOBALS['now']).",".fres($GLOBALS['U']['id']).",".fres($public).",".fres($private).");";
		qedb($query);

		$quoteid = qid();

		return $quoteid;
	}

	function requestQuoteMaterials($quote_materials, $order_number, $line_number) {
		global $DEV_ENV;

		$notes = 'Materials: <BR>';

		$quote_item_id = 0;

		foreach($quote_materials as $request) {
			// Grab the data pertaining to the material
			$query = "SELECT * FROM service_quote_materials WHERE id = ".res($request).";";
			$result = qedb($query);

			if(mysqli_num_rows($result)) {
				$r = mysqli_fetch_assoc($result);
				$quote_item_id = $r['quote_item_id'];

				$query2 = "INSERT INTO purchase_requests (techid, item_id, item_id_label, requested, partid, qty) VALUES (";
				$query2 .= res($GLOBALS['U']['id']) . ", " .res($r['quote_item_id']). ", 'quote_item_id', ".fres($GLOBALS['now']). ", " . fres($r['partid']) . ", " . fres($r['qty']);
				$query2 .= ");"; 

				qedb($query2);

				$notes .= getPart($r['partid']) . " QTY: ".$r['qty']."<BR>";

			} else {
				// Put in a die statement here as this should never occur
				continue;
			}
		}

		$title = 'Sourcing Request';
		$message = $title . ' for Quote# ' . $order_number;
		$link = '/sourcing_requests.php';

		$query = "INSERT INTO messages (datetime, message, userid, link, ref_1, ref_1_label, ref_2, ref_2_label) ";
			$query .= "VALUES ('".$GLOBALS['now']."', ".fres($message).", ".fres($GLOBALS['U']['id']).", ".fres($link).", NULL, NULL, ".fres($order_number).", '".($label == 'repair_item_id' ? 'ro_number' : 'so_number')."');";
		qedb($query);

		$messageid = qid();

		$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '8');";
		$result = qedb($query);

		if($result AND  ! $DEV_ENV) {
			$email_body_html = getRep($GLOBALS['U']['id'])." has submitted a sourcing request for <a target='_blank' href='https://www.stackbay.com/quote.php?taskid=".$quote_item_id."'>Quote# ".$order_number."-".$line_number."</a>. 
			<br>
			<br>
			".$notes."
			<br>
			<a target='_blank' href='https://www.stackbay.com".$link."?taskid=".$quote_item_id."''>Sourcing Requests View</a> ";
			$email_subject = $title;

			$email_name = "sourcing_request";
			$recipients = getSubEmail($email_name);

			// $recipients = array(
			// 	0 => array('ssabedra@ven-tel.com','Sam Sabedra'),
			// 	1 => array('joe@ven-tel.com','Joe Velasquez'),
			// 	2 => array('scott@ven-tel.com','Scott Johnston'),
			// );
			//$recipients = 'andrew@ven-tel.com';
			$bcc = 'david@ven-tel.com';
			
			$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
			if ($send_success) {
			    // echo json_encode(array('message'=>'Success'));
			} else {
			    $this->setError(json_encode(array('message'=>$SEND_ERR)));
			}
		}
	}

	function importQuoteMaterials($quote_materials, $item_id, $item_label, $order, $table = 'service_quote_materials') {
		global $DEV_ENV;

		$part_list = '';

		if(! empty($quote_materials)) {
			foreach($quote_materials as $quote_material_id) {

				$query = '';
				$deficit = 0;

				//fetch the information from the quoteid
				$query = "SELECT * FROM $table WHERE id =".res($quote_material_id).";";
				$result = qedb($query);

				if(mysqli_num_rows($result)) {
					$r = mysqli_fetch_assoc($result);

					// Don't need to insert in service BOM if we are converting BOM records to purchase requests
					if($table != 'service_bom') {
						// Insert statement into BOM
						$query2 = "INSERT INTO service_bom (partid, qty, amount, profit_pct, charge, type, item_id, item_id_label) VALUES (".fres($r['partid']).", ".fres($r['qty']).", ".fres($r['amount']).", ".fres($r['profit_pct']).", ".fres($r['quote']).", 'Material', ".fres($item_id).", ".fres($item_label).");";
						qedb($query2);
					}

					// Before inserting into the purchase requests check if we are updating a request or generating a new request
					// This also applies to imported quoted materials as the user should by then be updating the BOM qty

					$query4 = "SELECT SUM(qty) as totalRequested FROM purchase_requests WHERE item_id = ".fres($item_id)." AND item_id_label = ".fres($item_label)." AND partid = ".$r['partid']." GROUP BY partid;";
					$result4 = qedb($query4);

					if(mysqli_num_rows($result4)) {
						$r4 = mysqli_fetch_assoc($result4);

						// totalRequested being the purchase request sum generated for the part
						// qty being the qty needed from the BOM or the service_quote_materials table
						if($r4['totalRequested'] < $r['qty']) {
							// Qty on the BOM / Quote exceeds the amount currently requested so figure out what to do next (Update PR or generate a new PR)
							// Deficit being the amount still needed to be ordered
							$deficit = $r['qty'] - $r4['totalRequested'];

							// Check to see if there currently is a PR open with no PO attached to it yet
							$query5 = "SELECT * FROM purchase_requests WHERE item_id = ".fres($item_id)." AND item_id_label = ".fres($item_label)." AND partid = ".$r['partid']." AND po_number IS NULL ORDER BY id DESC;";
							$result5 = qedb($query5);

							// If there exists at least 1 then update the lastest
							if(mysqli_num_rows($result5)) {
								$r5 = mysqli_fetch_assoc($result5);
								$query6 = "UPDATE purchase_requests SET qty = qty + $deficit WHERE id = ".fres($r5['id']).";";

								qedb($query6);
							} else {
								// Insert as a new purchase request
								// If no purchase request exists or no open ones are available to alter
								$query3 = "INSERT INTO purchase_requests (techid, item_id, item_id_label, requested, partid, qty) VALUES (".fres($GLOBALS['U']['id']).", ".fres($item_id).", ".fres($item_label).", ".fres($GLOBALS['now']).", ".fres($r['partid']).", ".fres($deficit).");";
								qedb($query3);
							}
						}
					} else {
						// Insert as a new purchase request
						// If no purchase request at all for this itemid and partid combo
						$query3 = "INSERT INTO purchase_requests (techid, item_id, item_id_label, requested, partid, qty) VALUES (".fres($GLOBALS['U']['id']).", ".fres($item_id).", ".fres($item_label).", ".fres($GLOBALS['now']).", ".fres($r['partid']).", ".fres($r['qty']).");";
						qedb($query3);
					}

					$message = 'requested for Service# ' . $order;
					$link = '/purchase_requests.php';

					$query = "INSERT INTO messages (datetime, message, userid, link, ref_1, ref_1_label, ref_2, ref_2_label) ";
					$query .= "VALUES ('".$GLOBALS['now']."', ".fres($message).", ".fres('8').", ".fres($link).", ".fres($r['partid']).", 'partid', ".fres($order).", 'so_number');";

					qedb($query);
					$messageid = qid();

					//13 = Sam Sabedra
					$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '13');";
					$result = qedb($query);

					//Generate the list of parts being requested here for 1 email
					$part_list .= "<a target='_blank' href='https://www.stackbay.com/purchase_requests.php'>".getPart($r['partid'])."</a> Qty ". ($deficit?:$r['qty']) . "<br>";
				}
			}

			// Generate the email after all the notifications have been generated and the quoted materials imported
			if($result && ! $DEV_ENV) {
				$email_body_html = getRep('8')." has requested part(s): <br>" .$part_list. " on <a target='_blank' href='https://www.stackbay.com/service.php?order_type=Service&taskid=".$item_id."&tab=materials'>Service# ".$order."</a>";
				$email_subject = 'Purchase Request on Service# '.$order;
				// $recipients = 'andrew@ven-tel.com';
				$recipients = array(
					0 => array('ssabedra@ven-tel.com','Sam Sabedra'),
					1 => array('joe@ven-tel.com','Joe Velasquez'),
				);
				//$bcc = 'andrew@ven-tel.com';
				
				$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
				if ($send_success) {
				    // echo json_encode(array('message'=>'Success'));
				} else {
				    $this->setError(json_encode(array('message'=>$SEND_ERR)));
				}
			}
		}
	}

	function createNotification($activityid, $order_number, $label, $email = true) {
		$message = '';
		$link = '';
		$messageid = 0;
		$userid = 0;

		$activity = '';
		$title = '';
		$issue = '';

		global $DEV_ENV;

		// Get the activity notes
		$query = "SELECT notes, userid FROM activity_log WHERE id = ".res($activityid).";";
		$result = qedb($query);

		//echo $query;

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$activity = $r['notes'];
			$userid = $r['userid'];
		}


		if($label == 'repair_item_id') {
			$title = 'RO# ' . $order_number;
			$issue = 'Issue: ' . $activity;
			$message = $title . ' ' . $issue;
			$link = '/service.php?order_type=Repair&order_number=' . $order_number;
		} else if($label == 'service_item_id') {
			$title = 'SO# ' . $order_number;
			$issue = 'Issue: ' . $activity;
			$message = $title . ' ' . $issue;
			$link = '/service.php?order_type=Service&order_number=' . $order_number;
		}

		$query = "INSERT INTO messages (datetime, message, userid, link, ref_1, ref_1_label, ref_2, ref_2_label) ";
			$query .= "VALUES ('".$GLOBALS['now']."', ".fres($message).", ".fres($GLOBALS['U']['id']).", ".fres($link).", ".res($activityid).", 'activityid', ".fres($order_number).", '".($label == 'repair_item_id' ? 'ro_number' : 'so_number')."');";
		qedb($query);

		$messageid = qid();

		$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '8');";
		$result = qedb($query);
//&& ! $DEV_ENV
		if($result && ! $DEV_ENV) {
			$email_body_html = getRep($userid)." has submitted an issue for <a target='_blank' href='https://www.stackbay.com".$link."'>".$title."</a> " . $issue;
			$email_subject = 'Issue Submit for '.$title;
			$recipients = 'scott@ven-tel.com';
			//$recipients = 'ssabedra@ven-tel.com';
			// $bcc = 'dev@ven-tel.com';
			
			$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
			if ($send_success) {
			    // echo json_encode(array('message'=>'Success'));
			} else {
			    $this->setError(json_encode(array('message'=>$SEND_ERR)));
			}
		}
	}

	function returntoStock($order_type, $taskid) {
		global $ALERT;
		$T = order_type($order_type);

		$inventoryid = 0;

		$query = "SELECT invid FROM ".$T['items']." WHERE id = ".res($taskid).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$inventoryid = $r['invid'];

			// Set the inventory back to in stock
			$I = array('status'=>'received','id'=>$inventoryid);
			$inventoryid = setInventory($I);
		}

		$ALERT = 'Item has been returned to stock.';
	}

	if ($DEBUG) { print '<pre>' . print_r($_REQUEST, true). '</pre>'; }

	$order = 0;
	$line_number = 0; 
	$qty = 0; 
	$amount = 0.00; 
	$item_id = 0;
	$item_label = ''; 
	$ref_1 = ''; 
	$ref_1_label = '';
	$ref_2 = '';
	$ref_2_label = '';
	$labor_hours = 0;
	$labor_rate = 0;
	$expenses = 0;
	$charge = 0.00;

	$activity_notification = 0;

	$materials = array();
	$quote_materials = array();
	$outsourced = array();
	// joining table for tasks with outsourced orders
	$service_outsourced = array();

	$add_expense = array();
	$search = array();
	$documentation = array();
	$copZip = array();

	$search_type = '';

	$techid = 0;
	$tech_status = '';
	$LINE_NUMBER = 1;
	$service_item_id = 0;
	$notes = '';

	// Generate a Quote Order with these values
	$companyid = setCompany();
	$classid = 0; // Default this option to Installation
	$contactid = 0;
	$bill_to_id = 0;
	$public = '';
	$private = '';
	$description = '';

	$start_datetime = '';
	$end_datetime = '';

	$label = '';

	$import_materials = false;
	if (isset($_REQUEST['import_materials'])) { $import_materials = $_REQUEST['import_materials']; }
	if (isset($_REQUEST['import_materials']) AND $import_materials) { $quote_materials = $_REQUEST['quote_import']; }

	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }

	if (isset($_REQUEST['service_item_id'])) { $service_item_id = $_REQUEST['service_item_id']; $label = 'service_item_id'; }
	if (isset($_REQUEST['repair_item_id'])) { $service_item_id = $_REQUEST['repair_item_id']; }

	if (isset($_REQUEST['quote_item_id'])) { 
		$service_item_id = $_REQUEST['quote_item_id']; 
		$type = 'quote';
	}
	//dl 11-30-17 for adding notes
	if (isset($_REQUEST['repair_item_id']) OR $type=='Repair') { $label = 'repair_item_id'; }
	if (isset($_REQUEST['order'])) { $order = $_REQUEST['order']; } 
	if (isset($_REQUEST['line_number'])) { $line_number = $_REQUEST['line_number']; }
	if (isset($_REQUEST['qty'])) { $qty = trim($_REQUEST['qty']); }
	if (isset($_REQUEST['amount'])) { $amount = trim($_REQUEST['amount']); }
	if (isset($_REQUEST['item_id'])) { $item_id = $_REQUEST['item_id']; }
	if (isset($_REQUEST['item_label'])) { $item_label = $_REQUEST['item_label']; }
	if (isset($_REQUEST['ref_1'])) { $ref_1 = $_REQUEST['ref_1']; }
	if (isset($_REQUEST['ref_1_label'])) { $ref_1_label = $_REQUEST['ref_1_label']; }
	if (isset($_REQUEST['ref_2'])) { $ref_2 = $_REQUEST['ref_2']; }
	if (isset($_REQUEST['ref_2_label'])) { $ref_2_label = $_REQUEST['ref_2_label']; }
	if (isset($_REQUEST['labor_hours'])) { $labor_hours = trim($_REQUEST['labor_hours']); }
	if (isset($_REQUEST['labor_rate'])) { $labor_rate = trim($_REQUEST['labor_rate']); }

	if (isset($_REQUEST['expenses'])) { $expenses = $_REQUEST['expenses']; }
	if (isset($_REQUEST['expense'])) { $add_expense = $_REQUEST['expense']; }

	if (isset($_REQUEST['charge'])) { $charge = $_REQUEST['charge']; }

	if (isset($_REQUEST['activity_notification'])) { $activity_notification = $_REQUEST['activity_notification']; }
	if (isset($_REQUEST['activity_delete'])) { $activity_delete = $_REQUEST['activity_delete']; }

	if (isset($_REQUEST['quote_request'])) { $quote_materials = $_REQUEST['quote_request']; }

	if (isset($_REQUEST['documentation'])) { $documentation = $_REQUEST['documentation']; }
	// if (isset($_REQUEST['documentation'])) { $documentation = $_REQUEST['documentation']; }

	if (isset($_REQUEST['materials'])) { $materials = $_REQUEST['materials']; }
	if (isset($_REQUEST['outsourced'])) { $outsourced = $_REQUEST['outsourced']; }
	// if (isset($_REQUEST['service_outsourced'])) { $service_outsourced = $_REQUEST['service_outsourced']; }
	if (isset($_REQUEST['fieldid'])) { $search = $_REQUEST['fieldid']; }
	if (isset($_REQUEST['copZip'])) { $copZip = $_REQUEST['copZip']; }

	if (isset($_REQUEST['search_type'])) { $search_type = $_REQUEST['search_type']; }

	if (isset($_REQUEST['techid'])) { $techid = $_REQUEST['techid']; }
	if (isset($_REQUEST['tech_status'])) { $tech_status = $_REQUEST['tech_status']; }
	if (isset($_REQUEST['create'])) { $create = $_REQUEST['create']; }
	if (isset($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }
	if (isset($_REQUEST['description'])) { $description = $_REQUEST['description']; }

	if (isset($_REQUEST['classid'])) { $classid = $_REQUEST['classid']; }
	if (isset($_REQUEST['contactid'])) { $contactid = $_REQUEST['contactid']; }
	if (isset($_REQUEST['bill_to_id'])) { $bill_to_id = $_REQUEST['bill_to_id']; }
	if (isset($_REQUEST['public_notes'])) { $public = trim($_REQUEST['public_notes']); }
	if (isset($_REQUEST['private_notes'])) { $private = trim($_REQUEST['private_notes']); }

	if (isset($_REQUEST['start_datetime'])) { $start_datetime = trim($_REQUEST['start_datetime']); }
	if (isset($_REQUEST['end_datetime'])) { $end_datetime = trim($_REQUEST['end_datetime']); }

	if (isset($_REQUEST['return'])) { $return = trim($_REQUEST['return']); }

	// Return to Stock and return back to order
	if($return) {
		returntoStock($type, $service_item_id);

		if (! $DEBUG) { header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $service_item_id); }
		exit;
	}

	// Import Materials should only import and do nothing else to save the form
	if($import_materials) {
		if($line_number) {
			$order = $order . '-' . $line_number;
		}

		importQuoteMaterials($quote_materials, $service_item_id, $label, $order);

		if (! $DEBUG) { header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $service_item_id . '&tab=materials'); }
		exit;
	}

	if(! empty($activity_notification)) {
		if($line_number) {
			$order = $order . '-' . $line_number;
		}

		createNotification($activity_notification, $order, $label, true);

		if (! $DEBUG) { header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $service_item_id); }
		exit;

	} else if(! empty($activity_delete)) {
		if($line_number) {
			$order = $order . '-' . $line_number;
		}

		deleteNotes($activity_delete);

		if (! $DEBUG) { header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $service_item_id); }
		exit;

	} else if(! empty($notes) && ! empty($service_item_id)) {
		addNotes($notes, $order, $service_item_id, $label);

		if ($DEBUG) { exit; }

//		header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $service_item_id);
		if(! $line_number) {
			header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $service_item_id);
		} else {
			header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $service_item_id);
		}
		exit;
	// Add permission to a certain user upon the create or quote screen
	} else if(! empty($service_item_id) AND ($techid OR ! empty($tech_status))) {
		if($line_number) {
			$order = $order . '-' . $line_number;
		}

		editTech($techid, $tech_status, $service_item_id, $label, $order, $start_datetime, $end_datetime);

		if (! $DEBUG) { header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $service_item_id . '&tab=labor'); }
		exit;
	// Create a quote for the submitted task
	} else if($create == 'quote' || $create == 'save' || $type == 'quote') {
		$tab = 'materials';

		if(! $order) {
			$order = createQuote($companyid, $contactid, $classid, $bill_to_id, $public, $private);
		}

		$qid = quoteTask($order, $line_number, 1, $charge, $item_id, $item_label, $description, $labor_hours, $labor_rate, $expenses, $search, $search_type, $service_item_id);

		editMaterials($materials, $service_item_id, 'service_quote_materials');
		editOutsource($outsourced, $service_item_id, 'service_quote_outsourced');

		if(! empty($quote_materials)) {
			requestQuoteMaterials($quote_materials, $order, $line_number);
		}

		if ($DEBUG) { exit; }

		if ($service_item_id) {
			header('Location: /quote.php?taskid=' . $service_item_id.'&tab=' . $tab);
		} else {
			header('Location: /manage_quote.php?order_type=service_quote&order_number='.$order);
		}
		exit;

	// Convert the Quote Item over to an actual Service Item
	} else if($create == 'create') {
		$service_item_id = editTask($order, $line_number, $qty, $amount, $item_id, $item_label, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $service_item_id);

die("Problem here, see admin immediately");

		editMaterials($materials, $service_item_id, 'service_materials', $create);
		// editOutsource($outsourced, $qid, 'service_outsourced');

		if (! $DEBUG) { header('Location: /service.php?order_number=' . $order .'-'. $LINE_NUMBER); }

	// Else editing the task
	} else {
		$tab = 'labor';// . $service_item_id;

		// If completing
		if (isset($_REQUEST['repair_code_id'])) {
			completeTask($service_item_id, $_REQUEST['repair_code_id'], 'activity_log', 'item_id', $label);
		} else if(strtolower($type) != 'service') {
			// Editing a Repair Item
			// At the current state not going to happen
		} else {

			if(! empty($quote_materials)) {
				importQuoteMaterials($quote_materials, $service_item_id, $label, $order, 'service_bom');

				if (! $DEBUG) { header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $service_item_id . '&tab=materials'); }
				exit;
			}

			// If Documentation
			if($documentation) {
				addDocs($documentation, $service_item_id, $label);
				$tab = 'documentation';
			} 

			if(! $_FILES['filesImage']['error']) {
				$tab = 'images';
			}

			if ($materials) {
				editMaterials($materials, $service_item_id, 'service_bom', 'bom');
				$tab = 'materials';
			}

			// if ($service_outsourced) {
			// 	foreach ($service_outsourced as $i => $r) {
			// 		$query = "REPLACE service_outsourced (service_item_id, outsourced_item_id, profit_pct, charge";
			// 		if ($r['id']) { $query .= ", id"; }
			// 		$query .= ") VALUES ('".res($r['service_item_id'])."', '".res($r['outsourced_item_id'])."', '".res(trim($r['profit_pct']))."', '".res(trim($r['charge']))."'";
			// 		if ($r['id']) { $query .= ", '".res($r['id'])."'"; }
			// 		$query .= "); ";
			// 		$result = qedb($query);
			// 	}
			// }

			// Generate the COP Zip if the copZip array has something inside it
			if(! empty($copZip)) {
				$item_label = 'service_item_id';
				// Generate the $fileList array here using query
				$fileList = array();

				foreach($copZip as $docID) {
					$query = "SELECT filename, notes FROM service_docs WHERE id = ".res($docID).";";
					$result = qedb($query);

					if(mysqli_num_rows($result) > 0) {
						$r = mysqli_fetch_assoc($result);
						if($r['notes']) {
							$fileList[str_replace(' ', '_', $r['notes'])] = $r['filename'];
						} else {
							$path_parts = pathinfo($r['filename']);
							$fileList[str_replace(' ', '_', $path_parts['filename'])] = $r['filename'];
						}
					}
				}

				
				zipFiles($fileList, $service_item_id, $item_label, $order, 'Service');
				$tab = 'documentation';
			}

			if(! empty($add_expense) AND ($add_expense['amount']) OR $add_expense['units']) {
				addExpenses($add_expense, $label, $service_item_id);
				$tab = 'expenses';
			}
		}

		if ($DEBUG) { exit; }

		if(! $line_number) {
			header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $service_item_id . '&tab=' . $tab . ($ALERT?'&ALERT='.$ALERT:''));
		} else {
			header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $service_item_id . '&tab=' . $tab . ($ALERT?'&ALERT='.$ALERT:''));
		}
	}

	exit;
