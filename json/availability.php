<?php
	include_once '../inc/getSupply.php';

	// 0=first attempt, get static results from db; 1=second attempt, go get remote data from api's
	$attempt = 0;
	$max_ln = 2;
	if (isset($_REQUEST['attempt']) AND is_numeric($_REQUEST['attempt'])) { $attempt = $_REQUEST['attempt']; }
	$partids = "";
	if (isset($_REQUEST['partids'])) { $partids = $_REQUEST['partids']; }
	$ln = 0;
	if (isset($_REQUEST['ln']) AND is_numeric($_REQUEST['ln'])) { $ln = $_REQUEST['ln']; }
	// 0=all results; 1=pricing only; 2=ghosted inventories
	$results_mode = 0;
	if (isset($_REQUEST['results_mode']) AND is_numeric($_REQUEST['results_mode']) AND $_REQUEST['results_mode']>0) { $results_mode = $_REQUEST['results_mode']; }
	$detail = 0;
	if (isset($_REQUEST['detail']) AND $_REQUEST['detail']=='1') { $detail = 1; }

	// partids are passed in with comma-separated format
	$partid_array = explode(",",$partids);

	$results = array();
	if (isset($_REQUEST['type']) AND $_REQUEST['type']=='supply') {
		$results = getSupply($partid_array,$attempt,$ln,$max_ln);
	} else {
		$results = getDemand($partid_array,$attempt,$ln,$max_ln);
	}
/*
	if ($err) {
		$results = array('results'=>$results,'done'=>1,'err'=>$err,'errmsgs'=>$errmsgs);
	}
*/

	//print "<pre>".print_r($results,true)."</pre>";

	header("Content-Type: application/json", true);
	echo json_encode($results);
	exit;
?>
