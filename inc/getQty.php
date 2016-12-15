<?php
	include_once 'getPipeQty.php';

	$PIPEIDS_STR = '';
	$PIPE_NOTES = '';
	function getQty($partid=0,$data_source=false) {
		global $PIPEIDS_STR,$PIPE_NOTES,$NOTES;//won't need this after migration, I believe

		$qty = 0;

		if ($data_source=='PIPE') {
			if (! $partid OR (! is_numeric($partid) AND ! is_array($partid))) { return ($qty); }

			// add notes from global $NOTES, keyed by this/each pipeid below
			$PIPE_NOTES = '';
			$PIPEIDS_STR = '';
			// when a single value, handle accordingly; otherwise by array
			if (is_array($partid)) {
				foreach ($partid as $pipe_id => $arr) {
					$qty += getPipeQty($pipe_id);
					// $NOTES is set globally in getPipeQty()
					if (trim($NOTES[$pipe_id])) {
						if ($PIPE_NOTES) { $PIPE_NOTES .= chr(10).'<HR>'.chr(10); }
						$PIPE_NOTES .= $NOTES[$pipe_id];
					}
					if ($PIPEIDS_STR) { $PIPEIDS_STR .= ','; }
					$PIPEIDS_STR .= $pipe_id;
				}
			} else {
				$qty = getPipeQty($partid);
				// $NOTES is set globally in getPipeQty()
				$PIPE_NOTES = $NOTES[$partid];
				$PIPEIDS_STR = $partid;
			}
		} else {
			if (! $partid OR ! is_numeric($partid)) { return ($qty); }

			$query = "SELECT qty FROM qtys WHERE partid = '".res($partid)."'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)==0) { return ($qty); }
			$r = mysqli_fetch_assoc($result);
			$qty = $r['qty'];
		}

		return ($qty);
	}
?>
