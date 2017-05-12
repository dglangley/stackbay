<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/calcCost.php';

	$s = '';
	if (isset($_REQUEST['s']) AND $_REQUEST['s']) { $s = trim($_REQUEST['s']); }

	$partids = array();
	$results = hecidb($s);
	foreach ($results as $partid => $r) {
		$partids[] = $partid;

		echo '<strong>'.$r['search'].': '.$r['heci'].' '.$r['part'].'</strong><BR>'.
			' &nbsp; '.calcCost($partid).'<BR><BR>';
	}

	echo 'Total avg cost: '.calcCost($partids);
?>
