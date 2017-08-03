<?php
	$LOCATIONS = array();
	function getLocation($id) {
		global $LOCATIONS;

		if (! $id) { return false; }

		if (isset($LOCATIONS[$id])) { return ($LOCATIONS[$id]); }
		$LOCATIONS[$id] = '';

		$query = "SELECT * FROM locations WHERE id = '".$id."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			return false;
		}
		$r = mysqli_fetch_assoc($result);
		$LOCATIONS[$id] = $r['place'];
		if ($r['instance']) { $LOCATIONS[$id] .= '-'.$r['instance']; }

		return ($LOCATIONS[$id]);
	}
?>
