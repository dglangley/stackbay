<?php
	include '../inc/dbconnect.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$noreset = false;
	if (isset($_REQUEST['noreset'])) { $noreset = trim($_REQUEST['noreset']); }

	$classes = array();

//	if (! $q AND ! $noreset) { $classes[] = array('id'=>0,'text'=>'- Reset Categories -'); }

	$query = "SELECT * FROM expense_categories ";
	if ($q) { $query .= "WHERE category RLIKE '".res($q)."' "; }
	$query .= "; ";
	$fields = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($fields)) {
		$classes[] = array('id'=>$r['id'],'text'=>$r['category']);
	}

	echo json_encode($classes);
	exit;
?>
