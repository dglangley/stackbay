<?php
	$IDSEARCHES = array();
	function getSearch($str,$input='id',$output='search',$userid=0,$datetime='') {
		global $IDSEARCHES,$today;

		if ($output=='search') { $return = ''; }
		else if ($output=='id') { $return = 0; }

		$str = trim($str);
		if (! $str OR ($input=='id' AND ! is_numeric($str))) {
			return ($return);
		}

		if (isset($IDSEARCHES[$str]) AND isset($IDSEARCHES[$str][$input])) { return ($IDSEARCHES[$str][$input][$output]); }
		$IDSEARCHES[$str] = array(
			$input => array(
				$output => $return,
			),
		);

		$query = "SELECT * FROM searches WHERE $input = '".res($str)."' ";
		if ($userid) { $query .= "AND userid = '".res($userid)."' "; }
		if ($datetime) { $query .= "AND datetime LIKE '".$datetime."%' "; }
		$query .= "ORDER BY id DESC LIMIT 0,1; ";//get most recent
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) {
			return ($return);
		}
		$r = mysqli_fetch_assoc($result);
		$IDSEARCHES[$str][$input] = $r;
		return ($r[$output]);
	}
?>
