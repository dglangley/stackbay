<?php
	$CONTACTS = array();
	function getContact($search_field,$input_field='id',$output_field='name') {
		global $CONTACTS;

		if (! isset($CONTACTS[$search_field])) { $CONTACTS[$search_field] = array(); }

		if (isset($CONTACTS[$search_field][$input_field])) { return ($CONTACTS[$search_field][$input_field][$output_field]); }

		$CONTACTS[$search_field][$input_field] = array($output_field=>'');

		$query = "SELECT * FROM contacts WHERE $input_field = '".res($search_field)."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)>0) {
			$CONTACTS[$search_field][$input_field] = mysqli_fetch_assoc($result);
		}

		return ($CONTACTS[$search_field][$input_field][$output_field]);
	}
?>
