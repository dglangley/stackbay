<?php
	include '../inc/dbconnect.php';
	include '../inc/format_date.php';

	$recent_date = format_date($today,'Y-m-d 00:00:00',array('d'=>-90));

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$companyid = '';
	if (isset($_REQUEST['companyid'])) { $companyid = trim($_REQUEST['companyid']); }

	$warranties = array();
	$query = "SELECT w.*, COUNT(*) n FROM warranties w ";
	if ($companyid) {
		$query .= "LEFT JOIN purchase_items pi ON w.id = pi.warranty ";
		$query .= "LEFT JOIN purchase_orders po ON pi.po_number = po.po_number ";
	}
	$query .= "WHERE 1 = 1 ";
	if ($q) { $query .= "AND warranty RLIKE '".res($q)."' "; }
	if ($companyid) { $query .= "AND po.companyid = '".res($companyid)."' "; }
	$query .= "GROUP BY w.id ORDER BY ";
	if ($companyid) { $query .= "n DESC, "; }
	$query .= "IF(days>0,0,1), days ASC; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==0) {
		$warranties[] = array('id'=>'','text'=>'- Warranty -');
	}
	while ($r = mysqli_fetch_assoc($result)) {
		$warranties[] = array('id'=>$r['id'],'text'=>$r['warranty']);
	}

	header("Content-Type: application/json", true);
	echo json_encode($warranties);
	exit;
?>
