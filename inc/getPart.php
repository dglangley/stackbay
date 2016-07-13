<?php
	include_once 'getManf.php';
	include_once 'getSys.php';

	$PARTS = array();
	function getPart($partid,$return_field='') {
		global $PARTS;

		if (! isset($PARTS[$partid])) {
			$query = "SELECT * FROM parts WHERE id = '".res($partid)."'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) {
				$PARTS[$partid] = mysqli_fetch_assoc($result);
				$PARTS[$partid]['aliases'] = array();
			}
		}

		$part = explode(' ',$PARTS[$partid]['part']);
		$part_str = $part[0];
		foreach ($part as $k => $partstr) {
			if ($k==0) {
				$PARTS[$partid]['part'] = $partstr;
			} else {
				$PARTS[$partid]['aliases'][] = $partstr;
			}
		}

		if ($PARTS[$partid]['heci']) { $part_str .= ' '.$PARTS[$partid]['heci']; }

		$PARTS[$partid]['full_descr'] = '';
		if ($PARTS[$partid]['manfid']) {
			$PARTS[$partid]['full_descr'] .= getManf($PARTS[$partid]['manfid']);
		}
		if ($PARTS[$partid]['systemid']) {
			if ($PARTS[$partid]['full_descr']) { $PARTS[$partid]['full_descr'] .= ' '; }
			$PARTS[$partid]['full_descr'] .= getSys($PARTS[$partid]['systemid']);
		}
		if ($PARTS[$partid]['description']) {
			if ($PARTS[$partid]['full_descr']) { $PARTS[$partid]['full_descr'] .= ' '; }
			$PARTS[$partid]['full_descr'] .= $PARTS[$partid]['description'];
		}

		if ($return_field) { return ($PARTS[$partid][$return_field]); }
		else { return ($part_str); }
	}
?>
