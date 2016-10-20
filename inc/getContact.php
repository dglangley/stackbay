<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$CONTACTS = array();
	function getContact($search_field,$input_field='id',$output_field='name') {
		global $CONTACTS;

		$search_field = (string)$search_field;
		if (! isset($CONTACTS[$search_field])) { $CONTACTS[$search_field] = array(); }

		if (isset($CONTACTS[$search_field]) AND isset($CONTACTS[$search_field][$input_field])) { return ($CONTACTS[$search_field][$input_field][$output_field]); }

		$CONTACTS[$search_field][$input_field] = array($output_field=>false);

		// set to alternate variables in case the following 'userid' query needs it to be contactid for contacts table lookup, but
		// if we change the originating variable ($search_field/$input_field), it will store mismatching data in global $CONTACTS
		$search_value = $search_field;
		$get_field = $input_field;

		if ($input_field=='userid') {
			$query = "SELECT contactid FROM users WHERE id = '".res($search_field)."'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)==0) { return ($CONTACTS[$search_field][$input_field][$output_field]); }

			$r = mysqli_fetch_assoc($result);
			$search_value = $r['contactid'];
			$get_field = 'id';
		}

		$query = "SELECT contacts.* FROM contacts ";
		if ($input_field=='email') { $query .= ", emails "; }
		$query .= "WHERE $get_field = '".res($search_value)."' ";
		if ($input_field=='email') { $query .= "AND emails.contactid = contacts.id "; }
		$query .= "; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$r["email"] = "";//default
			$r["emails"] = array();
			$r["phone"] = "";//default
			$r["phones"] = array();

			$query2 = "SELECT * FROM emails WHERE contactid = '".$r['id']."'; ";
			$result2 = qdb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				// set default email for this contact, besides the array of all associated emails for this contact (if multiple)
				if (! $r["email"]) { $r["email"] = $r2["email"]; }
				$r["emails"][] = $r2["email"];
			}

			$query2 = "SELECT * FROM phones WHERE contactid = '".$r['id']."'; ";
			$result2 = qdb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				// set default phone for this contact, besides the array of all associated phones for this contact (if multiple)
				if (! $r["phone"]) { $r["phone"] = $r2["phone"]; }
				$r["phones"][] = $r2["phone"];
			}
			$CONTACTS[$search_field][$input_field] = $r;
		}

		// if we're returning userid, we need to do an addl query to get the user data
		if ($output_field=='userid') {
			$query = "SELECT id userid FROM users WHERE contactid = '".$CONTACTS[$search_field][$input_field]['id']."'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)==0) { return false; }
			$r = mysqli_fetch_assoc($result);
			$CONTACTS[$search_field][$input_field][$output_field] = $r['userid'];
		}

		return ($CONTACTS[$search_field][$input_field][$output_field]);
	}
?>
