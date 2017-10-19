<?php
	function getInventory($search1,$search2='') {
		$inv = array();
		$query = "SELECT * FROM inventory WHERE ";
		if ($search2) { $query .= "serial_no = '".res($search1)."' AND partid = '".res($search2)."' "; }
		else if (is_numeric($search1)) { $query .= "id = '".res($search1)."' "; }
		else { return ($inv); }
		$query .= "; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$inv = mysqli_fetch_assoc($result);
		}

		return ($inv);
	}
?>
