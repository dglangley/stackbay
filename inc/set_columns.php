<?php
	include_once 'array_find.php';
	include_once 'find_fields.php';
	include_once 'format_heci.php';
	include_once 'keywords.php';

	function set_columns($row_arr,$lines) {
		$num_lines = count($lines);

		$line_lower = array_map('strtolower',$row_arr);
		$part_col = array_find('part',$line_lower);
		if ($part_col===false) { $part_col = array_find('model',$line_lower); }
		if ($part_col===false) { $part_col = array_find('mpn',$line_lower); }
		if ($part_col===false) { $part_col = array_find('item',$line_lower); }
		$qty_col = array_find('qty',$line_lower);
		if ($qty_col===false) { $qty_col = array_find('quantity',$line_lower); }
		if ($qty_col===false) { $qty_col = array_find('qnty',$line_lower); }
		$heci_col = array_find('heci',$line_lower);
		if ($heci_col===false) { $heci_col = array_find('clei',$line_lower); }

		// search all fields for qualifying hecis to eliminate header-style fields such as "HECI: ENPQA0J"
		$heci_exists = false;
		if ($heci_col!==false) {
			foreach ($row_arr as $k => $field) {
				$ffield = format_heci($field);
				if ($ffield AND count(hecidb($ffield,'heci'))>0) { $heci_exists = $k; }
			}
		}

		// at least one of these two fields must be present, otherwise it's prob not a header row.
		if ($part_col!==false OR $heci_col!==false) {// AND $qty_col!==false)
			// if actual heci exists, return it as the column and indicate that it's not a header row
			if ($heci_exists) {
				if ($heci_exists==$part_col) { $part_col = false; }
				return (array($part_col,$qty_col,$heci_exists,false));
			} else {
				// actual heci doesn't exist, return columns and indicate that we have a header row ('true')
				return (array($part_col,$qty_col,$heci_col,true));
			}
		}

		// added this so we wouldn't end up with the word "qty" within the same line as the part#/heci
		// to become the actual qty column itself...it's intended only as the header in the above method;
		// hence, may need to remove $qty_col=false as the third variable in sample_fields() below
		if ($qty_col!==false AND $part_col===false AND $heci_col===false) { $qty_col = false; }

		if (count($row_arr)==1) {
			$part_col = 0;
			$qty_col = false;
			$heci_col = false;
		} else {
			// send in ALL lines of data so we can sample fields throughout, to find a common theme
			list($part_col,$qty_col,$heci_col) = sample_fields($lines,$num_lines,$qty_col);
		}

		return (array($part_col,$qty_col,$heci_col,false));
	}

	function sample_fields($lines,$num_lines,$qty_col=false) {/* passes in qty column (optionally) in case the field reads "qty" or as such */
		$part_col = false;
		$heci_col = false;

		// we have to auto-determine the rows, so use samplings to find types of columns
		if ($num_lines>3) {
			// ex: 6 lines; $r1=2; $r2=4
			$r1 = round($num_lines/3);
			$r2 = $r1*2;
			// take first sample from the first portion of array (ex: 6 lines; rand(1,2))
			$s1 = $lines[rand(1,$r1)];//sample array one
			// take second sample from middle half of array (ex: 6 lines; rand(3,4))
			$s2 = $lines[rand(($r1+1),$r2)];//sample array two
			// take third sample from end of array (ex: 6 lines; rand(5,5))
			$s3 = $lines[rand(($r2+1),($num_lines-1))];//sample array three
		} else {
			// if 1, 2 or 3 rows of data, our sample arrays must be within that range
			if ($num_lines==1) { $r1 = 0; $r2 = 0; $r3 = 0; }
			if ($num_lines==2) { $r1 = 0; $r2 = 1; $r3 = 1; }
			if ($num_lines==3) { $r1 = 0; $r2 = 2; $r3 = 2; }
			$s1 = $lines[$r1];
			$s2 = $lines[$r2];
			$s3 = $lines[$r3];
		}

		$ff1 = find_fields($s1,$qty_col);
		$ff2 = find_fields($s2,$qty_col);
		$ff3 = find_fields($s3,$qty_col);
		// if part and qty columns of first sample exist...
		if ($ff1[0]!==false AND $ff1[1]!==false) {
			// if first sample part and second sample part are the same, as well as first qty and second qty, OR
			// first part matches third, and first qty matches third...
			if (($ff1[0]==$ff2[0] AND $ff1[1]==$ff2[1]) OR ($ff1[0]==$ff3[0] AND $ff1[1]==$ff3[1])) {
				$part_col = $ff1[0];
				$qty_col = $ff1[1];
				if ($ff1[2]!==false) { $heci_col = $ff1[2]; }
				else if ($ff2[2]!==false) { $heci_col = $ff2[2]; }
				else if ($ff3[0]==$ff1[0] AND $ff3[1]==$ff1[1] AND $ff3[2]!==false) { $heci_col = $ff3[2]; }
			}
		} else if ($ff2[0]!==false AND $ff2[1]!==false AND $ff2[0]==$ff3[0] AND $ff2[1]==$ff3[1]) {
			$part_col = $ff2[0];
			$qty_col = $ff2[1];
			if ($ff2[2]!==false) { $heci_col = $ff2[2]; }
			else if ($ff3[2]!==false) { $heci_col = $ff3[2]; }
		} else if ($ff1[0]!==false) {
			$part_col = $ff1[0];//default to the only field found
		}

		return (array($part_col,$qty_col,$heci_col));
	}
?>
