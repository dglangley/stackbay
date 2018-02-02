<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	if ($U['hourly_rate']) {
		$query = "SELECT * FROM users u, user_classes c ";
		$query .= "WHERE u.id = '".$U['id']."' AND u.id = c.userid AND c.classid <> 10; ";
		$result = qedb($query);
		// service installation tech
		if (mysqli_num_rows($result)>0) {
			include 'services.php';
		} else {
//			if ($SEARCH_MODE<>'/' AND $SEARCH_MODE<>'index.php' AND ! $_REQUEST AND $SEARCH_MODE<>'#' AND $SEARCH_MODE<>'https://www.stackbay.com/#' AND $SEARCH_MODE<>'/signout.php') {
			if ($SEARCH_MODE=='sales.php' OR $SEARCH_MODE=='/sales.php') {
				header('Location: '.$SEARCH_MODE);
				exit;
			}

			include 'operations.php';
		}
	} else {
		include 'sales.php';
	}
	exit;
?>
