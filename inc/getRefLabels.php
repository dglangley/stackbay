<?php
	function getRefLabels() {
		$labels = array();
		$query = "SELECT * FROM reference_labels ORDER BY label ASC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$labels[] = $r['label'];
		}

		return ($labels);
	}
?>
