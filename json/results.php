<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/jsonDie.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	if (! isset($_REQUEST['partids'])) { jsonDie("No partids"); }
	$partids = $_REQUEST['partids'];
	$type = '';
	if (isset($_REQUEST['type'])) { $type = $_REQUEST['type']; }

	$T = order_type($type);

	$results = array();
	$query = "SELECT *, ".$T['order']." order_number, '".$T['abbrev']."' abbrev ";
	$query .= "FROM ".$T['items']." WHERE partid IN (".$partids.") AND ".$T['amount']." > 0 AND qty > 0 ORDER BY id DESC; ";
	$result = qedb($query);
	while ($r = qrow($result)) {
		$r[$T['amount']] = number_format($r[$T['amount']],2);
		$results[] = $r;
	}

	header("Content-Type: application/json", true);
	echo json_encode(array('results'=>$results,'message'=>''));
	exit;
?>
