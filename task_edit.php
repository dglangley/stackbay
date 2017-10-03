<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

	function editTask($order, $companyid, $bid, $contactid, $addressid, $public_notes, $private_notes, $partid, $quote_hours, $quote_rate, $mileage_rate, $tax_rate){
		$now = $GLOBALS['now'];
		
		$query = "UPDATE repair_orders SET companyid = ".prep($companyid).", cust_ref = ".prep($bid).", bill_to_id = ".prep($addressid).", public_notes = ".prep($public_notes).", private_notes = ".prep($private_notes).", contactid = ".prep($contactid)." WHERE ro_number = ".prep($order).";";
		qdb($query) OR die(qe() . ' ' . $query);
	}

	function quoteTask($order, $companyid, $bid, $contactid, $addressid, $public_notes, $private_notes, $partid, $quote_hours, $quote_rate, $mileage_rate, $tax_rate){
		$quoteid = 0;
		$now = $GLOBALS['now'];

		$query = ";";
		qdb($query) OR die(qe().' '.$query);
		$quoteid = qid();

		return $quoteid;
	}

	function quotedMaterials($partid, $qty, $price, $quoteid){
		$now = $GLOBALS['now'];

		$query = ";";
		qdb($query) OR die(qe().' '.$query);
	}

	function quotedExpenses($partid, $qty, $price, $quoteid){
		$now = $GLOBALS['now'];
		
		$query = ";";
		qdb($query) OR die(qe().' '.$query);
	}

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

	//print '<pre>' . print_r($_REQUEST, true). '</pre>';
	
	$order = 0;
	$type = '';
	$companyid = 0;
	$bid = '';
	$contactid = 0;
	$addressid = 0;
	$public_notes = '';
	$private_notes = '';
	$partid = 0;
	$quote_hours = 0;
	$quote_rate = 0;
	$mileage_rate = 0;
	$tax_rate = 0;

	$techid = 0;
	$item_id = 0;
	$tech_status = '';

		
	if (isset($_REQUEST['order'])) { $order = $_REQUEST['order']; }
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	if (isset($_REQUEST['bid'])) { $bid = $_REQUEST['bid']; }
	if (isset($_REQUEST['contactid'])) { $contactid = $_REQUEST['contactid']; }
	if (isset($_REQUEST['addressid'])) { $addressid = $_REQUEST['addressid']; }
	if (isset($_REQUEST['public_notes'])) { $public_notes = $_REQUEST['public_notes']; }
	if (isset($_REQUEST['private_notes'])) { $private_notes = $_REQUEST['private_notes']; }
	if (isset($_REQUEST['quote_hours'])) { $quote_hours = $_REQUEST['quote_hours']; }
	if (isset($_REQUEST['quote_rate'])) { $quote_rate = $_REQUEST['quote_rate']; }
	if (isset($_REQUEST['mileage_rate'])) { $mileage_rate = $_REQUEST['mileage_rate']; }
	if (isset($_REQUEST['tax_rate'])) { $tax_rate = $_REQUEST['tax_rate']; }
	if (isset($_REQUEST['partid'])) { $partid = $_REQUEST['partid']; }

	if (isset($_REQUEST['techid'])) { $techid = $_REQUEST['techid']; }
	if (isset($_REQUEST['tech_status'])) { $tech_status = $_REQUEST['tech_status']; }
	if (isset($_REQUEST['item_id'])) { $item_id = $_REQUEST['item_id']; }

	if(! empty($item_id) && ($techid || ! empty($tech_status))) {
		editTech($techid, $tech_status, $item_id);
		header('Location: /task_view.php?type='.$type.'&order=' . $order . '&tab=labor');
	} else {
		editTask($order, $companyid, $bid, $contactid, $addressid, $public_notes, $private_notes, $partid, $quote_hours, $quote_rate, $mileage_rate, $tax_rate);
		header('Location: /task_view.php?type='.$type.'&order=' . $order);
	}

	exit;
