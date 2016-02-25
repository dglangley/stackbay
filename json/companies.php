<?php
	include '../inc/dbconnect.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$add_custom = false;
	if (isset($_REQUEST['add_custom'])) { $add_custom = $_REQUEST['add_custom']; }

	$qlower = strtolower(preg_replace('/[^[:alnum:]]+/','',$q));

	$firsts = array();//exact match
	$seconds = array();//similar match (wildcard ending)
	$thirds = array();//all others
	$companies = array();

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

			// confirm main company data
			$query2 = "SELECT name FROM companies WHERE id = '".$r['companyid']."'; ";
			$result2 = qdb($query2);
			if (mysqli_num_rows($result2)==0) { continue; }
			$r2 = mysqli_fetch_assoc($result2);
			$arr['text'] .= ' ('.$r2['name'].')';

			if ($namelower===$qlower) { $firsts[$r['name'].'.'.$r['companyid']] = $arr; }
			else if (substr($namelower,0,strlen($qlower))===$qlower) { $seconds[$r['name'].'.'.$r['companyid']] = $arr; }
			else { $thirds[$r['name'].'.'.$r['companyid']] = $arr; }

//			$companies[] = array('id'=>$r['companyid'],'text'=>$r['name']);
		}
	}

	krsort($firsts);
	krsort($seconds);
	krsort($thirds);

	foreach ($firsts as $r) { $companies[] = $r; }
	foreach ($seconds as $r) { $companies[] = $r; }
	foreach ($thirds as $r) { $companies[] = $r; }

	if ($add_custom) {
		$companies[] = array('id'=>ucwords($q),'text'=>'Add '.ucwords($q).'...');
	}

	echo json_encode($companies);//array('results'=>$companies,'more'=>false));
	exit;
?>
