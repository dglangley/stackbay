<?php
	$CLASSES = array();
	function getClass($classid) {
		global $CLASSES;

		$classid = trim($classid);

		if (! $classid) { return false; }

		if (isset($CLASSES[$classid])) { return ($CLASSES[$classid]); }

		$query = "SELECT class_name FROM service_classes WHERE id = '".res($classid)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return false; }
		$r = mysqli_fetch_assoc($result);
		$CLASSES[$classid] = $r['class_name'];

		return ($r['class_name']);
	}
?>
