<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$PIPE = mysqli_init();
	//$PIPE = mysqli_connect('db.ven-tel.com', 'david', '33WbkcY6YBMs5cLWe7sD', 'inventory', '13306');
	$PIPE->options(MYSQLI_OPT_CONNECT_TIMEOUT,5);
	//$PIPE->real_connect('db.ven-tel.com', 'david', '33WbkcY6YBMs5cLWe7sD', 'inventory', '13306');
	$PIPE->real_connect('76.80.109.210', 'david', '33WbkcY6YBMs5cLWe7sD', 'inventory', '13306');
//	$PIPE->real_connect('192.168.1.112', 'david', '33WbkcY6YBMs5cLWe7sD', 'inventory', '3306');
	if (mysqli_connect_errno($PIPE)) {
		//add error to global array that is outputted to alert modal
		if (isset($ALERTS)) {
			$ALERTS[] = "Failed to connect to the PIPE!";
		} else {
			//die( "Failed to connect to MySQL: " . mysqli_connect_error() );
			echo "<BR><BR><BR><BR><BR>Failed to connect to MySQL: " . mysqli_connect_error(). "<BR><BR>";
		}
	}

//	echo '<BR><BR><BR>';

//	$search = 'NT5C07AC';
//	pipe($search);
?>
