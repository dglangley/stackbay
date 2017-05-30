<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';

	$query = "SELECT pc.*, serial_no FROM package_contents pc LEFT JOIN inventory i ";
	$query .= "ON i.id = serialid ";
	$query .= "WHERE i.id IS NULL; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
//		$query2 = "SELECT i.* FROM inventory_170504 i17, inventory i ";
//		$query2 .= "WHERE i17.id = '".$r['serialid']."' AND i17.serial_no IS NOT NULL AND i17.serial_no = i.serial_no; ";
		$query2 = "SELECT * FROM inventory_shipping WHERE serials LIKE '%".$r['serial_no']."%'; ";
echo $query2.'<BR>';
continue;
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
		if (mysqli_num_rows($result2)==0) { continue; }
		$r2 = mysqli_fetch_assoc($result2);

		$query2 = "SELECT * FROM package_contents WHERE serialid = '".$r2['id']."' AND packageid = '".$r['packageid']."'; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
			$query2 = "DELETE FROM package_contents WHERE serialid = '".$r['serialid']."' AND packageid = '".$r['packageid']."'; ";
echo $query2.'<BR>';
//			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		} else {
			$query2 = "UPDATE package_contents SET serialid = '".$r2['id']."' WHERE serialid = '".$r['serialid']."' AND packageid = '".$r['packageid']."'; ";
echo $query2.'<BR>';
//			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		}
	}
?>
