<?php
	include '../inc/dbconnect.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$manfid = 0;
	if (isset($_REQUEST['manfid']) AND is_numeric($_REQUEST['manfid']) AND $_REQUEST['manfid']>0) { $manfid = trim($_REQUEST['manfid']); }

	$qlower = strtolower(preg_replace('/[^[:alnum:]]+/','',$q));

	$firsts = array();//exact match
	$seconds = array();//similar match (wildcard ending)
	$thirds = array();//all others
	$systems = array();

	// require string length to be at least 2 chars
	$query = "SELECT * FROM systems ";
	if ($q OR $manfid) {
		$query .= "WHERE ";
		if ($q) { $query .= "system RLIKE '".res($q)."' "; }
/* dgl 10-26-16
		if ($q AND $manfid) { $query .= "AND "; }
		if ($manfid) { $query .= "manfid = '".$manfid."' "; }
*/
	}
	$query .= "ORDER BY ";
	if ($q) {
		//dgl 10-26-16
		if ($manfid) { $query .= "IF(manfid = '".$manfid."',0,1), "; }
		$query .= "IF(system = '".res($q)."',0,1), IF(system LIKE '".res($q)."%',0,1), ";
	}
	$query .= "system ASC; ";// LIMIT 0,20; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$systemlower = strtolower(preg_replace('/[^[:alnum:]]+/','',$r['system']));
		$arr = array('id'=>$r['id'],'text'=>$r['system']);

		if ($systemlower===$qlower) { $firsts[$r['system'].'.'.$r['id']] = $arr; }
		else if (substr($systemlower,0,strlen($qlower))===$qlower) { $seconds[$r['system'].'.'.$r['id']] = $arr; }
		else { $thirds[$r['system'].'.'.$r['id']] = $arr; }

		// to avoid duplicates in alias searching below
		$list[$r['id']] = $r['system'];
//		$systems[] = array('id'=>$r['id'],'text'=>$r['system']);
	}

/*
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
*/

	ksort($firsts);
	ksort($seconds);
	ksort($thirds);

	foreach ($firsts as $r) { $systems[] = $r; }
	foreach ($seconds as $r) { $systems[] = $r; }
	foreach ($thirds as $r) { $systems[] = $r; }

	echo json_encode($systems);//array('results'=>$systems,'more'=>false));
	exit;
?>
