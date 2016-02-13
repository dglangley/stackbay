<?php
	function getManf($search,$input_field='id',$output_field='name') {
		$query = "SELECT $output_field FROM manfs WHERE $input_field = '".res($search)."'; ";
		$result = qdb($query);
		$r = mysqli_fetch_assoc($result);
		return ($r[$output_field]);
	}
?>
