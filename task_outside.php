<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$DEBUG = 0;
	$ALERT = '';

	// build what is needed in manage_outside to generate the lines and create the order
	function convertRedirect($quoteImport, $taskid, $order_number) {
		global $ALERT, $T;

		$quotes = '';

		foreach($quoteImport as $quoteid) {
			$quotes .= '&os_quote_id[]='.$quoteid;
		}

		header('Location: /manage_outsourced.php?order_type='.$T['type'].'&order_number='.$order_number.'&taskid='.$taskid.'&ref_2='.$taskid.'&ref_2_label='.$T['item_label'].$quotes);
		exit;
	}

	function editOutsourced($taskid, $companyid, $description, $amount, $quote, $id = 0) {
		global $T;

		$query = "REPLACE INTO service_quote_outsourced ( quote_item_id, companyid, description, amount, quote";
		if($id) { $query .= ", id"; }
		$query .= ") VALUES (".res($taskid).", ".fres($companyid).", ".fres($description).", ".fres($amount).", ".fres($quote);
		if($id) { $query .= ", ".res($id); }
		$query .= ");";

		qedb($query);
	}

	function deleteQuote($id) {
		$query = "DELETE FROM service_quote_outsourced WHERE id=".res($id).";";
		qedb($query);
	}

	if(! $EDIT) {
		$taskid = '';
		if (isset($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }
		$type = '';
		if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }

		$order_number = '';
		if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	}
	
	$delete = '';
	if (isset($_REQUEST['delete'])) { $delete = trim($_REQUEST['delete']); }

	$T = order_type($type);

	// Outsourced Quote Deletion
	if($delete AND $type=='service_quote') {
		deleteQuote($delete);

		header('Location: /quoteNEW.php?taskid=' . $taskid . '&tab=outside' . ($ALERT?'&ALERT='.$ALERT:''));
		exit;
	}

	// Outsourced Quote Creation
	if($type=='service_quote') {
		$companyid = '';
		if (isset($_REQUEST['companyid'])) { $companyid = trim($_REQUEST['companyid']); }
		$description = '';
		if (isset($_REQUEST['os_description'])) { $description = trim($_REQUEST['os_description']); }
		$amount = '';
		if (isset($_REQUEST['amount'])) { $amount = trim($_REQUEST['amount']); }
		$quote = '';
		if (isset($_REQUEST['quote'])) { $quote = trim($_REQUEST['quote']); }

		editOutsourced($taskid, $companyid, $description, $amount, $quote, $id = 0);

		// Bypass all redirects 
		if(! $EDIT) {
			header('Location: /quoteNEW.php?taskid=' . $taskid . '&tab=outside' . ($ALERT?'&ALERT='.$ALERT:''));
			exit;
		}
	// else at this point the only usage for this form post is to convert over the order
	} else {
		$quoteImport = array();
		if (isset($_REQUEST['quoteImport'])) { $quoteImport = $_REQUEST['quoteImport']; }

		if(! empty($quoteImport)) {
			convertRedirect($quoteImport, $taskid, $order_number);
		}
	}

	if(! $EDIT) {

		// Responsive Testing
		$responsive = false;
		if (isset($_REQUEST['responsive'])) { $responsive = trim($_REQUEST['responsive']); }

		$link = '/service.php';

		if($responsive) {
			$link = '/responsive_task.php';
		} 

		header('Location: '.$link.'?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=outside' . ($ALERT?'&ALERT='.$ALERT:''));

		// header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=outside' . ($ALERT?'&ALERT='.$ALERT:''));
		exit;
	}
