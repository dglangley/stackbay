<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$DEBUG = 0;

	$remote = '';
	if (isset($_REQUEST['remote'])) { $remote = trim($_REQUEST['remote']); }
	$name = '';
	if (isset($_REQUEST['name'])) { $name = trim($_REQUEST['name']); }
	$contents = '';
	if (isset($_REQUEST['contents'])) { $contents = $_REQUEST['contents']; }//no trim
	$remoteid = false;
	if (isset($_REQUEST['remoteid'])) { $remoteid = $_REQUEST['remoteid']; }

	if ($remoteid===false OR ! is_numeric($remoteid)) {
		header('Location: remotes.php');
		exit;
	}

	$query = "SELECT * FROM remotes WHERE id = '".res($remoteid)."'; ";
	$result = qedb($query);
	if (qnum($result)==0) {
		header('Location: remotes.php');
		exit;
	}
	$r = qrow($result);

	$query = "REPLACE remotes (remote, name, id) VALUES ('".res($remote)."','".res($name)."','".res($remoteid)."'); ";
	$result = qedb($query);

	$query = "REPLACE remote_sessions (contents, remoteid) VALUES ('".res($contents)."','".res($remoteid)."'); ";
	$result = qedb($query);

	if ($DEBUG) { exit; }

	header('Location: remotes.php');
	exit;
?>
