<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/renderCheck.php';
	
	$paymentid = isset($_REQUEST['payment']) ? $_REQUEST['payment'] : '';

	$html = renderCheck($paymentid);

	echo $html;