<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/mapJob.php';

	$debug = 1;

	$query = "SELECT id, rep_id, amount, paid, paid_date, approved, job_id FROM services_commission c ";
	$query .= "WHERE canceled = '0'; ";
	echo $query.'<BR>';
	$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
	}
	echo '<BR><BR>';
?>
