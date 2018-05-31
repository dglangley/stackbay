<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';

	function format_address($addressid,$line_sep='<br/>',$include_name=true,$attn='',$companyid=0,$info_sep='',$include_ext=true) {
		$address = '';

		$query = "SELECT * FROM addresses WHERE id = '".res($addressid)."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) { return ($address); }

		$r = mysqli_fetch_assoc($result);
		$address = '';
		if ($include_name) { $address = $r['name']; }
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

		if ($attn) {
			$address .= $line_sep.'Attn: '.$attn;
		}

		if ($companyid) {
			$query = "SELECT * FROM company_addresses ";
			$query .= "WHERE addressid = '".res($addressid)."' AND companyid = '".res($companyid)."' ";
			$query .= "AND (nickname IS NOT NULL OR alias IS NOT NULL OR contactid IS NOT NULL OR code IS NOT NULL OR notes IS NOT NULL); ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);

				if (! $info_sep) { $info_sep = $line_sep; }

				$info = '';
				if ($r['nickname']) {
					$info .= '<strong>Site Name:</strong> '.$r['nickname'];
				}
//				if ($r['alias'] OR $r['code'] OR $r['notes']) { $info .= $info_sep; }
//				if ($r['alias']) { $info .= '<strong>Site Name:</strong> '.$r['alias']; }
				if ($r['contactid']) {
					if ($info) { $info .= $info_sep; }
					$info .= '<strong>Site Contact:</strong> '.getContact($r['contactid'],'id','name').' '.getContact($r['contactid'],'id','phone');
				}
				if ($include_ext) {
					if ($r['code']) {
						if ($info) { $info .= $info_sep; }
						$info .= '<strong>Site Code:</strong> '.$r['code'];
					}
					if ($r['notes']) {
						if ($info) { $info .= $info_sep; }
						$info .= '<strong>Notes:</strong> '.str_replace(chr(10),$info_sep,$r['notes']);
					}
				}

				// append all info from above to address for output below
				$address .= $info_sep.$info;
			}
		}

		return ($address);
	}
?>
