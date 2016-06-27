<?php
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/qty_functions.php';

	function find_fields($arr,$qty=false) {
		if ($GLOBALS['test']) { print "<pre>".print_r($arr,true)."</pre>"; }

		$qty = false;
		$heci = false;
		$part = false;

		// build row string to find phrases such as 'qty' to help us
		$row_str = '';
		foreach ($arr as $k => $col) {
			if ($row_str) { $row_str .= ' '; }
			$row_str .= $col;
		}

		// determine first if any 'qty - 10' type strings, and replace invalid fields
		// with XXX so we know not to include them below
		$row_str = preg_replace('/(qty[:]?[[:space:]]*)([0-9]+)(x|ea)?[+-]?/i','XXX $2',$row_str);

		$new_arr = explode(' ',$row_str);

		foreach ($new_arr as $k => $col) {
			$col = trim($col);
			if (! $col) { continue; }
			// easiest field to identify is qty
			$alt_qty = format_qty($col);
//			echo 'col:'.$col.':'.$alt_qty.'<BR>';
			if (is_numeric($col) AND $col<10000) {
				if ($qty!==false) { $qty = true; }//if qty has already been found, discredit it for finding a 2nd match
				else { $qty = $k; }
				continue;
			} else if (is_numeric($alt_qty) AND $alt_qty<10000) {
				if ($qty!==false) { $qty = true; }//if qty has already been found, discredit it for finding a 2nd match
				else { $qty = $k; }
				continue;
			}
			if ($heci===false AND preg_match('/^[[:alnum:]]{7,10}$/',$col)) {
				$query = "SELECT * FROM parts WHERE heci LIKE '".res($col)."%'; ";
				$result = qdb($query);
				if (mysqli_num_rows($result)>0) {
					$heci = $k;
					continue;
				}
			}
			if ($part===false AND strlen($col)>1 AND $col<>'XXX') {
				$part = $k;
//				$fields = explode(' ',$col);
//				if (strlen($fields[0])>1 AND $fields[0]<>'XXX') { $part = $k; }
			}
		}

		if ($qty===true) {
			// should I try to search the entire list array to get a better grasp of qty column?
			$qty = false;
		}

		return (array($part,$qty,$heci));
	}
?>
