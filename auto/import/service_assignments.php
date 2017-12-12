<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/imports.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';

	$debug = 0;

	$query = "DELETE FROM service_assignments WHERE item_id_label = 'service_item_id'; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);

	$query = "SELECT * FROM services_jobtasks WHERE date_assigned >= '2017-01-01 00:00:00' ";
	$query .= "; ";
	$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$taskid = mapJob($r['job_id']);
		if (! $taskid) { continue; }

		$userid = mapUser($r['assigned_to_id']);
		if (! $userid) { continue; }

		$query2 = "REPLACE service_assignments (item_id, item_id_label, userid) ";
		$query2 .= "VALUES ('".res($taskid)."', 'service_item_id', '".$userid."'); ";
		if ($debug) { echo $query2.'<BR>'; }
		else { $result2 = qdb($query2) OR die(qe().'<BR>'.$query2); }
	}

	echo 'COMPLETE!<BR>';
?>
