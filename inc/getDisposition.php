<?php
	$DISPOSITIONS = array();
	function getDisposition($id=0) {
		global $DISPOSITIONS;

		if (isset($DISPOSITIONS[$id])) { return ($DISPOSITIONS[$id]); }

		$query = "SELECT * FROM dispositions;";
		$result = qedb($query);
		while ($r = qrow($result)) {
			$DISPOSITIONS[$r['id']] = $r['disposition'];
		}

		return ($DISPOSITIONS[$id]);
	}
?>
