<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/keywords.php';
	include_once '../inc/jsonDie.php';

	$q = '';
	if (isset($_REQUEST['q']) AND trim($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$partid = 0;
	if (isset($_REQUEST['partid']) AND is_numeric($_REQUEST['partid']) AND $_REQUEST['partid']>0) { $partid = trim($_REQUEST['partid']); }

	$results = array();
	if (strlen($q)>2) {
		$db = hecidb($q);

		foreach ($db as $partid => $r) {
			$results[$partid] = array('id'=>$partid,'text'=>trim($r['part'].' '.$r['heci']));
		}
	} else {
		$query = "SELECT part, heci FROM parts WHERE id = '".res($partid)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			jsonDie("Invalid partid ".$partid);
		}
		$r = mysqli_fetch_assoc($result);

		// create array of part and heci strings for building a list of results
		$parts = explode(' ',$r['part']);
		if ($r['heci']) {
			$parts[] = substr($r['heci'],0,7);
		}

		foreach ($parts as $str) {
			if (strlen($str)<=2) { continue; }

			$db = hecidb($str);
			foreach ($db as $partid => $r) {
				$results[$partid] = array('id'=>$partid,'text'=>trim($r['part'].' '.$r['heci']));
			}
		}
	}

	header("Content-Type: application/json", true);
	echo json_encode($results);
	exit;
?>
