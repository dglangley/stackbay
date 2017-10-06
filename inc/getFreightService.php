<?php
	$FREIGHT_SERVICES = array();
	function getFreightService($str,$input_field='id',$output_field='method') {
		global $FREIGHT_SERVICES;

		$str = strtolower(trim($str));
		if (! $str) { return false; }

		if (isset($FREIGHT_SERVICES[$str]) AND isset($FREIGHT_SERVICES[$str][$input_field])) { return ($FREIGHT_SERVICES[$str][$input_field][$output_field]); }

		$FREIGHT_SERVICES[$str] = array($input_field=>array());

		$query = "SELECT * FROM freight_services WHERE $input_field = '".res($str)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) { return false; }

		$FREIGHT_SERVICES[$str][$input_field] = mysqli_fetch_assoc($result);
		return ($FREIGHT_SERVICES[$str][$input_field][$output_field]);
	}
?>
