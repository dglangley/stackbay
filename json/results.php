<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/cmp.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/searchRemotes.php';

	$urls = array(
		'te'=>'www.tel-explorer.com/Main_Page/Search/Part_srch_go.php?part=',
		'ps'=>'www.powersourceonline.com/iris-item-search.authenticated-en.jsa?Q=',
		'bb'=>'members.brokerbin.com/main.php?loc=partkey&clm=partclei&parts=',
		'et'=>'http://www.excel-telco.com/inventory?searchcriteria=',
		'ebay'=>'ebay.com/itm/',
	);
	function setSource($src,$search) {
		global $urls;

		if (is_numeric($src) AND strlen($src)==12) {//ebay ids are 12-chars
			$search = $src;
			$src = 'ebay';
		}

		return (array('source'=>$src,'ln'=>$urls[$src].$search));
	}

	if (! isset($_REQUEST['partids'])) { jsonDie("No partids"); }

	// 0=first attempt, get static results from db; 1=second attempt, go get remote data from api's
	$attempt = 0;
	$max_ln = 2;//when to stop forcing download of fresh results from remotes
	if (isset($_REQUEST['attempt']) AND is_numeric($_REQUEST['attempt'])) { $attempt = $_REQUEST['attempt']; }
	$ln = 0;
	if (isset($_REQUEST['ln']) AND is_numeric($_REQUEST['ln'])) { $ln = $_REQUEST['ln']; }
	$category = 'Sale';
	if (isset($_REQUEST['category'])) { $category = $_REQUEST['category']; }

	$summary_past = format_date($today,'Y-m-01',array('m'=>-11));

	$partids = $_REQUEST['partids'];
	$type = '';
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }
	$pricing = 0;
	if (isset($_REQUEST['pricing'])) { $pricing = $_REQUEST['pricing']; }

	if ($category=='Repair') {
		if ($type=='Supply') { $type = 'repair_sources'; }
		else if ($type=='Purchase') { $type = 'Outsourced'; }
		else if ($type=='Sale') { $type = 'Repair'; }
		else if ($type=='Demand') { $type = 'Repair Quote'; }
	}

	$max_results = 10;
	if ($pricing) { $max_results = 9999; }

	$T = order_type($type);

	$done = 1;
	if ($type=='Supply') {
		$done = searchRemotes($partids,$attempt,$ln,$max_ln);
	}

	$prev_price = array();
	$dates = array();
	$recent_date = format_date($today,'Y-m-d 00:00:00',array('d'=>-7));
	$old_date = format_date($today,'Y-m-01 00:00:00',array('m'=>-11));
	$res = array();
	$query = "SELECT name, companyid, ".$T['datetime']." date, (".$T['qty'].") qty, ".$T['amount']." price, '0' past_price, ";
	$query .= "t.".$T['order']." order_number, '".$T['abbrev']."' abbrev, ";
	if ($type=='Outsourced') { $query .= "t.item_id partid, "; } else { $query .= "t.partid, "; }
	if ($type=='Supply' OR $type=='Demand' OR $type=='Repair Quote') { $query .= "searchlistid slid, 'Active' status, searchid, "; } else { $query .= "'' slid, o.status, '' searchid, "; }
	if ($type=='Supply') { $query .= "source "; } else { $query .= "'' source "; }
	$query .= "FROM ".$T['items']." t, ".$T['orders']." o, companies c ";
	$query .= "WHERE ";
	if ($type=='Outsourced') { $query .= "t.item_label = 'partid' AND t.item_id "; } else { $query .= "partid "; }
	$query .= "IN (".$partids.") AND ".$T['qty']." > 0 ";
	if ($type=='Supply') {
		$query .= "AND c.id NOT IN (1118,669,2381,473,1125,1034,3053,1184,589) ";
	}
	if ($pricing) { $query .= "AND ".$T['amount']." > 0 "; }
	$query .= "AND t.".$T['order']." = o.".str_replace('meta','',$T['order'])." AND companyid = c.id ";
	if ($pricing) {
//		$query .= "GROUP BY t.".$T['order'].", ".$T['amount']." ";
	} else {
//		$query .= "GROUP BY companyid, LEFT(".$T['datetime'].",10), ".$T['amount']." ";
	}
	$query .= "ORDER BY LEFT(".$T['datetime'].",10) ASC, IF(".$T['amount'].">0,0,1), ".$T['qty']." DESC, t.id DESC; ";
	$result = qedb($query);
	while ($r = qrow($result)) {
//		if (count($dates)>=5) { break; }
		if (! $r['slid']) { $r['slid'] = ''; }

		if ($pricing) {
			$key = substr($r['date'],0,10).'.'.$r['order_number'].'.'.$r['price'];
		} else {
			$key = substr($r['date'],0,10).'.'.$r['companyid'];//.'.'.$r['partid'];//.'.'.$r['price'];
		}

		if (isset($res[$key])) {
			foreach ($res[$key] as $k => $r2) {
				if ($r['qty']>$r2['qty']) {
					if ($type=='Supply' OR $type=='Demand') { $res[$key][$k]['qty'] = $r['qty']; }
					else { $res[$key][$k]['qty'] += $r['qty']; }
				} else if ($type=='Purchase' OR $type=='Sale') {
					$res[$key][$k]['qty'] += $r['qty']; 
				}
				if (! $r2['price'] AND $r['price']) { $res[$key][$k]['price'] = $r['price']; }

				if ($r['source']) {
					$src = setSource($r['source'],getSearch($r['searchid']));

					$res[$key][$k]['sources'][$src['source']] = $src['ln'];
				}
			}
			continue;
		}

		$r['sources'] = array();

		if ($r['source']) {
			$src = setSource($r['source'],getSearch($r['searchid']));

			$r['sources'][$src['source']] = $src['ln'];
		}
		unset($r['source']);

		$amt = $r['price'];
		if (round($amt)==$amt) { $amt = round($amt); }
		else { $amt = number_format($r['price'],2,'.',''); }
		$r['price'] = $amt;

		$r['format'] = 'h6';
		if ($r['date']>=$recent_date) {
			$r['format'] = 'h5';
		} else if ($r['date']<$old_date) {
			$r['format'] = 'h4';
		}

//		$dates[substr($r['date'],0,10)] = true;

		// for supply results, we want to auto-populate past quoted prices for reference points, but
		// we also want them to be flagged past prices so they can be grayed out, so as not to confuse
		// with current pricing
		if ($type=='Supply' AND ! $r['price'] AND $prev_price[$r['companyid']]) {
			$r['price'] = $prev_price[$r['companyid']]['price'];
			if ($prev_price[$r['companyid']]['date']<$recent_date) { $r['past_price'] = '1'; }
		} else if ($r['price'] AND (! isset($prev_price[$r['companyid']]) OR $r['price']<>$prev_price[$r['companyid']])) {
//		if (! $prev_price[$r['companyid']] AND $r['price'] AND ! $r['past_price']) {
			// set this as a past price only if it's not already a past price
			$prev_price[$r['companyid']] = array('date'=>$r['date'],'price'=>$r['price']);
		}

		$res[$key][] = $r;
	}

	if ($type=='Purchase') {
		$query = "SELECT 0 companyid, requested date, qty, '' price, '0' past_price, po_number order_number, ";
		$query .= "'PR' abbrev, partid, '' slid, status, '' searchid ";
		$query .= "FROM purchase_requests ";
		$query .= "WHERE partid IN (".$partids.") AND po_number IS NULL AND (status = 'Active' OR status IS NULL) ";
		$query .= "ORDER BY LEFT(requested,10) ASC, id DESC; ";
		$result = qedb($query);
		while ($r = qrow($result)) {
			if (! $r['order_number']) { $r['order_number'] = ''; }
			if (! $r['status']) { $r['status'] = ''; }

			$key = substr($r['requested'],0,10);//.'.'.$r['item_id'].'.'.$r['item_id_label'].'.'.$r['order_number'];

			$r['name'] = 'PENDING';
			$r['sources'] = array();
			$r['format'] = 'h6';
			if ($r['date']>=$recent_date) {
				$r['format'] = 'h5';
			} else if ($r['date']<$old_date) {
				$r['format'] = 'h4';
			}

			$res[$key][] = $r;
		}
	}

	krsort($res);
//	print "<pre>".print_r($res,true)."</pre>";
//	exit;

	// restructure array without $key so we have a plain numerically-indexed array
	$priced = array();
	$nonpriced = array();
	foreach ($res as $key => $r2) {
		foreach ($r2 as $r) {
//			if (count($dates)>=$max_results) { break; }

			$dates[substr($r['date'],0,10)] = true;

			if ($r['price']>0) { $priced[substr($r['date'],0,10)][] = $r; }
			else { $nonpriced[substr($r['date'],0,10)][] = $r; }
		}
	}

	// create date-separated headers for each group of results
	if (! $pricing AND $type=='Supply') {
		$query = "SELECT LEFT(searches.datetime,10) date FROM keywords, parts_index, searches ";
		$query .= "WHERE parts_index.partid IN (".$partids.") AND scan LIKE '%1%' AND keywords.id = parts_index.keywordid AND keyword = search ";
		$query .= "GROUP BY date ";
		$query .= "ORDER BY searches.datetime DESC; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			if (! isset($dates[$r['date']])) {
				$dates[$r['date']] = 1;

				$format = 'h6';
				if ($r['date'].' 00:00:00'<$old_date) {
					$format = 'h4';
				}

				$nonpriced[$r['date']][] = array('date'=>$r['date'],'format'=>$format);
			}
		}
	}

	krsort($dates);

	$n = 0;
	$results = array();
	foreach ($dates as $date => $bool) {
		if ($n>=$max_results) { break; }
		$n++;

		if (isset($priced[$date]) AND is_array($priced[$date])) {
			uasort($priced[$date],$CMP('price','DESC'));

			foreach ($priced[$date] as $r) {
				$r['date'] = summarize_date($r['date']);

				$results[] = $r;
			}
		}

		if (isset($nonpriced[$date]) AND is_array($nonpriced[$date])) {
			uasort($nonpriced[$date],$CMP('qty','DESC'));

			foreach ($nonpriced[$date] as $r) {
				$r['date'] = summarize_date($r['date']);

				$results[] = $r;
			}
		}
	}

	$avg_cost = false;
	if ($type=='Purchase') {
		$avg_cost = number_format(getCost($partids),2);
	}

	header("Content-Type: application/json", true);
	echo json_encode(array('results'=>$results,'message'=>'','done'=>$done,'avg_cost'=>$avg_cost));
	exit;
?>
