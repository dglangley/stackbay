<?php
	// This function builds out an array of all receipients of the current email and return
	function getSubEmail($subscription, $output='email') {
		$emails = array();
		$users = array();

		$query = "SELECT e.* FROM subscriptions s, subscription_emails se, emails e, contacts c ";
		$query .= "WHERE s.subscription = ".fres($subscription)." AND se.subscriptionid = s.id ";
		$query .= "AND se.emailid = e.id AND e.contactid = c.id AND c.status = 'Active'; ";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$emailid = $r['emailid'];
			$emails[] = getEmail($emailid);

			if($output == 'userid') {
				$query = "SELECT userid FROM usernames WHERE emailid = ".$r['emailid']." LIMIT 1;";
				$result = qedb($query);

				if(mysqli_num_rows($result)) {
					$r = mysqli_fetch_assoc($result);

					$users[] = $r['userid'];
				}
			}
		}

		return ($output == 'userid' ? $users : $emails);
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
