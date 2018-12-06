<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrderNumber.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getTerms.php';

	function getNotes($label,$ref,$id,$n) {
		$notes = '';

		//if (! strstr($id,'NEW')) {
		if ($id) {
			if (strstr($label,'item_id')) {
				$T2 = order_type($label);
				$ref_order = getOrderNumber($ref,$T2['items'],$T2['order']);

				$notes = $T2['abbrev'].'# '.$ref_order;
			}
		}

		return ($notes);
	}

	$order_number = 0;
	if (isset($_REQUEST['order_number']) AND trim($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	$order_type = '';
	if (isset($_REQUEST['order_type']) AND trim($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }
	$taskid = array();
	if (isset($_REQUEST['taskid']) AND $_REQUEST['taskid']) { $taskid = $_REQUEST['taskid']; }
	$task_label = '';
	if (isset($_REQUEST['task_label']) AND trim($_REQUEST['task_label'])) { $task_label = trim($_REQUEST['task_label']); }

	$EDIT = true;
	$create_order = 'Sale';

	$items = array();
    $ln = 1;

    $rtv_notes = 'PO '.$order_number;
	$private_notes = 'RTV for '.$rtv_notes;
	$_REQUEST['ref_1_label'] = 'purchase_item_id';

	$ORDER = getOrder($order_number,$order_type);
	$companyid = $ORDER['companyid'];
	$billtoid = $ORDER['remit_to_id'];
//	$ORDER['order_type'] = $order_type;

	// get parameters related to originating order
	$T = order_type($order_type);
	$items_table = $T['items'];

	$order_type = 'Sale';
	$T = order_type($order_type);
	// we need this to get originating data from purchase items table later on
	$T['items'] = $items_table;
	$T['record_type'] = 'rtv';

	//Generate $ORDER if there isn't one already created
	foreach ($ORDER['items'] as $item) {
		if (! $taskid[$item['id']]) { continue; }

		$item['price'] = 0;
//		$item['ref_1'] = $item['id'];
//		$item['ref_1_label'] = 'purchase_item_id';
		$item['line_number'] = $ln;
		$items[($ln++)] = $item;

		$private_notes .= getNotes($item['item_id_label'],$item['item_id'],$item['id'],1) . "\n";
	}

	// reset
	$order_number = 0;
	unset($_REQUEST['order_number']);
	$_REQUEST['order_type'] = 'Sale';

	$ORDER = getOrder(0,$order_type);

	$ORDER['created'] = '';
	$ORDER['created_by'] = '';
	$ORDER['sales_rep_id'] = '';
	$ORDER['companyid'] = $companyid;
	$ORDER['contactid'] = '';
	$ORDER['addressid'] = $billtoid;
	$ORDER['ship_to_id'] = '';
	$ORDER['freight_carrier_id'] = '';
	$ORDER['freight_services_id'] = '';
	$ORDER['freight_account_id'] = '';
	$ORDER['termsid'] = getTerms('N/A');

	$ORDER['public_notes'] = $rtv_notes;
	$ORDER['private_notes'] = $private_notes;

	$ORDER['items'] = $items;

	include 'order.php';
	exit;
?>
