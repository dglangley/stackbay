<?php
	$RATES = array();
	function getSalesReps($selected_repid=0,$force_selected=false) {
		global $RATES;

		$reps = '';
		if (! $force_selected) { $reps = '<option value="0">- Select a Rep -</option>'.chr(10); }
		$query = "SELECT u.id, c.name, u.commission_rate, r.privilegeid FROM contacts c, users u, user_roles r, user_privileges p ";
		$query .= "WHERE c.id = u.contactid AND u.id = r.userid AND r.privilegeid = p.id ";
		$query .= "AND (p.privilege = 'Sales' OR p.privilege = 'Management') ";
		//$query .= "AND c.status = 'Active' ";//AND commission_rate > 0 ";
		$query .= "AND c.status = 'Active' AND commission_rate > 0 ";
		if ($force_selected) { $query .= "AND u.id = '".$selected_repid."' "; }
		$query .= "GROUP BY u.id ";
		$query .= "ORDER BY c.name ASC; ";
		$result = qdb($query) OR die("Could not get sales reps from database");
		while ($r = mysqli_fetch_assoc($result)) {
			$name = $r['name'];
			$RATES[$r['id']] = $r['commission_rate'];

			$s = '';
			if ($selected_repid==$r['id']) { $s = ' selected'; }
			$reps .= '<option value="'.$r['id'].'"'.$s.'>'.$name.'</option>'.chr(10);
		}
		return ($reps);
	}
?>
