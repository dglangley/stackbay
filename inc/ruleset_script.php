<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/calcQuarters.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/dictionary.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/cmp.php';

	// Formatting
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';

	// Getters
	include_once $_SERVER["ROOT_DIR"].'/inc/getRulesets.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRecords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCount.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFavorites.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getRFQ.php';

	include_once $_SERVER["ROOT_DIR"].'/inc/getSupply.php';

	// Emailer
	// include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';
	// include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';

	// setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	$DEBUG = 0;

	$record_start = '';
	$record_end = '';
	$company_filter = '';
	$sales_min = '';
	$sales_max = '';
	$min_price = false;
	$max_price = false;

	$favorites = 0;


	function getMinerData($RULESET_FILTERS, $ACTIONS, $SUPPLY) {
		global $favorites, $record_start, $record_end, $company_filter,$sales_min,$sales_max,$min_price,$max_price, $CMP, $QTYS;

		$FILTERS = true;

		$report_type = 'summary';
		$market_table = 'Demand';

		$data = array();

		if($RULESET_FILTERS['market']) {
			$market_table = $RULESET_FILTERS['market'];
		}

		$keyword = $RULESET_FILTERS['keyword'];

		$startDate = format_date($RULESET_FILTERS['start_date'],'m/d/Y');
		$endDate = format_date($RULESET_FILTERS['end_date'],'m/d/Y');

		$range_num = $RULESET_FILTERS['range_num'];
		$range_period = $RULESET_FILTERS['range_period'];
		if ($range_num AND $range_period) {
			$startDate = format_date($GLOBALS['today'],'m/d/Y',array($range_period=>-$range_num));
			$endDate = $GLOBALS['now'];
		}

		$min_records = $RULESET_FILTERS['min_records'];
		$max_records = $RULESET_FILTERS['max_records'];

		// Based on finding it seems if there isn't a set min_price then set it to 1.00
		$min_price = $RULESET_FILTERS['min_price'];
		$max_price = $RULESET_FILTERS['max_price'];

		$min_stock = ($RULESET_FILTERS['min_stock']?:false);
		$max_stock = ($RULESET_FILTERS['max_stock']?:false);

		// $favorites = $RULESET_FILTERS['favorites'];

		$companyid = $RULESET_FILTERS['companyid'];

		// These are global
		$record_start = $startDate;
		$record_end = $endDate;

		$company_filter = $companyid;

		$results = getRecords($keyword,'','csv',$market_table);

		$grouped = array();

		foreach ($results as $r) {
			$partid = $r['partid'];

			if (! isset($r['favorite'])) { $r['favorite'] = ''; }

			$db = hecidb($partid,'id');
			if (count($db)==0 OR ! isset($db[$partid])) { continue; }

			$H = $db[$partid];

			$r['key'] = '';
			if ($H['heci']) {
				$r['key'] = substr($H['heci'],0,7);
			} else {
				$r['primary_part'] = format_part($H['primary_part']);
				$r['key'] = $r['primary_part'];
			}

			if ($report_type=='detail') {
				$key = $r['cid'].'.'.$partid;
			} else {
				$key = $r['key'];
			}

			$r['company'] = $r['name'];
			foreach ($H as $k => $v) {
				$r[$k] = $v;
			}

			$stk_qty = false;
			if (! isset($QTYS[$partid])) {
				$stk_qty = getQty($partid);
			}
			$r['stk'] = $stk_qty;

			// echo $stk_qty . '<BR>';

			if (isset($grouped[$key])) {
				if ($grouped[$key]['stk']===false) { $grouped[$key]['stk'] = $stk_qty; }
				else if ($stk_qty!==false) { $grouped[$key]['stk'] += $stk_qty; }

				$grouped[$key]['partids'][$partid] = $partid;
			} else {
				$r['partids'] = array($partid=>$partid);
				$grouped[$key] = $r;
			}
		}

		$ord = 'datetime';//default
		$dir = 'desc';

		uasort($grouped,$CMP($ord,$dir));

		$string_searchs = array();

		// Generate all the partids associated with this string search to be updated
		$partids = array();

		foreach ($grouped as $key => $r) {
			$partid = $r['partid'];
	
			// determine if a favorite, because when filters are set, we have to circumvent normal favorites method
			if ($FILTERS) {
				// get favorites for any of the partids in this group, see grouping above
				$fav = getFavorites($r['partids']);
				if (count($fav)) {
					$r['favorite'] = 1;
				} else if ($favorites) {// if filter option for favorites is set, this group must have a favorite
					continue;
				}
			}
	
			$fav = 'fa-star-o';
			if ($r['favorite']) {
				$fav = 'fa-star text-danger';
			}
	
			$r['count'] = getCount($r['partids'],$startDate,$endDate,$market_table,$companyid);
			if ($r['count']<$min_records OR ($max_records<>'' AND $r['count']>$max_records)) { continue; }
	
			$partname = $r['primary_part'];
			if ($r['heci']) { $partname .= ' '.substr($r['heci'],0,7); }
	
			$aliases = '';
			foreach ($r['aliases'] as $alias) {
				if ($aliases) { $aliases .= ' '; }
				$aliases .= $alias;
			}
			if ($aliases) { $partname .= ' <small>'.$aliases.'</small>'; }
			$descr = $r['manf'];
			if ($r['system']) { $descr .= ' '.$r['system']; }
			if ($r['description']) { $descr .= ' '.$r['description']; }
	
			$cls = '';
	
			$stk_qty = $r['stk'];

			if ($min_stock!==false) {
				if ($stk_qty===false OR $stk_qty<$min_stock) { continue; }
			}
			if ($max_stock!==false) {
				if ($stk_qty>$max_stock) { continue; }
			}
			if ($stk_qty===false) { $stk_qty = '-'; }
			else if ($stk_qty>0) { $cls = 'in-stock'; }
	
			$company_col = '';
			if ($report_type=='detail') {
				$company_col = '<td><a href="profile.php?companyid='.$r['cid'].'"><i class="fa fa-building"></i></a> '.$r['company'].'</td>';
			}

			// Debug purposes have the heci be populated somewhere
			if ($r['heci']) {
				$string_searchs[] = substr($r['heci'],0,7);
			} else {
				$string_searchs[] = $r['primary_part'];
			}

			$results = hecidb(substr($r['heci'],0,7), 'heci');

			foreach($results as $partid => $part) {
				$partids[] = $partid;
			}
		}

		// If the ruleset is set to check the company's for supply then we need to update the availability using the getSupply function
		if($ACTIONS['option'] == 'Supply') {
			// echo 'Running update: <BR>';

			// $attempt: 0=first attempt, get static results from db; 1=second attempt, go get remote data from api's; 2=force download
			getSupply($partids, $SUPPLY);
			// echo 'Complete!';
		}

		return $string_searchs;
	}

	function getRulesetData($rulesetid, $companyid = 0, $supply = false) {
		$grouped = array();

		$ruleset = getRuleset($rulesetid);
		$actions = getRulesetActions($rulesetid);

		// supply variable if true then invokes the getsupply with attempt 1 or 2 to force or go through the api's
		$string_searchs = getMinerData($ruleset, $actions, $supply);

		$groupedParts = array();
		$company = array();

		if(! empty($string_searchs)){
			// Now we need to sourced all the companies that either 
			// A. We want to quote from
			// B. We want to sell to

			foreach($string_searchs as $str) {

				$results = hecidb($str);
				$partids = array();

				foreach($results as $partid => $part) {
					$partids[] = $partid;
				}

				$results = array();
				
				// Only get the static data, no API's or force
				if($actions['options'] == 'Demand') {
					$results = getDemand($partids, false);
				} else {
					$results = getSupply($partids, false);
				}

				// if we're getting data exclusively for $companyid, build the array around just that id
				if ($companyid) {
					if (is_array($companyid)) {
						$groupedParts[$str] = $companyid;
					} else {
						$groupedParts[$str] = array($companyid);
					}
				} else {
					$companys = array();
					foreach($results['results'] as $data) {
						if($company = array_column($data, 'cid')) {
							$companys = $company;
						}
					}

					$groupedParts[$str] = $companys; 
				}
			}

			// reverse sort so we grab the older requests first
			arsort($groupedParts);

			// Sort each company and the partids they have from the list of parts
			foreach($groupedParts as $str => $data) {
				$H = hecidb($str);
				$partids = array();
				foreach ($H as $partid => $r) {
					$partids[] = $partid;
				}

				foreach($data as $cid) {
					if (! isset($grouped[$cid])) { $grouped[$cid] = array('parts'=>array()); }

					// check if we've recently rfq'd this company

					$rfqs = getRFQ($partids,$cid);
					// if recently rfq'd, skip this item
					if (count($rfqs)) { continue; }

					if ($actions['max_lines']>0 AND count($grouped[$cid]['parts'])>=$actions['max_lines']) { continue; }

					$grouped[$cid]['parts'][] = $str;
				}
			}

			// print_r($grouped);
		}

		return $grouped;
	}

	// $rulesets = getRulesets();

	// foreach($rulesets as $ruleset) {
	// 	$actions = getRulesetActions($ruleset['id']);

	// 	getMinerData($ruleset, $actions);

	// 	break;
	// }
