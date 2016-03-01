<?php
	include_once '../inc/dbconnect.php';
	header("Content-Type: application/json", true);

	function reportError($err) {
		echo json_encode(array('message'=>$err));
		exit;
	}

	$userid = 1;

	$partid = 0;
	if (isset($_REQUEST['partid']) AND is_numeric($_REQUEST['partid'])) {
		$partid = $_REQUEST['partid'];
	}

	// confirm part existence
	$query = "SELECT * FROM parts WHERE id = '".$partid."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);
	if (mysqli_num_rows($result)<>1) {
		reportError("Could not find part in db");
	}

	$fav = 0;//0 for NOT fav as of now, 1 for yes
	$query = "SELECT * FROM favorites WHERE partid = '".res($partid)."'; ";
	$result = qdb($query) OR repotError(qe().' '.$query);
	// add favorite if none exists
	if (mysqli_num_rows($result)==0) {
		$query = "INSERT INTO favorites (partid, userid, datetime) ";
		$query .= "VALUES ('".res($partid)."','".res($userid)."','".$now."'); ";
		$result = qdb($query) OR repotError(qe().' '.$query);
		$fav = 1;
	} else {//delete it from favs
		$query = "DELETE FROM favorites WHERE partid = '".$partid."'; ";
		$result = qdb($query) OR repotError(qe().' '.$query);
		$fav = 0;
	}

	echo json_encode(array('message'=>'Success','favorite'=>$fav));
	exit;
?>
