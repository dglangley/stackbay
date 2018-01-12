<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/lici.php';

	$DEBUG = 0;

	$id = lici(0, '', 'out');

	header('Location: /');
	exit;
?>
