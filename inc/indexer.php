<?php
	set_time_limit(0);
	ini_set('memory_limit', '2000M');

	include_once 'dbconnect.php';
	include_once 'getManf.php';
	include_once 'getSys.php';
	include_once 'format_part.php';

	$PRIMARIES = array();
	function indexer($search='',$stype='') {
		$results = array();

		if (! $stype OR $stype=='heci' OR $stype=='part' OR $stype=='id' OR $stype=='partid' OR $stype=='systemid') {
			// generate keywords
			$query = "SELECT * FROM parts ";
			if ($stype=='heci') { $query .= "WHERE heci LIKE '".res($search)."%' "; }
			else if (! $stype OR $stype=='part') { $query .= "WHERE part LIKE '".res($search)."%' "; }
			else if ($stype=='id' OR $stype=='partid') { $query .= "WHERE id = '".res($search)."' "; }
			else if ($stype=='systemid') { $query .= "WHERE systemid = '".res($search)."' "; }//added 4-25-18
			$query .= "; ";
			$result = qedb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				$r['manf'] = getManf($r['manfid']);
				$r['system'] = getSys($r['systemid']);
				$k = $r['id'];
				$results[$k] = $r;
			}
		} else if ($stype=='manfid' OR $stype=='manf') {
			// generate manfs
			$query = "SELECT name manf, id FROM manfs; ";
			$result = qedb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				$k = $r['id'];
				$results[$k] = $r;
			}
		} else if ($stype=='sysid' OR $stype=='systemid' OR $stype=='sys' OR $stype=='system') {
			// generate systems
			$query = "SELECT system, id FROM systems ";
			if ($search) {
				if ($stype=='sysid' OR $stype=='systemid') { $query .= "WHERE id = '".res($search)."' "; }
				else if ($stype=='sys' OR $stype=='system') { $query .= "WHERE system = '".res($search)."' "; }
			}
			$query .= "; ";
			$result = qedb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				$k = $r['id'];
				$results[$k] = $r;
			}
		}

		foreach ($results as $k => $r) {
			if ($search) {
				$query = "DELETE FROM parts_index WHERE partid = '".$k."'; ";
				$result = qedb($query);
			}

			$PRIMARIES = array();//reset every part; makes sure secondary rank doesn't override primary if part# is in description, for example

			while (list($f,$v) = each($r)) {
				// don't use certain fields that don't have keywords, and don't use any capitalized
				// words that we generated above because they're duplicates or irrelevant
				if ($f=='id' OR $f=='classification' OR $f=='rel' OR $f=='systemid' OR $f=='manfid' OR ($f=='manf' AND is_numeric($v) AND strlen($v)==3)) { continue; }
//				if ($f=='id' OR $f=='classification' OR $f=='rel' OR $f=='manfid' OR ($f=='manf' AND is_numeric($v) AND strlen($v)==3)) { continue; }

				$v = trim($v);
				if (! $v) { continue; }

				if ($f=='manf' OR $f=='system') {
					$words = preg_split('/[[:space:]-]+/',$v);
					// if there were words to split, add full root word (original string) as well
					if (count($words)>1) { $words[] = $v; }
				} else {
					$words = explode(' ',$v);
				}
				while (list($dkey,$dword) = each($words)) {
					if ($GLOBALS['DEBUG']==3) {
//						echo $dword.' '.$f.' '.$k.' <BR> '.chr(10);
					}

					// change the primary key value to systemid for systems_index and manfid for manfs_index
					if ($f=='system') {
						addWords($dword,$f,$r['systemid'],$r['manfid']);
						keyword($dword,$k,'part');
					} else if ($f=='manf') {
						addWords($dword,$f,$r['manfid'],$r['manfid']);
					} else {
						addWords($dword,$f,$k,$r['manfid']);
					}
				}
				reset($words);
			}
			reset($r);
		}
	}

	$KEYWORDS = array();
	function keyword($keyword,$fieldid,$table_name) {
		global $KEYWORDS,$PRIMARIES;

		$keyword = strtoupper(trim(preg_replace('/[^[:alnum:]]*/','',$keyword)));
		if (! $keyword OR ! $fieldid OR ! $table_name) { return; }

		$rank = '';
		if ($table_name=='description') {
			$table_name = 'part';
			$rank = 'secondary';
		} else if ($table_name=='part') {
			$rank = 'primary';
		}
		$field_name = $table_name.'id';
		$table_name .= 's_index';

		if ($keyword=='CISCO' OR $keyword=='POWER' OR preg_match('/^(rev|iss)-?[0-9]*$/i',$keyword)) { $rank = 'secondary'; }

		$keywordid = 0;
		if (! isset($KEYWORDS[$keyword])) {
			$query = "SELECT * FROM keywords WHERE keyword = '".res($keyword)."'; ";
			$result = qedb($query);// OR die(qe().' '.$query);
			if (mysqli_num_rows($result)==0) {
				$query = "REPLACE keywords (keyword) VALUES ('".res($keyword)."'); ";
				$result = qedb($query);// OR die(qe().' '.$query);
				$keywordid = qid();
			} else {
				$r = mysqli_fetch_assoc($result);
				$keywordid = $r['id'];
			}
			$KEYWORDS[$keyword] = $keywordid;
		} else {
			$keywordid = $KEYWORDS[$keyword];
		}

		if ($rank=='primary') {
			$PRIMARIES[$keywordid] = true;
		} else if (isset($PRIMARIES[$keywordid])) {
			return false;
		}

		$query = "REPLACE $table_name (keywordid, ";
		if ($rank AND $table_name=='parts_index') { $query .= "rank, "; }
		$query .= "$field_name) VALUES ('".res($keywordid)."',";
		if ($rank AND $table_name=='parts_index') { $query .= "'".res($rank)."',"; }
		$query .= "'".res($fieldid)."'); ";
		if ($GLOBALS['DEBUG']==3) {
			echo $keyword.' ';
		}
		$result = qedb($query);// OR die(qe().' '.$query);
		return (qid());
	}

	function addWords($v,$col_name,$k,$manfid=false) {
		if (! $v) { return; }

		if ($col_name=='part') {
			keyword($v,$k,$col_name);

			// permute O's to 0's
/*
			$ppart = str_replace('O','0',$v);
			if ($ppart<>$v) {
				keyword($ppart,$k,'part');
			}

			// permute 0's to O's
			$ppart = str_replace('0','O',$v);
			if ($ppart<>$v) {
				keyword($ppart,$k,'part');
			}
*/

			$base_part = format_part($v,$manfid);
			if ($v<>$base_part) {
				keyword($base_part,$k,$col_name);
				$fbase = preg_replace('/[^[:alnum:]]*/','',$base_part);

				keyword($fbase,$k,$col_name);
			}
		} else if ($col_name=='heci') {
			keyword($v,$k,'part');

			$heci7 = substr($v,0,7);

			keyword($heci7,$k,'part');

			// permute O's to 0's
			$pheci = str_replace('O','0',$v);
			$pheci7 = substr($pheci,0,7);
			if ($pheci7<>substr($v,0,7)) {
				keyword($pheci,$k,'part');
				keyword($pheci7,$k,'part');
			}

			// permute 0's to O's
			$pheci = str_replace('0','O',$v);
			$pheci7 = substr($pheci,0,7);
			if ($pheci7<>substr($v,0,7)) {
				keyword($pheci,$k,'part');
				keyword($pheci7,$k,'part');
			}

			// permute 1's to I's
			$pheci = str_replace('1','I',$v);
			$pheci7 = substr($pheci,0,7);
			if ($pheci7<>substr($v,0,7)) {
				keyword($pheci,$k,'part');
				keyword($pheci7,$k,'part');
			}

			// permute I's to 1's
			$pheci = str_replace('I','1',$v);
			$pheci7 = substr($pheci,0,7);
			if ($pheci7<>substr($v,0,7)) {
				keyword($pheci,$k,'part');
				keyword($pheci7,$k,'part');
			}
		} else {
			$keywords = array($v);
			$words = preg_split('/[^[:alnum:]]+/',$v);
			foreach ($words as $word) {
				if ($word!==$v) { $keywords[] = $word; }
			}

			// now add all keywords
			foreach ($keywords as $keyword) {
				keyword($keyword,$k,$col_name);
			}
		}
	}
?>
