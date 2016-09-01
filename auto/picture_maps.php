<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
exit;

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

		$part = format_part($r['part_numbers']);
		$heci = $r['hecis'];

		$results = getPartId($part,$heci,0,true);
		if (count($results)==0) { continue; }

//echo $part.' '.$heci.'<BR>';
//print "<pre>".print_r($results,true)."</pre>";
//print "<pre>".print_r($imgs,true)."</pre>";
		foreach ($results as $row => $partid) {
			foreach ($imgs as $img) {
				$query3 = "INSERT INTO picture_maps (partid, image) VALUES ('".$partid."','".$img."'); ";
				$result3 = qdb($query3) OR die(qe().' '.$query3);
//echo $query3.'<BR>';
			}
		}
	}
?>
