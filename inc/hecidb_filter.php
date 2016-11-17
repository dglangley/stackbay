<?php
	include_once 'format_part.php';
	include_once 'keywords.php';

	function hecidb_filter($part_str,$line_str,$part_col) {
		$fpart = trim(filter_var(format_part($part_str), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH));
		$part_matches = array();
//		echo 'part str: ['.$fpart.']('.$line_str.')<BR>';
		// if the first column is a type of header/placeholder such as "MPN: xxxx" then process the entire line accordingly
		if ($part_col==0 AND preg_match('/^(product|upn|mpn|part|model|item)[[:alpha:][:space:]\\/#-]*[:]?[[:space:]]*/i',$fpart,$part_matches)) {
			$part_str = preg_replace('/^(product|upn|mpn|part|model|item)([[:space:]\\/#-]*[:]?[[:space:]]*)([[:alnum:]-]+).*/i','$3',$line_str);//.'<BR>';
			$fpart = $part_str;
		}
		$fpart = preg_replace('/-RF$/','',$fpart);
		$hecidb = hecidb($fpart);
		return ($hecidb);
	}
?>
