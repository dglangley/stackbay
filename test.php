<?php
//	include 'inc/api_template.php';
	include 'inc/dbconnect.php';
	include 'inc/bb.php';

	$DEBUG = 1;

	$search = '';
	if (isset($_REQUEST['search'])) { $search = trim($_REQUEST['search']); }

//	$err = api('',false,'http://assetrecovery.com/inventory.php');
	$err = bb($search);
	print "<pre>".print_r($err,true)."</pre>";
//echo $err;
?>
