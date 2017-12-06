<?php
	function getClass($classid) {
		$classid = trim($classid);

		if (! $classid) { return false; }

		$query = "SELECT class_name FROM service_classes WHERE id = '".res($classid)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return false; }
		$r = mysqli_fetch_assoc($result);
		return ($r['class_name']);
	}
?>
