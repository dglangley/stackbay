<?php
	include '../inc/dbconnect.php';
	include '../inc/getUsers.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$noreset = '';
	if (isset($_REQUEST['noreset'])) { $noreset = trim($_REQUEST['noreset']); }

	$users = getUsers('',$q);

	$list = array();
	if (! $q AND ! $noreset) { $list[] = array('id'=>0,'text'=>'- Reset Users -'); }
	foreach ($users as $id => $name) {
		if (! $id) { continue; }

		$list[] = array('id'=>$id,'text'=>$name);
	}

	header("Content-Type: application/json", true);
	echo json_encode($list);
	exit;
?>
