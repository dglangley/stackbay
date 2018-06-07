<?php
	ini_set('memory_limit', '2000M');

	header("Content-Type: application/json", true);

	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getField.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getShelflife.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getDQ.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getVisibleQty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFavorites.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getNotes.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCount.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/cmp.php';

	function getRows($type,$this_month,$partid_csv) {
		global $months_back;

		$T = order_type($type);

		// get data from supply/demand columns, then get pricing data also from orders tables to supplement pricing info
		$rows = array();
		$query = "SELECT ".$T['amount']." amount, LEFT(".$T['datetime'].",7) ym FROM ".$T['items']." t, ".$T['orders']." m ";
		$query .= "WHERE partid IN (".$partid_csv.") AND ".$T['datetime']." >= '".format_date($this_month,'Y-m-01 00:00:00',array('m'=>-$months_back))."' ";
		$query .= "AND m.".str_replace('metaid','id',$T['order'])." = t.".$T['order']." AND ".$T['amount']." > 0 ";
		$query .= "GROUP BY companyid, amount ORDER BY ".$T['datetime']." ASC; ";
//		if (strstr($partid_csv,'38308')) { die($query); }
		$result = qedb($query);
		while ($r = qrow($result)) {
			$rows[] = $r;
		}

		// get related orders data
		if ($type=='Supply') { $T = order_type('Purchase'); }
		else { $T = order_type('Sale'); }

		$query = "SELECT ".$T['amount']." amount, LEFT(".$T['datetime'].",7) ym FROM ".$T['items']." t, ".$T['orders']." m ";
		$query .= "WHERE partid IN (".$partid_csv.") AND ".$T['datetime']." >= '".format_date($this_month,'Y-m-01 00:00:00',array('m'=>-$months_back))."' ";
		$query .= "AND m.".str_replace('metaid','id',$T['order'])." = t.".$T['order']." AND ".$T['amount']." > 0 ";
		$query .= "GROUP BY companyid, amount ORDER BY ".$T['datetime']." ASC; ";
//		if (strstr($partid_csv,'38308')) { die($query); }
		$result = qedb($query);
		while ($r = qrow($result)) {
			$rows[] = $r;
		}

		return ($rows);
	}

	$Q = array('Supply'=>'offer','Demand'=>'quote');
	$months_back = 11;
	function getHistory($partids,$this_month) {
		global $months_back,$Q;

		$range = array('min'=>0,'max'=>0);
		$records = array('chart'=>array(),'range'=>$range);

		if (count($partids)==0) { return ($records); }

		$partid_csv = '';
		foreach ($partids as $partid) {
			if ($partid_csv) { $partid_csv .= ','; }
			$partid_csv .= $partid;
		}

		$starts_at = 0;
		$running_avg = 0;
		foreach ($Q as $type => $field) {

			$history = array();
			$rows = getRows($type,$this_month,$partid_csv);
			foreach ($rows as $r) {
				$history[$r['ym']][] = $r['amount'];
				if (! $starts_at AND $r['ym']>$starts_at) { $starts_at = $r['ym']; }

				if ($type=='Supply') {
					if (! $range['min'] OR $r['amount']<$range['min']) {
						$range['min'] = number_format($r['amount'],2,'.','');
					}
					if (! $range['max'] OR $r['amount']>$range['max']) {
						$range['max'] = number_format($r['amount'],2,'.','');
					}
				}
			}

			if (count($history)==0) { continue; }

			// the tips of the lines on the chart
			$open = 0;
			$close = 0;
			// the filled bars on the chart
			$high = 0;
			$low = 0;

			$last_high = 0;
			$last_low = 0;
			$start = false;
			$price_arr = array();
			for ($m=$months_back; $m>=0; $m--) {
				$ym = format_date(date("Y-m-01"),'Y-m',array('m'=>-($m-1)));
				//$mo = format_date(date("Y-m-01"),"M 'y",array('m'=>-$m));

				$prices = 0;
				$n = 0;
				if (isset($history[$ym]) AND is_array($history[$ym])) {
					$n = count($history[$ym]);
					if ($n==0 AND ! $start) { continue; }

					foreach ($history[$ym] as $price) {
						$prices += $price;
						$price_arr[] = $price;

						if ($price>$high) { $high = $price; }
						if (! $low OR $price<$low) { $low = $price; }
					}
				}

				if ($low AND $high AND $high==$low) {
					$high *= 1.2;
					$low *= .8;
				}

				if ($n>0 AND $prices>0) {
//					$mean = array_sum($price_arr)/$n;

					$avg_price = $prices/$n;
					$running_avg = $avg_price;
				}

				if (! $open OR $last_high<>$high) { $open = $last_high; }
				if (! $close OR $last_low<>$low) { $close = $last_low; }

/*
				if (! $open AND $high>0) { $open = $high; }
				if (! $close AND $low>0) { $close = $low; }
*/
$open = $high;
$close = $low;

				$records['chart'][$ym][$field] = array(
					'h' => (float)$high,
					'l' => (float)$low,
					'c' => (float)$close,
					'o' => (float)$open,
					't' => $ym,
					/*number_format($running_avg,2),*/
				);
				$start = true;

				$last_high = $high;
				$last_low = $low;

				$open = 0;
				$close = 0;
				$high = 0;
				$low = 0;
			}
		}
		ksort($records['chart']);

		$records['range'] = $range;

		return ($records);
	}

	function getQuote($listid,$list_type,$partid,$qty) {
		global $Q;

		$QUOTE = array('qty'=>$qty,'price'=>'','id'=>0);
		if (! $listid OR $list_type<>'metaid') { return ($QUOTE); }

		foreach ($Q as $type => $field) {
			$T = order_type($type);

			// get data from supply/demand columns, then get pricing data also from orders tables to supplement pricing info
			$rows = array();
			$query = "SELECT ".$T['amount']." amount, t.id, ";
			if ($T['items']=='demand') { $query .= "quote_qty qty "; } else { $query .= "avail_qty qty "; }//'".res($qty)."' qty "; }
			$query .= "FROM ".$T['items']." t, ".$T['orders']." m ";
			$query .= "WHERE partid = '".res($partid)."' AND t.metaid = m.id AND m.id = '".res($listid)."'; ";// AND t.".$T['amount']." > 0; ";
			$result = qedb($query);
			if (qnum($result)==0) { continue; }

			$r = qrow($result);
//			$QUOTE['qty'] = $r['qty'];
			// prevent 'null' output to json data
			if (! $QUOTE['qty'] AND $QUOTE['qty']!==0) { $QUOTE['qty'] = (string)$QUOTE['qty']; }
			$QUOTE['price'] = (string)$r['amount'];
			$QUOTE['id'] = $r['id'];
			break;
		}

		return ($QUOTE);
	}

	$listid = 0;
	$list_type = '';
	$record_type = '';
	$search_string = '';
	if (isset($_REQUEST['search']) AND trim($_REQUEST['search'])) { $search_string = trim($_REQUEST['search']); }
	if (isset($_REQUEST['listid'])) { $listid = $_REQUEST['listid']; }
	if (isset($_REQUEST['list_type'])) { $list_type = $_REQUEST['list_type']; }

	// are there any filters at all? true or false
	$filters = false;

	$filter_PR = false;
	if (isset($_REQUEST['PR']) AND is_numeric(trim($_REQUEST['PR'])) AND trim(str_replace('false','',$_REQUEST['PR'])<>'')) { $filter_PR = $_REQUEST['PR']; $filters = true; }
	$filter_fav = false;
	if (isset($_REQUEST['favorites']) AND is_numeric(trim($_REQUEST['favorites'])) AND trim($_REQUEST['favorites'])) { $filter_fav = $_REQUEST['favorites']; $filters = true; }
	$filter_LN = false;
	// if line number is passed in, decrement it by 1 because our line numbers below start at 0
	if (isset($_REQUEST['ln']) AND is_numeric(trim($_REQUEST['ln'])) AND trim(str_replace('false','',$_REQUEST['ln'])<>'')) { $filter_LN = $_REQUEST['ln']-1; $filters = true; }
	$filter_startDate = '';
	if (isset($_REQUEST['startDate']) AND trim($_REQUEST['startDate']<>'')) { $filter_startDate = format_date($_REQUEST['startDate'],'Y-m-d'); $filters = true; }
	$filter_endDate = '';
	if (isset($_REQUEST['endDate']) AND trim($_REQUEST['endDate']<>'')) { $filter_endDate = format_date($_REQUEST['endDate'],'Y-m-d'); $filters = true; }
	$filter_demandMin = false;
	if (isset($_REQUEST['demandMin']) AND is_numeric(trim($_REQUEST['demandMin'])) AND trim(str_replace('false','',$_REQUEST['demandMin']<>''))) { $filter_demandMin = $_REQUEST['demandMin']; $filters = true; }
	$filter_demandMax = false;
	if (isset($_REQUEST['demandMax']) AND is_numeric(trim($_REQUEST['demandMax'])) AND trim(str_replace('false','',$_REQUEST['demandMax']<>''))) { $filter_demandMax = $_REQUEST['demandMax']; $filters = true; }
	$filter_salesMin = false;
	if (isset($_REQUEST['salesMin']) AND is_numeric(trim($_REQUEST['salesMin'])) AND trim($_REQUEST['salesMin']<>'')) { $filter_salesMin = $_REQUEST['salesMin']; $filters = true; }
	$filter_searchid = false;
	if (isset($_REQUEST['searchid']) AND is_numeric(trim($_REQUEST['searchid'])) AND trim(str_replace('false','',$_REQUEST['searchid'])<>'')) { $filter_searchid = $_REQUEST['searchid']; $filters = true; }

	$lines = array();
	if (! $listid AND ! $search_string) {

		// product lines of results based on NO search string, and ONLY filters (i.e., database mining)
		if ($filters) {
			if ($filter_fav) {
				$query = "SELECT p.part, p.heci FROM favorites f, parts p ";
				$query .= "WHERE f.partid = p.id ";
				$query .= "GROUP BY LEFT(p.heci,7) ";
				$query .= "ORDER BY f.datetime DESC LIMIT 0,20; ";
				$result = qedb($query);
				while ($r = qrow($result)) {
					$parts = explode(' ',$r['part']);
					if ($r['heci']) { $favstr = substr($r['heci'],0,7); }
					else { $favstr = $parts[0]; }

					$lines[] = $favstr;
				}
			} else if ($filter_demandMin!==false) {
				$query = "SELECT LEFT(p.heci,7) heci, COUNT(DISTINCT(LEFT(datetime,10))) n, ";
				$query .= "SUM(i.qty) stk ";
				$query .= "FROM search_meta m, demand d, parts p ";
				$query .= "LEFT JOIN inventory i ON p.id = i.partid ";
				$query .= "WHERE m.id = d.metaid AND d.partid = p.id ";
				if ($filter_startDate) { $query .= "AND m.datetime >= '".res($filter_startDate)." 00:00:00' "; }
				if ($filter_endDate) { $query .= "AND m.datetime <= '".res($filter_endDate)." 23:59:59' "; }
//				$query .= "AND companyid IN (1206,1407,870,10,50) ";
				$query .= "AND (status = 'received' OR status IS NULL) AND heci IS NOT NULL ";
				$query .= "GROUP BY heci HAVING n >= '".res($filter_demandMin)."' AND (stk = 0 OR stk IS NULL) ";
				$query .= "; ";
				$result = qedb($query);
				while ($r = qrow($result)) {
					$lines[] = $r['heci'];
				}
			} else if ($filter_salesMin!==false) {
				$query = "SELECT LEFT(p.heci,7) heci, COUNT(DISTINCT(LEFT(created,10))) n, ";
				$query .= "SUM(i.qty) stk ";
				$query .= "FROM sales_orders so, sales_items si, parts p ";
				$query .= "WHERE so.so_number = si.so_number AND si.partid = p.id ";
				if ($filter_startDate) { $query .= "AND m.created >= '".res($filter_startDate)." 00:00:00' "; }
				if ($filter_endDate) { $query .= "AND m.created <= '".res($filter_endDate)." 23:59:59' "; }
				$query .= "AND heci IS NOT NULL ";
				$query .= "GROUP BY heci HAVING n >= '".res($filter_salesMin)."' ";
				$query .= "; ";
				$result = qedb($query);
				while ($r = qrow($result)) {
					$lines[] = $r['heci'];
				}
			}

			if (count($lines)==0) {
				jsonDie("No results found");
			}
		}

		if (count($lines)==0) {
			jsonDie('Enter your search above, or tap <i class="fa fa-list-ol"></i> for advanced search options...');
		}
	}

	//default field handling variables
	$col_search = 0;
	$sfe = false;//search from end
	$col_qty = 1;
	$qfe = false;//qty from end
	$col_price = false;
	$pfe = false;//price from end

	$this_month = date("Y-m-01");
	$recent_date = format_date($today,'Y-m-d 00:00:00',array('d'=>-15));

	$editable = true;//all lists should be editable unless the data isn't neatly packed for us (i.e., no line number sequencing)
	$line_number = 0;
	if (count($lines)>0) {
		$col_search = 1;
		$col_qty = false;
	} else if ($search_string) {
		//$lines = array($search_string);
		//$line_number = $filter_LN;
		$lines = array($filter_LN=>$search_string);
		$col_search = 1;
		$col_qty = false;
	} else if ($listid) {
		if ($list_type=='slid') {
			$query = "SELECT * FROM search_lists WHERE id = '".res($listid)."'; ";
			$result = qedb($query);
			$list = qfetch($result,'Could not find list');

			$lines = explode(chr(10),$list['search_text']);
			$fields = $list['fields'];

			$col_search = substr($fields,0,1);
			$col_qty = substr($fields,1,1);
			$col_price = substr($fields,2,1);
			if (strlen($list['fields'])>3) {
				$sfe = substr($fields,3,1);
				$qfe = substr($fields,4,1);
				$pfe = substr($fields,5,1);
			}
		} else if ($list_type=='metaid') {
			// detect type so we can extract data from searches table based on searchid in the corresponding records table (demand or availability)
			$query = "SELECT * FROM demand WHERE metaid = '".res($listid)."'; ";
			$result = qedb($query);
			if (qnum($result)>0) { $record_type = 'demand'; } else { $record_type = 'availability'; }

			if ($record_type=='demand') { $list_qty = 'request_qty'; } else { $list_qty = 'avail_qty'; }

			$col_search = 1;
			$col_qty = 2;
			$prev_ln = false;
			// get grouped search strings
			$query = "SELECT s.search, d.partid, ".$list_qty." qty, d.line_number, s.id searchid ";
			$query .= "FROM ".$record_type." d ";
			$query .= "LEFT JOIN searches s ON d.searchid = s.id ";
			$query .= "WHERE d.metaid = '".res($listid)."' ";
			if ($filter_LN!==false) { $query .= "AND d.line_number = '".res($filter_LN+1)."' "; }//increment because this is real data, not an array (see above)
			if ($filter_searchid!==false) { $query .= "AND s.id = '".res($filter_searchid)."' "; }
			$query .= "GROUP BY s.search, d.line_number ORDER BY d.line_number ASC, d.id ASC; ";
			$result = qedb($query);
			while ($r = qrow($result)) {
				// supplement search data with part string, if no search string data is present; this would probably be on older records
				if (! $r['search'] AND $r['partid']) {
					$r['search'] = format_part(getPart($r['partid'],'part'));
				}
				if ($filter_searchid!==false AND $filter_searchid<>$r['searchid']) { continue; }

				$l = $r['line_number'];
				// if iterating through the results of a list and the same line number keeps occurring, there's no valid
				// sequence for us to edit, otherwise data can get mixed up; lists should not be like this except on "older"
				// data (line numbers are 2016 and more recent, but some API's like NGT was discovered even recently in 2018
				// to not be numbering lines uniquely)
				if ($l==$prev_ln) { $editable = false; }
				$prev_ln = $l;

				$lines[$l] = $r['search'].' '.$r['qty'];
			}
		}
	}

	$results = array();
	foreach ($lines as $line_number => $line) {
//		if ($filter_LN!==false AND $line_number<>$filter_LN) {
//			$line_number++;
//			continue;
//		}
		$line = trim($line);

		$F = preg_split('/[[:space:]]+/',$line);
//		print_r($F);echo '<BR><BR>';
//		if ($i<=5) { continue; }

		$search = getField($F,$col_search,$sfe);
		if ($search===false OR ! $search) { continue; }

		$search_qty = getField($F,$col_qty,$qfe);
//		die($search_qty);
		if (! $search_qty OR ! is_numeric($search_qty)) { $search_qty = 1; }

		$search_price = getField($F,$col_price,$pfe);
		if ($search_price===false) { $search_price = ''; }

		$searches = array($search=>true);

		$id = 1234;
		$label = 'repair_item_id';

		// primary matches
		$partids = array();
		// all matches, primary or sub
		$all_partids = array();

		$stock = array();
		$zerostock = array();
		$nonstock = array();
//		echo 'search '.$search.'<BR>';

		// query the search string as the 7-digit heci if it's a potential 7-digit heci
		$heci7_search = false;
		$hlen = strlen($search);
		if ((($hlen==7 AND preg_match('/^[[:alnum:]]{7}$/',$search)) OR ($hlen==10 AND preg_match('/^[[:alnum:]]{10}$/',$search))) AND ! is_numeric($search)) {
			$query = "SELECT heci FROM parts WHERE heci LIKE '".substr($search,0,7)."%'; ";
			$result = qedb($query);
			if (qnum($result)>0) { $heci7_search = true; }
		}

//		$H = hecidb($search);

		if ($heci7_search) {
			$H = hecidb(substr($search,0,7));
		} else {
			$H = hecidb(format_part($search));
		}
		if (count($H)>50) { continue; }

		foreach ($H as $partid => $row) {
			$qty = getQty($partid);
			if ($qty===false) { $qty = ''; }

			$row['stk'] = $qty;
			$row['vqty'] = getVisibleQty($partid);

			$QUOTE = getQuote($listid,$list_type,$partid,$qty);
			$row['qty'] = $QUOTE['qty'];
			$row['price'] = $QUOTE['price'];

			$row['description'] = utf8_encode($row['description']);
			$row['Descr'] = utf8_encode($row['Descr']);

			$row['prop'] = array('checked'=>false,'disabled'=>false,'readonly'=>false);
			// flag this as a primary match (non-sub)
			if ($row['rank']=='primary') {
				$row['class'] = 'primary';
$row['prop']['checked'] = true;
				if (! $listid OR $list_type<>'metaid' OR $QUOTE['id']) { $row['prop']['checked'] = true; }
			} else {
				$row['class'] = 'sub';
			}
$row['prop']['readonly'] = true;

			$partids[$partid] = $partid;
			$all_partids[$partid] = $partid;

			if ($row['heci'] AND strlen($search)<>7) { $searches[substr($row['heci'],0,7)] = true; }
			$searches[format_part($row['primary_part'])] = true;
			foreach ($row['aliases'] as $alias) {
				if (strlen($alias)<=2) { continue; }

				$searches[format_part($alias)] = true;
			}

			$nflag = '';
			$notes = getNotes($partid);
			$row['notes'] = array();
			foreach ($notes as $note) {
				if ($note['datetime']>$recent_date) { $nflag = '<span class="item-notes text-danger"><i class="fa fa-sticky-note"></i></span>'; }
				else if (! $nflag) { $nflag = '<span class="item-notes text-warning"><i class="fa fa-sticky-note"></i></span>'; }

				$row['notes'][] = $note['id'];
			}

			if (! $nflag) { $nflag = '<span class="item-notes"><i class="fa fa-sticky-note-o"></i></span>'; }
			$row['notes_flag'] = $nflag;

			// gymnastics to force json to not re-sort array results, which happens when the key is an integer instead of string
			unset($H[$partid]);

			$row['fav'] = 'fa-star-o';

			if ($row['stk']>0) { $stock[$partid."-"] = $row; }
			else if ($row['stk']===0) { $zerostock[$partid."-"] = $row; }
			else { $nonstock[$partid."-"] = $row; }
//			$H[$partid."-"] = $row;
		}

		if ($filter_demandMin!==false OR $filter_demandMax!==false) {
			$demand = getCount($partids,$filter_startDate,$filter_endDate,'Demand');
			if (($filter_demandMin!==false AND $demand<$filter_demandMin) OR ($filter_demandMax!==false AND $demand>$filter_demandMax)) {
//				$line_number++;
				continue;
			}
		}

		if ($filter_salesMin!==false) {
			$num_sales = getCount($partids,$filter_startDate,$filter_endDate,'Sale');
			// did not meet sales minimum threshold
			if ($num_sales<$filter_salesMin) {
//				$line_number++;
				continue;
			}
		}

		// pre-sort because uasort has an awful tendency to reverse-sort arrays when all things are equal
		ksort($stock);
		ksort($zerostock);
		krsort($nonstock);
		// sort by class to get PRIMARY before SUB
		uasort($stock,$CMP('class','ASC'));
		uasort($zerostock,$CMP('class','ASC'));
		uasort($nonstock,$CMP('class','ASC'));

		// sort by stock first
		foreach ($stock as $k => $row) { $H[$k] = $row; }
		foreach ($zerostock as $k => $row) { $H[$k] = $row; }
		foreach ($nonstock as $k => $row) { $H[$k] = $row; }

		$stock = array();
		$zerostock = array();
		$nonstock = array();
		foreach ($searches as $str => $bool) {
			// don't use a string matching the $search above
			if ($search==$str) { continue; }

//			echo $str.'<BR>'.chr(10);
			$db = hecidb($str);

			// we don't want subs that are more likely bogus results
			if (count($db)>50) { continue; }

			foreach ($db as $partid => $row) {
				// don't duplicate a result already stored above
				if (isset($H[$partid."-"])) { continue; }

				$qty = getQty($partid);
				if ($qty===false) { $qty = ''; }

				$row['stk'] = $qty;
				$row['vqty'] = getVisibleQty($partid);

				$QUOTE = getQuote($listid,$list_type,$partid,$qty);
				$row['qty'] = $QUOTE['qty'];
				$row['price'] = $QUOTE['price'];

				$row['description'] = utf8_encode($row['description']);
				$row['Descr'] = utf8_encode($row['Descr']);

				// flag this result as a sub
				$row['class'] = 'sub';
				$row['prop'] = array('checked'=>false,'disabled'=>false,'readonly'=>false);
				if ($QUOTE['id']) { $row['prop']['checked'] = true; }

				// include sub matches
				$all_partids[$partid] = $partid;

				$row['notes'] = getNotes($partid);

				$row['fav'] = 'fa-star-o';

				// gymnastics to force json to not re-sort array results, which happens when the key is an integer instead of string
				unset($H[$partid]);

				if ($row['stk']>0) { $stock[$partid."-"] = $row; }
				else if ($row['stk']===0) { $zerostock[$partid."-"] = $row; }
				else { $nonstock[$partid."-"] = $row; }
//				$H[$partid."-"] = $row;
			}
		}

		// pre-sort because uasort has an awful tendency to reverse-sort arrays when all things are equal
		ksort($stock);
		ksort($zerostock);
		ksort($nonstock);
		// sort by class to get PRIMARY before SUB
		uasort($stock,$CMP('class','ASC'));
		uasort($zerostock,$CMP('class','ASC'));
		uasort($nonstock,$CMP('class','ASC'));

		// sort by stock first
		foreach ($stock as $k => $row) { $H[$k] = $row; }
		foreach ($zerostock as $k => $row) { $H[$k] = $row; }
		foreach ($nonstock as $k => $row) { $H[$k] = $row; }

		$favs = getFavorites($all_partids);
		if ($filter_fav AND count($favs)==0) {
//			$line_number++;
			continue;
		}

		// add to partid results
		foreach ($favs as $pid => $flag) {
			$H[$pid."-"]['fav'] = $flag;
		}

		$PR = getDQ($partids);
		if ($filter_PR!==false AND $PR<$filter_PR) {
			continue;
//			$line_number++;
		}

		$market = getHistory($partids,$this_month);
		/*$avg_cost = number_format(getCost($partids),2);*/
		$shelflife = getShelflife($partids);

		$row_ln = $line_number+1;
		if ($list_type=='metaid') { $row_ln = $line_number; }
		$r = array(
			'ln'=>$row_ln,
			'search'=>$search,
			'line'=>utf8_encode($line),
			'qty'=>$search_qty,
			'price'=>$search_price,
			'id'=>$id,
			'label'=>$label,
			'chart'=>$market['chart'],
			'range'=>$market['range'],
			/*'avg_cost'=>$avg_cost,*/
			'shelflife'=>$shelflife,
			'pr'=>$PR,
			'results'=>$H,
		);
		$results[$line_number] = $r;

//		$line_number++;
	}

	echo json_encode(array('results'=>$results,'message'=>''));
	exit;
?>
