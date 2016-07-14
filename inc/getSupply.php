<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/ps.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/bb.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/te.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/ebay.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/excel.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logRemotes.php';

	function array_append(&$arr1,$arr2) {
		foreach ($arr2 as $date => $arr) {
			if (! isset($arr1[$date])) { $arr1[$date] = array(); }

			if (! is_array($arr)) { $arr = array(); }
			foreach ($arr as $result) {
				foreach ($result as $r) {
					$r['price'] = format_price($r['price'],false);
					$arr1[$date][] = $r;
				}
			}
		}
	}
    function array_keysearch(&$haystack,$needle) {
        foreach ($haystack as $straw => $bool) {
//          echo $straw.':'.$needle.':'.stripos($straw,$needle).'<BR>';
            if (stripos($straw,$needle)!==false) {
                unset($haystack[$straw]);
//              break;
            }
        }
    }

	// global
	$err = array();
	$errmsgs = array();

	function getSupply($partid_array='',$attempt=0,$ln=0,$max_ln=2) {
		global $err,$errmsgs,$today;

		if (! $partid_array) { $partid_array = array(); }

		$done = '';
		// $attempt: 0=first attempt, get static results from db; 1=second attempt, go get remote data from api's; 2=force download
		$matches = array();

		// partids are passed in with comma-separated format
		$partid_str = "";
		foreach ($partid_array as $partid) {
			if ($partid_str) { $partid_str .= "OR "; }
			$partid_str .= "partid = '".$partid."' ";
		}

		$results = array();

		if (! $partid_str) {
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
		$query .= "WHERE (".$partid_str.") AND keywordid = keywords.id AND rank = 'primary' ";
		$query .= "ORDER BY LENGTH(keyword) DESC; ";//sort in desc length so we can work backwards to eliminate matching substrings later
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			// no duplicates, and also check if we've added 7-digit heci already or a truncated version of this string
			if (isset($searches[$r['keyword']])) { continue; }

			// eliminate matching super-strings
			array_keysearch($limited,$r['keyword']);

			$searches[$r['keyword']] = true;
			$limited[$r['keyword']] = true;

			// somehow saving processing time ???
			if (isset($checked_ids[$r['partid']])) { continue; }

			// for ebay, get original-formatted keyword with punctuations because we don't get the same results
			// with punctuation-less keywords as we do with original formats (ie, "090-58022-01")
			$query2 = "SELECT part FROM parts WHERE id = '".$r['partid']."'; ";
			$result2 = qdb($query2);
			if (mysqli_num_rows($result2)==0) { continue; }
			$checked_ids[$r['partid']] = true;

			$r2 = mysqli_fetch_assoc($result2);
			$part_strs = explode(' ',$r2['part']);
			foreach ($part_strs as $part_str) {
				$part_str = format_part($part_str);
				$fpart = preg_replace('/[^[:alnum:]]+/','',$part_str);
				if ($r['keyword']==$fpart) { $searches[$part_str] = true; }
			}

//			echo $r['keyword'].'<BR>';
		}


		/***** BROKER STRING BUILDING *****/

		// string unique searches now into single line-separated string
		$psstr = '';
		$bbstr = '';
		$ebaystr = '';
		$excelstr = '';
		$ps_err = '';
		$bb_err = '';
		$te_err = '';
		$ebay_err = '';
		$excel_err = '';
		foreach ($searches as $keyword => $bool) {
			// try remotes only after the first attempt ($attempt==0) because we want the first attempt to produce
			// statically-stored db results
			if ($attempt>=1 AND ($ln<=$max_ln OR $attempt==2)) {
				// log attempts on remotes for every keyword based on current remote session settings, regardless of error outcomes below

				// if this is not in $limited[] it's because it would produce redundant results for broker sites,
				// but for ebay it's more precise because their search method is pickier
				if (! isset($limited[$keyword])) {
					$RLOG = logRemotes($keyword,'000100');
				} else {
					$RLOG = logRemotes($keyword);
				}
			} else {
				$RLOG = logRemotes($keyword,'000000');
			}

			// place this first because results below may be limited due to $limited/$searches differences
			if ($RLOG['ebay']) {
				if ($ebaystr) { $ebaystr .= ','; }
				$ebaystr .= $keyword;
			}

			// only continue beyond this point if the keyword is in $limited[], which is our less-redundant
			// array (ie, NTK555DA only, as opposed to NTK555DA + NTK555DAE5) on account of ebay's pickier
			// search method but the broker sites are more open / relaxed
			if (! isset($limited[$keyword])) { continue; }

			if ($RLOG['ps']) { $psstr .= $keyword.chr(10); }
			if ($RLOG['bb']) { $bbstr .= $keyword.chr(10); }
			if ($RLOG['excel']) { $excelstr .= $keyword.chr(10); }
//			$bbstr .= $keyword.chr(10);

			// gotta hit tel-explorer individually because there's no work-around for their multi-search (when not logged in)
			if ($RLOG['te']) {
				$te = te($keyword);
				if ($te_err) {
					$err[] = 'te';
					$errmsgs[] = $te_err;
				}
			}
		}

		if ($attempt>=1) {
			if ($psstr) {
				$ps_err = ps($psstr);
				if ($ps_err) {
					$err[] = 'ps';
					$errmsgs[] = $ps_err;
				}
			}
			if ($bbstr) {
				$bb_err = bb($bbstr);
				if ($bb_err) {
					$err[] = 'bb';
					$errmsgs[] = $bb_err;
				}
			}
			if ($ebaystr) {
				$ebay_err = ebay($ebaystr);
				if ($ebay_err) {
					$err[] = 'ebay';
					$errmsgs[] = $ebay_err;
				}
			}
			if ($excelstr) {
				$excel_err = excel($excelstr);
				if ($excel_err) {
					$err[] = 'excel';
					$errmsgs[] = $excel_err;
				}
			}

			// when we're done with all remote calls
			$done = 1;
		}


		// get stored rfqs from various users on the partids passed in

		$rfqs = array();
//		$query = "SELECT partid FROM rfqs WHERE userid = '".$U['id']."' AND datetime LIKE '".$today."%' AND (".$partid_str."); ";
		$query = "SELECT partid, companyid, LEFT(datetime,10) date FROM rfqs WHERE datetime LIKE '".$today."%' AND (".$partid_str."); ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$rfqs[$r['partid']][$r['companyid']][$r['date']] = true;
		}


		$query = "SELECT partid, companies.name, search_meta.datetime, SUM(avail_qty) qty, avail_price price, source, companyid, '' rfq ";
		$query .= "FROM availability, search_meta, companies ";
		$query .= "WHERE (".$partid_str.") AND metaid = search_meta.id AND search_meta.companyid = companies.id ";
		$query .= "AND companies.id <> '1118' ";
//		$query .= "GROUP BY search_meta.datetime, companyid, source ORDER BY datetime DESC; ";
		$query .= "GROUP BY partid, datetime, companyid, source ORDER BY datetime DESC; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$date = substr($r['datetime'],0,10);
			$key = $date.'.'.$r['companyid'].'.'.$r['source'];

			// if an rfq has been submitted against this partid, log it against the $key
			if (isset($rfqs[$r['partid']]) AND isset($rfqs[$r['partid']][$r['companyid']]) AND isset($rfqs[$r['partid']][$r['companyid']][$date])) { $r['rfq'] = 'Y'; }

			// add missing gaps of info from previous iterations (ie, same date but earlier in the day had a price, whereas the first found record had no price)
			if (isset($results[$key])) {
				// if price is in this iteration whereas not found in previous ($results), set it to this price
				if ($r['price']>0 AND (! $results[$key]['price'] OR $results[$key]['price']=='0.00')) { $results[$key]['price'] = $r['price']; }
				$results[$key]['qty'] += $r['qty'];

				// add rfq flag if it has been rfq'd by user (see query above)
				if ((isset($rfqs[$r['partid']]) AND isset($rfqs[$r['partid']][$r['companyid']]) AND isset($rfqs[$r['partid']][$r['companyid']][$date])) OR $results[$key]['rfq']=='Y') {
					$results[$key]['rfq'] = 'Y';
				}
				continue;
			}
			// save memory in array
			unset($r['partid']);

//			$result[] = $r;
			$results[$key] = $r;
		}

		// legacy code/query
		$query = "SELECT partid, name, datetime, SUM(qty) qty, price, source, companyid, '' rfq FROM market, companies ";
		$query .= "WHERE (".$partid_str.") AND market.companyid = companies.id ";
		$query .= "AND companies.id <> '1118' ";
//		$query .= "GROUP BY datetime, companyid, source ORDER BY datetime DESC; ";
		$query .= "GROUP BY partid, datetime, companyid, source ORDER BY datetime DESC; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$date = substr($r['datetime'],0,10);
			$key = $date.'.'.$r['companyid'].'.'.$r['source'];

			// if an rfq has been submitted against this partid, log it against the $key
			if (isset($rfqs[$r['partid']]) AND isset($rfqs[$r['partid']][$r['companyid']]) AND isset($rfqs[$r['partid']][$r['companyid']][$date])) { $r['rfq'] = 'Y'; }

			if (isset($results[$key])) {
				if ($r['price']>0 AND (! $results[$key]['price'] OR $results[$key]['price']=='0.00')) { $results[$key]['price'] = $r['price']; }
				$results[$key]['qty'] += $r['qty'];
				if ((isset($rfqs[$r['partid']]) AND isset($rfqs[$r['partid']][$r['companyid']]) AND isset($rfqs[$r['partid']][$r['companyid']][$date])) OR $results[$key]['rfq']=='Y') {
					$results[$key]['rfq'] = 'Y';
				}
				continue;
			}
			unset($r['partid']);

//			$result[] = $r;
			$results[$key] = $r;
		}

		$min_price = false;
		$max_price = false;
//		$keys = array();//prevent duplicate results on same day
		foreach ($results as $r) {
			$date = substr($r['datetime'],0,10);

			// log based on keyed result to avoid duplicates
//			if (isset($keys[$date.'.'.$r['companyid'].'.'.$r['source']])) { continue; }
//			$keys[$date.'.'.$r['companyid'].'.'.$r['source']] = true;

			$price = false;
			if ($r['price']>0) {
				$price = $r['price'];
				if ($min_price===false OR $r['price']<$min_price) { $min_price = $r['price']; }
				if ($max_price===false OR $r['price']>$max_price) { $max_price = $r['price']; }
			}

			$source = false;
			$companyid_key = $r['companyid'];
			if (! is_numeric($r['source']) AND $r['source']<>'List') {
				$source = $r['source'];
			} else if (is_numeric($r['source']) AND strlen($r['source'])==12) {//ebay ids are 12-chars
//				$companyid_key .= '.'.$r['source'];
				$source = 'ebay';
			}

			if (! isset($matches[$date])) { $matches[$date] = array(); }
			if (! isset($matches[$date][$companyid_key])) {
				$matches[$date][$companyid_key] = array(
					'company' => $r['name'],
					'cid' => $r['companyid'],
					'qty' => $r['qty'],
					'price' => $price,
					'changeFlag' => 'circle-o',
					'rfq' => $r['rfq'],
					'sources' => array(),
					'min_price' => $price,
					'max_price' => $price,
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

			if ($source AND array_search($source,$matches[$date][$companyid_key]['sources'])===false) { $matches[$date][$companyid_key]['sources'][] = $source; }
		}
		unset($results);
//		unset($keys);


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
		if ($ln<=$max_ln OR $attempt==2) {
			$market = array($today=>array());
		}
		array_append($market,$priced);
		array_append($market,$standard);


		// create date-separated headers for each group of results
		$query = "SELECT LEFT(searches.datetime,10) date FROM keywords, parts_index, searches ";
		$query .= "WHERE (".$partid_str.") AND scan LIKE '%1%' AND keywords.id = parts_index.keywordid AND keyword = search ";
		$query .= "GROUP BY date; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$search_date = $r['date'];
			if (! isset($market[$search_date])) { $market[$search_date] = array(); }
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
			if ($n>=5) { break; }

			$newRows = array();
			foreach ($r as $k => $row) {
/*
				if ($k>=$attempt AND $rDate==$today) {
					$newResults['done'] = false;
					continue;
				}
*/
				$newRows[] = $row;
			}

			$rDate = summarize_date($rDate);

			$newResults['results'][$rDate] = $newRows;
			$n++;
		}
//		print "<pre>".print_r($newResults,true)."</pre>";

		return ($newResults);
	}
?>
