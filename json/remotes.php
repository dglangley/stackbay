<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$remote = '';
	if (isset($_REQUEST['remote'])) { $remote = strtolower($_REQUEST['remote']); }

	$query = "SELECT * FROM remotes WHERE remote = '".res($remote)."'; ";
	$result = qdb($query);
	if (mysqli_num_rows($result)==0) { exit; }
	$rem = mysqli_fetch_assoc($result);

	include_once $_SERVER["ROOT_DIR"].'/inc/'.$remote.'.php';

	header("Content-Type: application/json", true);
	echo json_encode(array('response'=>$rem['name'].' activated!'));
	exit;

	$err = call_user_func($remote);
	if ($err) {
		echo json_encode(array('err'=>$err));
	} else {
		echo json_encode(array('response'=>$rem['name'].' activated!'));
	}
	exit;
?>
