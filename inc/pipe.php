<?php
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/dbconnect.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/format_price.php';

	$PIPE = mysqli_connect('db.ven-tel.com', 'david', '33WbkcY6YBMs5cLWe7sD', 'inventory', '13306');
	if (mysqli_connect_errno($PIPE)) {
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}
//	echo '<BR><BR><BR>';

//	$search = 'NT5C07AC';
//	pipe($search);
?>
