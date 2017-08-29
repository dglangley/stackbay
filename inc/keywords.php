<?php
	include_once 'getManf.php';
	include_once 'getSys.php';
	include_once 'format_part.php';

//	$revs = '([^[:alnum:]]((S[-]?[[:alnum:]]{2})|(REV[-]?[[:alnum:]]{1,2})|(ISS[-]?[[:alnum:]]{1,2})))*';
//	$rev_base = '(S[-]?|REV[-]?|ISS[-]?)';
	// generate keywords in primaries and secondaries
	$primaries = array();
	$secondaries = array();

//	$rev_values = array(0,1,2,3,4,5,6,7,8,9);
	$a = 'A';
	$rev_values = array();
	for ($n=1; $n<=26; $n++) {
		$rev_values[$a++] = $n;
	}

	function relValue(&$part,&$rel) {
		global $rev_base,$rev_ext,$rev_values,$revs;

		$rel = trim($rel);
		if (! $rel) {
			$base_part = preg_replace('/'.$revs.'$/','',$part);
			$rel = preg_replace('/^[^[:alnum:]]?/','',str_replace($base_part,'',$part));
			if ($rel) { $part = $base_part; }
		}

//		echo $rev_base.' / ('.$rev_ext.')<BR>';
		// get the rev itself, after any "REV-" or such lingo
		$frel = preg_replace('/'.$rev_base.'('.$rev_ext.')/','$2',$rel);
//		echo $rel.' to '.$frel.'<BR>';
		$v = '';
		if (is_numeric($frel)) {
			$v = (int)$frel;
		} else {
			$p = 1;
			for ($i=strlen($frel); $i>0; $i--) {
				$n = $i-1;
				$l = $frel[$n];
				if (! isset($rev_values[$l])) { $rev_values[$l] = 0; }
				$v += ((9*$p)+($rev_values[$l]*$p))/(10/$p);
				$p *= 10;
			}
		}

		while (strlen($v)<6) {
			$v = '0'.$v;
		}

		return ($v);
	}

	function addKeyword($v,$col_name,$k) {
		global $primaries,$secondaries,$revs;

		// formatted without non-alphanumerics
		$fv = preg_replace('/[^[:alnum:]]*/','',$v);
		if (! $fv OR isset($primaries[$v])) { return; }

		if ($col_name=='part') {
			$primaries[$v] = $k;//[$k] = true;

			// base part# without rev
			$base_part = preg_replace('/'.$revs.'$/','',$v);
			if ($v<>$base_part) {
				if (isset($primaries[$base_part])) { return; }

				$primaries[$base_part] = $k;//[$k] = true;
				$fbase = preg_replace('/[^[:alnum:]]*/','',$base_part);
				if (isset($primaries[$fbase])) { return; }

				$primaries[$fbase] = $k;//[$k] = true;
			}
			if ($v<>$fv) { $primaries[$fv] = $k; }//[$k] = true; }
		} else if ($col_name=='heci') {
			$primaries[$v] = $k;//[$k] = true;

			$heci7 = substr($v,0,7);
			if (isset($primaries[$heci7])) { return; }

			$primaries[$heci7] = $k;//[$k] = true;

			$pheci = str_replace('0','O',$v);
			$pheci7 = substr($pheci,0,7);
			if ($pheci7==substr($v,0,7) OR isset($primaries[$pheci7]) OR isset($secondaries[$pheci7])) { return; }

			$secondaries[$pheci] = $k;//[$k] = true;
			$secondaries[$pheci7] = $k;//[$k] = true;
		} else {
			if (isset($secondaries[$v]) OR isset($secondaries[$fv])) { return; }

			$secondaries[$v] = $k;//[$k] = true;
			if ($v<>$fv) { $secondaries[$fv] = $k; }//[$k] = true; }
		}
	}

	$keywords = array();
	$keyword_manfs = array();
//	$PARTSDB = array();
	function hecidb($search,$search_type='',$manfid='',$sysid='') {
		global $keywords,$keyword_manfs;//,$PARTSDB;

		// formatted without non-alphanumerics
		$fsearch = preg_replace('/[^[:alnum:]]*/','',$search);

		$fsearch_lower = strtolower($fsearch);
		if ($fsearch_lower=='cisco' OR preg_match('/^(rev|iss)-?[0-9]*$/',$fsearch_lower) OR strlen($fsearch)<=1) { return array(); }
//		if (strtolower($fsearch)=='cisco' OR strtolower($fsearch)=='rev' OR strlen($fsearch)<=1) { return array(); }

		$half_life = strlen($fsearch)*.51;

		// for accuracy purposes, testing methods here
		$manfs = array();

		$hecidb = array();
		$sorted = array();
		$sub = 0;
		$query = "SELECT parts.* FROM parts ";
		if ($search_type=='eci' OR $search_type=='id') {
			$query .= "WHERE id = '".res($search)."' ";
		} else {
			$query .= ", parts_index, keywords ";
			// the strict search is good for items like LNW8, which bogusly produces LNW80 if wildcarded
			//$query .= "WHERE keyword = '".res($fsearch)."' AND rank = 'primary' AND parts_index.keywordid = keywords.id ";
			$query .= "WHERE keyword LIKE '".res($fsearch)."%' AND rank = 'primary' AND parts_index.keywordid = keywords.id ";
			// on non-heci looking strings (not 7-digits), try to limit bogus results by restricting a trailing integer from an ending integer
			if (strlen($fsearch)<>7 AND is_numeric(substr($fsearch,(strlen($fsearch)-1),1))) { $query .= "AND SUBSTRING(keyword,".(strlen($fsearch)+1).",1) NOT RLIKE '[0-9]' "; }
			$query .= "AND parts.id = parts_index.partid ";
			if ($manfid) { $query .= "AND parts.manfid = '".res($manfid)."' "; }
			if ($sysid) { $query .= "AND parts.systemid = '".res($sysid)."' "; }
			$query .= "GROUP BY parts.id ";
			$query .= "ORDER BY IF(rank='primary',0,1), part, rel, heci ";
		}
		$query .= "; ";
//		echo $query.'<BR>';
		$result = qdb($query);
		$num_results = mysqli_num_rows($result);
		// try to get at least a couple results, even at the expense of literal matching
		//while (($num_results==0 OR ($num_results<=0 AND ! $sub)) AND strlen($fsearch)>=$half_life AND $search_type<>'id' AND $search_type<>'eci') {
		if ($num_results==0 AND strlen($fsearch)>2) {
//			$sub = 1;
//			$fsearch = substr($fsearch,0,strlen($fsearch)-1);

			// check that it's not a manf name; added 5-10-16, mainly for email parser
			$query2 = "SELECT COUNT(manfid) n FROM keywords, manfs_index WHERE keyword = '".res($fsearch)."' AND manfs_index.keywordid = keywords.id; ";
			$result2 = qdb($query2);
			$manf_exists = 0;
			if (mysqli_num_rows($result2)>0) { $r2 = mysqli_fetch_assoc($result2); $manf_exists = $r2['n']; }
			if ($manf_exists==0) {
				$query = "SELECT parts.* FROM parts, parts_index, keywords ";
				$query .= "WHERE keyword LIKE '".res($fsearch)."%' AND parts_index.keywordid = keywords.id ";
				if (strlen($fsearch)<7 OR strlen($fsearch)>10) {
					$query .= "AND heci NOT LIKE '".res($fsearch)."%' ";//LEFT(keyword,7) <> LEFT(heci,7) ";
				}
//				$query .= "AND parts.id = parts_index.partid ";
				$query .= "AND rank = 'primary' AND parts.id = parts_index.partid ";
				$query .= "GROUP BY parts.id ";
				$query .= "ORDER BY IF(rank='primary',0,1), part, rel, heci; ";
				$result = qdb($query);
				$num_results += mysqli_num_rows($result);
			}
		}
		while ($r = mysqli_fetch_assoc($result)) {
			$k = $r['id'];

			// if the call is explicitly for a heci, compare it not only directly against the search field but also permuted
			if ($search_type=='heci') {
				$permuted_search = str_replace('0','O',$fsearch);
				if (substr($r['heci'],0,7)!==substr($fsearch,0,7) AND substr($r['heci'],0,7)!==substr($permuted_search,0,7)) { continue; }
			} else if (strlen($fsearch)<>7 AND strlen($fsearch)<>10 AND strtoupper($fsearch)==substr($r['heci'],0,strlen($fsearch)) AND ! stristr($r['part'],$fsearch) AND ! stristr($fsearch,$r['part'])) {
				// do not allow for search strings that are NOT 7- or 10-digits (heci pattern) AND the substring of the HECI matches the search,
				// AND the search string is not part of the Part string in any way
				continue;
			}

			// used as a simple counter of the manf variance within the results
			if (! isset($keyword_manfs[$search])) { $keyword_manfs[$search] = array(); }
			$keyword_manfs[$search][$r['manfid']] = true;
			$r['manf'] = getManf($r['manfid']);
			$r['Manf'] = $r['manf'];
			$r['system'] = getSys($r['systemid']);
			$r['Sys'] = $r['system'];
			$r['Part'] = $r['part'];
			$r['fpart'] = preg_replace('/[^[:alnum:]]*/','',$r['part']);
			$r['Rel'] = $r['rel'];
			$r['heci'] = $r['heci'];
			$r['HECI'] = $r['heci'];
			$r['heci7'] = substr($r['heci'],0,7);
			$r['Descr'] = $r['description'];
			$rv = relValue($r['part'],$r['rel']);
			$r['RelValue'] = $rv;
			$part_key = $r['part'].'.'.$r['RelValue'].'.'.$r['heci'];
			$r['Key'] = $part_key;
			$r['sub'] = $sub;
			$r['search'] = $search;
			$keywords[$r['heci']] = $k;
			$keywords[$r['heci7']] = $k;
			$keywords[$r['fpart']] = $k;
//			$PARTSDB[$r['id']] = $r;

			$hecidb[$k] = $r;

			$sorted[$part_key] = $k;
		}

		// sort array by key we made from part.rel.heci
		ksort($sorted);

		$newdb = array();// this will become our new hecidb[]
		while (list($s1,$k) = each($sorted)) {
			$newdb[$k] = $hecidb[$k];
		}
		reset($sorted);

		return ($newdb);
	}
?>
