<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$comms = array();
	if (isset($_REQUEST['comm']) AND is_array($_REQUEST['comm'])) {
		$comms = $_REQUEST['comm'];
	}

	foreach ($comms as $commissionid => $comm_amount) {
		$query = "SELECT * FROM commissions WHERE id = '".res($commissionid)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
//			print "<pre>".print_r($r,true)."</pre>";
//			echo $comm_amount.'<BR>';

			$query2 = "INSERT INTO commission_payouts (commissionid, paid_date, amount, userid) ";
			$query2 .= "VALUES ('".res($commissionid)."','".$now."','".$comm_amount."','".$U['id']."'); ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		}
	}

	header('Location: /commissions.php');
	exit;
?>