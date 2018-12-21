<?php
	// This function builds out an array of all receipients of the current email and return
	function getSubEmail($subscription, $output='email') {
		$emails = array();
		$users = array();

		$query = "SELECT e.email, u.id userid FROM subscriptions s, subscription_emails se, emails e, contacts c, users u ";
		$query .= "WHERE s.subscription = ".fres($subscription)." AND se.subscriptionid = s.id AND c.id = u.contactid ";
		$query .= "AND se.emailid = e.id AND e.contactid = c.id AND c.status = 'Active'; ";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$emails[] = $r['email'];
			$users[] = $r['userid'];
		}

		return ($output == 'userid' ? $users : $emails);
	}
/*
	function getEmail($emailid) {
		$email = '';

		if (! $emailid) { return ($email); }

		$query = "SELECT email FROM emails WHERE id = ".res($emailid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$r = mysqli_fetch_assoc($result);

			$email = $r['email'];
		}

		return $email;
	}
*/
?>
