<?php
	include_once '../inc/getSupply.php';

	// 0=first attempt, get static results from db; 1=second attempt, go get remote data from api's
	$attempt = 0;
	$max_ln = 2;
	if (isset($_REQUEST['attempt']) AND is_numeric($_REQUEST['attempt'])) { $attempt = $_REQUEST['attempt']; }
	$partids = "";
	if (isset($_REQUEST['partids'])) { $partids = $_REQUEST['partids']; }
// don't think I'm using this 7/7/16
//	$metaid = 0;
//	if (isset($_REQUEST['metaid']) AND is_numeric($_REQUEST['metaid'])) { $metaid = $_REQUEST['metaid']; }
	$ln = 0;
	if (isset($_REQUEST['ln']) AND is_numeric($_REQUEST['ln'])) { $ln = $_REQUEST['ln']; }
	$pricing_only = 0;
	if (isset($_REQUEST['pricing_only']) AND $_REQUEST['pricing_only']<>'') { $pricing_only = 1; }
	$detail = 0;
	if (isset($_REQUEST['detail']) AND $_REQUEST['detail']=='1') { $detail = 1; }

	// partids are passed in with comma-separated format
	$partid_array = explode(",",$partids);

//	$results = array();

	$results = getSupply($partid_array,$attempt,$ln,$max_ln);
/*
	if ($err) {
		$results = array('results'=>$results,'done'=>1,'err'=>$err,'errmsgs'=>$errmsgs);
	}
*/

//	print "<pre>".print_r($results,true)."</pre>";

	header("Content-Type: application/json", true);
	echo json_encode($results);
	exit;
?>
