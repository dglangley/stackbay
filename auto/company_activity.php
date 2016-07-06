<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/format_date.php';

	$past_date = format_date($today,'Y-m-d 00:00:00',array('m'=>-6));

	$query = "DELETE FROM company_activity; ";
	$result = qdb($query) OR die(qe());

	$activity = array();
	$query = "SELECT companyid, COUNT(search_meta.id) n FROM demand, search_meta ";
	$query .= "WHERE datetime >= '".$past_date."' AND demand.metaid = search_meta.id ";
	$query .= "GROUP BY companyid ORDER BY n DESC; ";
	$result = qdb($query) OR die(qe());
	while ($r = mysqli_fetch_assoc($result)) {
		$activity[$r['companyid']] = array('demand'=>$r['n'],'supply'=>0);
	}

	$query = "SELECT companyid, COUNT(search_meta.id) n FROM availability, search_meta ";
	$query .= "WHERE datetime >= '".$past_date."' AND availability.metaid = search_meta.id ";
	$query .= "GROUP BY companyid ORDER BY n DESC; ";
	$result = qdb($query) OR die(qe());
	while ($r = mysqli_fetch_assoc($result)) {
		$demand = 0;
		if (isset($activity[$r['companyid']])) { $demand = $activity[$r['companyid']]['demand']; }
		$activity[$r['companyid']] = array('demand'=>$demand,'supply'=>$r['n']);
	}

	foreach ($activity as $companyid => $T) {
		$query = "INSERT INTO company_activity (companyid, demand_volume, supply_volume) ";
		$query .= "VALUES ('".$companyid."','".$T['demand']."','".$T['supply']."'); ";
		$result = qdb($query) OR die(qe());
	}
?>
