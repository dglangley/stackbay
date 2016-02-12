<?php
/*
	include 'inc/mconnect.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$add_custom = false;
	if (isset($_REQUEST['add_custom'])) { $add_custom = $_REQUEST['add_custom']; }

	$companies = array();
	// require string length to be at least 2 chars
	if (strlen($q)>1) {
		$query = "SELECT * FROM companies WHERE name RLIKE '".res($q)."' ";
		$query .= "ORDER BY IF(name LIKE '".res($q)."%',0,1), name ASC LIMIT 0,20; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$list[$r['id']] = $r['name'];
			$companies[] = array('id'=>$r['id'],'text'=>$r['name']);
		}

		$query = "SELECT * FROM company_aliases WHERE name RLIKE '".res($q)."' ";
		$query .= "ORDER BY IF(name LIKE '".res($q)."%',0,1), name ASC LIMIT 0,20; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			if (isset($list[$r['companyid']])) { continue; }//no duplicates
			$companies[] = array('id'=>$r['companyid'],'text'=>$r['name']);
//			if (isset($list[$r['companyid']])) { $list[$r['companyid']] .= ' / '.$r['name']; continue; }

//			$query2 = "SELECT * FROM companies WHERE id = '".res($r['companyid'])."'; ";
//			$result2 = qdb($query2);
//			$r2 = mysqli_fetch_assoc($result2);
//			$list[$r['companyid']] = $r2['name'].' / '.$r['name'];
//		}

//		foreach ($list as $cid => $name) {
//			$companies[] = array('id'=>$cid,'text'=>$name);
		}
	}
*/
$companies = array(
	array('id'=>'4','text'=>'Bell Enterprise'),
	array('id'=>'3','text'=>'Sector Supply'),
	array('id'=>'5','text'=>'Tempest Telecom'),
	array('id'=>'1','text'=>'Ventura Telephone'),
	array('id'=>'2','text'=>'WestWorld Telecom'),
);

	if ($add_custom) {
		$companies[] = array('id'=>ucwords($q),'text'=>'Add '.ucwords($q).'...');
	}

	echo json_encode($companies);//array('results'=>$companies,'more'=>false));
	exit;
?>
