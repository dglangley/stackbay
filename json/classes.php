<?php
	include '../inc/dbconnect.php';
//	include '../inc/getClass.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$noreset = false;
	if (isset($_REQUEST['noreset'])) { $noreset = trim($_REQUEST['noreset']); }

	$classes = array();

	if (! $q AND ! $noreset) { $classes[] = array('id'=>0,'text'=>'- Reset Classes -'); }

	$query = "SELECT * FROM service_classes ";
	if ($q) { $query .= "WHERE class_name RLIKE '".res($q)."' "; }
	$query .= "; ";
	$fields = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($fields)) {
		$classes[] = array('id'=>$r['id'],'text'=>$r['class_name']);
	}

	echo json_encode($classes);
	exit;
?>
