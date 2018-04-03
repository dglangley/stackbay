<?php
	function getInventory($search1,$search2='',$status='') {
		$inv = array();
		$query = "SELECT * FROM inventory WHERE ";

		// Adding a ternary to allow null serials to be searched
		if ($search2) { $query .= "serial_no ".($search1 ? "=" : "IS")." ".fres($search1)." AND partid = '".res($search2)."' "; }
		// If there is no search2 but there is search 1 then search serial_no only
		if (! $search2 AND $search1 AND $status) { $query .= "serial_no = ".fres($search1)." "; }
		if ($status) { $query .= "AND status = '".res($status)."' "; }
		else if (is_numeric($search1)) { $query .= "id = '".res($search1)."' "; }
		else { return ($inv); }
		$query .= "; ";

		$result = qdb($query) OR die(qe().'<BR>'.$query);
		// If there is only 1 record then pass it back
		if (mysqli_num_rows($result) == 1) {
			$inv = mysqli_fetch_assoc($result);
		} else if (mysqli_num_rows($result) > 1) {
			while($r = mysqli_fetch_assoc($result)) {
				$inv[] = $r;
			}
		}

		return ($inv);
	}