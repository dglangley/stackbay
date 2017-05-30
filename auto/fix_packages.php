<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';

	$query = "SELECT pc.*, serial_no FROM package_contents pc LEFT JOIN inventory i ";
	$query .= "ON i.id = serialid ";
	$query .= "WHERE i.id IS NULL; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$query2 = "SELECT i.* FROM inventory_170504 i17, inventory i ";
		$query2 .= "WHERE i17.id = '".$r['serialid']."' AND i17.serial_no IS NOT NULL AND i17.serial_no = i.serial_no; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)==0) { continue; }
		$r2 = mysqli_fetch_assoc($result2);

		$query2 = "UPDATE package_contents SET serialid = '".$r2['id']."' WHERE serialid = '".$r['serialid']."'; ";
echo $query2.'<BR>';
//		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
	}
?>
