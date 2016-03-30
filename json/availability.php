<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/format_date.php';
	include_once '../inc/format_price.php';
	include_once '../inc/ps.php';
	include_once '../inc/bb.php';
	include_once '../inc/te.php';
	include_once '../inc/excel.php';
	include_once '../inc/logRemotes.php';

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
	function array_stristr(&$haystack,$needle) {
		foreach ($haystack as $straw => $bool) {
//			echo $straw.':'.$needle.':'.stripos($straw,$needle).'<BR>';
			if (stripos($straw,$needle)!==false) {
				unset($haystack[$straw]);
//				break;
			}
		}
	}

	$done = '';
	$attempt = 0;
	$max_ln = 2;
	if (isset($_REQUEST['attempt']) AND is_numeric($_REQUEST['attempt'])) { $attempt = $_REQUEST['attempt']; }
	$partids = "";
	if (isset($_REQUEST['partids'])) { $partids = $_REQUEST['partids']; }
	$metaid = 0;
	if (isset($_REQUEST['metaid']) AND is_numeric($_REQUEST['metaid'])) { $metaid = $_REQUEST['metaid']; }
	$ln = 0;
	if (isset($_REQUEST['ln']) AND is_numeric($_REQUEST['ln'])) { $ln = $_REQUEST['ln']; }

	$matches = array();
	$partid_array = explode(",",$partids);
	$partid_str = "";
	foreach ($partid_array as $partid) {
		if ($partid_str) { $partid_str .= "OR "; }
		$partid_str .= "partid = '".$partid."' ";
	}

	$err = array();
	$errmsgs = array();


/* for testing purposes
		$results = array();
		if ($ln<=$max_ln) { $results = array($today=>array()); }
		$newResults = array('results'=>$results,'done'=>'','err'=>$err,'errmsgs'=>$errmsgs);
		header("Content-Type: application/json", true);
		echo json_encode($newResults);
		exit;
*/


	if (! $partid_str) {
		// limit today's results (and broker searches below) to the first few lines
		$results = array();
		if ($ln<=$max_ln) { $results = array($today=>array()); }
		$newResults = array('results'=>$results,'done'=>1,'err'=>$err,'errmsgs'=>$errmsgs);
		header("Content-Type: application/json", true);
		echo json_encode($newResults);
		exit;
	}

	// for now (feb 2016) we have results in market and availability tables; get from both by storing in single array
	$results = array();

	// stash all searches in strings for each remote
	$searches = array();

	$query = "SELECT keyword FROM keywords, parts_index WHERE (".$partid_str.") AND keywordid = keywords.id AND rank = 'primary' ";
	$query .= "ORDER BY LENGTH(keyword) DESC; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		// no duplicates, and also check if we've added 7-digit heci already or a truncated version of this string
		if (isset($searches[$r['keyword']])) { continue; }

		array_stristr($searches,$r['keyword']);
		$searches[$r['keyword']] = true;

//		echo $r['keyword'].'<BR>';
	}

	// string unique searches now into single line-separated string
	$psstr = '';
	$bbstr = '';
	$excelstr = '';
	$ps_err = '';
	$bb_err = '';
	$te_err = '';
	$excel_err = '';
	foreach ($searches as $keyword => $bool) {
		// try remotes only after the first attempt ($attempt==0) because we want the first attempt to produce
		// statically-stored db results
		if ($attempt>=1 AND $ln<=$max_ln) {
			// log attempts on remotes for every keyword based on current remote session settings, regardless of error outcomes below
			$RLOG = logRemotes($keyword);
		} else {
			$RLOG = logRemotes($keyword,'00000');
		}

		if ($RLOG['ps']) { $psstr .= $keyword.chr(10); }
		if ($RLOG['bb']) { $bbstr .= $keyword.chr(10); }
		if ($RLOG['excel']) { $excelstr .= $keyword.chr(10); }
//		$bbstr .= $keyword.chr(10);

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
		if ($excelstr) {
			$excel_err = excel($excelstr);
			if ($excel_err) {
				$err[] = 'excel';
				$errmsgs[] = $excel_err;
			}
		}
		$done = 1;
	}

	$query = "SELECT companies.name, search_meta.datetime, SUM(avail_qty) qty, avail_price price, source, companyid ";
	$query .= "FROM availability, search_meta, companies ";
	$query .= "WHERE (".$partid_str.") AND metaid = search_meta.id AND search_meta.companyid = companies.id ";
$query .= "AND companies.id <> '1118' ";
	$query .= "GROUP BY search_meta.datetime, companyid, source ORDER BY datetime DESC; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$results[] = $r;
	}

	$query = "SELECT name, datetime, SUM(qty) qty, price, source, companyid FROM market, companies ";
	$query .= "WHERE (".$partid_str.") AND market.companyid = companies.id ";
$query .= "AND companies.id <> '1118' ";
	$query .= "GROUP BY datetime, companyid, source ORDER BY datetime DESC; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$results[] = $r;
	}

	$keys = array();//prevent duplicate results on same day
	foreach ($results as $r) {
		$date = substr($r['datetime'],0,10);

		// log based on keyed result to avoid duplicates
		if (isset($keys[$date.'.'.$r['companyid'].'.'.$r['source']])) { continue; }
		$keys[$date.'.'.$r['companyid'].'.'.$r['source']] = true;

		$price = false;
		if ($r['price']>0) { $price = $r['price']; }
		$source = false;
		if (! is_numeric($r['source']) AND $r['source']<>'List') { $source = $r['source']; }

		if (! isset($matches[$date])) { $matches[$date] = array(); }
		if (! isset($matches[$date][$r['companyid']])) {
			$matches[$date][$r['companyid']] = array(
				'company' => $r['name'],
				'cid' => $r['companyid'],
				'qty' => $r['qty'],
				'price' => $price,
				'changeFlag' => 'circle-o',
				'sources' => array(),
			);
		} else if ($r['qty']>$matches[$date][$r['companyid']]['qty']) {
			$matches[$date][$r['companyid']]['qty'] = $r['qty'];
		}

		if ($source) { $matches[$date][$r['companyid']]['sources'][] = $source; }
	}
	unset($results);
	unset($keys);

	krsort($matches);

	$priced = array();
	$standard = array();
	foreach ($matches as $date => $companies) {
		foreach ($companies as $companyid => $r) {
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
	if ($ln<=$max_ln) {
		$market = array($today=>array());
	}
	array_append($market,$priced);
	array_append($market,$standard);

	unset($priced);
	unset($standard);

	$newResults = array('results'=>array(),'done'=>$done,'err'=>$err,'errmsgs'=>$errmsgs);
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
//	print "<pre>".print_r($newResults,true)."</pre>";

	header("Content-Type: application/json", true);
	echo json_encode($newResults);
	exit;
?>
