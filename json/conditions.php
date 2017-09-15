<?php
	include '../inc/dbconnect.php';
	include '../inc/format_date.php';

	$recent_date = format_date($today,'Y-m-d 00:00:00',array('d'=>-90));

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }

	$conditions = array();
	$query = "SELECT c.*, COUNT(*) n FROM conditions c ";
	$query .= "LEFT JOIN inventory i ON c.id = i.conditionid ";
	if ($q) { $query .= "WHERE c.condition RLIKE '".res($q)."' "; }
	$query .= "GROUP BY c.id ORDER BY ";
	if ($q) { $query .= "IF(c.condition LIKE '".res($q)."%',0,1), "; }
	else { $query .= "IF(c.id>0,0,1), "; }
	$query .= "n DESC; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==0) {
		$conditions[] = array('id'=>'','text'=>'- Select Condition -');
	}
	while ($r = mysqli_fetch_assoc($result)) {
		$conditions[] = array('id'=>$r['id'],'text'=>$r['condition']);
	}

	header("Content-Type: application/json", true);
	echo json_encode($conditions);
	exit;
?>
