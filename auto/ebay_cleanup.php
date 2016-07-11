<?php
	include_once '../inc/dbconnect.php';

	$query = "SELECT ebayid, COUNT(id) n FROM contacts WHERE ebayid IS NOT NULL ";
	$query .= "GROUP BY name, ebayid HAVING n > 1 ORDER BY name ASC, id ASC; ";
	$result = qdb($query) OR die(qe());
	while ($r = mysqli_fetch_assoc($result)) {
//		echo $r['ebayid'].' '.$r['n'].'x<BR>';
		$query2 = "SELECT id FROM contacts WHERE ebayid = '".$r['ebayid']."' ORDER BY id ASC LIMIT 1,".$r['n']."; ";
		$result2 = qdb($query2) OR die(qe());
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$query3 = "DELETE FROM contacts WHERE id = '".$r2['id']."'; ";
//			$result3 = qdb($query3);
		}
	}
?>
