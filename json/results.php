<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/cmp.php';

	$urls = array(
		'te'=>'www.tel-explorer.com/Main_Page/Search/Part_srch_go.php?part=',
		'ps'=>'www.powersourceonline.com/iris-clei-search.authenticated-en.jsa?Q=',
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

	$summary_past = format_date($today,'Y-m-01',array('m'=>-11));

	$partids = $_REQUEST['partids'];
	$type = '';
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }
	$pricing = 0;
	if (isset($_REQUEST['pricing'])) { $pricing = $_REQUEST['pricing']; }

	$max_results = 10;

	$T = order_type($type);

//	$GROUP = 'SUM';
//	if ($type=='Supply' OR $type=='Demand') { $GROUP = 'MAX'; }

	$prev_price = array();
	$dates = array();
	$recent_date = format_date($today,'Y-m-d 00:00:00',array('d'=>-7));
	$old_date = format_date($today,'Y-m-01 00:00:00',array('m'=>-11));
	$res = array();
	$query = "SELECT name, companyid, ".$T['datetime']." date, (".$T['qty'].") qty, ".$T['amount']." price, '0' past_price, t.".$T['order']." order_number, '".$T['abbrev']."' abbrev, ";
	if ($type=='Supply') { $query .= "source "; } else { $query .= "'' source "; }
	$query .= "FROM ".$T['items']." t, ".$T['orders']." o, companies c ";
	$query .= "WHERE partid IN (".$partids.") AND ".$T['qty']." > 0 ";
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

		if ($pricing) {
			$key = substr($r['date'],0,10).'.'.$r['order_number'].'.'.$r['price'];
		} else {
			$key = substr($r['date'],0,10).'.'.$r['companyid'];//.'.'.$r['price'];
		}

		if (isset($res[$key])) {
			foreach ($res[$key] as $k => $r2) {
				if ($r['qty']>$r2['qty']) {
					if ($type=='Supply' OR $type=='Demand') { $res[$key][$k]['qty'] = $r['qty']; }
					else { $res[$key][$k]['qty'] += $r['qty']; }
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
		else { $amt = number_format($r['price'],2); }
		$r['price'] = $amt;

		$r['format'] = 'h6';
		if ($r['date']>=$recent_date) {
			$r['format'] = 'h5';
		} else if ($r['date']<$old_date) {
			$r['format'] = 'h4';
		}

//		$dates[substr($r['date'],0,10)] = true;

		if ($type=='Supply' AND ! $r['price'] AND $prev_price[$r['companyid']]) {
			$r['price'] = $prev_price[$r['companyid']]['price'];
			if ($prev_price[$r['companyid']]['date']<$recent_date) { $r['past_price'] = '1'; }
		}
		$prev_price[$r['companyid']] = array('date'=>$r['date'],'price'=>$r['price']);

		$res[$key][] = $r;
	}

	krsort($res);
//	print "<pre>".print_r($res,true)."</pre>";
//	exit;

	// restructure array without $key so we have a plain numerically-indexed array
	$priced = array();
	$nonpriced = array();
	foreach ($res as $key => $r2) {
		foreach ($r2 as $r) {
			if (count($dates)>=$max_results) { break; }

			$dates[substr($r['date'],0,10)] = true;

			if ($r['price']>0) { $priced[substr($r['date'],0,10)][] = $r; }
			else { $nonpriced[substr($r['date'],0,10)][] = $r; }
		}
	}

	$results = array();
	foreach ($dates as $date => $bool) {
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

	header("Content-Type: application/json", true);
	echo json_encode(array('results'=>$results,'message'=>''));
	exit;
?>
