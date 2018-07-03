<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRecords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/searchRemotes.php';

	// global
	$err = array();
	$errmsgs = array();
	$rfq_base_date = format_date($today,'Y-m-d 00:00:00',array('d'=>-21));//look up rfq's within the past 3 weeks
	// 0=all results; 1=pricing only; 2=ghosted inventories
	if (! isset($results_mode)) { $results_mode = 0; }
	if (! isset($detail)) { $detail = 0; }
	$urls = array(
		'te'=>'www.tel-explorer.com/Main_Page/Search/Part_srch_go.php?part=',
		'ps'=>'www.powersourceonline.com/iris-item-search.authenticated-en.jsa?Q=',
		'bb'=>'members.brokerbin.com/main.php?loc=partkey&clm=partclei&parts=',
		'et'=>'',
	);

	if (! isset($record_start)) { $record_start = ''; }
	if (! isset($record_end)) { $record_end = ''; }
	function getSupply($partid_array='',$attempt=0,$ln=0,$max_ln=2) {
		global $err,$errmsgs,$today,$rfq_base_date,$results_mode,$detail,$urls,$record_start,$record_end;

		if (! $partid_array) { $partid_array = array(); }

		// $attempt: 0=first attempt, get static results from db; 1=second attempt, go get remote data from api's; 2=force download
		$matches = array();

		// partids are passed in with comma-separated format
		$partid_csv = "";
		foreach ($partid_array as $partid) {
			if ($partid_csv) { $partid_csv .= ','; }
			$partid_csv .= $partid;
		}

		$results = array();

		if (! $partid_csv) {
			if ($ln<=$max_ln OR $attempt==2) { $results = array($today=>array()); }
			$newResults = array('results'=>$results,'price_range'=>'','done'=>1,'err'=>$err,'errmsgs'=>$errmsgs);
			return ($newResults);
		}

		// for now (feb 2016) we have results in market and availability tables; get from both by storing in single array
		$results = array();

		$done = searchRemotes($partid_csv,$attempt,$ln,$max_ln);



		$prices = array();//track prices in query results below so we can post-humously price later-dated results
		$rows = array();

		// get stored rfqs from various users on the partids passed in

		$rfqs = array();
//		$query = "SELECT partid FROM rfqs WHERE userid = '".$U['id']."' AND datetime LIKE '".$today."%' AND (".$partid_str."); ";
		//dgl 11-17-16
		//$query = "SELECT partid, companyid, LEFT(datetime,10) date FROM rfqs WHERE datetime >= '".$rfq_base_date."%' AND (".$partid_str."); ";
		$query = "SELECT partid, companyid, LEFT(datetime,10) date FROM rfqs WHERE datetime >= '".$rfq_base_date."%' AND partid IN (".$partid_csv.") ";
		$query .= "ORDER BY datetime DESC; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			//$rfqs[$r['partid']][$r['companyid']][$r['date']] = true;
			$rfqs[$r['partid']][$r['companyid']] = format_date($r['date'],'D n/j/y');

			// to be sure rfqs are added to results, even if the qty is 0
			$key = $r['date'].'.'.$r['companyid'];
			$row = array(
				'company' => getCompany($r['companyid']),
				'cid' => $r['companyid'],
				'qty' => 0,
				'price' => '',
				'date' => $r['date'],
				'partid' => $r['partid'],
				'changeFlag' => 'circle-o',
				'rfq' => $rfqs[$r['partid']][$r['companyid']],
				'sources' => array(),
				'min_price' => false,
				'max_price' => false,
				'lns' => array('search'=>''),

				'datetime' => $r['date'],
				'companyid' => $r['companyid'],
				'name' => getCompany($r['companyid']),
			);

			$rows[] = $row;
//			$results[$key] = $row;
		}

		//$query = "SELECT availability.partid, companies.name, search_meta.datetime, SUM(avail_qty) qty, ";
		$query = "SELECT availability.partid, companies.name, search_meta.datetime, MAX(avail_qty) qty, ";
		$query .= "avail_price price, source, search_meta.companyid, '' rfq, searchid, availability.id ";
		$query .= "FROM availability, search_meta, companies ";
		// view only ghosted inventories
		if ($results_mode==2) { $query .= ", staged_qtys "; }
		$query .= "WHERE availability.partid IN (".$partid_csv.") AND metaid = search_meta.id AND search_meta.companyid = companies.id ";
//		$query .= "AND companies.id <> '1118' AND companies.id <> '669' AND companies.id <> '2381' AND companies.id <> '473' AND companies.id <> '1125' AND companies.id <> '1034' ";
		$query .= "AND companies.id NOT IN (1118,669,2381,473,1125,1034,3053,1184) ";
		if ($record_start && $record_end){$query .= " AND search_meta.datetime between CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
		// view only ghosted inventories
		if ($results_mode==2) { $query .= "AND staged_qtys.partid = availability.partid AND staged_qtys.companyid = search_meta.companyid "; }
//		$query .= "GROUP BY search_meta.datetime, search_meta.companyid, source ORDER BY datetime DESC; ";
//$query .= "AND companyid = 3 ";
		$query .= "GROUP BY availability.partid, datetime, search_meta.companyid, source ORDER BY IF(price>0,0,1), datetime DESC; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$date = substr($r['datetime'],0,10);
			if ($r['price']>0) {
				if ($results_mode==0) {// we don't want grouped or summed or averaged prices when in pricing-only mode
					$prices[$r['companyid']][$date] = $r['price'];
				}
			} else if ($results_mode==1) {//in modes where the user wants to see only records that have prices
				continue;
			}
			$rows[] = $r;
		}

		foreach ($rows as $r) {
			$date = substr($r['datetime'],0,10);
			$base = $date.'.'.$r['companyid'].'.';
			$key = $base.$r['source'];

			if ((! $r['price'] OR $r['price']=='0.00') AND isset($prices[$r['companyid']])) {
				krsort($prices[$r['companyid']]);//sort in reverse order to get most recent result first
				foreach ($prices[$r['companyid']] as $p) {
					$r['price'] = $p;
					break;//we got what we wanted with the first identifiable price, now get out of here!
				}
			}
			// create array of partids so we can sum qtys on a given date or avoid duplicating qtys
			$r['partids'] = array($r['partid']);

			// if an rfq has been submitted against this partid, log it against the $key
			if (isset($rfqs[$r['partid']]) AND isset($rfqs[$r['partid']][$r['companyid']])) { $r['rfq'] = $rfqs[$r['partid']][$r['companyid']]; }

			// add missing gaps of info from previous iterations (ie, same date but earlier in the day had a price, whereas the first found record had no price)
			if (isset($results[$key])) {
				if ($results_mode==0) {// we don't want grouped or summed or averaged prices when in pricing-only mode
					// if price is in this iteration whereas not found in previous ($results), set it to this price
					if ($r['price']>0 AND (! $results[$key]['price'] OR $results[$key]['price']=='0.00')) { $results[$key]['price'] = $r['price']; }
				}
				// add search string to result if we don't have it in a previously-iterated result
				if ($r['searchid'] AND ! $results[$key]['searchid']) { $results[$key]['searchid'] = $r['searchid']; }

				// check array of partids and if partid hasn't been logged, sum qty; otherwise we don't count it to avoid duplicating qty
				if (array_search($r['partid'],$results[$key]['partids'])===false) {
					$results[$key]['partids'][] = $r['partid'];
					$results[$key]['qty'] += $r['qty'];
				}

				// add rfq flag if it has been rfq'd by user (see query above)
				if ((isset($rfqs[$r['partid']]) AND isset($rfqs[$r['partid']][$r['companyid']])) OR $results[$key]['rfq']) {
					if (! isset($results[$key]['rfq'])) { $results[$key]['rfq'] = false; }
					$results[$key]['rfq'] = $rfqs[$r['partid']][$r['companyid']];
				}
				continue;
			} else if (isset($results[$base])) {//update source-less results
				if ($r['searchid'] AND ! $results[$base]['searchid']) { $results[$base]['searchid'] = $r['searchid']; }
			}
			// save memory in array
			unset($r['partid']);

//			$result[] = $r;
			$results[$key] = $r;
		}
//print_r($results);exit;

/* dgl 6-20-17 because we don't really need this market (broker sites) data at this point

		// legacy code/query
		$query = "SELECT market.partid, name, datetime, SUM(qty) qty, price, source, market.companyid, ";
		$query .= "'' rfq, '' searchid, '' id ";
		$query .= "FROM market, companies ";
		// view only ghosted inventories
		if ($results_mode==2) { $query .= ", staged_qtys "; }
		$query .= "WHERE partid IN (".$partid_csv.") AND market.companyid = companies.id ";
		$query .= "AND companies.id <> '1118' AND companies.id <> '669' ";
		// view only ghosted inventories
		if ($results_mode==2) { $query .= "AND staged_qtys.partid = market.partid AND staged_qtys.companyid = market.companyid "; }
//		$query .= "GROUP BY datetime, companyid, source ORDER BY datetime DESC; ";
		$query .= "GROUP BY market.partid, datetime, market.companyid, source ORDER BY datetime DESC; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			if (($r['price']=='0.00' OR ! $r['price']) AND $results_mode==1) {//in modes where the user wants to see only records that have prices
				continue;
			}

			$date = substr($r['datetime'],0,10);
			$key = $date.'.'.$r['companyid'].'.'.$r['source'];
			// create array of partids so we can sum qtys on a given date or avoid duplicating qtys
			$r['partids'] = array($r['partid']);

			// if an rfq has been submitted against this partid, log it against the $key
			if (isset($rfqs[$r['partid']]) AND isset($rfqs[$r['partid']][$r['companyid']])) { $r['rfq'] = $rfqs[$r['partid']][$r['companyid']]; }

			if (isset($results[$key])) {
				if ($r['price']>0 AND (! $results[$key]['price'] OR $results[$key]['price']=='0.00')) { $results[$key]['price'] = $r['price']; }

				// check array of partids and if partid hasn't been logged, sum qty; otherwise we don't count it to avoid duplicating qty
				if (array_search($r['partid'],$results[$key]['partids'])===false) {
					$results[$key]['partids'][] = $r['partid'];
					$results[$key]['qty'] += $r['qty'];
				}

				if ((isset($rfqs[$r['partid']]) AND isset($rfqs[$r['partid']][$r['companyid']])) OR $results[$key]['rfq']) {
					if (! isset($results[$key]['rfq'])) { $results[$key]['rfq'] = false; }
					$results[$key]['rfq'] = $rfqs[$r['partid']][$r['companyid']];
				}
				continue;
			}
			unset($r['partid']);

//			$result[] = $r;
			$results[$key] = $r;
		}
*/

		$min_price = false;
		$max_price = false;
		foreach ($results as $r) {
			$date = substr($r['datetime'],0,10);

			$price = false;
			if ($r['price']>0) {
				$price = $r['price'];
				if ($min_price===false OR $r['price']<$min_price) { $min_price = $r['price']; }
				if ($max_price===false OR $r['price']>$max_price) { $max_price = $r['price']; }
			}

			$source = false;
			$ref_ln = '';
			$companyid_key = $r['companyid'];
			$search = getSearch($r['searchid']);
			if (! is_numeric($r['source']) AND $r['source']<>'List') {
				$source = strtolower($r['source']);
				if (isset($urls[$r['source']]) AND $r['searchid']) {
					$ref_ln = $urls[$r['source']].$search;
				}
			} else if (is_numeric($r['source']) AND strlen($r['source'])==12) {//ebay ids are 12-chars
				$source = 'ebay';
				$ref_ln = 'ebay.com/itm/'.$r['source'];
			}

			if (! isset($matches[$date])) { $matches[$date] = array(); }
			if (! isset($matches[$date][$companyid_key])) {
				$matches[$date][$companyid_key] = array(
					'company' => $r['name'],
					'cid' => $r['companyid'],
					'qty' => $r['qty'],
					'price' => $price,
					'date' => $date,
					'changeFlag' => 'circle-o',
					'rfq' => $r['rfq'],
					'sources' => array(),
					'min_price' => $price,
					'max_price' => $price,
					'lns' => array(),
					'search' => $search,
				);
			} else {
				// on ebay results, sum the qtys and show price range rather than every individual result
				if ($source=='ebay' AND $price>0 AND $price<>$matches[$date][$companyid_key]['price'] AND $matches[$date][$companyid_key]['price']>0) {
					$matches[$date][$companyid_key]['qty'] += $r['qty'];
					if ($price>0 AND $price<$matches[$date][$companyid_key]['min_price']) {
						$matches[$date][$companyid_key]['min_price'] = $price;
					}
					if ($price>$matches[$date][$companyid_key]['max_price']) {
						$matches[$date][$companyid_key]['max_price'] = $price;
					}
					if ($matches[$date][$companyid_key]['min_price']<>$matches[$date][$companyid_key]['max_price']) {
						$matches[$date][$companyid_key]['price'] = $matches[$date][$companyid_key]['min_price'].'-'.$matches[$date][$companyid_key]['max_price'];
					}
				} else if ($r['qty']>$matches[$date][$companyid_key]['qty']) {
					$matches[$date][$companyid_key]['qty'] = $r['qty'];
				}
			}
			if ($detail AND $ref_ln) {
				$matches[$date][$companyid_key]['lns'][$source][] = $ref_ln;
			}

			if ($source AND array_search($source,$matches[$date][$companyid_key]['sources'])===false) { $matches[$date][$companyid_key]['sources'][] = $source; }
		}
		unset($results);


		/***** SORTING FOR VISUAL DISPLAY *****/
		// sort results with priced items first, then descending by qty

		krsort($matches);

		$priced = array();
		$standard = array();
		foreach ($matches as $date => $companies) {
			foreach ($companies as $companyid_key => $r) {
				if ($r['price']) {
					$priced[$date][$r['price']][] = $r;
				} else {
					$standard[$date][$r['qty']][] = $r;
				}
				// sort descending by keys
				if (isset($priced[$date])) { krsort($priced[$date]); }
				if (isset($standard[$date])) { krsort($standard[$date]); }
			}
		}

		$market = array();
		// include today's date preset in case there are no results, since we still need the header, so long as
		// within the first few lines of results; after that, we want the user to see that broker searches didn't happen
		if ($results_mode==0 AND ($ln<=$max_ln OR $attempt==2)) {
			$market = array($today=>array());
		}
		array_append($market,$priced);
		array_append($market,$standard);


		// create date-separated headers for each group of results
		if ($results_mode==0) {
			$query = "SELECT LEFT(searches.datetime,10) date FROM keywords, parts_index, searches ";
			//dgl 11-17-16
			//$query .= "WHERE (".$partid_str.") AND scan LIKE '%1%' AND keywords.id = parts_index.keywordid AND keyword = search ";
			$query .= "WHERE parts_index.partid IN (".$partid_csv.") AND scan LIKE '%1%' AND keywords.id = parts_index.keywordid AND keyword = search ";
			$query .= "GROUP BY date; ";
			$result = qdb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				$search_date = $r['date'];
				if (! isset($market[$search_date])) { $market[$search_date] = array(); }
			}
		}

		// sort here instead of order in query above because $market already contains date-keyed results and we want to sort altogether
		krsort($market);
//		print "<pre>".print_r($market,true)."</pre>";exit;

		unset($priced);
		unset($standard);

		$newResults = array('results'=>array(),'price_range'=>array('min'=>$min_price,'max'=>$max_price),'done'=>$done,'err'=>$err,'errmsgs'=>$errmsgs);

		$n = 0;
		foreach ($market as $rDate => $r) {
//for now, just show past 5 dates
			if ($n>=5 AND $results_mode==0 AND ! $detail) { break; }

			$rDate = summarize_date($rDate);

			if (count($r)==0) {
				$newResults['results'][$rDate] = array();
				$n++;
				continue;
			}

//see commented block below, and explanation, 8-24-16
//			$newRows = array();
			foreach ($r as $k => $row) {
				// changed 8-24-16 mostly to allow for past grouped results to be enumerated rather
				// than overwritten by a conflict of such a gruoped date between new results and legacy results
				//$newRows[] = $row;
				if (isset($newResults['results'][$rDate][$rDate.'.'.$row['cid']])) { continue; }

				$newResults['results'][$rDate][$rDate.'.'.$row['cid']] = $row;
			}
			if (count($r)>0) { $n++; }

//see commented section above, 8-24-16
//			$newResults['results'][$rDate] = $newRows;
//			$n++;
		}
//		print "<pre>".print_r($newResults,true)."</pre>";

		return ($newResults);
	}

	function getDemand($partid_array='',$attempt=0,$ln=0,$max_ln=2) {
		global $err,$errmsgs,$today,$rfq_base_date,$results_mode,$detail,$urls,$REMOTES;

		if (! $partid_array) { $partid_array = array(); }

		$done = '';
		// $attempt: 0=first attempt, get static results from db; 1=second attempt, go get remote data from api's; 2=force download
		$matches = array();

		// partids are passed in with comma-separated format
//		$partid_str = "";
		$partid_csv = "";
		foreach ($partid_array as $partid) {
//			if ($partid_str) { $partid_str .= "OR "; }
//			$partid_str .= "partid = '".$partid."' ";
			if ($partid_csv) { $partid_csv .= ','; }
			$partid_csv .= $partid;
		}

		$results = array();

		if (! $partid_csv) {
			if ($ln<=$max_ln OR $attempt==2) { $results = array($today=>array()); }
			$newResults = array('results'=>$results,'price_range'=>'','done'=>1,'err'=>$err,'errmsgs'=>$errmsgs);
			return ($newResults);
		}

		// for now (feb 2016) we have results in market and availability tables; get from both by storing in single array
		$results = array();

		// stash all searches in strings for each remote
		$searches = array();
		$limited = array();


		/***** KEYWORD SEARCH *****/

		$checked_ids = array();
		// get primary keywords (part, heci, etc) and corresponding partid's
		$query = "SELECT keyword, partid FROM keywords, parts_index ";
		//dgl 11-17-16
		//$query .= "WHERE (".$partid_str.") AND keywordid = keywords.id AND rank = 'primary' ";
		$query .= "WHERE partid IN (".$partid_csv.") AND keywordid = keywords.id AND rank = 'primary' ";
		$query .= "ORDER BY LENGTH(keyword) DESC; ";//sort in desc length so we can work backwards to eliminate matching substrings later
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			if (strlen($r['keyword'])<2) { continue; }

			// no duplicates, and also check if we've added 7-digit heci already or a truncated version of this string
			if (isset($searches[$r['keyword']])) { continue; }

			// eliminate matching super-strings
			array_keysearch($limited,$r['keyword']);

			$searches[$r['keyword']] = $r['keyword'];
			$limited[$r['keyword']] = true;

			// somehow saving processing time ???
			if (isset($checked_ids[$r['partid']])) { continue; }

			$query2 = "SELECT part FROM parts WHERE id = '".$r['partid']."'; ";
			$result2 = qdb($query2);
			if (mysqli_num_rows($result2)==0) { continue; }
			$checked_ids[$r['partid']] = true;

			$r2 = mysqli_fetch_assoc($result2);
			$part_strs = explode(' ',$r2['part']);
			foreach ($part_strs as $part_str) {
				$part_str = format_part($part_str);
				$fpart = preg_replace('/[^[:alnum:]]+/','',$part_str);
				if ($r['keyword']==$fpart) { $searches[$part_str] = $part_str; }
			}

		}


		/***** BROKER STRING BUILDING *****/

		// string unique searches now into single line-separated string
		$rows = array();

		//$query = "SELECT demand.partid, companies.name, search_meta.datetime, SUM(request_qty) qty, ";
		$query = "SELECT demand.partid, companies.name, search_meta.datetime, MAX(request_qty) qty, ";
		$query .= "quote_price price, source, search_meta.companyid, searchid, demand.id ";
		$query .= "FROM demand, search_meta, companies ";
		$query .= "WHERE demand.partid IN (".$partid_csv.") AND metaid = search_meta.id AND search_meta.companyid = companies.id ";
		if ($record_start && $record_end){$query .= " AND search_meta.datetime BETWEEN CAST('".$record_start."' AS DATETIME) and CAST('".$record_end."' AS DATETIME) ";}
		//$query .= "GROUP BY demand.partid, CAST(datetime AS DATE), search_meta.companyid, source ORDER BY IF(price>0,0,1), datetime DESC; ";
		$query .= "GROUP BY demand.partid, CAST(datetime AS DATE), search_meta.companyid ORDER BY IF(price>0,0,1), datetime DESC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$date = substr($r['datetime'],0,10);
			$rows[] = $r;
		}

		foreach ($rows as $r) {
			$date = substr($r['datetime'],0,10);
			$key = $date.'.'.$r['companyid'].'.'.$r['source'];

			// create array of partids so we can sum qtys on a given date or avoid duplicating qtys
			$r['partids'] = array($r['partid']);

			// add missing gaps of info from previous iterations (ie, same date but earlier in the day had a price, whereas the first found record had no price)
			if (isset($results[$key])) {
				if ($results_mode==0) {// we don't want grouped or summed or averaged prices when in pricing-only mode
					// if price is in this iteration whereas not found in previous ($results), set it to this price
					if ($r['price']>0 AND (! $results[$key]['price'] OR $results[$key]['price']=='0.00')) { $results[$key]['price'] = $r['price']; }
				}
				// add search string to result if we don't have it in a previously-iterated result
				if ($r['searchid'] AND ! $results[$key]['searchid']) { $results[$key]['searchid'] = $r['searchid']; }

				// check array of partids and if partid hasn't been logged, sum qty; otherwise we don't count it to avoid duplicating qty
				if (array_search($r['partid'],$results[$key]['partids'])===false) {
					$results[$key]['partids'][] = $r['partid'];
					$results[$key]['qty'] += $r['qty'];
				}

				continue;
			}
			// save memory in array
			unset($r['partid']);

			$results[$key] = $r;
		}


		$min_price = false;
		$max_price = false;
//		$keys = array();//prevent duplicate results on same day
		foreach ($results as $r) {
			$date = substr($r['datetime'],0,10);

			$price = false;
			if ($r['price']>0) {
				$price = $r['price'];
				if ($min_price===false OR $r['price']<$min_price) { $min_price = $r['price']; }
				if ($max_price===false OR $r['price']>$max_price) { $max_price = $r['price']; }
			}

			$source = false;
			$ref_ln = '';
			$companyid_key = $r['companyid'];
			$search = getSearch($r['searchid']);
			if (! is_numeric($r['source']) AND $r['source']<>'List') {
				$source = strtolower($r['source']);
				if (isset($urls[$r['source']]) AND $r['searchid']) {
					$ref_ln = $urls[$r['source']].$search;
				}
			} else if (is_numeric($r['source']) AND strlen($r['source'])==12) {//ebay ids are 12-chars
//				$companyid_key .= '.'.$r['source'];
				$source = 'ebay';
				$ref_ln = 'ebay.com/itm/'.$r['source'];
			}

			if (! isset($matches[$date])) { $matches[$date] = array(); }
			if (! isset($matches[$date][$companyid_key])) {
				$matches[$date][$companyid_key] = array(
					'company' => $r['name'],
					'cid' => $r['companyid'],
					'qty' => $r['qty'],
					'price' => $price,
					'date' => $date,
					'changeFlag' => 'circle-o',
					'sources' => array(),
					'min_price' => $price,
					'max_price' => $price,
					'lns' => array(),
					'search' => $search,
				);
			} else {
				$matches[$date][$companyid_key]['qty'] = $r['qty'];
			}
			if ($detail AND $ref_ln) {
				$matches[$date][$companyid_key]['lns'][$source][] = $ref_ln;
			}

			if ($source AND array_search($source,$matches[$date][$companyid_key]['sources'])===false) { $matches[$date][$companyid_key]['sources'][] = $source; }
		}
		unset($results);


		/***** SORTING FOR VISUAL DISPLAY *****/
		// sort results with priced items first, then descending by qty

		krsort($matches);

		$market = array();
		$standard = array();
		foreach ($matches as $date => $companies) {
			foreach ($companies as $companyid_key => $r) {
				$standard[$date][$r['qty']][] = $r;

				// sort descending by keys
				if (isset($standard[$date])) { krsort($standard[$date]); }
			}
		}

		array_append($market,$standard);

		// sort here instead of order in query above because $market already contains date-keyed results and we want to sort altogether
		krsort($market);
//		print "<pre>".print_r($market,true)."</pre>";exit;

		unset($standard);

		$newResults = array('results'=>array(),'price_range'=>array('min'=>$min_price,'max'=>$max_price),'done'=>$done,'err'=>$err,'errmsgs'=>$errmsgs);

		$n = 0;
		foreach ($market as $rDate => $r) {
//for now, just show past 5 dates
			// if ($n>=5 AND $results_mode==0 AND ! $detail) { break; }

			$ogDate = $rDate;
			$rDate = summarize_date($rDate,false);

			if (count($r)==0) {
				$newResults['results'][$rDate] = array();
				$n++;
				continue;
			}

			foreach ($r as $k => $row) {
				//$newRows[] = $row;
				if (isset($newResults['results'][$rDate][$rDate.'.'.$row['cid']. '.' . $ogDate])) { continue; }

				$newResults['results'][$rDate][$rDate.'.'.$row['cid'] . '.' . $ogDate] = $row;


			}
			if (count($r)>0) { $n++; }
		}

		// Clean Up all the blank values from the summary
		if ($results_mode==0 AND ! $detail) { 
			if(count($newResults)>=5) {
				$newResults['results'] = array_filter($newResults['results']);
			}
			$newResults['results'] = array_slice($newResults['results'],0,5,true);
		}
		//print "<pre>".print_r($newResults,true)."</pre>";

		return ($newResults);
	}
?>
