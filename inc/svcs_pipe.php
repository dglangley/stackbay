<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$SVCS_PIPE = mysqli_init();
	$SVCS_PIPE->options(MYSQLI_OPT_CONNECT_TIMEOUT,5);
	//$SVCS_PIPE->real_connect('192.69.242.135', 'stackbay', '33WbkcY6YBMs5cLWe7sD', 'service', '13306');
	//$SVCS_PIPE->real_connect('76.80.109.210', 'stackbay', '33WbkcY6YBMs5cLWe7sD', 'service', '13306');
	//$SVCS_PIPE->real_connect('db.ven-tel.com', 'stackbay', '33WbkcY6YBMs5cLWe7sD', 'service', '13306');
	// External
	$SVCS_PIPE->real_connect('db.ven-tel.com', 'andrew', 'venpass01', 'service', '13306');

	// Internal
	//$SVCS_PIPE->real_connect('192.168.1.112', 'stackbay', '33WbkcY6YBMs5cLWe7sD', 'service', '3306');
	if (mysqli_connect_errno($SVCS_PIPE)) {
		//add error to global array that is outputted to alert modal
		$ALERTS[] = "Failed to connect to the Services PIPE!";
	}
?>
