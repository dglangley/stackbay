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

	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function editTask($so_number, $line_number, $qty, $amount, $item_id, $item_label, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $service_item_id){
		global $LINE_NUMBER;

		$id = 0;

		// Set line number automatically
		// Search for the largest line_number for current service order
		// Does not set another line_number if one is inserted in
		if(empty($line_number)) {
			$query = "SELECT line_number FROM service_items WHERE so_number = ".res($service_item_id)." ORDER BY line_number DESC LIMIT 1;";
			$result = qdb($query) OR die(qe().'<BR>'.$query);

			// Get the largest line_number if exists and increment by 1
			if(mysqli_num_rows($result)){
				$r = mysqli_fetch_assoc($result);

				$line_number = $r['line_number'] + 1;
			}
		}

		$query = "REPLACE INTO service_items (so_number, line_number, qty, amount, item_id, item_label, description, ref_1, ref_1_label, ref_2, ref_2_label";
		if($service_item_id) { $query .= " ,id"; }
		$query .= ") VALUES (".fres($so_number).", ".fres($line_number).", ".fres($qty).", ".fres($amount).", ".fres('').", ".fres($item_label).", ".fres($description).", ".fres($ref_1).", ".fres($ref_1_label).", ".fres($ref_2).", ".fres($ref_2_label);
		if($service_item_id) { $query .= ", " . fres($service_item_id); }
		$query .= ");";

		qdb($query) OR die(qe().'<BR>'.$query);
		$id = qid();

		$LINE_NUMBER = $line_number;

		return $id;
	}

	function quoteTask($quoteid, $line_number, $qty, $amount, $item_id, $item_label, $description, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $labor_hours, $labor_rate, $expenses, $search, $search_type, $quote_item_id){
		global $LINE_NUMBER;

		// print_r($search);

		// Currently only 1 id associated per item
		$search = $search[0];

		$id = 0;

		// Set line number automatically
		// Search for the largest line_number for current quoteid
		if(empty($line_number) && ! $quote_item_id) {
			$query = "SELECT line_number FROM service_quote_items WHERE quoteid = ".res($quoteid)." ORDER BY line_number DESC LIMIT 1;";
			$result = qdb($query) OR die(qe().'<BR>'.$query);

			// Get the largest line_number if exists and increment by 1
			if(mysqli_num_rows($result)){
				$r = mysqli_fetch_assoc($result);

				$line_number = $r['line_number'] + 1;
			} else {
				$line_number = 1;
			}
		}

		$query = "REPLACE INTO service_quote_items (quoteid, line_number, qty, amount, item_id, item_label, description, ref_1, ref_1_label, ref_2, ref_2_label, labor_hours, labor_rate, expenses";
		if($quote_item_id) { $query .= " ,id"; }
		$query .= ") VALUES (".fres($quoteid).", ".fres($line_number).", ".fres(($qty?:1)).", ".fres($amount).", ".fres($search).", ".fres(($search_type == 'Site' ? 'addressid' : 'partid')).", ".fres($description).", ".fres($ref_1).", ".fres($ref_1_label).", ".fres($ref_2).", ".fres($ref_2_label).", ".fres($labor_hours).", ".fres($labor_rate).", ".fres($expenses);
		if($quote_item_id) { $query .= ", " . fres($quote_item_id); }
		$query .= ");";

		// echo $query;

		qdb($query) OR die(qe().'<BR>'.$query);
		$id = qid();

		$LINE_NUMBER = $line_number;

		return $id;
	}

	function editMaterials($materials, $quoteid, $table, $field){
		// Get the ID of all the currently / existing quoted items
		$quotedItems = array();

		if($field == 'create') {
			$item_id = 'service_item_id';
		} else {
			$item_id = 'quote_item_id';
		}

		if(! empty($materials)){
			foreach($materials as $partid => $data) {
				foreach($data as $line) {

					$query = "REPLACE INTO $table ($item_id, partid, qty, amount, leadtime, leadtime_span, profit_pct, quote";
					if($line['quoteid'] && $field != 'create') { $query .= " ,id"; }
					$query .= ") VALUES (".fres($quoteid).", ".fres($partid).", ".fres($line['qty']).", ".fres($line['amount']).", ".fres($line['leadtime']).", ".fres(ucwords($line['lead_span'])).", ".fres($line['profit']).", ".fres($line['quote']);
					if($line['quoteid'] && $field != 'create') { $query .= ", " . fres($line['quoteid']); }
					$query .= ");";
					//echo $query;
					qdb($query) OR die(qe().'<BR>'.$query);

					$quotedItems[] = qid();
				}
			}

			// DELETE all records that did not get pushed back up into the update
			$query = "DELETE FROM $table WHERE id NOT IN ('".join("','", $quotedItems)."') AND $item_id = ".fres($quoteid).";";
			qdb($query) OR die(qe().'<BR>'.$query);
		}
	}

	function editOutsource($outsourced, $quoteid, $table, $field){
		// Get the ID of all the currently / existing quoted items
		$quotedItems = array();

		if(! empty($outsourced)){
			foreach($outsourced as $line) {
				//foreach($line_item as $line) {
					if($line['companyid']) {
						$query = "REPLACE INTO $table (quote_item_id, companyid, description, amount";
						if($line['quoteid'] && $field != 'create') { $query .= " ,id"; }
						$query .= ") VALUES (".fres($quoteid).", ".fres($line['companyid']).", ".fres($line['description']).", ".fres($line['amount']);
						if($line['quoteid'] && $field != 'create') { $query .= ", " . fres($line['quoteid']); }
						$query .= ");";
						//echo $query;
						qdb($query) OR die(qe().'<BR>'.$query);

						$quotedItems[] = qid();
					}
				//}
			}

			// DELETE all records that did not get pushed back up into the update
			$query = "DELETE FROM service_quote_outsourced WHERE id NOT IN ('".join("','", $quotedItems)."') AND quote_item_id = ".fres($quoteid).";";
			qdb($query) OR die(qe().'<BR>'.$query);
		}
	}

	function quotedOutsideServices($partid, $qty, $price, $quoteid){
		$now = $GLOBALS['now'];
		
		$query = ";";
		qdb($query) OR die(qe().'<BR>'.$query);
	}

	function editTech($techid, $status, $item_id, $item_id_label = 'service_item_id', $order_number, $start_datetime='', $end_datetime='') {
		if(! empty($status)) {
			$query = "DELETE FROM service_assignments WHERE userid = ".res($status)." AND item_id = ".res($item_id)." AND item_id_label = ".fres($item_id_label).";";
			qdb($query) OR die(qe() . ' ' . $query);
		} else {
			// Check first if the user has already been assigned to this job
			$query = "SELECT * FROM service_assignments WHERE item_id = ".res($item_id)." AND item_id_label = ".fres($item_id_label)." AND userid = ".res($techid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if(mysqli_num_rows($result) > 0) {
				$query = "DELETE FROM service_assignments WHERE userid = ".res($techid)." AND item_id = ".res($item_id)." AND item_id_label = ".fres($item_id_label).";";
				qdb($query) OR die(qe() . ' ' . $query);
			}

			if ($start_datetime) { $start_datetime = date("Y-m-d H:i:s", strtotime($start_datetime)); }
			if ($end_datetime) { $end_datetime = date("Y-m-d H:i:s", strtotime($end_datetime)); }

			$query = "REPLACE INTO service_assignments (item_id, item_id_label, userid, start_datetime, end_datetime) ";
			$query .= "VALUES (".res($item_id).", ".fres($item_id_label).", ".res($techid).", ".fres($start_datetime).", ".fres($end_datetime).");";
			qdb($query) OR die(qe() . ' ' . $query);

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
			qdb($query) OR die(qe() . '<BR>' . $query);

			$messageid = qid();

			$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', ".res($techid).");";
			$result = qdb($query) or die(qe() . '<BR>' . $query);
		}
	}

	function addNotes($notes, $order_number, $item_id, $label) {
		//$query = "INSERT INTO activity_log (ro_number, repair_item_id, datetime, techid, notes) VALUES (".fres($order_number).", ".fres($repair_item_id).", ".fres($GLOBALS['now']).", ".fres($GLOBALS['U']['id']).", ".fres($notes).")";
		$query = "INSERT INTO activity_log (item_id, item_id_label, datetime, userid, notes) VALUES (".fres($item_id).", ".fres($label).", ".fres($GLOBALS['now']).", ".fres($GLOBALS['U']['id']).", ".fres($notes).")";
		//echo $query;
		qdb($query) OR die(qe() . ' ' . $query);
	}

	function addDocs($documents, $item_id, $item_label) {
		//foreach($documents as $doc) {
			$query = "INSERT INTO service_docs (filename, notes, datetime, userid, type, item_label, item_id) VALUES (NULL, ".fres($documents['notes']).", ".fres($GLOBALS['now']).", ".fres($GLOBALS['U']['id']).", ".fres($documents['type']).", ".fres($item_label).", ".fres($item_id).");";

			// echo $query;

			qdb($query) OR die(qe() .'<BR>'. $query);
			$docid = qid();

			if(! empty($_FILES)) {
				if(! $_FILES['files']['error']) {
					$BUCKET = 'ventel.stackbay.com-docs';

					$name = $_FILES['files']['name'];
					$temp_name = $_FILES['files']['tmp_name'];

					$file = array('name'=>str_replace($TEMP_DIR,'',$name),'tmp_name'=>$temp_name);
					$file_url = saveFile($file);

					$query = "UPDATE service_docs SET filename = ".fres($file_url)." WHERE id = ".res($docid).";";

					qdb($query) OR die(qe() . ' ' . $query);
				}
			}
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
				$result = qdb($query) OR die(qe() . "<BR>" . $query);

				if(mysqli_num_rows($result)) {
					$r = mysqli_fetch_assoc($result);

					$amount = $r['mileage_rate'];
				}
			}

			$query = "INSERT INTO expenses (item_id, item_id_label, expense_date, description, categoryid, ";
			$query .= "units, amount, file, userid, datetime, reimbursement) ";
			$query .= "VALUES ('".res($item_id)."', ".fres($label).", ".fres($date).", ".fres($expense['description']).", ".fres($expense['categoryid']).", ";
			$query .= "'".res($units)."', ".fres($amount).", '', ".res($expense['techid']).", '".res($GLOBALS['now'])."', '".res($reimburse)."');";

			qdb($query) OR die(qe().'<BR>'.$query);
	}

	function createQuote($companyid, $contactid, $classid, $bill_to_id, $public, $private) {
		$quoteid = 0;

		$query = "INSERT INTO service_quotes (classid, companyid, contactid, datetime, userid, public_notes, private_notes) VALUES (".fres($classid).",".fres($companyid).",".fres($contactid).",".fres($GLOBALS['now']).",".fres($GLOBALS['U']['id']).",".fres($public).",".fres($private).");";
		qdb($query) OR die(qe().'<BR>'.$query);

		$quoteid = qid();

		return $quoteid;
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
		$result = qdb($query) OR die(qe() . ' ' . $query);

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
			$query .= "VALUES ('".$GLOBALS['now']."', ".prep($message).", ".prep($GLOBALS['U']['id']).", ".prep($link).", ".res($activityid).", 'activityid', ".prep($order_number).", '".($label == 'repair_item_id' ? 'ro_number' : 'so_number')."');";
		qdb($query) OR die(qe() . '<BR>' . $query);

		$messageid = qid();

		$query = "INSERT INTO notifications (messageid, userid) VALUES ('$messageid', '8');";
		$result = qdb($query) or die(qe() . '<BR>' . $query);
//&& ! $DEV_ENV
		if($result && ! $DEV_ENV) {
			$email_body_html = getRep($userid)." has submitted an issue for <a target='_blank' href='".$_SERVER['HTTP_HOST'].$link."'>".$title."</a> " . $issue;
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

	// print '<pre>' . print_r($_REQUEST, true). '</pre>';

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

	$activity_notification = 0;

	$materials = array();
	$outsourced = array();
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
	$companyid = 0;
	$classid = 0; // Default this option to Installation
	$contactid = 0;
	$bill_to_id = 0;
	$public = '';
	$private = '';
	$description = '';

	$start_datetime = '';
	$end_datetime = '';

	$label = '';

	if (isset($_REQUEST['service_item_id'])) { $service_item_id = $_REQUEST['service_item_id']; $label = 'service_item_id'; }
	if (isset($_REQUEST['repair_item_id'])) { $service_item_id = $_REQUEST['repair_item_id']; }

	if (isset($_REQUEST['quote_item_id'])) { $service_item_id = $_REQUEST['quote_item_id']; }
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }
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

	if (isset($_REQUEST['activity_notification'])) { $activity_notification = $_REQUEST['activity_notification']; }

	if (isset($_REQUEST['documentation'])) { $documentation = $_REQUEST['documentation']; }
	if (isset($_REQUEST['materials'])) { $materials = $_REQUEST['materials']; }
	if (isset($_REQUEST['outsourced'])) { $outsourced = $_REQUEST['outsourced']; }
	if (isset($_REQUEST['addressid'])) { $search = $_REQUEST['addressid']; }
	if (isset($_REQUEST['copZip'])) { $copZip = $_REQUEST['copZip']; }

	if (isset($_REQUEST['search_type'])) { $search_type = $_REQUEST['search_type']; }

	if (isset($_REQUEST['techid'])) { $techid = $_REQUEST['techid']; }
	if (isset($_REQUEST['tech_status'])) { $tech_status = $_REQUEST['tech_status']; }
	if (isset($_REQUEST['create'])) { $create = $_REQUEST['create']; }
	if (isset($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }
	if (isset($_REQUEST['description'])) { $description = $_REQUEST['description']; }

	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	if (isset($_REQUEST['classid'])) { $classid = $_REQUEST['classid']; }
	if (isset($_REQUEST['contactid'])) { $contactid = $_REQUEST['contactid']; }
	if (isset($_REQUEST['bill_to_id'])) { $bill_to_id = $_REQUEST['bill_to_id']; }
	if (isset($_REQUEST['public_notes'])) { $public = trim($_REQUEST['public_notes']); }
	if (isset($_REQUEST['private_notes'])) { $private = trim($_REQUEST['private_notes']); }

	if (isset($_REQUEST['start_datetime'])) { $start_datetime = trim($_REQUEST['start_datetime']); }
	if (isset($_REQUEST['end_datetime'])) { $end_datetime = trim($_REQUEST['end_datetime']); }

	if(! empty($activity_notification)) {
		if($line_number) {
			$order = $order . '-' . $line_number;
		}

		createNotification($activity_notification, $order, $label, true);

		header('Location: /service.php?order_type='.$type.'&order_number=' . $order);
	} else if(! empty($notes) && ! empty($service_item_id)) {
		addNotes($notes, $order, $service_item_id, $label);

//		header('Location: /service.php?order_type='.$type.'&order_number=' . $order);
		if(! $line_number) {
			header('Location: /service.php?order_type='.$type.'&order_number=' . $order);
		} else {
			header('Location: /service.php?order_type='.$type.'&order_number=' . $order . '-' . $line_number);
		}
	// Add permission to a certain user upon the create or quote screen
	} else if(! empty($service_item_id) AND ($techid OR ! empty($tech_status))) {
		if($line_number) {
			$order = $order . '-' . $line_number;
		}

		editTech($techid, $tech_status, $service_item_id, $label, $order, $start_datetime, $end_datetime);
		header('Location: /service.php?order_type='.$type.'&order_number=' . $order . '&tab=labor');

	// Create a quote for the submitted task
	} else if($create == 'quote' || $create == 'save') {
		if(! $order) {
			$order = createQuote($companyid, $contactid, $classid, $bill_to_id, $public, $private);
		}
		$qid = quoteTask($order, $line_number, $qty, $amount, $item_id, $item_label, $description, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $labor_hours, $labor_rate, $expenses, $search, $search_type, $service_item_id);

		editMaterials($materials, $qid, 'service_quote_materials');
		editOutsource($outsourced, $qid, 'service_quote_outsourced');

		header('Location: /quote.php?order_number=' . $order .'-'. $LINE_NUMBER);

	// Convert the Quote Item over to an actual Service Item
	} else if($create == 'create') {
		$service_item_id = editTask($order, $line_number, $qty, $amount, $item_id, $item_label, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $service_item_id);
		editMaterials($materials, $service_item_id, 'service_materials', $create);
		// editOutsource($outsourced, $qid, 'service_outsourced');

		header('Location: /service.php?order_number=' . $order .'-'. $LINE_NUMBER);

	// Else editing the task
	} else {
		$tab = 'labor' . $_REQUEST['repair_item_id'];

		// print_r($_REQUEST);
		// If completing
		if (isset($_REQUEST['repair_code_id'])) {
			completeTask($service_item_id, $_REQUEST['repair_code_id'], 'activity_log', 'item_id', $label);
			//completeTask($service_item_id, $order, $_REQUEST['repair_code_id'], $GLOBALS['U']['id']);
		} else if(strtolower($type) != 'service') {
			// Editing a Repair Item
		}

		//print_r($expenses);

		if(strtolower($type) == 'service') {
			// If Documentation
			if($documentation) {
				addDocs($documentation, $service_item_id, $label);
				$tab = 'documentation';
			}

			// Generate the COP Zip if the copZip array has something inside it
			if(! empty($copZip)) {
				// Generate the $fileList array here using query
				$fileList = array();
				
				zipFiles($filelist, $service_item_id, $item_label);
			}

			if($add_expense) {
				addExpenses($add_expense, $label, $service_item_id);
				$tab = 'expenses';
			}

			// Editing a service task
			// editTask($order, $line_number, $qty, $amount, $item_id, $item_label, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $service_item_id);
		}

		if(! $line_number) {
			header('Location: /service.php?order_type='.$type.'&order_number=' . $order . '&tab=' . $tab);
		} else {
			header('Location: /service.php?order_type='.$type.'&order_number=' . $order . '-' . $line_number . '&tab=' . $tab);
		}
	}

	exit;
