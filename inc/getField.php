<?php
	function getField($F,$col,$end) {
		$s = false;

		// can't go any lower than 0, must be a default stored value that is not a real field
		if ($col==0) { return ($s); }

		$num_fields = count($F);

		// count from end
		$field = $col-1;
		if ($end!==false) {
			$field = $num_fields-$field;
		}

		if (isset($F[$field])) { $s = strtoupper(trim($F[$field])); }

		return ($s);
	}
?>
