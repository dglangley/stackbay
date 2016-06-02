<?php
	function format_heci($str) {
		// strip off leading and trailing non-alphanumeric chars, but those chars are not allowed
		// within the body of the string
		$fheci = preg_replace('/^[^[:alnum:]]*([[:alnum:]]{7,10})[^[:alnum:]]*$/','$1',$str);
		if (! $fheci OR strlen($fheci)<6 OR strlen($fheci)>10 OR is_numeric($fheci) OR preg_match('/[^[:alnum:]]+/',$fheci)) { return false; }

		return ($fheci);
	}
?>
