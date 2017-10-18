<?php
	function getSerial($invid) {
		$serial;

		$query = "SELECT serial_no FROM inventory WHERE id = ".prep($invid).";";
		$result = qdb($query) or die(qe());
		if (mysqli_num_rows($result)){
			$row = mysqli_fetch_assoc($result);
			$serial = $row['serial_no'];
		}

		return $serial;
	}
?>
