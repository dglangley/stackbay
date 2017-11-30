<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

	function editTask($so_number, $line_number, $qty, $amount, $item_id, $item_label, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $service_item_id){
		global $LINE_NUMBER;

		$id = 0;

		// Set line number automatically
		// Search for the largest line_number for current service order
		// Does not set another line_number if one is inserted in
		if(empty($line_number)) {
			$query = "SELECT line_number FROM service_items WHERE so_number = ".res($service_item_id)." ORDER BY line_number DESC LIMIT 1;";
			$result = qdb($query) OR die(qe().' '.$query);

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

		qdb($query) OR die(qe().' '.$query);
		$id = qid();

		$LINE_NUMBER = $line_number;

		return $id;
	}

	function quoteTask($quoteid, $line_number, $qty, $amount, $item_id, $item_label, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $labor_hours, $labor_rate, $expenses, $search, $search_type, $quote_item_id){
		global $LINE_NUMBER;

		// Currently only 1 id associated per item
		$search = reset($search)[0];

		$id = 0;

		// Set line number automatically
		// Search for the largest line_number for current quoteid
		if(empty($line_number) && ! $quote_item_id) {
			$query = "SELECT line_number FROM service_quote_items WHERE quoteid = ".res($quoteid)." ORDER BY line_number DESC LIMIT 1;";
			$result = qdb($query) OR die(qe().' '.$query);

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
		$query .= ") VALUES (".fres($quoteid).", ".fres($line_number).", ".fres($qty).", ".fres($amount).", ".fres($search).", ".fres(($search_type == 'Site' ? 'addressid' : 'partid')).", ".fres($description).", ".fres($ref_1).", ".fres($ref_1_label).", ".fres($ref_2).", ".fres($ref_2_label).", ".fres($labor_hours).", ".fres($labor_rate).", ".fres($expenses);
		if($quote_item_id) { $query .= ", " . fres($quote_item_id); }
		$query .= ");";

		// echo $query;

		qdb($query) OR die(qe().' '.$query);
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
					qdb($query) OR die(qe().' '.$query);

					$quotedItems[] = qid();
				}
			}

			// DELETE all records that did not get pushed back up into the update
			$query = "DELETE FROM $table WHERE id NOT IN ('".join("','", $quotedItems)."') AND $item_id = ".fres($quoteid).";";
			qdb($query) OR die(qe().' '.$query);
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
						qdb($query) OR die(qe().' '.$query);

						$quotedItems[] = qid();
					}
				//}
			}

			// DELETE all records that did not get pushed back up into the update
			$query = "DELETE FROM service_quote_outsourced WHERE id NOT IN ('".join("','", $quotedItems)."') AND quote_item_id = ".fres($quoteid).";";
			qdb($query) OR die(qe().' '.$query);
		}
	}

	function quotedOutsideServices($partid, $qty, $price, $quoteid){
		$now = $GLOBALS['now'];
		
		$query = ";";
		qdb($query) OR die(qe().' '.$query);
	}

	function editTech($techid, $status, $item_id, $item_id_label = 'service_item_id') {
		if(! empty($status)) {
			$query = "DELETE FROM service_assignments WHERE userid = ".res($status)." AND item_id = ".res($item_id).";";
			qdb($query) OR die(qe() . ' ' . $query);
		} else {
			// Check first if the user has already been assigned to this job
			$query = "SELECT * FROM service_assignments WHERE item_id = ".res($item_id)." AND item_id_label = ".fres($item_id_label)." AND userid = ".res($techid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if(mysqli_num_rows($result) == 0) {
				$query = "INSERT INTO service_assignments (item_id, item_id_label, userid) VALUES (".res($item_id).", ".fres($item_id_label).", ".res($techid).");";
				qdb($query) OR die(qe() . ' ' . $query);
			}
		}

	}

	function addNotes($notes, $order_number, $item_id, $label) {
		//$query = "INSERT INTO activity_log (ro_number, repair_item_id, datetime, techid, notes) VALUES (".fres($order_number).", ".fres($repair_item_id).", ".fres($GLOBALS['now']).", ".fres($GLOBALS['U']['id']).", ".fres($notes).")";
		$query = "INSERT INTO activity_log (item_id, item_id_label, datetime, userid, notes) VALUES (".fres($item_id).", ".fres($label).", ".fres($GLOBALS['now']).", ".fres($GLOBALS['U']['id']).", ".fres($notes).")";
		//echo $query;
		qdb($query) OR die(qe() . ' ' . $query);
	}

	function completeTask($item_id, $order_number, $repair_code_id, $techid, $table = 'activity_log', $field = 'item_id', $type = 'repair') {

		$notes = '';

		$query = "SELECT description FROM repair_codes WHERE id = ".res($repair_code_id).";";
		$result = qdb($query) OR die(qe() . ' ' . $query);

		//echo $query;

		if(mysqli_num_rows($result)) {
			$r = mysqli_fetch_assoc($result);
			$notes = ucwords($type) . ' completed. Final Status: <b>' . $r['description'].'</b>';
		}

		//$order_number = getOrderNumber($item_id);

		//$query = "INSERT INTO $table (ro_number, $field, datetime, techid, notes) VALUES (".res($order_number).",".res($item_id).", '".res($GLOBALS['now'])."', ".res($techid).", '".res($notes)."');";
		$query = "INSERT INTO $table ($field, item_id_label, datetime, userid, notes) VALUES (".res($item_id).", '".$type."_item_id', '".res($GLOBALS['now'])."', ".res($techid).", '".res($notes)."');";
		//echo $query;
		qdb($query) OR die(qe().' '.$query);
//echo $query;
		// Update the repair_code_id to the order
		if($type == 'repair') {
			$query = "UPDATE repair_orders SET repair_code_id = ".res($repair_code_id)." WHERE ro_number = ".res($order_number).";";
			qdb($query) OR die(qe().' '.$query);
		}

	}

	function createQuote($companyid, $contactid, $classid, $bill_to_id, $public, $private) {
		$quoteid = 0;

		$query = "INSERT INTO service_quotes (classid, companyid, contactid, datetime, userid, public_notes, private_notes) VALUES (".fres($classid).",".fres($companyid).",".fres($contactid).",".fres($GLOBALS['now']).",".fres($GLOBALS['U']['id']).",".fres($public).",".fres($private).");";
		qdb($query) OR die(qe().' '.$query);

		$quoteid = qid();

		return $quoteid;
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

	$materials = array();
	$outsourced = array();
	$expenses = array();
	$search = array();

	$search_type = '';

	$techid = 0;
	$tech_status = '';
	$LINE_NUMBER = 1;
	$service_item_id = 0;
	$notes = '';

	// Generate a Quote Order with these values
	$companyid = 0;
	$classid = 2; // Default this option to Installation
	$contactid = 0;
	$bill_to_id = 0;
	$public = '';
	$private = '';

	$label = '';

	if (isset($_REQUEST['service_item_id'])) { $service_item_id = $_REQUEST['service_item_id']; $label = 'service_item_id'; }
	if (isset($_REQUEST['repair_item_id'])) { $service_item_id = $_REQUEST['repair_item_id']; $label = 'repair_item_id'; }

	if (isset($_REQUEST['quote_item_id'])) { $service_item_id = $_REQUEST['quote_item_id']; }
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }
	if (isset($_REQUEST['order'])) { $order = $_REQUEST['order']; } 
	if (isset($_REQUEST['line_number'])) { $line_number = $_REQUEST['line_number']; }
	if (isset($_REQUEST['qty'])) { $qty = $_REQUEST['qty']; }
	if (isset($_REQUEST['amount'])) { $amount = $_REQUEST['amount']; }
	if (isset($_REQUEST['item_id'])) { $item_id = $_REQUEST['item_id']; }
	if (isset($_REQUEST['item_label'])) { $item_label = $_REQUEST['item_label']; }
	if (isset($_REQUEST['ref_1'])) { $ref_1 = $_REQUEST['ref_1']; }
	if (isset($_REQUEST['ref_1_label'])) { $ref_1_label = $_REQUEST['ref_1_label']; }
	if (isset($_REQUEST['ref_2'])) { $ref_2 = $_REQUEST['ref_2']; }
	if (isset($_REQUEST['ref_2_label'])) { $ref_2_label = $_REQUEST['ref_2_label']; }
	if (isset($_REQUEST['labor_hours'])) { $labor_hours = $_REQUEST['labor_hours']; }
	if (isset($_REQUEST['labor_rate'])) { $labor_rate = $_REQUEST['labor_rate']; }

	if (isset($_REQUEST['expenses'])) { $expenses = $_REQUEST['expenses']; }
	if (isset($_REQUEST['materials'])) { $materials = $_REQUEST['materials']; }
	if (isset($_REQUEST['outsourced'])) { $outsourced = $_REQUEST['outsourced']; }
	if (isset($_REQUEST['addressid'])) { $search = $_REQUEST['addressid']; }

	if (isset($_REQUEST['search_type'])) { $search_type = $_REQUEST['search_type']; }

	if (isset($_REQUEST['techid'])) { $techid = $_REQUEST['techid']; }
	if (isset($_REQUEST['tech_status'])) { $tech_status = $_REQUEST['tech_status']; }
	if (isset($_REQUEST['create'])) { $create = $_REQUEST['create']; }
	if (isset($_REQUEST['notes'])) { $notes = $_REQUEST['notes']; }

	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	if (isset($_REQUEST['classid'])) { $classid = $_REQUEST['classid']; }
	if (isset($_REQUEST['contactid'])) { $contactid = $_REQUEST['contactid']; }
	if (isset($_REQUEST['bill_to_id'])) { $bill_to_id = $_REQUEST['bill_to_id']; }
	if (isset($_REQUEST['public_notes'])) { $public = $_REQUEST['public_notes']; }
	if (isset($_REQUEST['private_notes'])) { $private = $_REQUEST['private_notes']; }

	if(! empty($notes) && ! empty($service_item_id)) {
		addNotes($notes, $order, $service_item_id, $label);

		header('Location: /service.php?order_type='.$type.'&order_number=' . $order);
	// Add permission to a certain user ipon the create or quote screen
	} else if(! empty($service_item_id) && ($techid || ! empty($tech_status))) {

		editTech($techid, $tech_status, $service_item_id);
		if(! $line_number) {
			header('Location: /service.php?order_type='.$type.'&order_number=' . $order . '&tab=labor');
		} else {
			header('Location: /service.php?order_type='.$type.'&order_number=' . $order . '-' . $line_number . '&tab=labor');
		}

	// Create a quote for the submitted task
	} else if($create == 'quote' || $create == 'save') {
		if(! $order) {
			$order = createQuote($companyid, $contactid, $classid, $bill_to_id, $public, $private);
		}
		$qid = quoteTask($order, $line_number, $qty, $amount, $item_id, $item_label, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $labor_hours, $labor_rate, $expenses, $search, $search_type, $service_item_id);

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
		// If completing a Repair Item
		if (isset($_REQUEST['repair_item_id'])) {
			completeTask($service_item_id, $order, $_REQUEST['repair_code_id'], $GLOBALS['U']['id']);
		} else {
			// Editing a Repair Item
		}

		if(tolower($type) == 'service') {
			// Editing a service task
			editTask($order, $line_number, $qty, $amount, $item_id, $item_label, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $service_item_id);
		}

		if(! $line_number) {
			header('Location: /service.php?order_type='.$type.'&order_number=' . $order . '&tab=labor');
		} else {
			header('Location: /service.php?order_type='.$type.'&order_number=' . $order . '-' . $line_number . '&tab=labor');
		}
	}

	exit;
