<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';

	function getPartId($part,$heci='',$manfid=0,$return_all_results=false) {
		$partid = 0;

		$part = trim($part);
		if (strlen($part)<=1 AND ! $heci) {
			if ($return_all_results) { return (array()); } else { return (''); }
		}
		$heci = trim($heci);

		$num_results = 0;
		if ($heci AND strlen($heci)>=7 AND strlen($heci)<=10) {
			$query = "SELECT id FROM parts WHERE heci LIKE '".res($heci)."%' ORDER BY ";
			if (strlen($heci)==10) { $query .= "IF(heci='".res($heci)."',0,1), "; }
			if ($part) { $query .= "IF(part LIKE '".res($part)."%',0,1), "; }
			$query .= "part, rel, heci; ";
			$result = qdb($query) OR die(qe().' '.$query);
			$num_results = mysqli_num_rows($result);
		}
		if ($num_results==0 AND $part) {
			// strip off non-alphanumerics
			$fpart = preg_replace('/[^[:alnum:]]+/','',$part);

			$ord = "ORDER BY part, rel, heci; ";
			$keyword_query = "SELECT parts.id FROM keywords, parts_index, parts ";
			$keyword_query .= "WHERE keyword = '".res($fpart)."' AND keywords.id = parts_index.keywordid ";
			$keyword_query .= "AND rank = 'primary' AND parts_index.partid = parts.id ";
			$keyword_query .= "AND (LEFT(keyword,7) <> LEFT(heci,7) OR heci IS NULL) ".$ord;
			$result = qdb($keyword_query);
			$num_results = mysqli_num_rows($result);
			if ($num_results==0) {
				$keyword_query = "SELECT parts.id FROM keywords, parts_index, parts ";
				$keyword_query .= "WHERE keyword LIKE '".res($fpart)."%' AND keywords.id = parts_index.keywordid ";
				$keyword_query .= "AND rank = 'primary' AND parts_index.partid = parts.id ";
				$keyword_query .= "AND (LEFT(keyword,7) <> LEFT(heci,7) OR heci IS NULL) ".$ord;
				$result = qdb($keyword_query);
				$num_results = mysqli_num_rows($result);
			}

			if ($num_results==0) {
				// get base part#, maybe rev ending is messing up query; strip off non-alphanumerics
				$fbase_part = preg_replace('/[^[:alnum:]]+/','',format_part($part));
				if ($fpart<>$fbase_part) {
					$keyword_query = "SELECT parts.id FROM keywords, parts_index, parts ";
					$keyword_query .= "WHERE keyword = '".res($fbase_part)."' AND keywords.id = parts_index.keywordid ";
					$keyword_query .= "AND rank = 'primary' AND parts_index.partid = parts.id ";
					$keyword_query .= "AND (LEFT(keyword,7) <> LEFT(heci,7) OR heci IS NULL) ".$ord;
					$result = qdb($keyword_query);
					$num_results = mysqli_num_rows($result);
				}
			}

			if ($num_results==0) {
				$keyword_query = "SELECT parts.id FROM keywords, parts_index, parts ";
				$keyword_query .= "WHERE keyword LIKE '".res($part)."%' AND keywords.id = parts_index.keywordid ";
				$keyword_query .= "AND rank = 'primary' AND parts_index.partid = parts.id ";
				$keyword_query .= "AND (LEFT(keyword,7) <> LEFT(heci,7) OR heci IS NULL) ".$ord;
				$result = qdb($keyword_query);
				$num_results = mysqli_num_rows($result);
			}

			// maybe it's a heci? retry above query without heci exclusion
			if ($num_results==0 AND strlen($part)>=7 AND strlen($part)<=10) {
				$keyword_query = "SELECT parts.id FROM keywords, parts_index, parts ";
				$keyword_query .= "WHERE keyword LIKE '".res($part)."%' AND keywords.id = parts_index.keywordid ";
				$keyword_query .= "AND rank = 'primary' AND parts_index.partid = parts.id ".$ord;
				$result = qdb($keyword_query);
				$num_results = mysqli_num_rows($result);
			}
		}

		if ($return_all_results) { $dbresult = array(); } else { $dbresult = ''; }
		if ($num_results>0) {
			while ($r = mysqli_fetch_assoc($result)) {
				if (! $return_all_results) {
					$dbresult = $r['id'];
					break;
				}
				$dbresult[] = $r['id'];
			}
		}

		return ($dbresult);
	}
?>
