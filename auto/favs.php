<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSupply.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/array_stristr.php';

	$partids = array();
	$keywords = array();
	$query = "SELECT p.part, p.heci, f.partid, f.userid, f.datetime ";
	$query .= "FROM favorites f, parts p ";
	$query .= "WHERE f.partid = p.id ";
	$query .= "GROUP BY p.id ORDER BY p.part, p.heci LIMIT 0,3; ";
//	echo $query.'<BR>';
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$partids[$r['partid']] = $r['partid'];
continue;

		$part_words = array();
		$query2 = "SELECT i.rank, i.keywordid, k.keyword ";
		$query2 .= "FROM parts_index i, keywords k ";
		$query2 .= "WHERE i.partid = '".$r['partid']."' AND i.keywordid = k.id ";
		$query2 .= "AND i.rank = 'primary' ";
		$query2 .= "GROUP BY i.keywordid ";
		$query2 .= "ORDER BY LENGTH(k.keyword) ASC; ";
//		echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			if (array_stristr($part_words,$r2['keyword'],true)!==false) { continue; }

			$part_words[$r2['keyword']] = $r2['keyword'];
		}

		// add formatted part numbers to group of words for this partid, if unique
		$parts = explode(' ',$r['part']);
		foreach ($parts as $part) {
//			$fpart = preg_replace('/[^[:alnum:]]*/','',format_part($part));
//			if (! isset($part_words[$fpart])) { $part_words[$fpart] = true; }
		}

		foreach ($part_words as $word) {
			if (array_stristr($keywords,$word,true)!==false) { continue; }

			$keywords[$word] = $word;
		}
//		print "<pre>".print_r($keywords,true)."</pre>";
	}

//	print "<pre>".print_r($partids,true)."</pre>";

	$results = getSupply($partids,1);
	print "<pre>".print_r($results,true)."</pre>";

	foreach ($results as $datestr => $r) {
	}
?>
