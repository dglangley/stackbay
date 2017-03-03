<?php
	function getPartSerials($partid = 0, $locationid = "") {
		$partSerial_array = array();
		
		$query  = "SELECT * FROM inventory where partid = " . res($partid);
		if($locationid){
			$locationid = prep($locationid);
			$query .= " AND locationid = $locationid";
		}
		$query .= " ORDER BY IF(conditionid=1,0,1), qty DESC; ";
		$result = qdb($query);
		
		while ($row = $result->fetch_assoc()) {
			$partSerial_array[] = $row;
		}
		// echo $query;
		return $partSerial_array;
	}
?>
