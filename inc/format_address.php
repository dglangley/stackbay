<?php
	function format_address($addressid,$line_sep='<br/>') {
		$address = '';

		$query = "SELECT * FROM addresses WHERE id = '".res($addressid)."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$address = $r['name'];
			if ($address AND $r['street']) { $address .= $line_sep; }
			if ($r['street']) { $address .= $r['street']; }
			if ($address AND $r['addr2']) { $address .= $line_sep; }
			if ($r['addr2']) { $address .= $r['addr2']; }
			if ($address AND $r['addr3']) { $address .= $line_sep; }
			if ($r['addr3']) { $address .= $r['addr3']; }
			if ($address AND $r['city'].$r['state'].$r['postal_code']) { $address .= $line_sep; }
			if ($r['city']) { $address .= $r['city']; }
			if ($r['city'] AND $r['state']) { $address .= ', '; }
			if ($r['state']) { $address .= $r['state']; }
			if (($r['city'] OR $r['state']) AND $r['postal_code']) { $address .= ' '; }
			if ($r['postal_code']) { $address .= $r['postal_code']; }

			return ($address);
		}
	}
?>
