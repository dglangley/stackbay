<?php
	if (! isset($root_dir)) {
		$root_dir = '';
		if (isset($_SERVER["HOME"]) AND $_SERVER["HOME"]=='/Users/davidglangley') { $root_dir = '/Users/Shared/WebServer/Sites/lunacera.com/db'; }
		else if (isset($_SERVER["DOCUMENT_ROOT"]) AND $_SERVER["DOCUMENT_ROOT"]) { $root_dir = preg_replace('/\/$/','',$_SERVER["DOCUMENT_ROOT"]).'/db'; }
		else { $root_dir = '/var/www/html/db'; }
	}
	include_once $root_dir.'/inc/mconnect.php';
	include_once $root_dir.'/inc/format_date.php';
	include_once $root_dir.'/inc/download_te.php';
	include_once $root_dir.'/inc/download_bb.php';
	include_once $root_dir.'/inc/download_ps.php';
	include_once $root_dir.'/inc/download_ebay.php';
	include_once $root_dir.'/inc/download_et.php';
	include_once $root_dir.'/inc/format_price.php';
	include_once $root_dir.'/inc/insertMarket.php';
	include_once $root_dir.'/inc/restricted.php';
	include_once $root_dir.'/inc/matchHeci.php';
	include_once $root_dir.'/inc/getHotlist.php';

	// default settings
	$REMOTES = array(
		'ps'=>array('setting'=>'N'),
		'bb'=>array('setting'=>'N'),
		'te'=>array('setting'=>'N'),
		'ebay'=>array('setting'=>'Y'),
		'et'=>array('setting'=>'N'),
		'alu'=>array('setting'=>'N'),
	);

	if (! isset($company_filter)) { $company_filter = array(); }
	if (! isset($filterOn)) { $filterOn = false; }
	if (! isset($inventory)) { $inventory = 0; }
	if (! isset($search_field)) { $search_field = 1; }
	if (! isset($qty_field)) { $qty_field = 2; }
	if (! isset($price_field)) { $price_field = 0; }//optional, so 0 is default
	if (! isset($search_from_right)) { $search_from_right = 0; }//bool
	if (! isset($qty_from_right)) { $qty_from_right = 0; }//bool
	if (! isset($price_from_right)) { $price_from_right = 0; }//bool
	if (! isset($HOTLIST)) { $HOTLIST = 0; }
	if (! isset($SCANNER)) { $SCANNER = 0; }
	if (! isset($REMOTE_SEARCH)) { $REMOTE_SEARCH = false; }
	if (! isset($startDate)) { $startDate = format_date($now,'Y-m-d',array('d'=>-7)); }
	if (! isset($yesterday)) { $yesterday = format_date($now,'Y-m-d',array('d'=>-1)); }
	if (! isset($PARTS_ONLY)) { $PARTS_ONLY = false; }//used for parts db lookup, no remote calls

	if (! isset($hecidb)) { $hecidb = array(); }
	$IGNITOR = array();
	$TOTALS = array();
	$past_time = format_date($now,'Y-m-d H:i:s',array('i'=>-360));//60 mins
	function getResults($search='',$manfid='',$sysid='') {
		global $IGNITOR,$hecidb,$TOTALS,$restricted;

		// all global variables that don't change
		$REMOTES = $GLOBALS['REMOTES'];
		$U = $GLOBALS['U'];
		$startDate = $GLOBALS['startDate'];
		$company_filter = $GLOBALS['company_filter'];
		$filterOn = $GLOBALS['filterOn'];
		$inventory = $GLOBALS['inventory'];
		$today = $GLOBALS['today'];
		$yesterday = $GLOBALS['yesterday'];
		$SF = $GLOBALS['search_field'];
		$QF = $GLOBALS['qty_field'];
		$PF = $GLOBALS['price_field'];
		$SFR = $GLOBALS['search_from_right'];
		$QFR = $GLOBALS['qty_from_right'];
		$PFR = $GLOBALS['price_from_right'];
		$HOTLIST = $GLOBALS['HOTLIST'];
		$SCANNER = $GLOBALS['SCANNER'];
		$userid = $GLOBALS['U']['id'];
		if (! $userid) { $userid = 1; }

		// columns are counted less 1 for arrays
		$SF--;
		$QF--;
		$PF--;

		$results = array();
		$all_results = array();
		$hidden_results = array();
		$searches = array();//for combining keyed results, such as by heci

		if ($search) {
			$search = strtoupper($search);
			$store = array();

			$lines = explode(chr(10),$search); // for multiple search lines
			// use the method below to find duplicate eci's in case the user is searching by description/manf/sys
			$singles = array();
			$doubles = array();
			foreach ($lines as $k => $line) {
				$line = trim($line);
				$fields = preg_split('/[[:space:]]+/',$line);//split line data on white space(s)

				// counting backwards is weird because we already decremented $SF above, and count() should be decremented to account for arrays
				if ($SFR) { $SF = (count($fields)-1)-$SF; }
				if ($QFR) { $QF = (count($fields)-1)-$QF; }
				if ($PFR) { $PF = (count($fields)-1)-$PF; }

				// no point continuing any further if there aren't even enough fields to find the search string
				if (count($fields)<($SF+1)) { continue; }

				$search_str = '';//search field for this line
				$qty = 1;//qty for this line
				$price = 0;//price for this line
				// trim it first
				if (isset($fields[$SF])) { $search_str = trim($fields[$SF]); }
				if (isset($fields[$QF])) { $qty = trim($fields[$QF]); }
				if (isset($fields[$PF])) { $price = trim($fields[$PF]); }

				// strip off leading parentheses
				if ($qty[0]=='(') { $qty = substr($qty,1); }
				// strip off trailing parentheses
				if ($qty[(strlen($qty)-1)]==')') { $qty = substr($qty,0,strlen($qty)-1); }
				// trim again in case there was padding within parentheses
				$qty = trim($qty);
				// strip out 'x' in cases where qty is something like "12x" or "x12"
				$qty = trim(str_ireplace('ea','',str_ireplace('x','',$qty)));

				if (! is_numeric($qty)) { $qty = 1; }

				// auto-find a heci on this line, or use as a scanner for all parts/hecis, on user selection
				foreach ($fields as $n => $autofield) {
//for now, don't use this auto-matcher
break;
					// we don't want to auto-detect anything in the already-selected search field
					if ($n==$SF) { continue; }

					$fAuto = preg_replace('/[^[:alnum:]]*/','',$autofield);
					if (strlen($fAuto)<=2) { continue; }

					if ($SCANNER) {
						if (isset($restricted[strtolower($fAuto)])) { continue; }
						$searches[$fAuto] = 1;
						continue;
					}

					$heciMatch = matchHeci($autofield);
					if (! $heciMatch) {
						// try to further break up string in case the heci is embedded in other text
						if (strstr($autofield,'-')) {
							$chunks = explode('-',$autofield);
							foreach ($chunks as $chunk) {
								$heciMatch = matchHeci($chunk);
								if (! $heciMatch) { continue; }
								break;
							}
						}
						// if still nothing found in chunks above, go to next
						if (! $heciMatch) { continue; }
					}

					// if no sub-string found in chunking by dashes above, use autofield here
					if ($heciMatch) { $search_str = $heciMatch; }
					else { $search_str = $autofield; }
				}
				if ($SCANNER) { continue; }

				// if 10digits: search first for heci-match, and if the case then truncate to 7-digits
				$fSearch = preg_replace('/[^[:alnum:]]*/','',$search_str);

				if (strlen($fSearch)<=2) { continue; }
//				if (strlen($search_str)<=1 OR strlen($search_str)>20) { continue; }

				$fPartStr = format_part($search_str);

				// combine results and sum qtys
				if (! isset($searches[$fPartStr])) { $searches[$fPartStr] = 0; }// initialize
				$searches[$fPartStr] += $qty;
			}

			$num_searches = count($searches);
			// set remote search indicator based on global setting and/or number of searches==1
			$remote_search = $GLOBALS['REMOTE_SEARCH'];
			// always turn it on for single searches
			if ($num_searches==1) { $remote_search = true; }

			if ($GLOBALS['PARTS_ONLY']) { return ($searches); }

			// build list of all searches to get from remotes
			$psStr = '';
			$bbStr = '';
			$etStr = '';
			foreach ($searches as $partStrKey => $qty) {
				// strip out all punct
				$fSearch = preg_replace('/[^[:alnum:]]*/','',$partStrKey);

				// capture the user's search and what remote scans have been performed
				if ($remote_search) {//log a broker search only if there's a single search, or if remote is forced
					$SLOG = logSearch($fSearch);
				} else {
					$SLOG = logSearch($fSearch,'0000');//force no broker searches
				}

				// gotta hit tel-explorer individually because there's no work-around for their multi-search (when not logged in)
				if ($SLOG['te'] AND $REMOTES['te']['setting']=='Y' AND $remote_search) {
					$te = download_te($fSearch);
					if ($te===false) {
						// set flag in db so we can alert user on main screen
						$query = "UPDATE remotes SET failed = 'Y', setting = 'N' ";
						$query .= "WHERE remote = 'te' AND userid = '".$userid."' LIMIT 1; ";
						$result = qdb($query);
					}
				}
				if ($SLOG['ps'] AND $REMOTES['ps']['setting']=='Y' AND $remote_search) { $psStr .= $fSearch.chr(10); }
				if ($SLOG['bb'] AND $REMOTES['bb']['setting']=='Y' AND $remote_search) { $bbStr .= $fSearch.chr(10); }
				if ($SLOG['et'] AND $REMOTES['et']['setting']=='Y' AND $remote_search) { $etStr .= $partStrKey.chr(10); }
				// parse ebay individually as well
				if ($SLOG['ebay'] AND $REMOTES['ebay']['setting']=='Y' AND $remote_search) {
					$ebay = download_ebay($fSearch);
				}
			}

			// batch searches on these sites so we don't hammer them
			if ($psStr) {
				$ps = download_ps($psStr);
				if ($ps===false) {
					// set flag in db so we can alert user on main screen
					$query = "UPDATE remotes SET failed = 'Y', setting = 'N' ";
					$query .= "WHERE remote = 'ps' AND userid = '".$userid."' LIMIT 1; ";
					$result = qdb($query);
				}
			}
			if ($bbStr) {
				$bb = download_bb($bbStr);
				if ($bb===false) {
					// set flag in db so we can alert user on main screen
					$query = "UPDATE remotes SET failed = 'Y', setting = 'N' ";
					$query .= "WHERE remote = 'bb' AND userid = '".$userid."' LIMIT 1; ";
					$result = qdb($query);
				}
			}
			if ($etStr) {
				$et = download_et($etStr);
				if ($et===false) {
					// set flag in db so we can alert user on main screen
					$query = "UPDATE remotes SET failed = 'Y', setting = 'N' ";
					$query .= "WHERE remote = 'et' AND userid = '".$userid."' LIMIT 1; ";
					$result = qdb($query);
				}
			}

			// don't proceed when the method is invoked only to get remote data
			if ($GLOBALS['REMOTE_SEARCH']) { return false; }
		} else {
			$hotlistArr = getHotlist();

			// reframe in format of array needed below (key=str, value=qty
			foreach ($hotlistArr as $itemStr) {
				$searches[$itemStr] = 1;
			}
		}

		foreach ($searches as $partStrKey => $qty) {
			$fSearch = preg_replace('/[^[:alnum:]]*/','',$partStrKey);

			$newdb = hecidb($fSearch,false,$manfid,$sysid);

			$isHotlist = false;
			// determine hotlist settings across all results
			foreach ($newdb as $ecikey => $n) {
				$newdb[$ecikey]['search_qty'] = $qty;
				$newdb[$ecikey]['hotlist'] = false;//setting by default
				$query = "SELECT * FROM ignitor WHERE partid = '".$n['id']."' AND userid = '".res($U['id'])."'; ";
				$result = qdb($query);
				if (mysqli_num_rows($result)==0) { continue; }
				$newdb[$ecikey]['hotlist'] = true;
				$isHotlist = true;
			}
			// if user wants only hotlist matches
			if ($HOTLIST AND ! $isHotlist) { continue; }

//			print "<pre>".print_r($newdb,true)."</pre>";

			foreach ($newdb as $ecikey => $r) {
//				print "<pre>".print_r($r,true)."</pre>";

				// added these three lines and commented the below lines when adding the new list processor, 4/8/15
				if (isset($hecidb[$fSearch.'.'.$r['id']])) { continue; }
//				if (! $inventory) { $all_results[$r['id']] = array(); }
				$hecidb[$fSearch.'.'.$r['id']] = $r;
			}
		}
//		print "<pre>".print_r($hecidb,true)."</pre>";

		$ns_results = array();//no stock results
		$last_search = '';
		while (list($searchEciKey,$v) = each($hecidb)) {
			$eci = $v['id'];
			$s = trim(preg_replace('/[^[:alnum:]]*/','',$v['search']));
			if (! $s) { continue; }
			if ($s<>$last_search) {
				foreach ($ns_results as $nsKey => $allArr) {
					$results[$nsKey] = $allArr;
				}
				$ns_results = array();
			}
			$last_search = $s;

			$TOTALS[$eci] = 0;

			if ($v['hotlist']) { $IGNITOR[$eci] = 1; }

//			print "<pre>".print_r($v,true)."</pre>";

			$sleepers = array();
			$keys = array();
			$market = getMarket($eci,$startDate,$company_filter);
			// non-stock results, so it will be sorted last
			if (count($market)==0) {
				$ns_results[$searchEciKey] = array();
			}
			foreach ($market as $r) {
				// for ebay, key the companyid with the ebay id
				if ($r['companyid']==34) { $row_key = $r['companyid'].'.'.$r['source'].'.'.$r['price']; }
				else { $row_key = $r['companyid'].'.'.$r['price']; }

//				if (isset($keys[$row_key])) { continue; }
				// don't repeat the same company/price key, or if there is no price but the company has already
				// been shown, don't repeat; notice the assumption is that priced items are sorted first, and
				// that repeats of the same company are shown IF there are prices on both
				if (isset($keys[$row_key]) OR (! $r['price'] AND isset($keys[$r['companyid']]))) {
					// added 5/12/15 to combine sources for any result, to show on main results view
					foreach ($results[$searchEciKey] as &$res) {
						if ($res[1]<>$r['companyid']) { continue; }
						else if (array_search(strtolower($r['source']),$res[6])!==false) { continue; }//only unique occurrences for source

						$res[6][] = strtolower($r['source']);
//						$results[$searchEciKey][6][] = strtolower($r['source']);
					}
					continue;
				}
				$keys[$row_key] = true;
				//also key as just company, so if there's a row *with* a price as well as a row *without* a price,
				//we won't show the no-price; we really only want to see multiple results for same company if
				//there are multiple above-zero prices...
				$keys[$r['companyid']] = true;

				$TOTALS[$eci] += $r['qty'];

				$itemValue = format_price($r['price'],$r['companyid'],$r['source']);

				$row_data = array($eci,$r['companyid'],$r['qty'],$v['part'],$itemValue,format_date($r['datetime'],'D n/j/y'),array(strtolower($r['source'])),$r['price'],$r['id']);
//				print "<pre>".print_r($row_data,true)."</pre>";

				if ($REMOTES[strtolower($r['source'])]['setting']=='N') {
					$hidden_results[$searchEciKey][] = $row_data;
				} else {
					reset($results[$searchEciKey]);
					$results[$searchEciKey][] = $row_data;
				}

				// remove this item from all results because it'll appear first in stocked results
//				if (isset($all_results[$searchEciKey])) { unset($all_results[$searchEciKey]); }
			}
		}
		reset($hecidb);

//		print "<pre>".print_r($results,true)."</pre>";
		foreach ($ns_results as $searchEciKey => $allArr) {
			$results[$searchEciKey] = $allArr;
		}
		$ns_results = array();

/*
		} else {//!$search
			$query = "SELECT *, parts.id partid FROM parts ";
			if ($filterOn) { $query .= ", market "; }
			else if (! $inventory OR $inventory===true) { $query .= ", ignitor "; }
			$query .= "WHERE 1 = 1 ";
			if ($GLOBALS['test']) { $query .= "AND heci LIKE 'SN%' "; }
			if ($filterOn) {
				$query .= "AND parts.id = market.partid AND expired = 'F' ";
				if ($inventory!==true AND $inventory>0) { $query .= "AND market.source = '".res($inventory)."' "; }
			} else if (! $inventory OR $inventory===true) {
				$query .= "AND ignitor.partid = parts.id AND ignitor.userid = '".res($U['id'])."' ";
			}
			// if companies are entered by user...
			$cquery = "";
			foreach ($company_filter as $cid) {
				if ($cquery) { $cquery .= "OR "; }
				$cquery .= "market.companyid = '".res($cid)."' ";
			}
			if ($cquery) { $query .= "AND ($cquery) "; }
			$query .= "GROUP BY parts.id ";
			if ($filterOn) {
				$query .= "ORDER BY LEFT(datetime,10) DESC, part, heci LIMIT 0,500 ";
			} else {
				$query .= "ORDER BY part, heci ";
				if ($inventory!==true AND $inventory>0) { $query .= "LIMIT 0,100 "; }
			}
			$query .= "; ";
//			echo $query.'<BR>';
			$result = qdb($query);
			$group_matches = array();
			while ($r = mysqli_fetch_assoc($result)) {
				$ns_results = array();

				if (! $r['heci']) {
					$group_matches[] = $r;
				} else {
					$query2 = "SELECT *, parts.id partid FROM parts, market ";
					$query2 .= "WHERE heci LIKE '".res(substr($r['heci'],0,7))."%' ";
					$query2 .= "AND parts.id = market.partid AND expired = 'F' ";
					if ($inventory!==true AND $inventory>0) { $query2 .= "AND market.source = '".res($inventory)."' "; }
					if ($cquery) { $query2 .= "AND ($cquery) "; }
					$query2 .= "GROUP BY parts.id ";
					$query2 .= "ORDER BY part, rel, heci; ";
					$result2 = qdb($query2);
					while ($r2 = mysqli_fetch_assoc($result2)) {
						$group_matches[] = $r2;
					}
				}
			}

			foreach ($group_matches as $r) {
				$partKey = substr($r['heci'],0,7);
				if (! $partKey) { $partKey = $r['partid']; }

//				$all_results[$r['partid']] = array();

				if ($inventory>0 AND $inventory!==true) {
					$query2 = "SELECT * FROM ignitor WHERE partid = '".$r['partid']."' AND userid = '".res($U['id'])."'; ";
					$result2 = qdb($query2);
					if (mysqli_num_rows($result2)>0) {
						$IGNITOR[$r['partid']] = 1;
					}
				} else {
					$IGNITOR[$r['partid']] = 1;
				}

				$qty = 0;
				$actives = array();
				if ($filterOn) {
					$price_source = '';
					if (isset($r['source'])) { $price_source = $r['source']; }
					$itemValue = format_price($r['price'],$r['companyid'],$price_source);

					$results[$partKey][] = array($r['partid'],$r['companyid'],$r['qty'],$r['part'],$itemValue,format_date($r['datetime'],'D n/j/y'));
					$qty = $r['qty'];
				} else {
					$keys = array();//can't GROUP in query below due to ordering datetime the way we are, so use this to key results
					$query2 = "SELECT market.*, volume FROM market LEFT JOIN regulars ON regulars.companyid = market.companyid ";
					$query2 .= "WHERE partid = '".res($r['partid'])."' AND datetime >= '".format_date($startDate,'Y-m-d')." 00:00:00' AND expired = 'F' ";
					$query2 .= "AND (regulars.userid IS NULL OR regulars.userid = '".res($U['id'])."') ";
					$query2 .= "ORDER BY datetime DESC, volume DESC; ";
					$result2 = qdb($query2);
					while ($r2 = mysqli_fetch_assoc($result2)) {
						if (isset($keys[$r2['companyid'].$r2['partid']])) { continue; }

						$keys[$r2['companyid'].$r2['partid']] = true;

						$itemValue = format_price($r2['price'],$r2['companyid'],$r2['source']);

						$dt = format_date($r2['datetime'],'D n/j/y');

						$actives[] = array($r2['partid'],$r2['companyid'],$r2['qty'],$r['part'],$itemValue,$dt,strtolower($r2['source']));
						$results[$partKey][] = array($r2['partid'],$r2['companyid'],$r2['qty'],$r['part'],$itemValue,$dt,strtolower($r2['source']));
						$qty += $r2['qty'];
					}
//					if (count($actives)>0) { $results[$partKey] = $actives; }
				}

//				print "<pre>".print_r($results,true)."</pre>";

				if (! isset($TOTALS[$r['partid']])) { $TOTALS[$r['partid']] = 0; }
				$TOTALS[$r['partid']] += $qty;

				$newdb = hecidb($r['partid'],'id');
				foreach ($newdb as $idkey => $row) {
					$hecidb[$idkey] = $row;
				}
			}
		}
*/

		foreach ($hidden_results as $searchEciKey => $hRows) {
			foreach ($hRows as $hArr) {
				$results[$searchEciKey][] = $hArr;
			}
		}

		return ($results);
	}

	$SEARCHES = array();
	function logSearch($search,$user_remotes='') {
		global $SEARCHES;

		$pos = array('ps','bb','te','ebay','et');
		// set defaults as 0's across all remotes
		$def = array();
		$R = $GLOBALS['REMOTES'];
//		$user_remotes = '';//storing in search log below

		// set the most recent datetime for this search
		if (! isset($SEARCHES[$search])) { $SEARCHES[$search] = array(); }

		if (! $user_remotes) {
			foreach ($pos as $k => $s) {
				$def[$s] = false;
				if (isset($R[$pos[$k]]) AND $R[$pos[$k]]['setting']=='Y') { $user_remotes .= '1'; }
				else { $user_remotes .= '0'; }
			}
		}

		if (! $search OR $GLOBALS['inventory']) { return ($def); }

		$userid = $GLOBALS['U']['id'];

		// check for duplicate search within the recent time that scanned inventories,
		// and also check for user's same search within time frame for logging purposes
		$logid = 0;
		$query = "SELECT id, datetime, scan, userid FROM searches WHERE search = '".res($search)."' ";
//		$query .= "AND datetime >= '".$GLOBALS['past_time']."' ";
		$query .= "ORDER BY datetime DESC; ";
		$result = qdb($query);
		$expired_time = false;//once set to true, we know the ordered results are all expired
		$datedSearches = 0;
		while ($r = mysqli_fetch_assoc($result)) {
			// once the datetime is beyond the allowable past time, we are not setting booleans for preventing search
			if ($expired_time===false AND $r['datetime']<$GLOBALS['past_time']) { $expired_time = true; }

			// after we've found a search that was scanned AND we're past the expired time, break from loop
			if ($expired_time===true AND $datedSearches>1) { break; }

			$foundRemote = false;//if we find at least one remote scanned, this is tripped so we increment $datedSearches
			// for each remote as keyed by $pos, find its most recent scan time
			for ($i=0; $i<strlen($r['scan']); $i++) {
				if (! isset($SEARCHES[$search][$i]) OR ! $SEARCHES[$search][$i]) { $SEARCHES[$search][$i] = ''; }
				if (substr($r['scan'],$i,1)==1) {//scanned recently already
					if (! $SEARCHES[$search][$i]) { $SEARCHES[$search][$i] = $r['datetime']; }
					if ($expired_time===false) {
						$foundRemote = true;
						$def[$pos[$i]] = 0;//instructs not to scan remote
						$user_remotes[$i] = '0';
					}
				} else if ($def[$pos[$i]]===false) {//not scanned and $def isn't set
					if ($expired_time===false) {
						if ($user_remotes[$i]=='1') { $def[$pos[$i]] = 1; }// user has remote activated
						else { $def[$pos[$i]] = 0; }// not activated so don't turn on
					}
				}
			}
			if ($foundRemote) { $datedSearches++; }

			// overwrite previous search if same user and same scan settings
			if ($expired_time===false AND $r['userid']==$userid AND $r['scan']==$user_remotes AND ! $logid) { $logid = $r['id']; }
		}
		if ($datedSearches==0) {
			foreach ($pos as $k => $s) {
				if ($user_remotes[$k]==1) { $SEARCHES[$search][$k] = $GLOBALS['now']; }
				else { $SEARCHES[$search][$k] = ''; }
				$def[$s] = $user_remotes[$k];
			}
		}
/*
		// convert array to string for storing in search log
		$user_remotes = '';
		foreach ($def as $rem => $t) {
			$user_remotes .= $t;
		}
*/

		$query = "REPLACE searches (search, userid, datetime, scan";
		if ($logid) { $query .= ", id"; }
		$query .= ") VALUES ('".res($search)."','".$userid."','".$GLOBALS['now']."',";
		// log the user's scans that will be active for this search
		$query  .= "'".res($user_remotes)."'";
		if ($logid) { $query .= ",'$logid'"; }
		$query .= "); ";
		$result = qdb($query);

		return ($def);
	}

	function getMarket($partid,$startDate=false,$company_filter=false) {
		$U = $GLOBALS['U'];
		$results = array();
		if (! $company_filter) { $company_filter = array(); }

		$query = "SELECT market.*, volume FROM market LEFT JOIN regulars ON regulars.companyid = market.companyid ";
		$query .= "WHERE partid = '".res($partid)."' AND expired = 'F' ";
		if ($startDate) { $query .= "AND datetime >= '".format_date($startDate,'Y-m-d')." 00:00:00' "; }
		// if companies are entered by user...
		$cquery = "";
		foreach ($company_filter as $cid) {
			if ($cquery) { $cquery .= "OR "; }
			$cquery .= "market.companyid = '".res($cid)."' ";
		}
		if ($cquery) { $query .= "AND ($cquery) "; }
		$query .= "AND (regulars.userid IS NULL OR regulars.userid = '".res($U['id'])."') ";
		$query .= "ORDER BY datetime DESC, volume DESC, IF(price>0,0,1); ";
//		echo $query.'<BR>';
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$results[] = $r;
		}
		return ($results);
	}
?>
