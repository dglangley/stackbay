<?php
/*
    $host = 'aa1qkk16kza9ick.cbjttt30gz5p.us-west-2.rds.amazonaws.com';
    $user = 'ventel';
    $password = 'joder1234';
    $dbname = 'rusi';
    $port = '3306';
    $charset = 'utf8';

    $dbi = mysqli_connect($host, $user, $password, $dbname, $port);
    if (mysqli_connect_errno($dbi)) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }

	$k = 1;
	$query = "SELECT * FROM master_records ORDER BY id ASC; ";// LIMIT 0,1000; ";
	$result = mysqli_query($dbi,$query);
	while ($r = mysqli_fetch_assoc($result)) {
		if ($r['id']<$k) { continue; }

		$query2 = "INSERT INTO aaron_primary (userid, roleid) VALUES ('".$r['userid']."','".$r['roleid']."'); ";
		$result2 = mysqli_query($dbi,$query2);
		$query2 = "INSERT INTO aaron_unique (userid, roleid) VALUES ('".$r['userid']."','".$r['roleid']."'); ";
		$result2 = mysqli_query($dbi,$query2);
		$query2 = "INSERT INTO andrew_primary (userid, roleid) VALUES ('".$r['userid']."','".$r['roleid']."'); ";
		$result2 = mysqli_query($dbi,$query2);
		$query2 = "INSERT INTO andrew_unique (userid, roleid) VALUES ('".$r['userid']."','".$r['roleid']."'); ";
		$result2 = mysqli_query($dbi,$query2);
		$query2 = "INSERT INTO david_primary (userid, roleid) VALUES ('".$r['userid']."','".$r['roleid']."'); ";
		$result2 = mysqli_query($dbi,$query2);
		$query2 = "INSERT INTO david_unique (userid, roleid) VALUES ('".$r['userid']."','".$r['roleid']."'); ";
		$result2 = mysqli_query($dbi,$query2);

		$k += rand(2,5);
	}
*/
?>
