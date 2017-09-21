<?php
	$NOTIFICATIONS = array();
	if ($U['id']) {
		$query = "SELECT * FROM notifications WHERE userid = '".$U['id']."' AND read_datetime IS NULL ORDER BY id DESC LIMIT 0,100; ";
		$result = qdb($query) OR die(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$NOTIFICATIONS[$r['messageid']] = true;
		}
	}
?>
