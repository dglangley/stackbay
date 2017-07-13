<?php
	$MANFS = array();
	function getManf($search,$input_field='id',$output_field='name') {
		global $MANFS;

		if (! $search) { return (''); }

		$manf = array('name'=>'','id'=>0);
		if (isset($MANFS[$search]) AND isset($MANFS[$search][$input_field])) { return ($MANFS[$search][$input_field][$output_field]); }
		$MANFS[$search] = array(
			$input_field=>array($manf),
		);

		$query = "SELECT * FROM manfs WHERE $input_field = '".res($search)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			return ('');
		}
		$r = mysqli_fetch_assoc($result);
		$MANFS[$search][$input_field] = $r;

		return ($r[$output_field]);
	}
?>
