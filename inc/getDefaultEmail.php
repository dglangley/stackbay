<?php
	function getDefaultEmail($cid) {
		global $SEND_ERR;

		$e = false;//array('email'=>'','name'=>'');

		$query = "SELECT default_email email, '' name FROM companies WHERE id = '".$cid."' AND default_email <> '' AND default_email IS NOT NULL; ";
		$result = qedb($query);
		$num_emails = qnum($result);
		// no default, get first email found
		if ($num_emails==0) {
			$query = "SELECT email, name FROM emails, contacts ";
			$query .= "WHERE contacts.companyid = '".$cid."' AND emails.contactid = contacts.id AND contacts.status = 'Active' ";
			$query .= "ORDER BY IF(contacts.title = 'Sales',0,1), IF(contacts.title RLIKE 'A[PR]',1,0); ";//prioritize Sales, and minimize AP/AR
			$result = qedb($query);
			$num_emails = qnum($result);
		}
		if ($num_emails==0) {
			if ($SEND_ERR) { $SEND_ERR .= chr(10); }
			$SEND_ERR .= getCompany($cid).' is missing an email recipient so no RFQ was sent to them!';
			return ($e);
		}

		$e = qrow($result);

		// parse down to first name
		if ($e["name"]) {
			$names = explode(" ",$e["name"]);
			$e["name"] = $names[0];
		}

		$to = array($e['email'],$e['name']);

		return ($to);
	}
?>
