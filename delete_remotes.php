<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/download_te.php';

	$search = 'SLPQ0BE';
	$res = download_te($search);//,false,'/Main_Page/Manage/Inv_db/Inv_del_find.php');
echo $res;
?>
