<?php
	// This function builds out an array of all receipients of the current email and return
	function getSubEmail($subscription) {
		$emails = array();

		$query = "SELECT e.* FROM subscriptions s, subscription_emails e WHERE s.subscription = ".fres($subscription)." AND e.subscriptionid = s.id;";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$emailid = $r['emailid'];
			$emails[] = getEmail($emailid);
		}

		return $emails;
	}

	function getEmail($emailid) {
		$email = '';

		$query = "SELECT email FROM emails WHERE id = ".res($emailid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$r = mysqli_fetch_assoc($result);

			$email = $r['email'];
		}

		return $email;
	}