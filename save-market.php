<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearchMeta.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/insertMarket.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getItems.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logRFQ.php';

	$DEBUG = 0;
	if ($DEBUG) { print "<pre>".print_r($_REQUEST,true)."</pre>"; }

	/*** HEADER DATA ***/
	$companyid = setCompany();//uses $_REQUEST['companyid'] if passed in
	$contactid = 0;
	if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid'])) { $contactid = trim($_REQUEST['contactid']); }
	$listid = 0;
	if (isset($_REQUEST['listid']) AND is_numeric($_REQUEST['listid'])) { $listid = $_REQUEST['listid']; }
	$list_type = '';
	if (isset($_REQUEST['list_type'])) { $list_type = $_REQUEST['list_type']; }
	$mode = '';//Buy vs Sell
	if (isset($_REQUEST['mode'])) { $mode = $_REQUEST['mode']; }
	$category = '';//Sale vs Repair
	if (isset($_REQUEST['category'])) { $category = $_REQUEST['category']; }
	$handler = 'List';//List vs WTB vs PR
	if (isset($_REQUEST['handler'])) { $handler = $_REQUEST['handler']; }
	$filter_LN = false;
	if (isset($_REQUEST['ln']) AND is_numeric($_REQUEST['ln'])) { $filter_LN = $_REQUEST['ln']; }
	$filter_searchid = false;
	if (isset($_REQUEST['searchid']) AND is_numeric($_REQUEST['searchid'])) { $filter_searchid = $_REQUEST['searchid']; }

	$slid = 0;
	if ($listid AND $list_type=='slid') { $slid = $listid; }
	$metaid = 0;
	if ($listid AND $list_type=='metaid') { $metaid = $listid; }
	$taskid = 0;
	if ($listid AND ($list_type=='Service' OR $list_type=='Repair')) { $taskid = $listid; }

	if ($category=='Sale') {
		$order_type = $mode;
	} else if ($category=='Repair') {
		if ($list_type=='Repair') { $mode = 'purchase_request'; }
		else if ($mode=='Buy') { $mode = 'repair_sources'; }
		else if ($mode=='Sell') { $mode = 'Repair Quote'; }
	} else if ($category=='Service') {
		$mode = 'service_bom';
		$order_type = $mode;
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
	$leadtime = array();
	if (isset($_REQUEST['leadtime'])) { $leadtime = $_REQUEST['leadtime']; }
	$leadtime_span = array();
	if (isset($_REQUEST['leadtime_span'])) { $leadtime_span = $_REQUEST['leadtime_span']; }
	$markup = array();
	if (isset($_REQUEST['markup'])) { $markup = $_REQUEST['markup']; }

	/*** ITEMS DATA ***/
	$items = array();
	if (isset($_REQUEST['items'])) { $items = $_REQUEST['items']; }
	$item_qtys = array();
	if (isset($_REQUEST['item_qtys'])) { $item_qtys = $_REQUEST['item_qtys']; }
	$item_prices = array();
	if (isset($_REQUEST['item_prices'])) { $item_prices = $_REQUEST['item_prices']; }

	$searches_str = '';
	$partids = array();

	$userid = 0;
	if ($U['id']) { $userid = $U['id']; }

	$T = order_type($mode);
	$order_type = $T['type'];

	if ($handler=='List') {
		if ($list_type=='Service') {
			$query = "DELETE FROM service_bom WHERE item_id = '".res($taskid)."' AND item_id_label = 'service_item_id'; ";
			$result = qedb($query);
		} else if ($list_type=='Repair') {
//			$query = "DELETE FROM purchase_requests WHERE item_id = '".res($taskid)."' AND item_id_label = 'repair_item_id' AND po_number IS NULL; ";
//			$result = qedb($query);
		} else if (! $metaid) {
			$metaid = logSearchMeta($companyid,$slid,$now,'',$U['id'],$contactid);
		} else {
			// update the meta data with companyid and contactid, even if we're updating with same data
			$query = "UPDATE search_meta ";
			$query .= "SET companyid = '".res($companyid)."', contactid = ".fres($contactid)." ";
			$query .= "WHERE id = '".res($metaid)."'; ";
			$result = qedb($query);

			$query = "DELETE FROM ".$T['items']." WHERE metaid = '".res($metaid)."' ";
			if ($filter_LN!==false) { $query .= "AND line_number = '".res($filter_LN)."' "; }
			if ($filter_searchid!==false) { $query .= "AND searchid = '".res($filter_searchid)."' "; }
			$query .= "; ";
			$result = qedb($query);
		}
	}

	foreach ($rows as $ln) {
		if (! is_numeric($ln)) { $ln = 0; }//default in case of corrupt data

		$list_qty = 1;
		if (isset($list_qtys[$ln])) { $list_qty = $list_qtys[$ln]; }

		$search = '';
		if (isset($searches[$ln])) {
			$search = trim($searches[$ln]);

			if ($search) {
				$searches_str .= $search.' '.$list_qty.'<br/>';
			}
		}

		if ($list_type=='Service' OR $list_type=='Repair') {
			$searchid = $listid;
		} else {
			if ($filter_searchid!==false) { $searchid = $filter_searchid; }
			else { $searchid = getSearch($search,'search','id',$userid,$today); }
		}

		$ids = array();
		if (isset($items[$ln])) { $ids = $items[$ln]; }

		$list_price = false;
		if (isset($list_prices[$ln])) { $list_price = $list_prices[$ln]; }

		$response_qty = 0;
		if (isset($response_qtys[$ln])) { $response_qty = trim($response_qtys[$ln]); }
		$response_price = false;
		if (isset($response_prices[$ln])) { $response_price = trim($response_prices[$ln]); }
		if ($response_price>0 AND ! $response_qty) { $response_qty = 1; }

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

			if ($companyid AND (($order_type=='Demand' OR ($order_type=='Supply' AND $listid)) OR ($order_type=='Repair Quote' AND $price>0)) AND $handler=='List') {
				$insert_ln = $ln;
				if ($list_type=='metaid' AND $listid) { $insert_ln--; }

				if ($order_type=='Demand' OR $order_type=='Repair Quote') {
					insertMarket($partid,$list_qty,$list_price,$qty,$price,$metaid,$T['items'],$searchid,$insert_ln);
//				} else if ($order_type=='Supply' AND $listid) {
//					insertMarket($partid,$qty,$price,$response_qty,$response_price,$metaid,$T['items'],$searchid,$ln);
				}
				$n++;
			}

			$partids[] = $partid;
		}

		if ($list_type=='Repair' AND $handler=='List' AND $default_partid) {
			$actives = 0;
			$query = "SELECT id, status FROM purchase_requests WHERE item_id = '".res($taskid)."' AND item_id_label = 'repair_item_id' ";
			$query .= "AND po_number IS NULL ";//AND (status = 'Active' OR status IS NULL) ";
			$query .= "AND (line_number IS NULL OR line_number = '0' ";
			if ($ln) { $query .= "OR line_number = '".$ln."' "; }
			$query .= "); ";
			$result = qedb($query);
			$num_requests = qnum($result);
			while ($r = qrow($result)) {
				if (! $r['status'] OR $r['status']=='Active') {
					$query2 = "DELETE FROM purchase_requests WHERE id = '".$r['id']."'; ";
					$result2 = qedb($query2);
					$actives++;
				}
			}

			// if no purchase requests exist at all, add it; or if at least one active request exists, it's deleted above so re-add
			if ($num_requests==0 OR $actives>0) {
				$insert_ln = $ln-1;
				insertMarket($default_partid,$list_qty,$GLOBALS['U']['id'],$GLOBALS['now'],'',false,$T['items'],$taskid,$insert_ln);
			}
		}

		//if ($companyid AND ($order_type=='Supply' OR $order_type=='Repair Quote') AND $handler=='List') {
		if ((($order_type=='service_bom' AND $listid) OR ($companyid AND $order_type=='Supply')) AND $handler=='List' AND ($search OR $default_partid)) {//no action here when editing a list
			$lt = false;
			if (isset($leadtime[$ln])) { $lt = trim($leadtime[$ln]); }
			$lt_span = false;
			if (isset($leadtime_span[$ln])) { $lt_span = $leadtime_span[$ln]; }
			$profit_pct = false;

			$F = getItems($order_type);
			if (isset($markup[$ln]) AND array_key_exists('profit_pct',$F)) { $profit_pct = $markup[$ln]; }

//			if (! $listid) {
				$insert_ln = $ln;
				if ($list_type=='Service' OR ($list_type=='metaid' AND $listid)) { $insert_ln--; }

				insertMarket($partid,$list_qty,$list_price,$response_qty,$response_price,$metaid,$T['items'],$searchid,$insert_ln,$lt,$lt_span,$profit_pct);
//			}
		}
	}

	if ($companyid AND $handler=='WTB' AND $searches_str) {
		include_once $_SERVER["ROOT_DIR"].'/inc/sendCompanyRFQ.php';

		$message_body = 'Please quote:<br/><br/>'.$searches_str;
		$sbj = 'WTB '.date('n/j/y ga');

		if ($DEBUG) {
			echo $message_body.'<BR>';
		} else {
			sendCompanyRFQ($companyid,$message_body,$sbj,$contactid);
		}

		foreach($partids as $partid) {
			$rfqid = logRFQ($partid,$companyid);
		}
	}

	if ($DEBUG) { exit; }

	if (($list_type=='Service' OR $list_type=='Repair') AND $listid) {
		header('Location: service.php?order_type='.$list_type.'&taskid='.$listid.'&tab=materials');
	} else if (! $metaid) {
		header('Location: market.php');
	} else {
		header('Location: view_quote.php?metaid='.$metaid);
	}
	exit;
?>
