<?php
	$search = '';
	if (isset($_REQUEST['search']) AND strlen(trim($_REQUEST['search']))>=2) { $search = trim($_REQUEST['search']); }
	$search_type = '';
	if (isset($_REQUEST['search_type']) AND strlen(trim($_REQUEST['search_type']))>=2) { $search_type = trim($_REQUEST['search_type']); }

	$search = preg_replace('/[^[:alnum:]-]+/','',$search);
	if (strlen($search)<2) { die("Invalid search string"); }

	ini_set('memory_limit', '2000M');
//	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/indexer.php';

	$DEBUG = 0;

	indexer($search,$search_type);
?>
