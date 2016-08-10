<?php
	set_time_limit(0);
	ini_set('memory_limit', '2000M');
	ini_set('mbstring.func_overload', '2');
	ini_set('mbstring.internal_encoding', 'UTF-8');

	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';

	$listid = 0;
	if (isset($_REQUEST['listid']) AND is_numeric($_REQUEST['listid']) AND $_REQUEST['listid']>0) { $listid = $_REQUEST['listid']; }
	else if (isset($argv[1])) { $listid = $argv[1]; }

	if (! $listid) {
		die("No list passed in");
	}

	$lines = array();
	$favs = array();

	$search_index = 0;
	$qty_index = false;
	$query = "SELECT search_meta.id metaid, uploads.type FROM search_meta, uploads ";
	$query .= "WHERE uploads.id = '".res($listid)."' AND uploads.metaid = search_meta.id; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		if ($r['type']=='demand') { $table_qty = 'request_qty'; }
		else { $table_qty = 'avail_qty'; }

		$query2 = "SELECT search, ".$table_qty." qty FROM parts, ".$r['type'].", searches ";
		$query2 .= "WHERE metaid = '".$r['metaid']."' AND parts.id = partid AND ".$r['type'].".searchid = searches.id; ";
		$result2 = qdb($query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			if (array_search($r2['search'],$lines)!==false) { continue; }

			$lines[] = $r2['search'];//.' '.$r2['qty'];
		}
	}

	foreach ($lines as $ln => $line) {
		// split into words/terms on white-space separations, whether space or tab
		$terms = preg_split('/[[:space:]]+/',$line);
		// trim search string and convert to uppercase
		$search_str = strtoupper(trim($terms[$search_index]));
		if (strlen($search_str)<=1) { continue; }

/*
		$search_qty = 1;//default
		if (isset($terms[$qty_index])) {
			$qty_text = trim($terms[$qty_index]);
			$qty_text = preg_replace('/^(qty|qnty|quantity)?([.]|-)?0?([0-9]+)([.]|-)?(x|ea)?/i','$3',$qty_text);

			if (is_numeric($qty_text) AND $qty_text>0) { $search_qty = $qty_text; }
		}
*/

		// if 10-digit string, detect if qualifying heci, determine if heci so we can search by 7-digit instead of full 10
		$heci7_search = false;
		if (strlen($search_str)==10 AND ! is_numeric($search_str) AND preg_match('/^[[:alnum:]]{10}$/',$search_str)) {
			$query = "SELECT heci FROM parts WHERE heci LIKE '".substr($search_str,0,7)."%'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) { $heci7_search = true; }
		}

		if ($heci7_search) {
			$results = hecidb(substr($search_str,0,7));
		} else {
			$results = hecidb(format_part($search_str));
		}

		// pre-process results so that we can build a partid string for this group as well as to group results
		// if the user is showing just favorites
		foreach ($results as $partid => $P) {
			// check favorites
			$query = "SELECT * FROM favorites WHERE partid = '".$partid."'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) {
				$favs[$partid] = $P;
			}
		}
	}

	foreach ($favs as $partid => $P) {
		echo $partid.':'.$P['part'].' '.$P['heci'].' "'.$P['search'].'"'.chr(10);
	}
?>
