<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/logRemotes.php';
	if ($REMOTES['ps']['setting']=='Y') {
		include_once $_SERVER["ROOT_DIR"].'/inc/ps.php';
	}
	if ($REMOTES['te']['setting']=='Y') {
		include_once $_SERVER["ROOT_DIR"].'/inc/te.php';
	}
	if ($REMOTES['bb']['setting']=='Y') {
		include_once $_SERVER["ROOT_DIR"].'/inc/bb.php';
	}
	if ($REMOTES['excel']['setting']=='Y') {
		include_once $_SERVER["ROOT_DIR"].'/inc/excel.php';
	}
	if ($REMOTES['ebay']['setting']=='Y') {
		include_once $_SERVER["ROOT_DIR"].'/inc/ebay.php';
	}
	include_once $_SERVER["ROOT_DIR"].'/inc/array_keysearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/array_append.php';

	function searchRemotes($partid_csv,$attempt,$ln,$max_ln) {
		global $err,$errmsgs,$REMOTES;

		$done = '';

		// stash all searches in strings for each remote
		$searches = array();
		$limited = array();

		/***** KEYWORD SEARCH *****/

		$checked_ids = array();
		// get primary keywords (part, heci, etc) and corresponding partid's
		$query = "SELECT keyword, partid FROM keywords, parts_index ";
		$query .= "WHERE partid IN (".$partid_csv.") AND keywordid = keywords.id AND rank = 'primary' ";
		$query .= "ORDER BY LENGTH(keyword) DESC; ";//sort in desc length so we can work backwards to eliminate matching substrings later
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			if (strlen($r['keyword'])<2) { continue; }

			// no duplicates, and also check if we've added 7-digit heci already or a truncated version of this string
			if (isset($searches[$r['keyword']])) { continue; }

			// eliminate matching super-strings
			array_keysearch($limited,$r['keyword']);

			$searches[$r['keyword']] = $r['keyword'];
			$limited[$r['keyword']] = true;

			// somehow saving processing time ???
			if (isset($checked_ids[$r['partid']])) { continue; }

			// for ebay, get original-formatted keyword with punctuations because we don't get the same results
			// with punctuation-less keywords as we do with original formats (ie, "090-58022-01")
			$query2 = "SELECT part FROM parts WHERE id = '".$r['partid']."'; ";
			$result2 = qdb($query2);
			if (mysqli_num_rows($result2)==0) { continue; }
			$checked_ids[$r['partid']] = true;

			$r2 = mysqli_fetch_assoc($result2);
			$part_strs = explode(' ',$r2['part']);
			foreach ($part_strs as $part_str) {
				$part_str = format_part($part_str);
				$fpart = preg_replace('/[^[:alnum:]]+/','',$part_str);
				if ($r['keyword']==$fpart) { $searches[$part_str] = $part_str; }
			}
		}


		/***** BROKER STRING BUILDING *****/

		// string unique searches now into single line-separated string
		$psstr = '';
		$bbstr = '';
		$ebaystr = '';
		$excelstr = '';
		$ps_err = '';
		$bb_err = '';
		$te_err = '';
		$ebay_err = '';
		$excel_err = '';
		foreach ($searches as $keyword => $k2) {
			// try remotes only after the first attempt ($attempt==0) because we want the first attempt to produce
			// statically-stored db results
			if ($attempt>=1 AND ($ln<=$max_ln OR $attempt==2)) {
				// log attempts on remotes for every keyword based on current remote session settings, regardless of error outcomes below

				// if this is not in $limited[] it's because it would produce redundant results for broker sites,
				// but for ebay it's more precise because their search method is pickier
				if (! isset($limited[$keyword])) {
					$scan_code = '';
					for ($n=0; $n<$GLOBALS['NUM_REMOTES']; $n++) {
						if ($n==3) { $scan_code .= '1'; } else { $scan_code .= '0'; }
					}
					$RLOG = logRemotes($keyword,$scan_code);
				} else {
					$RLOG = logRemotes($keyword);
				}
			} else {
				$RLOG = logRemotes($keyword,$GLOBALS['REMDEF']);
			}

			// place this first because results below may be limited due to $limited/$searches differences
			if ($RLOG['ebay']) {
				if ($ebaystr) { $ebaystr .= ','; }
				$ebaystr .= $keyword;
			}

			// only continue beyond this point if the keyword is in $limited[], which is our less-redundant
			// array (ie, NTK555DA only, as opposed to NTK555DA + NTK555DAE5) on account of ebay's pickier
			// search method but the broker sites are more open / relaxed
			if (! isset($limited[$keyword])) { continue; }

			if ($RLOG['ps']) { $psstr .= $keyword.chr(10); }
//			if ($RLOG['bb']) { $bbstr .= $keyword.chr(10); }
			if ($RLOG['excel']) { $excelstr .= $keyword.chr(10); }
//			$bbstr .= $keyword.chr(10);

			// gotta hit brokerbin individually because SOAP
			if ($RLOG['bb']) {

				$bb = bb($keyword);
				if ($bb_err) {
					$err[] = 'bb';
					$errmsgs[] = $bb_err;
				}
			} else if ($REMOTES['bb']['setting']=='N') {
				$err[] = 'bb';
				$errmsgs[] = $REMOTES['bb']['name'].' is not activated';
			}

			// gotta hit tel-explorer individually because there's no work-around for their multi-search (when not logged in)
			if ($RLOG['te']) {

				$te = te($keyword);
				if ($te_err) {
					$err[] = 'te';
					$errmsgs[] = $te_err;
				}
			} else if ($REMOTES['te']['setting']=='N') {
				$err[] = 'te';
				$errmsgs[] = $REMOTES['te']['name'].' is not activated';
			}
		}

		if ($attempt>=1) {
			if ($psstr) {

				$ps_err = ps($psstr);
				if ($ps_err) {
					$err[] = 'ps';
					$errmsgs[] = $ps_err;
				}
			} else if ($REMOTES['ps']['setting']=='N') {
				$err[] = 'ps';
				$errmsgs[] = $REMOTES['ps']['name'].' is not activated';
			}
			if ($bbstr) {

				$bb_err = bb($bbstr);
				if ($bb_err) {
					$err[] = 'bb';
					$errmsgs[] = $bb_err;
				}
			} else if ($REMOTES['bb']['setting']=='N') {
				$err[] = 'bb';
				$errmsgs[] = $REMOTES['bb']['name'].' is not activated';
			}
			if ($ebaystr) {

				$ebay_err = ebay($ebaystr);
				if ($ebay_err) {
					$err[] = 'ebay';
					$errmsgs[] = $ebay_err;
				}
			} else if ($REMOTES['ebay']['setting']=='N') {
				$err[] = 'ebay';
				$errmsgs[] = $REMOTES['ebay']['name'].' is not activated';
			}
			if ($excelstr) {

				$excel_err = excel($excelstr);
				if ($excel_err) {
					$err[] = 'excel';
					$errmsgs[] = $excel_err;
				}
			} else if ($REMOTES['excel']['setting']=='N') {
				$err[] = 'excel';
				$errmsgs[] = $REMOTES['excel']['name'].' is not activated';
			}

			// when we're done with all remote calls
			$done = 1;
		}

		return ($done);
	}
?>
