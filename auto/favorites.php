<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFavorites.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/listFavorites.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_favorites.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_email.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';

	set_time_limit(0);
	ini_set('memory_limit', '2000M');
	ini_set('mbstring.func_overload', '2');
	ini_set('mbstring.internal_encoding', 'UTF-8');

	$DEBUG = 0;
	$FAVS = true;//using this in inc/searchRemotes() to restrict certain remotes
	$senderid = 5;//userid for amea; this should be made a database global at some point
	$recipients = getSubEmail('favorites');

	// grabs content from this file and writes it to the page header as html content
	$EMAIL_CSS = file_get_contents($_SERVER["ROOT_DIR"].'/css/favorites.css');

	$fav_partids = getFavorites(true);//true downloads all favs

	$favs = listFavorites($fav_partids,1);//,10);

	//added 2/10/17 by david so that we can show entire list (not just delta) on Fridays
	$N = date("N");

	$fav_str = 'Hey there! Here are the latest changes to our favorites on the marketplace. You can also '.
		'<a href="https://www.stackbay.com/favorites.php">view this in a browser</a>.<BR><BR>'.format_favorites($favs,$N,true);

	setGoogleAccessToken($senderid);// initializes gmail API session

	$sbj = 'Favorites Daily '.date("M j, Y");
	$send_success = send_gmail($fav_str,$sbj,$recipients,'','','','',$EMBEDDED_SOURCES);//see format_favorites() for $EMBEDDED_SOURCES
	if ($send_success) {
		echo json_encode(array('message'=>'Success'));
	} else {
		echo json_encode(array('message'=>$SEND_ERR));
	}
?>
