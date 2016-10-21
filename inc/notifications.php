<?php
	$NOTIFICATIONS = array();
	if ($U['id']) {
		$query = "SELECT * FROM notifications WHERE userid = '".$U['id']."' AND read_datetime IS NULL; ";
		$result = qdb($query) OR die(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$NOTIFICATIONS[$r['partid']] = true;
		}
	}
?>
