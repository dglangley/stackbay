<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$TITLE = 'Inventory';
	$TYPE = 'inventory';
	include_once $_SERVER["ROOT_DIR"].'/inventory_dashboard.php';

	exit;