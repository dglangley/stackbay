<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$order = preg_replace('/^([\/])([SPR]O[0-9]{4,6})$/i','$2',trim($_SERVER["REQUEST_URI"]));
	$type = substr($order,0,2);

	$_REQUEST['on'] = substr($order,2);
	if ($type=='SO') { $_REQUEST['ps'] = 'Sale'; }
	else if ($type=='PO') { $_REQUEST['ps'] = 'Purchase'; }
	include 'order_form.php';
?>
