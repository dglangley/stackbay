<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';

	header("Content-type: text/csv; charset=utf-8");
	header("Cache-Control: no-store, no-cache");
	header('Content-Disposition: attachment; filename="repair-export-'.$today.'.csv"');

	$outstream = fopen("php://output",'w');

	$header = array('Part','HECI','Manf','System');
	fputcsv($outstream, $header, ',', '"');

	$query = "SELECT part, heci, m.name, system, systemid, COUNT(ri.id) n ";
	$query .= "FROM repair_orders ro, repair_items ri, manfs m, parts p ";
	$query .= "LEFT JOIN systems s ON p.systemid = s.id ";
	$query .= "WHERE ro.ro_number = ri.ro_number AND ri.partid = p.id ";
	$query .= "AND created >= '2016-01-01 00:00:00' AND p.manfid = m.id ";
	$query .= "AND systemid IN ( ";
	$query .= "	SELECT systemid FROM repair_orders ro, repair_items ri, parts p ";
	$query .= "	LEFT JOIN systems s ON p.systemid = s.id ";
	$query .= "	WHERE ro.ro_number = ri.ro_number AND ri.partid = p.id ";
	$query .= "	AND created >= '2016-01-01 00:00:00' ";
	$query .= "	AND systemid IS NOT NULL ";
	$query .= "	GROUP BY systemid HAVING COUNT(ri.id) > 2 ";
	$query .= ") ";
	$query .= "GROUP BY partid ORDER BY n DESC; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$manf = $r['name'];
		$system = $r['system'];

		$aliases = explode(' ',$r['part']);
		$heci = $r['heci'];
		foreach ($aliases as $part) {
			//echo '"'.format_part($part).'","'.$heci.'","'.$manf.'","'.$system.'"'.chr(10);
			$row = array(
				format_part($part),
				$heci,
				$manf,
				$system,
			);

			fputcsv($outstream, $row, ',', '"');
			$heci = '';//reset to NOT be included on next alias
		}
	}

	fclose($outstream);
?>
