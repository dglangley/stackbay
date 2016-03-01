<?php
	include '../inc/dbconnect.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }

	$qlower = strtolower(preg_replace('/[^[:alnum:]]+/','',$q));

	$firsts = array();//exact match
	$seconds = array();//similar match (wildcard ending)
	$thirds = array();//all others
	$lists = array();

	$query = "SELECT name, filename, uploads.id FROM uploads, companies ";
	$query .= "WHERE companies.id = uploads.companyid ";
	if ($q) {
		$query .= "AND filename RLIKE '".res($q)."' OR companies.name RLIKE '".res($q)."' ";
	}
	$query .= "ORDER BY ";
	if ($q) {
		$query .= "IF(name = '".res($q)."',0,1), IF(name LIKE '".res($q)."%',0,1), IF(filename LIKE '".res($q)."%',0,1), name ASC, ";
	}
	$query .= "uploads.datetime DESC LIMIT 0,20; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$namelower = strtolower(preg_replace('/[^[:alnum:]]+/','',$r['name']));
		$filenamelower = strtolower(preg_replace('/[^[:alnum:]]+/','',$r['filename']));
		$arr = array('id'=>$r['id'],'text'=>$r['name'].' '.$r['filename']);

		if ($namelower===$qlower) { $firsts[$r['name'].'.'.$r['id']] = $arr; }
		else if (substr($namelower,0,strlen($qlower))===$qlower) { $seconds[$r['name'].'.'.$r['id']] = $arr; }
		else { $thirds[$r['name'].'.'.$r['id']] = $arr; }

		// to avoid duplicates in alias searching below
		$list[$r['id']] = $r['name'];
//		$lists[] = array('id'=>$r['id'],'text'=>$r['name']);
	}

	krsort($firsts);
	krsort($seconds);
	krsort($thirds);

	foreach ($firsts as $r) { $lists[] = $r; }
	foreach ($seconds as $r) { $lists[] = $r; }
	foreach ($thirds as $r) { $lists[] = $r; }

	echo json_encode($lists);
	exit;
?>
