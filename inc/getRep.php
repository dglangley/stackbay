<?php
	$OLDDB_REPS = array(2=>2,18=>1,13=>0);
	$REPS = array();
	function getRep($userid,$input='id',$output='name') {
		global $OLDDB_REPS,$REPS;

		if (! $userid) { return false; }

		if ($input=='repid') {
			$userid = $OLDDB_REPS[$userid];
			// if all we're doing is cross-referencing old to new, pass it back
			if ($output=='id') { return ($userid); }
		}

		if (isset($REPS[$userid])) { return ($REPS[$userid][$output]); }

		$REPS[$userid] = '';
		$query = "SELECT users.id, users.id userid, contacts.name, contacts.id contactid FROM users, contacts ";
		$query .= "WHERE users.id = '".res($userid)."' AND users.contactid = contacts.id; ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)==0) { return ($REPS[$userid]); }

		$r = mysqli_fetch_assoc($result);
		$REPS[$userid] = $r;

		return ($REPS[$userid][$output]);
	}
?>
