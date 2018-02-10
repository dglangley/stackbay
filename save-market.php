<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearchMeta.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/insertMarket.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSearch.php';

	$DEBUG = 3;
	if ($DEBUG) { print "<pre>".print_r($_REQUEST,true)."</pre>"; }

	/*** HEADER DATA ***/
	$companyid = setCompany();//uses $_REQUEST['companyid'] if passed in
	$contactid = 0;
	if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid'])) { $contactid = trim($_REQUEST['contactid']); }
	$slid = 0;
	if (isset($_REQUEST['slid'])) { $slid = $_REQUEST['slid']; }
	$mode = '';
	if (isset($_REQUEST['mode'])) { $mode = $_REQUEST['mode']; }

	/*** ROWS DATA ***/
	$rows = array();
	if (isset($_REQUEST['rows'])) { $rows = $_REQUEST['rows']; }
	$list_qtys = array();
	if (isset($_REQUEST['list_qtys'])) { $list_qtys = $_REQUEST['list_qtys']; }
	$list_prices = array();
	if (isset($_REQUEST['list_prices'])) { $list_prices = $_REQUEST['list_prices']; }
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
	$metaid = logSearchMeta($companyid,$slid);

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

		$response_qty = 0;
		$response_price = false;

		$n = 0;
		foreach ($ids as $partid => $isChk) {
			if (! $isChk) { continue; }

			$qty = trim($item_qtys[$partid]);
			$price = format_price(trim($item_prices[$partid]),true,'',true);

			if ($companyid AND ($order_type=='Demand' OR ($order_type=='Supply' AND $n==0 AND $response_qty>0))) {
				insertMarket($partid,$list_qty,$list_price,$qty,$price,$metaid,$T['items'],$searchid,$ln);
				$n++;
			}
		}
	}

	if ($DEBUG) { exit; }

	header('Location: view_quote.php?order_type='.$order_type.'&order_number='.$slid);
	exit;
?>
