<?php
	include 'inc/api_template.php';

	$DEBUG = 1;

	$err = api('',false,'http://assetrecovery.com/inventory.php');
	print "<pre>".print_r($err,true)."</pre>";
//echo $err;
?>
