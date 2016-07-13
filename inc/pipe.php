<?php
	include_once 'dbconnect.php';
	include_once 'format_price.php';

	$PIPE = mysqli_init();
	//$PIPE = mysqli_connect('db.ven-tel.com', 'david', '33WbkcY6YBMs5cLWe7sD', 'inventory', '13306');
	$PIPE->options(MYSQLI_OPT_CONNECT_TIMEOUT,5);
	$PIPE->real_connect('db.ven-tel.com', 'david', '33WbkcY6YBMs5cLWe7sD', 'inventory', '13306');
	if (mysqli_connect_errno($PIPE)) {
		die( "Failed to connect to MySQL: " . mysqli_connect_error() );
	}
//	echo '<BR><BR><BR>';

//	$search = 'NT5C07AC';
//	pipe($search);
?>
