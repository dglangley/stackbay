<?php
	$OLDDB_REPS = array(2=>2,18=>1);
	$REPS = array();
	function getRep($repid,$db='new') {
		global $OLDDB_REPS,$REPS;

		if ($db=='old') {
			$repid = $OLDDB_REPS[$repid];
		}

		if (isset($REPS[$repid])) { return ($REPS[$repid]); }

		$REPS[$repid] = '';
		$query = "SELECT name FROM users, contacts ";
		$query .= "WHERE users.id = '".res($repid)."' AND users.contactid = contacts.id; ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)==0) { return ($REPS[$repid]); }

		$r = mysqli_fetch_assoc($result);
		$REPS[$repid] = $r['name'];

		return ($REPS[$repid]);
	}
?>
