<?php
	$NOTIFICATIONS = array();
	if ($U['id']) {
		$last_dt = '0000-00-00 00:00:00';

		$query = "SELECT MAX(datetime) datetime FROM userlog WHERE userid = '".$U['id']."' AND datetime <> '".$now."'; ";
		$result = qedb($query);
		if (qnum($result)>0) {
			$r = qrow($result);
			$last_dt = $r['datetime'];
		}

		$query = "SELECT * FROM notifications WHERE userid = '".$U['id']."' AND read_datetime IS NULL ORDER BY id DESC LIMIT 0,100; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$NOTIFICATIONS[$r['messageid']] = true;

			// check if notification is added since the user last accessed the site
			$query2 = "SELECT * FROM messages WHERE id = '".$r['messageid']."' AND datetime > '".$last_dt."'; ";
			$result2 = qedb($query2);
			if (qnum($result2)>0) {
				$r2 = qrow($result2);
				$ALERTS[] = $r2['message'].' <a href=\"'.$r2['link'].'\" target=\"_new\"><i class=\"fa fa-arrow-right\"></i></a>';
			}
		}
	}
?>
