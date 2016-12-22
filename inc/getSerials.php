<?php
	function getPartSerials($partid = 0) {
		$partSerial_array = array();
		
		$query  = "SELECT * FROM inventory where partid = " . res($partid) . " ORDER BY
				  CASE item_condition
				    WHEN 'new' THEN 1
				    WHEN 'used' THEN 2
				    ELSE 3
				  END, qty DESC;";
		$result = qdb($query);
		
		while ($row = $result->fetch_assoc()) {
			$partSerial_array[] = $row;
		}
		
		return $partSerial_array;
	}
?>