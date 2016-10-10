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

	$time_start = microtime(true);
*/


/* DAVID */
/*
	$query = "SELECT * FROM master_records ORDER BY id ASC; ";
	$result = mysqli_query($dbi,$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$query2 = "REPLACE david_primary (userid, roleid, id) VALUES ('".$r['userid']."','".$r['roleid']."','".$r['id']."'); ";
		$result2 = mysqli_query($dbi,$query2);
		$query2 = "REPLACE david_unique (userid, roleid) VALUES ('".$r['userid']."','".$r['roleid']."'); ";
		$result2 = mysqli_query($dbi,$query2);
	}
*/

/* ANDREW */
/*
$query = "SELECT * FROM master_records ORDER BY id ASC; ";
$result = mysqli_query($dbi,$query);
while ($r = mysqli_fetch_assoc($result)) {
  $query2 = "INSERT INTO andrew_primary (id, userid, roleid) VALUES ('".$r['id']."','".$r['userid']."','".$r['roleid']."') ON DUPLICATE KEY UPDATE userid = VALUES(userid), roleid = VALUES(roleid); ";
  $result2 = mysqli_query($dbi,$query2) OR die(qe().' '.$query2);
  $query2 = "INSERT INTO andrew_unique (userid, roleid) VALUES ('".$r['userid']."','".$r['roleid']."') ON DUPLICATE KEY UPDATE userid = VALUES(userid), roleid = VALUES(roleid); ";
  $result2 = mysqli_query($dbi,$query2) OR die(qe().' '.$query2);
}


	$time_end = microtime(true);
	$time = $time_end - $time_start;
	echo $time.chr(10);
*/
?>
