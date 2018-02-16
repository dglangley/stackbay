<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearchMeta.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/insertMarket.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSearch.php';

	$DEBUG = 0;
	if ($DEBUG) { print "<pre>".print_r($_REQUEST,true)."</pre>"; }

	/*** HEADER DATA ***/
	$companyid = setCompany();//uses $_REQUEST['companyid'] if passed in
	$contactid = 0;
	if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid'])) { $contactid = trim($_REQUEST['contactid']); }
	$slid = 0;
	if (isset($_REQUEST['slid'])) { $slid = $_REQUEST['slid']; }
	$mode = '';//Buy vs Sell
	if (isset($_REQUEST['mode'])) { $mode = $_REQUEST['mode']; }
	$category = '';//Sale vs Repair
	if (isset($_REQUEST['category'])) { $category = $_REQUEST['category']; }

	if ($category=='Sale') {
		$order_type = $mode;
	} else if ($category=='Repair') {
		if ($mode=='Buy') { $mode = 'repair_sources'; }
		else if ($mode=='Sell') { $mode = 'Repair Quote'; }
	}

	/*** ROWS DATA ***/
	$rows = array();
	if (isset($_REQUEST['rows'])) { $rows = $_REQUEST['rows']; }
	$list_qtys = array();
	if (isset($_REQUEST['list_qtys'])) { $list_qtys = $_REQUEST['list_qtys']; }
	$list_prices = array();
	if (isset($_REQUEST['list_prices'])) { $list_prices = $_REQUEST['list_prices']; }
	$response_qtys = array();
	if (isset($_REQUEST['response_qtys'])) { $response_qtys = $_REQUEST['response_qtys']; }
	$response_prices = array();
	if (isset($_REQUEST['response_prices'])) { $response_prices = $_REQUEST['response_prices']; }
	$searches = array();
	if (isset($_REQUEST['searches'])) { $searches = $_REQUEST['searches']; }

	/*** ITEMS DATA ***/
	$items = array();
	if (isset($_REQUEST['items'])) { $items = $_REQUEST['items']; }
	$item_qtys = array();
	if (isset($_REQUEST['item_qtys'])) { $item_qtys = $_REQUEST['item_qtys']; }
	$item_prices = array();
	if (isset($_REQUEST['item_prices'])) { $item_prices = $_REQUEST['item_prices']; }

	$userid = 0;
	if ($U['id']) { $userid = $U['id']; }
	$metaid = logSearchMeta($companyid,$slid,$now,'',$U['id'],$contactid);

	$T = order_type($mode);
	$order_type = $T['type'];

	foreach ($rows as $ln) {
		if (! is_numeric($ln)) { $ln = 0; }//default in case of corrupt data

		$search = '';
		if (isset($searches[$ln])) { $search = trim($searches[$ln]); }
		$searchid = getSearch($search,'search','id',$userid,$today);

		$ids = array();
		if (isset($items[$ln])) { $ids = $items[$ln]; }

		$list_qty = 1;
		if (isset($list_qtys[$ln])) { $list_qty = $list_qtys[$ln]; }
		$list_price = false;
		if (isset($list_prices[$ln])) { $list_price = $list_prices[$ln]; }

		$first_partid = 0;
		$default_partid = 0;
		$n = 0;
		foreach ($ids as $partid => $isChk) {
			// default if nothing is checked below
			if (! $default_partid) { $default_partid = $partid; }

			if (! $isChk) { continue; }

			// first partid that's checked
			if (! $first_partid) { $first_partid = $partid; }

			$qty = 1;
			if (isset($item_qtys[$ln]) AND isset($item_qtys[$ln][$partid])) { $qty = trim($item_qtys[$ln][$partid]); }
			$price = false;
			if (isset($item_prices[$ln]) AND isset($item_prices[$ln][$partid])) { $price = format_price(trim($item_prices[$ln][$partid]),true,'',true); }

			if ($companyid AND $order_type=='Demand') {
				insertMarket($partid,$list_qty,$list_price,$qty,$price,$metaid,$T['items'],$searchid,$ln);
				$n++;
			}
		}

		if ($companyid AND ($order_type=='Supply' OR $order_type=='Repair Quote')) {
			$response_qty = 0;
			if (isset($response_qtys[$ln])) { $response_qty = trim($response_qtys[$ln]); }
			$response_price = false;
			if (isset($response_prices[$ln])) { $response_price = trim($response_prices[$ln]); }
			if ($response_price>0 AND ! $response_qty) { $response_qty = 1; }

			insertMarket($partid,$list_qty,$list_price,$response_qty,$response_price,$metaid,$T['items'],$searchid,$ln);
		}
	}

	if ($DEBUG) { exit; }

	header('Location: view_quote.php?metaid='.$metaid);
	exit;
?>
