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
	$list_label = '';
	if (isset($_REQUEST['list_label'])) { $list_label = $_REQUEST['list_label']; }
	$list_type = '';// Sale/Repair/etc
	if (isset($_REQUEST['list_type'])) { $list_type = $_REQUEST['list_type']; }
	$filter_LN = false;
	if (isset($_REQUEST['ln']) AND is_numeric($_REQUEST['ln'])) { $filter_LN = $_REQUEST['ln']; }
	$filter_searchid = false;
	if (isset($_REQUEST['searchid']) AND is_numeric($_REQUEST['searchid'])) { $filter_searchid = $_REQUEST['searchid']; }

	if ($list_type) {
		$T = order_type($list_type);
	} else {
		$T = order_type($list_label);
		$list_type = $T['type'];
	}

	$taskid = 0;
	if ($listid AND ($list_type=='Service' OR $list_type=='Repair')) { $taskid = $listid; }

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

	// helps us determine how to save certain pieces of data like line numbers
	$new_save = true;

	if ($list_type=='Service') {
		$query = "DELETE FROM service_bom WHERE item_id = '".res($taskid)."' AND item_id_label = '".$T['item_label']."'; ";
		$result = qedb($query);
	} else if ($list_type=='Repair') {
//		$query = "DELETE FROM purchase_requests WHERE item_id = '".res($taskid)."' AND item_id_label = '".$T['item_label']."' AND po_number IS NULL; ";
//		$result = qedb($query);
	} else if ($list_type=='Demand' OR $list_type=='Supply') {
		if ($list_label=='slid') {
			// convert to metaid
			$listid = logSearchMeta($companyid,$listid,$now,'',$U['id'],$contactid);
			$list_label = 'metaid';
		} else if ($list_label=='metaid') {
			$new_save = false;//updating existing record, evidenced by presence of metaid

			// update the meta data with companyid and contactid, even if we're updating with same data
			$query = "UPDATE search_meta ";
			$query .= "SET companyid = '".res($companyid)."', contactid = ".fres($contactid)." ";
			$query .= "WHERE id = '".res($listid)."'; ";
			$result = qedb($query);

			// added 12/27/18: user can now switch a list from Demand to Supply and vice versa, meaning that
			// $T['items'] below will reflect the value of the selected option (Supply), but not necessarily
			// the value of the SAVED option (Demand). That is, a list being saved a Supply should delete from
			// Demand if the user is switching it, whereas if the user is NOT switching, Supply would
			// delete from Supply. We will delete from the selected table below, as well as the alternate...

			$query = "DELETE FROM ".$T['items']." WHERE ".$list_label." = '".res($listid)."' ";
			if ($filter_LN!==false) { $query .= "AND line_number = '".res($filter_LN)."' "; }
			if ($filter_searchid!==false) { $query .= "AND searchid = '".res($filter_searchid)."' "; }
			$query .= "; ";
			$result = qedb($query);

			$alt_items = ($list_type=='Demand' ? $alt_items = 'availability' : $alt_items = 'demand');

			$query = "DELETE FROM ".$alt_items." WHERE ".$list_label." = '".res($listid)."' ";
			if ($filter_LN!==false) { $query .= "AND line_number = '".res($filter_LN)."' "; }
			if ($filter_searchid!==false) { $query .= "AND searchid = '".res($filter_searchid)."' "; }
			$query .= "; ";
			$result = qedb($query);

			// check also if this is an uploaded list, and update the table accordingly
			$query = "SELECT type, id FROM uploads WHERE ".$list_label." = '".res($listid)."'; ";
			$result = qedb($query);
			if (qnum($result)>0) {
				$r = qrow($result);

				$query = "UPDATE uploads SET type = '".$T['items']."' WHERE id = '".$r['id']."'; ";
				$result = qedb($query);
			}
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

			if ($companyid AND (($list_type=='Demand' OR ($list_type=='Supply' AND $listid)) OR ($list_type=='Repair Quote' AND $price>0))) {
				$insert_ln = $ln;
				if (! $new_save AND $listid) { $insert_ln--; }

				if ($list_type=='Demand' OR $list_type=='Repair Quote') {
					insertMarket($partid,$list_qty,$list_price,$qty,$price,$listid,$T['items'],$searchid,$insert_ln);
//				} else if ($list_type=='Supply' AND $listid) {
//					insertMarket($partid,$qty,$price,$response_qty,$response_price,$listid,$T['items'],$searchid,$ln);
				}
				$n++;
			}

			$partids[] = $partid;
		}

		if ($list_type=='Repair' AND $default_partid) {
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

		if ((($list_type=='Service' AND $listid) OR ($companyid AND $list_type=='Supply')) AND ($search OR $default_partid)) {//no action here when editing a list
			$lt = false;
			if (isset($leadtime[$ln])) { $lt = trim($leadtime[$ln]); }
			$lt_span = false;
			if (isset($leadtime_span[$ln])) { $lt_span = $leadtime_span[$ln]; }
			$profit_pct = false;

			$items_table = $T['items'];
			if ($items_table=='service_items') {
				$items_table = 'service_bom';
				$F = getItems($items_table);
			} else {
				$F = getItems($list_type);
			}

			if (isset($markup[$ln]) AND array_key_exists('profit_pct',$F)) { $profit_pct = $markup[$ln]; }

//			if (! $listid) {
				$insert_ln = $ln;
				if ($list_type=='Service') {
					$insert_ln = 0;
					$max_ln = 0;
					$query = "SELECT * FROM service_bom WHERE item_id = '".$searchid."' AND item_id_label = 'service_item_id'; ";
					$result = qedb($query);
					while ($r = qrow($result)) {
						if ($default_partid==$r['partid']) { $insert_ln = $r['line_number']-1; }
						if ($r['line_number']>$max_ln) { $max_ln = $r['line_number']; }
					}
					if (! $insert_ln) { $insert_ln = $max_ln; }
				} else if (! $new_save AND $listid) {
					$insert_ln--;
				}

				insertMarket($default_partid,$list_qty,$list_price,$response_qty,$response_price,$listid,$items_table,$searchid,$insert_ln,$lt,$lt_span,$profit_pct);
//			}
		}
	}

	if ($companyid AND $list_type=='WTB' AND $searches_str) {
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
	} else if (! $listid) {
		header('Location: market.php');
	} else {
		header('Location: view_quote.php?listid='.$listid.'&list_label=metaid');
	}
	exit;
?>
