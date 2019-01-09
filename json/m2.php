<?php
	set_time_limit(0);
	ini_set('memory_limit', '2000M');
	ini_set('mbstring.func_overload', '2');
	ini_set('mbstring.internal_encoding', 'UTF-8');

	header("Content-Type: application/json", true);

	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getField.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getShelflife.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';
//	include_once $_SERVER["ROOT_DIR"].'/inc/getDQ.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getVisibleQty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFavorites.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getNotes.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCount.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterials.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrderNumber.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/partKey.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/cmp.php';

	function getRows($type,$this_month,$partid_csv) {
		global $months_back;

		$T = order_type($type);

		// get data from supply/demand columns, then get pricing data also from orders tables to supplement pricing info
		$rows = array();
		$query = "SELECT companyid, ".$T['amount']." amount, LEFT(".$T['datetime'].",7) ym FROM ".$T['items']." t, ".$T['orders']." m ";
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

		$query = "SELECT companyid, ".$T['amount']." amount, LEFT(".$T['datetime'].",7) ym FROM ".$T['items']." t, ".$T['orders']." m ";
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

//		$range = array('min'=>0,'max'=>0);
		$records = array('chart'=>array());//,'range'=>$range);

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

/*
				if ($type=='Supply' AND (! strstr(getCompany($r['companyid']),'eBay'))) {
					if (! $range['min'] OR $r['amount']<$range['min']) {
						$range['min'] = number_format($r['amount'],2,'.','');
					}
					if (! $range['max'] OR $r['amount']>$range['max']) {
						$range['max'] = number_format($r['amount'],2,'.','');
					}
				}
*/
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

//		$records['range'] = $range;

		return ($records);
	}

	function getQuote($listid,$list_label,$partid,$qty) {
		global $Q;

		$QUOTE = array('qty'=>$qty,'price'=>'','id'=>0);
		if (! $listid OR $list_label<>'metaid') { return ($QUOTE); }

		foreach ($Q as $type => $field) {
			$T = order_type($type);

			// get data from supply/demand columns, then get pricing data also from orders tables to supplement pricing info
			$rows = array();
			$query = "SELECT ".$T['amount']." amount, t.id, ";
			if ($T['items']=='demand') { $query .= "quote_qty qty, request_qty aux_qty "; } else { $query .= "avail_qty qty, offer_qty aux_qty "; }//'".res($qty)."' qty "; }
			$query .= "FROM ".$T['items']." t, ".$T['orders']." m ";
			$query .= "WHERE partid = '".res($partid)."' AND t.metaid = m.id AND m.id = '".res($listid)."'; ";// AND t.".$T['amount']." > 0; ";
			$result = qedb($query);

			// added 12/26/18 because I think this is where I'm getting qty discrepancies on saved lists - I don't want
			// stk qty saved as quote qty if we're pulling up a saved quote; otherwise, I definitely DO want stk qty
			// for the quote qty, but I think that's the exception given above where $list_label<>'metaid'
			$QUOTE['qty'] = '';
			if (qnum($result)==0) { continue; }

			$r = qrow($result);
			if ($r['amount'] AND ! $QUOTE['qty'] AND $r['aux_qty']) {
				$QUOTE['qty'] = $r['aux_qty'];
			} else if ($r['qty']) {
				$QUOTE['qty'] = $r['qty'];
			}
//			$QUOTE['qty'] = $r['qty'];
			// prevent 'null' output to json data
			if (! $QUOTE['qty'] AND $QUOTE['qty']!==0) { $QUOTE['qty'] = (string)$QUOTE['qty']; }
			$QUOTE['price'] = (string)$r['amount'];
			$QUOTE['id'] = $r['id'];
			break;
		}

		return ($QUOTE);
	}

	$companyid = 0;
	$listid = 0;
	$list_label = '';
	$list_type = '';
	$record_type = '';
	$search_string = '';
	$search_type = '';
	$import_quote = false;
	if (isset($_REQUEST['search']) AND trim($_REQUEST['search'])) { $search_string = trim($_REQUEST['search']); }
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	if (isset($_REQUEST['listid'])) { $listid = $_REQUEST['listid']; }
	if (isset($_REQUEST['list_label'])) { $list_label = $_REQUEST['list_label']; }
	if (isset($_REQUEST['list_type'])) { $list_type = $_REQUEST['list_type']; }
	$lim = 0;
	if (isset($_REQUEST['lim']) AND is_numeric(trim($_REQUEST['lim'])) AND trim($_REQUEST['lim'])<>'') { $lim = trim($_REQUEST['lim']); }
	if (isset($_REQUEST['import_quote']) AND trim($_REQUEST['import_quote'])) { $import_quote = true; }
	$N = 0;//number of results found below
/*
	$taskid = 0;
	$task_label = '';
	if (isset($_REQUEST['taskid'])) { $taskid = $_REQUEST['taskid']; }
	if (isset($_REQUEST['task_label'])) { $task_label = $_REQUEST['task_label']; }
*/

	// are there any filters at all? true or false
	$filters = false;

	$filter_PR = false;
	if (isset($_REQUEST['PR']) AND is_numeric(trim($_REQUEST['PR'])) AND trim(str_replace('false','',$_REQUEST['PR'])<>'')) { $filter_PR = $_REQUEST['PR']; $filters = true; }
	$filter_fav = false;
	if (isset($_REQUEST['favorites']) AND is_numeric(trim($_REQUEST['favorites'])) AND trim($_REQUEST['favorites'])) { $filter_fav = $_REQUEST['favorites']; $filters = true; }
	$filter_LN = false;
	if (isset($_REQUEST['ln']) AND is_numeric(trim($_REQUEST['ln'])) AND trim(str_replace('false','',$_REQUEST['ln'])<>'')) { $filter_LN = $_REQUEST['ln']; $filters = true; }
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

	$aux = array();//supplemental data corresponding to each line in $lines
	$lines = array();
	//if (! $listid AND ! $search_string AND (! $taskid OR ! $task_label)) {
	if (! $listid AND ! $search_string AND $list_label<>'Service' AND $list_label<>'Repair') {

		// product lines of results based on NO search string, and ONLY filters (i.e., database mining)
		if ($filters) {
			if ($filter_fav) {
				$query = "SELECT p.part, p.heci FROM favorites f, parts p ";
				$query .= "WHERE f.partid = p.id ";
				$query .= "GROUP BY LEFT(p.heci,7) ";
				$query .= "ORDER BY f.datetime DESC ";
				$query .= "LIMIT 0,20 ";
				$query .= "; ";
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
				$query .= "SUM(si.qty) stk ";
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
		$N = count($lines);
	}

	//default field handling variables
	$col_search = 1;
	$sfe = false;//search from end
	$col_qty = 2;
	$qfe = false;//qty from end
	$col_price = false;
	$pfe = false;//price from end
	$prev_ln = false;
	$max_lines = 10;
	if ($list_type=='Service') { $max_lines = 0; }
	$add_line = true;

	$this_month = date("Y-m-01");
	$recent_date = format_date($today,'Y-m-d 00:00:00',array('d'=>-15));

	$editable = true;//all lists should be editable unless the data isn't neatly packed for us (i.e., no line number sequencing)
	$line_number = 0;
	if (count($lines)>0) {
		$col_search = 1;
		if ($list_label<>'Service' AND $list_label<>'Repair') { $col_qty = false; }
	} else if ($listid) {
		if ($list_label=='slid') {
			$query = "SELECT * FROM search_lists WHERE id = '".res($listid)."'; ";
			$result = qedb($query);
			$r = qrow($result);//,'Could not find list');

			$text_lines = explode(chr(10),$r['search_text']);
			// if filtering to just a single line, get that line's data
			if ($filter_LN!==false) {
				$lines = array($filter_LN=>$text_lines[$filter_LN]);
				$N = count($lines);
			} else {
				$N = count($text_lines);
				$lines = array();
				$c = count($text_lines);
				if ($max_lines) {
					for ($i=$lim; $i<($lim+$max_lines); $i++) {
						if ($i>=$c AND ! $text_lines[$i]) { break; }
						$lines[$i] = $text_lines[$i];
					}
				} else {
					$lines = $text_lines;
				}

				if (count($text_lines)>count($lines)) { $add_line = false; }
			}
			// save memory
			unset($text_lines);

			$fields = $r['fields'];

			$col_search = substr($fields,0,1);
			$col_qty = substr($fields,1,1);
			$col_price = substr($fields,2,1);
			if (strlen($r['fields'])>3) {
				$sfe = substr($fields,3,1);
				$qfe = substr($fields,4,1);
				$pfe = substr($fields,5,1);
			}
		} else if ($list_label=='metaid') {
			// detect type so we can extract data from searches table based on searchid in the corresponding records table (demand or availability)
			$query = "SELECT * FROM demand WHERE metaid = '".res($listid)."'; ";
			$result = qedb($query);
			if (qnum($result)>0) { $record_type = 'demand'; } else { $record_type = 'availability'; }

			if ($record_type=='demand') { $list_qty = 'request_qty'; } else { $list_qty = 'avail_qty'; }

/*
			$col_search = 1;
			$col_qty = 2;
*/

			// get grouped search strings
			$query = "SELECT s.search, d.partid, ".$list_qty." qty, d.line_number, s.id searchid ";
			$query .= "FROM ".$record_type." d ";
			$query .= "LEFT JOIN searches s ON d.searchid = s.id ";
			$query .= "WHERE d.metaid = '".res($listid)."' ";
			if ($filter_LN!==false) { $query .= "AND d.line_number = '".res($filter_LN)."' "; }
			if ($filter_searchid!==false) { $query .= "AND s.id = '".res($filter_searchid)."' "; }
			$query .= "GROUP BY s.search, d.line_number ORDER BY d.line_number ASC, d.id ASC ";
//			if (! $filters) { $query .= "LIMIT ".$lim.",".$max_lines." "; }
			$query .= "; ";
			$result = qedb($query);
			$N = qnum($result);
			if ($max_lines AND $N>$max_lines) { $add_line = false; }
			$i = 0;
			while ($r = qrow($result)) {
				if ($i<$lim OR ($max_lines AND $i>=($lim+$max_lines))) { continue; }

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
		} else if ($list_label=='Service' OR $list_label=='Repair') {
			$T = order_type($list_label);

			$materials = getMaterials($listid,$T,true);

			if ($import_quote) {
				$order_number = getOrderNumber($listid,$T['items'],$T['order'],true);
				$ORDER = getOrder($order_number, $list_label);
				$quoted_materials = getQuotedMaterials($ORDER['items'][$listid]['quote_item_id']);

//				$new_materials = array();
				// reformat quoted materials array into array that will work for iteration below
				foreach ($quoted_materials as $partid => $q) {
					if (isset($materials[$partid])) { continue; }

					foreach ($q as $n => $r) {
						$r['quote'] = $r['quote']/$r['qty'];//needed because of how getQuotedMaterials pre-multiplies the amount; grrrr
//						$new_materials[$partid.'-'.$n.'-'.rand(0,1000000)] = $r;
						$materials[$partid.'-'.$n.'-'.rand(0,1000000)] = $r;
					}
				}
//				$materials = $new_materials;
			}

//			$query = "SELECT id FROM purchase_requests WHERE item_id = ".res($item_id)." AND item_id_label = ".fres($item_id_label)." AND partid = ".res($partid).";";
//			$result = qedb($query);

			$search_type = 'id';

			foreach ($materials as $pid => $M) {
				$pid = $M['partid'];

//				$ss = '';
				$H = hecidb($pid,'id');
				$r = $H[$pid];
//				if ($r['heci']) { $ss = $r['heci']; }
//				else { $ss = format_part($r['part']); }

				$qty = 1;
				if (isset($M['requested'])) { $qty = $M['requested']; }
				else if (isset($M['qty'])) { $qty = $M['qty']; }

				$amount = '';
				if (isset($M['amount'])) { $amount = $M['amount']; }
				$quote = '';
				if (isset($M['quote'])) { $quote = $M['quote']; }
				$leadtime = '';
				if (isset($M['leadtime'])) { $leadtime = $M['leadtime']; }
				$leadtime_span = '';
				if (isset($M['leadtime_span'])) { $leadtime_span = $M['leadtime_span']; }
				$profit_pct = '';
				if (isset($M['profit_pct'])) { $profit_pct = $M['profit_pct']; }

				$l = $M['line_number'];
				if (! $l) { $l = 1; }

				if (! $M['line_number'] AND $prev_ln!==false) {
					$l = $prev_ln+1;
				}
				$prev_ln = $l;

				if ($filter_LN!==false AND $l<>$filter_LN) { continue; }

				$aux[$l] = array(
					'qty'=>$qty,
					'amount'=>$amount,
					'leadtime'=>$leadtime,
					'leadtime_span'=>$leadtime_span,
					'profit_pct'=>$profit_pct,
					'quote'=>$quote,
				);
				//$lines[$l] = $ss.' '.$qty;
				$lines[$l] = $pid;//.' '.$qty;
			}

			$N = count($lines);
		}

		// user adding a line item to list
		if ($search_string AND $filter_LN>=count($lines)) {
			$search_type = '';
			$lines[$filter_LN] = $search_string;//.' 1';
			// we can re-assign these since this is a single-line search
			$col_search = 1;
			$col_qty = 2;

			$N = count($lines);
		}
	} else if ($search_string) {
		//$lines = array($search_string);
		//$line_number = $filter_LN;
		$lines = array($filter_LN=>$search_string);
		$col_search = 1;
		$col_qty = false;

		$N = count($lines);
	}

	$id = 0;
	$label = '';
	if ($list_label=='Service' OR $list_label=='Repair') {
		$id = $listid;
		$label = $list_label;
	}

	$results = array();
	$manfs = array();
	$systems = array();
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

		// cannot be of type Service/Repair because rows are fixed unless adding PAST existing line numbers (adding new item)
		if ($filter_LN!==false AND $filter_LN==$line_number AND $search_string AND $search_string!==$search AND $list_label<>'Service' AND $list_label<>'Repair') {
			$search = $search_string;
		}

		$search_qty = getField($F,$col_qty,$qfe);
//		die($search_qty);
		if (! $search_qty OR ! is_numeric($search_qty)) { $search_qty = 1; }

		$search_price = getField($F,$col_price,$pfe);
		if ($search_price===false) { $search_price = ''; }

		$searches = array($search=>true);

		$row_quote = '';
		$row_lt = '';
		$row_ltspan = '';
		$row_markup = '';

		if ($id AND $label) {
			if (isset($aux[$line_number]) AND $aux[$line_number]['qty']) { $search_qty = $aux[$line_number]['qty']; }
			if (isset($aux[$line_number]) AND $aux[$line_number]['amount']) { $search_price = format_price($aux[$line_number]['amount'],true,'',true); }
			if (isset($aux[$line_number]) AND $aux[$line_number]['leadtime']) { $row_lt = format_price($aux[$line_number]['leadtime'],true,'',true); }
			if (isset($aux[$line_number]) AND $aux[$line_number]['leadtime_span']) { $row_ltspan = format_price($aux[$line_number]['leadtime_span'],true,'',true); }
			if (isset($aux[$line_number]) AND $aux[$line_number]['profit_pct']) { $row_markup = format_price($aux[$line_number]['profit_pct'],true,'',true); }
			if (isset($aux[$line_number]) AND $aux[$line_number]['quote']) { $row_quote = format_price($aux[$line_number]['quote'],true,'',true); }
		}

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

		if ($search_type) {
			$H = hecidb($search,$search_type);
		} else if ($list_label=='Service' AND $list_label=='Repair') {
			$H = hecidb($line,'id');
		} else if ($heci7_search) {
			$H = hecidb(substr($search,0,7));
		} else {
			$H = hecidb(format_part($search));
		}
		if (count($H)>50) { continue; }

		// reframe search string as part key rather than abstract id to the user
		if ($search_type=='id') {
			$search = partKey($search);
		}

		$one_checked = false;
		foreach ($H as $partid => $row) {
			$qty = getQty($partid);
			if ($qty===false) { $qty = ''; }

			$row['stk'] = $qty;
			$row['vqty'] = getVisibleQty($partid);

			$QUOTE = getQuote($listid,$list_label,$partid,$qty);
			$row['qty'] = $QUOTE['qty'];
			$row['price'] = $QUOTE['price'];

			$row['descr'] = utf8_encode($row['description']);
			//$row['Descr'] = utf8_encode($row['Descr']);
			unset($row['description']);
			unset($row['Descr']);
			unset($row['Manf']);
			unset($row['RelValue']);
			if ($row['manfid']) {
				$manfs[$row['manfid']] = $row['manf'];
			}
			unset($row['manf']);
			if ($row['systemid']) {
				$systems[$row['systemid']] = $row['system'];
			}
			unset($row['system']);
			$row['part'] = $row['primary_part'];
			unset($row['primary_part']);
			unset($row['Part']);
			unset($row['HECI']);
			unset($row['heci7']);
			unset($row['name']);
			$row['class'] = $row['classification'];
			unset($row['classification']);
			unset($row['fpart']);
			unset($row['Key']);
			unset($row['Sys']);

			$row['prop'] = array('checked'=>false,'disabled'=>false,'readonly'=>false,'type'=>'checkbox');
			// flag this as a primary match (non-sub)
			if ($row['rank']=='primary') {
				$row['class'] = 'primary';

				if ($list_label=='Service') {
					if ($line==$partid OR ($search_string AND ! $one_checked)) {
						$row['prop']['checked'] = true;
						$one_checked = true;
					}
				} else {
					$row['prop']['checked'] = true;

					if (! $listid OR $list_label<>'metaid' OR $QUOTE['id']) { $row['prop']['checked'] = true; }
				}

				// moved this here 6-26-18 because we were getting results based on SUBS as well as true primary's,
				// which seemed to show a LOT of extraneous, meaningless results
				if ($row['heci'] AND strlen($search)<>7) { $searches[substr($row['heci'],0,7)] = true; }
				$searches[format_part($row['part'])] = true;
				foreach ($row['aliases'] as $alias) {
					if (strlen($alias)<=2) { continue; }
	
					$searches[format_part($alias)] = true;
				}

				$partids[$partid] = $partid;
			} else {
				$row['class'] = 'sub';
			}

			if ($list_label=='Repair') {
//				$row['prop']['type'] = 'radio';
				$row['prop']['checked'] = false;
				if (! $one_checked) {
					$row['prop']['checked'] = true;
					$one_checked = true;
				} else if (! $search_string) {
					$row['prop']['disabled'] = true;
				}
			}

			$all_partids[$partid] = $partid;

			$nflag = 0;
			$notes = getNotes($partid);
			$row['notes'] = array();
			foreach ($notes as $note) {
				if ($note['datetime']>$recent_date) { $nflag = 2; }
				else if (! $nflag) { $nflag = 1; }

//				$row['notes'][] = $note['id'];
			}

//			if (! $nflag) { $nflag = '<span class="item-notes"><i class="fa fa-sticky-note-o"></i></span>'; }
			$row['notes'] = $nflag;

			// gymnastics to force json to not re-sort array results, which happens when the key is an integer instead of string
			unset($H[$partid]);

			$row['fav'] = 0;//'fa-star-o';

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

				$QUOTE = getQuote($listid,$list_label,$partid,$qty);
				$row['qty'] = $QUOTE['qty'];
				$row['price'] = $QUOTE['price'];

				$row['descr'] = utf8_encode($row['description']);
				//$row['Descr'] = utf8_encode($row['Descr']);
				unset($row['description']);
				unset($row['Descr']);
				unset($row['Manf']);
				unset($row['RelValue']);
				if ($row['manfid']) {
					$manfs[$row['manfid']] = $row['manf'];
				}
				unset($row['manf']);
				if ($row['systemid']) {
					$systems[$row['systemid']] = $row['system'];
				}
				unset($row['system']);
				$row['part'] = $row['primary_part'];
				unset($row['primary_part']);
				unset($row['Part']);
				unset($row['HECI']);
				unset($row['heci7']);
				unset($row['name']);
				$row['class'] = $row['classification'];
				unset($row['classification']);
				unset($row['fpart']);
				unset($row['Key']);
				unset($row['Sys']);

				// flag this result as a sub
				$row['class'] = 'sub';
				$row['prop'] = array('checked'=>false,'disabled'=>false,'readonly'=>false,'type'=>'checkbox');
				if ($QUOTE['id']) { $row['prop']['checked'] = true; }

				if ($list_label=='Repair') {
					$row['prop']['checked'] = false;
//					$row['prop']['type'] = 'radio';
					if (! $search_string) {
						$row['prop']['disabled'] = true;
					}
				}

				// include sub matches
				$all_partids[$partid] = $partid;

//				$row['notes'] = getNotes($partid);
				$nflag = 0;
				$notes = getNotes($partid);
				$row['notes'] = array();
				foreach ($notes as $note) {
					if ($note['datetime']>$recent_date) { $nflag = 2; }
					else if (! $nflag) { $nflag = 1; }

//					$row['notes'][] = $note['id'];
				}

//				if (! $nflag) { $nflag = '<span class="item-notes"><i class="fa fa-sticky-note-o"></i></span>'; }
				$row['notes'] = $nflag;

				$row['fav'] = 0;//'fa-star-o';

				// gymnastics to force json to not re-sort array results, which happens when the key is an integer instead of string
				unset($H[$partid]);

				if ($row['stk']>0) { $stock[$partid."-"] = $row; }
				else if ($row['stk']===0) { $zerostock[$partid."-"] = $row; }
				else { $nonstock[$partid."-"] = $row; }
//				$H[$partid."-"] = $row;
			}
		}

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
		foreach ($favs as $pid => $F) {
			// if this user has marked one of these as a favorite, show it as their own
			if (isset($F[$U['id']])) {
				$H[$pid."-"]['fav'] = 2;
			} else {
				$H[$pid."-"]['fav'] = 1;
			}
		}

/*
		$PR = getDQ($partids);
		if ($filter_PR!==false AND $PR<$filter_PR) {
			continue;
//			$line_number++;
		}
*/

		$market = getHistory($partids,$this_month);

		if ($list_label=='Service' OR $list_label=='Repair') {
			$row_ln = $line_number;
		} else {
			$row_ln = $line_number+1;
		}
//		echo $row_ln.chr(10);

		$prop = '';//disabled';

		if ($list_label=='metaid') { $row_ln = $line_number; }
		$r = array(
			'ln'=>$row_ln,
			's'=>$search,
			'str'=>utf8_encode($line),
			'q'=>$search_qty,
			'p'=>$search_price,
			'lt'=>$row_lt,
			'lts'=>$row_ltspan,
			'm'=>$row_markup,
			'qt'=>$row_quote,
			'id'=>$id,
			'lbl'=>$label,
			'prop'=>$prop,
			'results'=>$H,
		);
		$results[$line_number] = $r;
	}

	if (! $line_number) { $line_number = 0; }
	if ($filter_LN===false AND (! $max_lines OR ($lim+$max_lines))>=$N) {
		$line_number++;
		if ($list_label=='metaid' OR $list_label=='Service' OR $list_label=='Repair') { $row_ln = $line_number; }
		else { $row_ln = $line_number+1; }

		$results[$line_number] = array(
			'ln' => $row_ln,
			's' => '',
			'str' => '',
			'q' => '',
			'p' => '',
			'lt' => '',
			'lts' => '',
			'm' => '',
			'qt' => '',
			'id' => '',
			'lbl' => '',
			'results' => array(),
		);
	}
//	print "<pre>".print_r($results,true)."</pre>"; exit;

	echo json_encode(
		array(
			'results'=>$results,
			'manfs' => $manfs,
			'systems' => $systems,
			'n'=>$N,
			'message' => '',
		)
	);
	exit;
?>
