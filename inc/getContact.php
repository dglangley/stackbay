<?php
	$CONTACTS = array();
	function getContact($search_field,$input_field='id',$output_field='name') {
		global $CONTACTS;

		if (! isset($CONTACTS[$search_field])) { $CONTACTS[$search_field] = array(); }

		if (isset($CONTACTS[$search_field][$input_field])) { return ($CONTACTS[$search_field][$input_field][$output_field]); }

		$CONTACTS[$search_field][$input_field] = array($output_field=>false);

		$query = "SELECT contacts.* FROM contacts ";
		if ($input_field=='email') { $query .= ", emails "; }
		$query .= "WHERE $input_field = '".res($search_field)."' ";
		if ($input_field=='email') { $query .= "AND emails.contactid = contacts.id "; }
		$query .= "; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$r["email"] = "";//default
			$r["emails"] = array();

			$query2 = "SELECT * FROM emails WHERE contactid = '".$r['id']."'; ";
			$result2 = qdb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				// set default email for this contact, besides the array of all associated emails for this contact (if multiple)
				if (! $r["email"]) { $r["email"] = $r2["email"]; }
				$r["emails"][] = $r2["email"];
			}
			$CONTACTS[$search_field][$input_field] = $r;
		}

		return ($CONTACTS[$search_field][$input_field][$output_field]);
	}
?>
