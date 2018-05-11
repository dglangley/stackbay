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

	function deleteQuote($id) {
		$query = "DELETE FROM service_quote_outsourced WHERE id=".res($id).";";
		qedb($query);
	}

	$taskid = '';
	if (isset($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }
	$type = '';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }

	$order_number = '';
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }

	$delete = '';
	if (isset($_REQUEST['delete'])) { $delete = trim($_REQUEST['delete']); }

	$T = order_type($type);

	// Outsourced Quote Deletion
	if($delete AND $type=='service_quote') {
		deleteQuote($delete);

		header('Location: /quoteNEW.php?taskid=' . $taskid . '&tab=outside' . ($ALERT?'&ALERT='.$ALERT:''));
		exit;
	}

	$quoteImport = array();
	if (isset($_REQUEST['quoteImport'])) { $quoteImport = $_REQUEST['quoteImport']; }

	if(! empty($quoteImport)) {
		convertRedirect($quoteImport, $taskid, $order_number);
	}

	header('Location: /serviceNEW.php?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=outside' . ($ALERT?'&ALERT='.$ALERT:''));

	exit;
