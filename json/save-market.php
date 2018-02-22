<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/jsonDie.php';
	include_once '../inc/format_date.php';
	include_once '../inc/format_price.php';
	header("Content-Type: application/json", true);

	$partids = '';//should be a csv string
	$companyid = 0;
	$date = '';
	$price = '';
	$records_table = 'availability';//hard-coded for now, dgl 11-18-16
	$type = 'supply';

	if (isset($_REQUEST['partids']) AND $_REQUEST['partids']) { $partids = $_REQUEST['partids']; }
	if (isset($_REQUEST['type']) AND $_REQUEST['type']) { $type = $_REQUEST['type']; }
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) { $companyid = $_REQUEST['companyid']; }
	if (isset($_REQUEST['date']) AND $_REQUEST['date']) { $date = format_date($_REQUEST['date'],'Y-m-d'); }
	if (isset($_REQUEST['price']) AND $_REQUEST['price']) { $price = format_price($_REQUEST['price'],false,'',true); }

	$price_field = '';
	if (strtolower($type)=='supply') { $price_field = 'avail_price'; }
	else if (strtolower($type)=='demand') { $price_field = 'quote_price'; $records_table = 'demand';}

	// get metaid so we can query item records based on partids, and update the price
	$query = "SELECT * FROM search_meta WHERE companyid = '".res($companyid)."' AND datetime LIKE '".res($date)."%'; ";
	$result = qdb($query) OR jsonDie(qe());
	while ($r = mysqli_fetch_assoc($result)) {
		// update all records associated with metaid and all partids
		$query2 = "UPDATE ".$records_table." SET ".$price_field." = ";
		if ($price) { $query2 .= "'".res($price)."' "; } else { $query2 .= "NULL "; }
		$query2 .= "WHERE metaid = '".$r['id']."' AND partid IN (".$partids."); ";
		//echo json_encode(array('message'=>$query));
		// echo json_encode(array('message'=>$query2));
		// exit;

		$result2 = qdb($query2) OR jsonDie(qe().' '.$query2);
	}

	echo json_encode(array('message'=>'Success'));
	exit;
?>
