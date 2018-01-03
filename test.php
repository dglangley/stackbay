<?php
	include 'inc/ps.php';

	$DEBUG = 1;

	$ps_err = ps('990102-A');
	print "<pre>".print_r($ps_err,true)."</pre>";
//echo $ps_err;
?>
