<?php
	$search = '';
	if (isset($_REQUEST['search']) AND strlen(trim($_REQUEST['search']))>2) { $search = trim($_REQUEST['search']); }
	$search = preg_replace('/[^[:alnum:]-]+/','',$search);
	if (strlen($search)<=2) { die("Invalid search string"); }

	ini_set('memory_limit', '2000M');
	include_once $_SERVER["ROOT_DIR"].'/inc/mconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/indexer.php';

	$test = 0;
	indexer($search);
?>
