<?php
	set_time_limit(0);
	ini_set('memory_limit', '2000M');

	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';

//	$query = "DELETE FROM picture_maps; ";
//	$result = qdb($query) OR die(qe().' '.$query);

	$query = "SELECT part_numbers, hecis, id FROM inventory_looseinventoryimagegroup; ";
	$result = qdb($query,'PIPE');
	while ($r = mysqli_fetch_assoc($result)) {
		$group_id = $r['id'];

		$imgs = array();
		$query2 = "SELECT * FROM inventory_looseinventoryimage WHERE group_id = '".$group_id."'; ";
		$result2 = qdb($query2,'PIPE');
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$imgs[] = str_replace('LooseInventory/','',$r2['image']);
		}

		$part = format_part(trim($r['part_numbers']));
		$heci = trim(substr($r['hecis'],0,7));

		// 4th parameter is 'true' for returning all results
		$results = getPartId($part,$heci,0,true);
		if (count($results)==0) { continue; }

//echo $part.' '.$heci.'<BR>';
//print "<pre>".print_r($results,true)."</pre>";
//print "<pre>".print_r($imgs,true)."</pre>";
		foreach ($results as $row => $partid) {
			// get prime picture, if one already set
			$prime = '';
			$query3 = "SELECT * FROM picture_maps WHERE partid = '".$partid."' AND prime = '1'; ";
			$result3 = qdb($query3) OR die(qe().' '.$query3);
			if (mysqli_num_rows($result3)>0) {
				$r3 = mysqli_fetch_assoc($result3);
				$prime = $r3['image'];
			}

			foreach ($imgs as $img) {
				$img_prime = '0';
				if ($img==$prime) { $img_prime = '1'; }
				//$query3 = "INSERT INTO picture_maps (partid, image) VALUES ('".$partid."','".$img."'); ";
				$query3 = "REPLACE picture_maps (partid, prime, image) VALUES ('".$partid."','".$img_prime."','".$img."'); ";
				$result3 = qdb($query3) OR die(qe().' '.$query3);
//echo $query3.'<BR>';
			}
		}
	}
	echo 'Success!';
?>
