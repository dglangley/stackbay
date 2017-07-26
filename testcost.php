<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setAverageCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';

	$debug = 0;
	$ser = 'ASDF';
	$partid = 64076;
	$item_id = 3626;
	$id_field = 'repair_item_id';
	$status = 'shelved';
	$stock_date = $now;

	$invid = setInventory($ser,$partid,$item_id,$id_field,$status,$stock_date,1);
	setCost($invid);

	//setAverageCost($partid);
	//setAverageCost($partid);
?>
