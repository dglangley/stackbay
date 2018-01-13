<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$DEBUG = 0;

	if ($DEBUG) { print "<pre>".print_r($_REQUEST,true)."</pre>"; }
	$order_number = 0;
	$order_type = '';
	$change_type = '';
	$line_item_id = 0;
	$quote_item_id = 0;
	$new_item = false;
	$new_order = false;
	$charge = '';
	$description = '';
	$items = array();
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	if (isset($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }
	if (isset($_REQUEST['change_type'])) { $change_type = trim($_REQUEST['change_type']); }
	if (isset($_REQUEST['line_item_id'])) { $line_item_id = trim($_REQUEST['line_item_id']); }
	if (isset($_REQUEST['new_item'])) { $new_item = trim($_REQUEST['new_item']); }
	if (isset($_REQUEST['new_order'])) { $new_order = trim($_REQUEST['new_order']); }
	if (isset($_REQUEST['charge'])) { $charge = trim($_REQUEST['charge']); }
	if (isset($_REQUEST['description'])) { $description = trim($_REQUEST['description']); }
	if (isset($_REQUEST['quote_item_id'])) { $quote_item_id = trim($_REQUEST['quote_item_id']); }

	// taking a quoted "change order" to a true change order by virtue of attaching the quote
	// to the service order/item that it's referencing
	if ($quote_item_id) {
		$query = "SELECT si.so_number, si.id service_item_id, qi.* FROM service_quote_items qi, service_items si ";
		$query .= "WHERE qi.id = '".res($quote_item_id)."' AND qi.ref_2 = si.quote_item_id AND qi.ref_2_label = 'service_quote_item_id'; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) {
			die("Could not find valid quote item or its parent service task for ".$quote_item_id.", please see Admin.");
		}
		$r = mysqli_fetch_assoc($result);

		$order_number = $r['so_number'];
		$order_type = 'Service';
		$new_item = 1;
		$line_item_id = $r['service_item_id'];
		$items = array(
			'so_number' => $r['so_number'],
			'qty' => $r['qty'],
			'amount' => $r['amount'],
			'item_id' => $r['item_id'],
			'item_label' => $r['item_label'],
			'quote_item_id' => $r['id'],
			'description' => $r['description'],
			'ref_1' => $r['ref_1'],
			'ref_1_label' => $r['ref_1_label'],
			'ref_2' => $r['service_item_id'],
			'ref_2_label' => 'service_item_id',
		);
	}

	$T = order_type($order_type);

	$taskid = 0;
	$max_ln = 0;
	$query = "SELECT * FROM ".$T['items']." WHERE ";
	if ($order_number) { $query .= $T['order']." = '".res($order_number)."' "; }
	else if ($line_item_id) { $query .= "id = '".res($line_item_id)."' "; }
	$query .= "; ";
	$result = qedb($query);
	if (mysqli_num_rows($result)==0) {
		die('Uh oh: '.$query);
	}
	while ($r = mysqli_fetch_assoc($result)) {
		if ($r['id']==$line_item_id AND ! $quote_item_id) {
			$items = $r;
		} else if ($r['ref_2_label']==$T['item_label'] AND $r['ref_2']) {
			if ($r['task_name']>$max_ln) { $max_ln = $r['task_name']; }
		}
	}
	$max_ln++;

	// user has decided for a new line item
	if ($new_item) {
		$query = "INSERT INTO ".$T['items']." (";
		$item_query = "";
		$val_query = "";
		foreach ($items as $item => $val) {
			if ($item_query) { $item_query .= ", "; }
			$item_query .= $item;

			if ($val_query) { $val_query .= ", "; }
			// these are fields we want to override and not reproduce from the items table from the query above
			if ($item=='id' OR $item=='line_number' OR $item=='labor_hours' OR $item=='labor_rate') { $val = ''; }
			else if ($item==$T['amount'] AND $change_type=='Internal') { $val = '0.00'; }
			else if ($item==$T['amount']) { $val = $charge; }
			else if ($item=='ref_2') { $val = $line_item_id; }
			else if ($item=='ref_2_label') { $val = $T['item_label']; }
			else if ($item=='qty') { $val = 1; }
			else if ($item==$T['description']) { $val = $description; }
			else if ($item=='task_name') { $val = $max_ln; }
			$val_query .= fres($val);
		}
		$query .= $item_query.") VALUES (".$val_query."); ";
		$result = qedb($query);
		$taskid = qid();
	} else if ($new_order) {
		// insert new order
	}

	if ($DEBUG) { exit; }

	//header('Location: edit_order.php?order_type='.$order_type.'&order_number='.$order_number);
	if ($taskid) {
		if ($order_type=='service_quote') {
			header('Location: quote.php?order_type='.$order_type.'&taskid='.$taskid);
		} else {
			header('Location: service.php?order_type='.$order_type.'&taskid='.$taskid);
		}
	} else {
		header('Location: service.php?order_type='.$order_type.'&order_number='.$order_number.'-'.$items['line_number']);
	}
	exit;
?>
