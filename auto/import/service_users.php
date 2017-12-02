<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setContact.php';

	$debug = 1;

	function setEmail($email,$contactid) {
		$query = "REPLACE emails (email, contactid) VALUES ('".res($email)."','".res($contactid)."'); ";
		if ($GLOBALS['debug']) { echo $query.'<BR>'; }
		else { $result = qdb($query) OR die(qe().'<BR>'.$query); }
		$emailid = qid();

		return ($emailid);
	}

	$query = "SELECT id, fullname, admin, commission, technician, tech_rate, tech_perdiem, phone, tech_position ";
	$query .= "FROM services_userprofile; ";
	$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$userid = getUser($r['fullname'],'name','id');

		if (! $userid) {
			$contactid = setContact($r['fullname'],25,$r['tech_position']);
			if (! $contactid AND ! $debug) { die("Failed"); }

			$names = explode(' ',$r['fullname']);
			$first_initial = substr($names,0,1);

			$email = strtolower($first_initial.$last_name).'@ven-tel.com';
			$emailid = setEmail($email);

			$query2 = "INSERT INTO users (contactid, login_emailid, hourly_rate) ";
			$query2 .= "VALUES ('".$contactid."', '".$emailid."', ".fres($r['tech_rate'])."); ";
			if ($GLOBALS['debug']) { echo $query2.'<BR>'; }
			else { $result2 = qdb($query2) OR die(qe().'<BR>'.$query2); }
			$userid = qid();
		}
		echo $r['fullname'].' = '.$userid.'<BR>';
	}
?>
