<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrderNumber.php';

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
	$master_order = '';
	if (isset($_REQUEST['order_type']) AND trim($_REQUEST['order_type'])) { $master_order = trim($_REQUEST['order_type']); }

	if (isset($_REQUEST['purchase_request'])) { 
		$order_numbers = ($_REQUEST['purchase_request']); 
	} else {
		$order_numbers = explode(',', $order_number);
	}

	$EDIT = true;

	$items = array();
    $ln = 1;

    $private_notes = 'Purchase Request'.chr(10);

	foreach($order_numbers as $o) {
		$order_type = $master_order;
		$REQUEST = getOrder($o,$order_type);

		// prepare parameters for a new order form, basically
		$T = order_type($order_type);
		$items_table = $T['items'];

		$order_type = $T['order_type'];
		//$_REQUEST['order_type'] = $order_type;

		$T = order_type($order_type);
		$T['items'] = $items_table;
		$T['record_type'] = 'quote';//5-3-18 was 'purchase_request' but caused all sorts of ID problems in order.php

		//Generate $ORDER if there isn't one already created
		foreach ($REQUEST['items'] as $item) {
            $item['line_number'] = $ln;
            $items[($ln++)] = $item;

			$pnote = getNotes($item['item_id_label'],$item['item_id'],$item['id'],1).chr(10);
			if (stristr($private_notes,$pnote)===false) {
	            $private_notes .= $pnote;
			}
        }
	}

	$order_number = 0;
	unset($_REQUEST['order_number']);

	$ORDER = getOrder(0,$order_type);

	$ORDER['created'] = '';
	$ORDER['created_by'] = '';
	$ORDER['sales_rep_id'] = '';
	$ORDER['companyid'] = '';
	$ORDER['contactid'] = '';
	$ORDER['remit_to_id'] = '';
	$ORDER['ship_to_id'] = '';
	$ORDER['freight_carrier_id'] = '';
	$ORDER['freight_services_id'] = '';
	$ORDER['freight_account_id'] = '';
	$ORDER['termsid'] = '';

	$ORDER['private_notes'] = $private_notes;

	$ORDER['items'] = $items;

	include 'order.php';
	exit;
?>
