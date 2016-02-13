<?php
	$SYSTEMS = array();
	function getSys($search,$input_field='id',$output_field='system') {
		global $SYSTEMS;
		if (! $search) { return (''); }

		if (isset($SYSTEMS[$search])) { return ($SYSTEMS[$search]); }

		$query = "SELECT $output_field FROM systems WHERE $input_field = '".res($search)."'; ";
		$result = qdb($query);
		$r = mysqli_fetch_assoc($result);
		return ($r[$output_field]);
	}
	function getSystem($search) { return (getSys($search)); }
?>
