<?php
	include '../inc/dbconnect.php';
	include '../inc/format_date.php';

	$q = '';
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }

	$recent_date = format_date($today,'Y-m-d 00:00:00',array('d'=>-90));

	$locs = array();
	$query = "SELECT l.*, COUNT(*) n FROM locations l ";
	$query .= "LEFT JOIN inventory i ON i.locationid = l.id ";
	// get most recent popular locations
	if ($q) {
		$query .= "WHERE CONCAT(l.place,l.instance) RLIKE '".res($q)."' ";
		$query .= "AND (i.date_created IS NULL OR i.date_created >= '".$recent_date."') ";
		$query .= "GROUP BY l.id ORDER BY IF(l.place LIKE '".res($q)."%',0,1), n DESC; ";
	} else {
		$locs[] = array('id'=>0,'text'=>'- Reset Locations -');
		$query .= "WHERE (i.date_created >= '".$recent_date."') ";
		$query .= "GROUP BY l.id ORDER BY n DESC LIMIT 0,10; ";
	}
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==0) {
		$locs[] = array('id'=>'','text'=>'- Select Location -');
	}
	while ($r = mysqli_fetch_assoc($result)) {
		$locs[] = array('id'=>$r['id'],'text'=>trim($r['place'].$r['instance']));
	}

	header("Content-Type: application/json", true);
	echo json_encode($locs);
	exit;
?>
