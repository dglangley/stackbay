<?php
	$SYSTEMS = array();
	function getSys($search,$input_field='id',$output_field='system') {
		global $SYSTEMS;
		if (! $search) { return (''); }

		if (isset($SYSTEMS[$search]) AND isset($SYSTEMS[$search][$input_field])) { return ($SYSTEMS[$search][$input_field][$output_field]); }

		$query = "SELECT * FROM systems WHERE $input_field = '".res($search)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			return ('');
		}
		$r = mysqli_fetch_assoc($result);
		$SYSTEMS[$search][$input_field] = $r;
		return ($r[$output_field]);
	}
	function getSystem($search) { return (getSys($search)); }
?>
