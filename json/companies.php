<?php
	include '../inc/dbconnect.php';
	include '../inc/format_date.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$add_custom = false;
	if (isset($_REQUEST['add_custom'])) { $add_custom = $_REQUEST['add_custom']; }

	$qlower = strtolower(preg_replace('/[^[:alnum:]]+/','',$q));

	$firsts = array();//exact match
	$seconds = array();//similar match (wildcard ending)
	$thirds = array();//all others
	$companies = array();
	$past_date = format_date($today,"Y-m-d 00:00:00",array("d"=>-30));

	// require string length to be at least 2 chars
	if (strlen($q)>1) {
		$query = "SELECT * FROM companies WHERE name RLIKE '".res($q)."' ";
		$query .= "ORDER BY IF(name = '".res($q)."',0,1), IF(name LIKE '".res($q)."%',0,1), name ASC LIMIT 0,20; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$namelower = strtolower(preg_replace('/[^[:alnum:]]+/','',$r['name']));
			$arr = array('id'=>$r['id'],'text'=>$r['name']);

			if ($namelower===$qlower) { $firsts[$r['name'].'.'.$r['id']] = $arr; }
			else if (substr($namelower,0,strlen($qlower))===$qlower) { $seconds[$r['name'].'.'.$r['id']] = $arr; }
			else { $thirds[$r['name'].'.'.$r['id']] = $arr; }

			// to avoid duplicates in alias searching below
			$list[$r['id']] = $r['name'];
//			$companies[] = array('id'=>$r['id'],'text'=>$r['name']);
		}

		$query = "SELECT * FROM company_aliases WHERE name RLIKE '".res($q)."' ";
		$query .= "ORDER BY IF(name = '".res($q)."',0,1), IF(name LIKE '".res($q)."%',0,1), name ASC LIMIT 0,20; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$namelower = strtolower(preg_replace('/[^[:alnum:]]+/','',$r['name']));
			$arr = array('id'=>$r['companyid'],'text'=>$r['name']);

			if (isset($list[$r['companyid']])) { continue; }//no duplicates
			// to avoid further duplicates in alias looping
			$list[$r['companyid']] = $r['name'];

			// confirm main company data, withholding duplicating similar names ("Universal Wireless Solutions" and "Universal Wireless Solutions, Inc")
			$query2 = "SELECT name FROM companies WHERE id = '".$r['companyid']."'; ";
			$result2 = qdb($query2);
			if (mysqli_num_rows($result2)==0) { continue; }
			$r2 = mysqli_fetch_assoc($result2);
			if (! stristr($r2['name'],$r['name']) AND ! stristr($r['name'],$r2['name'])) {
				$arr['text'] .= ' ('.$r2['name'].')';
			}

			if ($namelower===$qlower) { $firsts[$r['name'].'.'.$r['companyid']] = $arr; }
			else if (substr($namelower,0,strlen($qlower))===$qlower) { $seconds[$r['name'].'.'.$r['companyid']] = $arr; }
			else { $thirds[$r['name'].'.'.$r['companyid']] = $arr; }

//			$companies[] = array('id'=>$r['companyid'],'text'=>$r['name']);
		}

		// sort the companies in $seconds (secondary priority) by recent activity
		$new_seconds = array();
		foreach ($seconds as $k => $arr) {
			$n = 0;
			$query = "SELECT COUNT(search_meta.id) n FROM search_meta ";
			$query .= "WHERE datetime >= '".$past_date."' AND companyid = '".$arr['id']."'; ";
			$result = qdb($query) OR die(qe());
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$n = $r['n'];
			}
			$new_seconds[$n][$k] = $arr;
		}
		krsort($new_seconds);

		$seconds = array();//reset for new order of results below
		foreach($new_seconds as $n => $results) {
			$seconds = array_merge($seconds,$results);
		}
//		print "<pre>".print_r($seconds,true)."</pre>";

		krsort($firsts);
	} else {
		$query = "SELECT companyid id, name, COUNT(search_meta.id) n FROM search_meta, companies ";
		$query .= "WHERE datetime >= '".$past_date."' AND search_meta.companyid = companies.id ";
		$query .= "AND (source IS NULL OR (LENGTH(source)<>2 AND source NOT RLIKE '^[0-9]+$')) ";//this is to filter out ebay results and broker-sites that are amea-pulled
		if ($U['id']>0) { $query .= "AND userid = '".$U['id']."' "; }
		$query .= "GROUP BY companyid ORDER BY n DESC; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$arr = array('id'=>$r['id'],'text'=>$r['name']);

			$firsts[$r['name'].'.'.$r['id']] = $arr;
		}
	}

//now sorted only within scope of a user search ($q)
//	krsort($firsts);
//this array is now sorted above, in priority rather than straight key-order
//	krsort($seconds);
	krsort($thirds);

	foreach ($firsts as $r) { $companies[] = $r; }
	foreach ($seconds as $r) { $companies[] = $r; }
	foreach ($thirds as $r) { $companies[] = $r; }

	if ($add_custom AND strlen($q)>1) {
		$companies[] = array('id'=>ucwords($q),'text'=>'Add '.ucwords($q).'...');
	}

	echo json_encode($companies);//array('results'=>$companies,'more'=>false));
	exit;
?>
