<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/cmp.php';

	$query = "SELECT i.part_number, /*r.part_number, r.shipped_pn, r.clei repair_clei, */ i.clei, i.heci, r.shipped_clei, ";
	$query .= "i.short_description description, m.name manf, r.inventory_id ";
	$query .= "FROM inventory_repair r, inventory_inventory i, inventory_manufacturer m ";
	$query .= "WHERE i.id = r.inventory_id AND r.created_at >= '2015-01-01 00:00:00' AND i.manufacturer_id_id = m.id; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
//echo $query.'<BR>';
	while ($r = mysqli_fetch_assoc($result)) {
		if ($r['clei']) { $r['heci'] = $r['clei']; }
		else if (strlen($r['heci'])<>7 OR is_numeric($r['heci']) OR preg_match('/[^[:alnum:]]+/',$r['heci'])) { $r['heci'] = ''; }

		$partid = getPartId($r['part_number'],$r['heci']);
		if (! $partid) {
			$partid = setPart(array('part'=>$r['part_number'],'heci'=>$r['heci'],'manf'=>$r['manf'],'descr'=>$r['description']));
		}
//echo $r['part_number'].' / '.$r['clei'].' = '.$partid.'<BR>';

		$manf = '';
		$sys = '';
		$query2 = "SELECT name manf, system FROM parts p, manfs m, systems s ";
		$query2 .= "WHERE p.id = $partid AND p.manfid = m.id AND p.systemid = s.id; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$manf = $r2['manf'];
			$sys = $r2['system'];
			break;//end while loop, should just be one result
		}
		$key = $manf;
		if ($sys) { $key .= '|'.$sys; }
		if (! isset($parts[$key])) { $parts[$key] = array(); }
		$parts[$key][] = array('part'=>$r['part_number'],'heci'=>$r['heci']);
	}

	uasort($parts,'cmp_count');
	foreach ($parts as $key => $r) {
		echo $key.'<BR>';//).' '.count($r).'<BR>';
	}
?>
