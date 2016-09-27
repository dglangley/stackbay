<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$query = "SELECT manfs.name, systems.system, parts.systemid, systems.manfid, COUNT(search_meta.id) n
            FROM search_meta, demand, parts, manfs, systems
                WHERE demand.partid = parts.id
                AND systems.manfid = manfs.id
                AND parts.systemid = systems.id
                AND LEFT(search_meta.datetime,10) >= CURDATE() - interval 30 day
                AND search_meta.id = demand.metaid
                AND sysimg = 1
            GROUP BY systems.system
            ORDER BY n DESC LIMIT 30; ";
	$result = qdb($query) OR die(qe().' '.$query);
	// so long as we have a successful query above, delete previous instances
	if (mysqli_num_rows($result)>0) {
		$query2 = "DELETE FROM featured_products; ";
		$result2 = qdb($query2) OR die(qe().' '.$query2);
	}
	while ($r = mysqli_fetch_assoc($result)) {
		$url = '/products/'.strtolower(preg_replace('/[^[:alnum:]]+/','-',$r['name'])).'/'.strtolower(preg_replace('/[^[:alnum:]]+/','-',$r['system']));
		$query2 = "INSERT INTO featured_products (name, system, manfid, systemid, url, n) ";
		$query2 .= "VALUES ('".res($r['name'])."','".res($r['system'])."','".$r['manfid']."',";
		$query2 .= "'".$r['systemid']."','".res($url)."','".$r['n']."'); ";
		$result2 = qdb($query2) OR die(qe().' '.$query2);
	}
?>
