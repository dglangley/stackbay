<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFavorites.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/listFavorites.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_favorites.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_email.php';

	//gets added globally to email header within format_email() (inside send_gmail)
	$EMAIL_CSS = file_get_contents($_SERVER["ROOT_DIR"].'/css/favorites.css');

	$fav_partids = getFavorites(true);//true downloads all favs
	$sbj = 'Favorites Daily '.date("M j, Y");

	$favs = listFavorites($fav_partids,0,10);

	//added 2/10/17 by david so that we can show entire list (not just delta) on Fridays
	$N = date("N");

	$fav_str = format_favorites($favs,$N,false);

	echo format_email($sbj,$fav_str);
?>
