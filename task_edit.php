<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

	// function editTask($order, $companyid, $bid, $contactid, $addressid, $public_notes, $private_notes, $partid, $quote_hours, $quote_rate, $mileage_rate, $tax_rate){
	// 	$now = $GLOBALS['now'];
		
	// 	$query = "UPDATE repair_orders SET companyid = ".prep($companyid).", cust_ref = ".prep($bid).", bill_to_id = ".prep($addressid).", public_notes = ".prep($public_notes).", private_notes = ".prep($private_notes).", contactid = ".prep($contactid)." WHERE ro_number = ".prep($order).";";
	// 	qdb($query) OR die(qe() . ' ' . $query);
	// }

	function quoteTask($quoteid, $line_number, $qty, $amount, $item_id, $item_label, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $labor_hours, $labor_rate, $expenses){
		global $LINE_NUMBER;

		$id = 0;

		// Set line number automatically
		// Search for the largest line_number for current quoteid
		if(empty($line_number)) {
			$query = "SELECT line_number FROM service_quote_items WHERE quoteid = ".res($quoteid)." ORDER BY line_number DESC LIMIT 1;";
			$result = qdb($query) OR die(qe().' '.$query);

			// Get the largest line_number if exists and increment by 1
			if(mysqli_num_rows($result)){
				$r = mysqli_fetch_assoc($result);

				$line_number = $r['line_number'] + 1;
			}
		}

		$query = "REPLACE INTO service_quote_items (quoteid, line_number, qty, amount, item_id, item_label, description, ref_1, ref_1_label, ref_2, ref_2_label, labor_hours, labor_rate, expenses";
		if($item_id) { $query .= " ,id"; }
		$query .= ") VALUES (".fres($quoteid).", ".fres($line_number).", ".fres($qty).", ".fres($amount).", ".fres($item_id).", ".fres($item_label).", ".fres($description).", ".fres($ref_1).", ".fres($ref_1_label).", ".fres($ref_2).", ".fres($ref_2_label).", ".fres($labor_hours).", ".fres($labor_rate).", ".fres($expenses);
		if($item_id) { $query .= ", " . fres($item_id); }
		$query .= ");";

		qdb($query) OR die(qe().' '.$query);
		$id = qid();

		$LINE_NUMBER = $line_number;

		return $id;
	}

	function quotedMaterials($materials, $quoteid){
		// Get the ID of all the currently / existing quoted items
		$quotedItems = array();

		if(! empty($materials)){
			foreach($materials as $partid => $data) {
				foreach($data as $line) {

					$query = "REPLACE INTO service_quote_materials (quote_item_id, partid, qty, amount, leadtime, leadtime_span, profit_pct, quote";
					if($line['quoteid']) { $query .= " ,id"; }
					$query .= ") VALUES (".fres($quoteid).", ".fres($partid).", ".fres($line['qty']).", ".fres($line['amount']).", ".fres($line['leadtime']).", ".fres(ucwords($line['lead_span'])).", ".fres($line['profit']).", ".fres($line['quote']);
					if($line['quoteid']) { $query .= ", " . fres($line['quoteid']); }
					$query .= ");";
					//echo $query;
					qdb($query) OR die(qe().' '.$query);

					$quotedItems[] = qid();
				}
			}

			// DELETE all records that did not get pushed back up into the update
			$query = "DELETE FROM service_quote_materials WHERE id NOT IN ('".join("','", $quotedItems)."') AND quote_item_id = ".fres($quoteid).";";
			qdb($query) OR die(qe().' '.$query);
		}
	}

	function quotedOutsource($outsourced, $quoteid){
		// Get the ID of all the currently / existing quoted items
		$quotedItems = array();

		if(! empty($outsourced)){
			foreach($outsourced as $line) {
				//foreach($line_item as $line) {
					if($line['companyid']) {
						$query = "REPLACE INTO service_quote_outsourced(quote_item_id, companyid, description, amount";
						if($line['quoteid']) { $query .= " ,id"; }
						$query .= ") VALUES (".fres($quoteid).", ".fres($line['companyid']).", ".fres($line['description']).", ".fres($line['amount']);
						if($line['quoteid']) { $query .= ", " . fres($line['quoteid']); }
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

	// function quotedExpenses($partid, $qty, $price, $quoteid){
	// 	$now = $GLOBALS['now'];
		
	// 	$query = ";";
	// 	qdb($query) OR die(qe().' '.$query);
	// }

	function quotedOutsideServices($partid, $qty, $price, $quoteid){
		$now = $GLOBALS['now'];
		
		$query = ";";
		qdb($query) OR die(qe().' '.$query);
	}

	function editTech($techid, $status, $item_id) {
		if(! empty($status)) {
			$query = "DELETE FROM service_assignments WHERE userid = ".res($status)." AND service_item_id = ".res($item_id).";";
			qdb($query) OR die(qe() . ' ' . $query);
		} else {
			// Check first if the user has already been assigned to this job
			$query = "SELECT * FROM service_assignments WHERE service_item_id = ".res($item_id)." AND userid = ".res($techid).";";
			$result = qdb($query) OR die(qe() . ' ' . $query);

			if(mysqli_num_rows($result) == 0) {
				$query = "INSERT INTO service_assignments (service_item_id, userid) VALUES (".res($item_id).", ".res($techid).");";
				qdb($query) OR die(qe() . ' ' . $query);
			}
		}
	}

	// print '<pre>' . print_r($_REQUEST, true). '</pre>';

	//$quoteid = 1; 
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

	$techid = 0;
	$tech_status = '';

	$LINE_NUMBER = 1;

		
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }

	//if (isset($_REQUEST['quoteid'])) { $quoteid = $_REQUEST['quoteid']; }
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

	if (isset($_REQUEST['techid'])) { $techid = $_REQUEST['techid']; }
	if (isset($_REQUEST['tech_status'])) { $tech_status = $_REQUEST['tech_status']; }

	if (isset($_REQUEST['create'])) { $create = $_REQUEST['create']; }

	// Add permission to a certain user ipon the create or quote screen
	if(! empty($item_id) && ($techid || ! empty($tech_status))) {
		editTech($techid, $tech_status, $item_id);
		header('Location: /service.php?order_type='.$type.'&order_number=' . $order . '&tab=labor');
	// Create a quote for the submitted task
	} else if($create == 'quote' || $create == 'save') {
		$qid = quoteTask($order, $line_number, $qty, $amount, $item_id, $item_label, $ref_1, $ref_1_label, $ref_2, $ref_2_label, $labor_hours, $labor_rate, $expenses);
		quotedMaterials($materials, $qid);
		quotedOutsource($outsourced, $qid);

		header('Location: /quote.php?order_number=' . $order .'-'. $LINE_NUMBER);
	} 
	else {
		//editTask($order, $companyid, $bid, $contactid, $addressid, $public_notes, $private_notes, $partid, $quote_hours, $quote_rate, $mileage_rate, $tax_rate);
		header('Location: /service.php?order_type='.$type.'&order_number=' . $order);
	}

	exit;