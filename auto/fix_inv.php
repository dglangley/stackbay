<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/mergeInventories.php';

	$debug = 1;
	if (isset($_REQUEST['debug'])) { $debug = $_REQUEST['debug']; }

	$s = '';
	if (isset($_REQUEST['s'])) { $s = trim($_REQUEST['s']); }

	mergeInventories($s);
?>
