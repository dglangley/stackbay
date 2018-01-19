<?php
exit;
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/import_aid.php';

	$query = "SELECT id, name, inventory_id, closed FROM inventory_project; ";
	$result = qdb($query,'PIPE') or die(qe("PIPE")." | $query");
	while ($r = mysqli_fetch_assoc($result)) {
		$partid = translateID($r['inventory_id']);
		$status = 'Active';
		if ($r['closed']) { $status = 'Completed'; }

		$query2 = "INSERT INTO builds (name, partid, status, id) ";
		$query2 .= "VALUES ('".trim($r['name'])."','".$partid."','$status','".$r['id']."'); ";
		$result2 = qdb($query2) or die(qe()." | $query2");
	}
?>