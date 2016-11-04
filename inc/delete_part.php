<?php
	include_once 'dbconnect.php';
exit;

	// this is a batch script for part deletion; USER BEWARE!!!

	$query = "SELECT * FROM parts WHERE part RLIKE '^325A[01][0-9].*'; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$partid = $r['id'];

echo $r['part'].'<BR>';
		$query2 = "DELETE FROM availability WHERE partid = '".res($partid)."'; ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().' '.$query2);

		$query2 = "DELETE FROM market WHERE partid = '".res($partid)."'; ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().' '.$query2);

		$query2 = "DELETE FROM demand WHERE partid = '".res($partid)."'; ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		$query2 = "DELETE FROM favorites WHERE partid = '".res($partid)."'; ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		$query2 = "DELETE FROM prices WHERE partid = '".res($partid)."'; ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		$query2 = "DELETE FROM notifications WHERE partid = '".res($partid)."'; ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		$query2 = "DELETE FROM purchase_items WHERE partid = '".res($partid)."'; ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		$query2 = "DELETE FROM sales_items WHERE partid = '".res($partid)."'; ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		$query2 = "DELETE FROM rfqs WHERE partid = '".res($partid)."'; ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		$query2 = "DELETE FROM parts WHERE id = '".res($partid)."'; ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		$query2 = "DELETE FROM parts_index WHERE partid = '".res($partid)."'; ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		$query2 = "DELETE FROM picture_maps WHERE partid = '".res($partid)."'; ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().' '.$query2);
echo '<BR><BR>';
	}

	exit;
?>
