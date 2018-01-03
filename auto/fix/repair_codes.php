<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$query = "SELECT ro.repair_code_id, ri.id FROM repair_orders ro, repair_items ri ";
	$query .= "WHERE ro.ro_number = ri.ro_number ";
	$query .= "AND ro.repair_code_id IS NOT NULL AND ri.repair_code_id IS NULL; ";
	$result = qedb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$query2 = "UPDATE repair_items SET repair_code_id = '".$r['repair_code_id']."' WHERE id = '".$r['id']."' AND repair_code_id IS NULL; ";
//echo $query2.'<BR>';
	}
?>
