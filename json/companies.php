<?php
	include '../inc/dbconnect.php';
	include '../inc/format_date.php';
	include '../inc/cmp.php';
	include '../inc/order_type.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$order_type = '';
	if (isset($_REQUEST['order_type'])) { $order_type = trim($_REQUEST['order_type']); }
	if ($order_type=='S' OR $order_type=='Sale' OR $order_type=='Sales' OR $order_type=='sales') { $order_type = 'Sale'; }
	else if ($order_type=='P' OR $order_type=='Purchase' OR $order_type=='Purchases' OR $order_type=='purchases') { $order_type = 'Purchase'; }
	$add_custom = false;
	if (isset($_REQUEST['add_custom'])) { $add_custom = $_REQUEST['add_custom']; }
	$noreset = false;
	if (isset($_REQUEST['noreset'])) { $noreset = trim($_REQUEST['noreset']); }

	$qlower = strtolower(preg_replace('/[^[:alnum:]]+/','',$q));

	$firsts = array();//exact match
	$seconds = array();//similar match (wildcard ending)
	$thirds = array();//all others
	$companies = array();
	if (! $q AND ! $noreset) { $companies[] = array('id'=>0,'text'=>'- Reset Companies -'); }
	$past_date = format_date($today,"Y-m-d 00:00:00",array("d"=>-90));

	// require string length to be at least 2 chars
	if (strlen($q)>1) {
		$T = array();
		if ($order_type) { $T = order_type($order_type); }

		$query = "SELECT * ";
		if ($order_type) { $query .= ", COUNT(*) n "; }
		$query .= "FROM companies c ";
		if ($order_type) { $query .= "LEFT JOIN ".$T['orders']." o ON o.companyid = c.id "; }
		$query .= "WHERE c.name RLIKE '".res($q)."' ";
		if ($order_type) { $query .= "GROUP BY c.id "; }
		$query .= "ORDER BY ";
		if ($order_type) { $query .= "n DESC, "; }
		$query .= "IF(c.name = '".res($q)."',0,1), IF(c.name LIKE '".res($q)."%',0,1), c.name ASC LIMIT 0,20; ";
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
		if (! $order_type OR $order_type=='search') {
			$query = "SELECT companyid id, name, COUNT(search_meta.id) n FROM search_meta, companies ";
			$query .= "WHERE datetime >= '".$past_date."' AND search_meta.companyid = companies.id ";
			$query .= "AND (source IS NULL OR (LENGTH(source)<>2 AND source NOT RLIKE '^[0-9]+$')) ";//this is to filter out ebay results and broker-sites that are amea-pulled
			if ($U['id']>0) { $query .= "AND userid = '".$U['id']."' "; }
			$query .= "GROUP BY companyid ORDER BY n DESC; ";
			$result = qdb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				$arr = array('id'=>$r['id'],'text'=>$r['name'],'n'=>$r['n']);
				$key = $r['name'].'.'.$r['id'];

				$firsts[$key] = $arr;
			}
		}

		if ($order_type=='orders' OR $order_type=='Sale') {
			$query = "SELECT companyid id, name, COUNT(so_number) n FROM sales_orders, companies ";
			$query .= "WHERE created >= '".$past_date."' AND sales_orders.companyid = companies.id ";
			if ($U['id']>0) { $query .= "AND created_by = '".$U['id']."' "; }
			$query .= "GROUP BY companyid ORDER BY n DESC; ";
			$result = qdb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				$arr = array('id'=>$r['id'],'text'=>$r['name'],'n'=>$r['n']);
				$key = $r['name'].'.'.$r['id'];

				// sum results if already present
				if (isset($firsts[$key])) {
					$firsts[$key]['n'] += $r['n'];
				} else {
					$firsts[$key] = $arr;
				}
			}
		}

		if ($order_type=='orders' OR $order_type=='purchases') {
			$query = "SELECT companyid id, name, COUNT(po_number) n FROM purchase_orders, companies ";
			$query .= "WHERE created >= '".$past_date."' AND purchase_orders.companyid = companies.id ";
			if ($U['id']>0) { $query .= "AND created_by = '".$U['id']."' "; }
			$query .= "GROUP BY companyid ORDER BY n DESC; ";
			$result = qdb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				$arr = array('id'=>$r['id'],'text'=>$r['name'],'n'=>$r['n']);
				$key = $r['name'].'.'.$r['id'];

				// sum results if already present
				if (isset($firsts[$key])) {
					$firsts[$key]['n'] += $r['n'];
				} else {
					$firsts[$key] = $arr;
				}
			}
		}
		uasort($firsts,'cmp_n');
	}

//now sorted only within order_type of a user search ($q)
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
