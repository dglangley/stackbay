<?php
	function getUsers($privs=[]) {
		$privids = '';
		if (is_array($privs)) {
			foreach ($privs as $id) {
				if ($privids) { $privids .= ','; }
				$privids .= $id;
			}
		} else if ($privs>0) {
			$privids = $privs;
		}

		$users = array();

		$query = "SELECT u.id, c.name FROM users u, contacts c ";
		if ($privids) { $query .= ", user_roles r "; }
		$query .= "WHERE u.contactid = c.id ";
		if ($privids) { $query .= "AND r.userid = u.id AND r.privilegeid IN (".$privids.") "; }
		$query .= "AND c.status = 'Active' ";
		$query .= "ORDER BY c.name ASC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$users[$r['id']] = $r['name'];
		}

		return ($users);
	}
?>
