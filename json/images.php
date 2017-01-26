<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/img_exists.php';

	if (isset($_SERVER["SERVER_NAME"]) AND $_SERVER["SERVER_NAME"]=='marketmanager.local') {
		$dir = '/devimgs/';
	} else {
		$dir = 'https://s3-us-west-2.amazonaws.com/ven-tel.com-product-images/';
	}
	$basePart = preg_replace('/[^[:alnum:]]+/','',$_REQUEST['search']);

	$primary = '';
	$images = array();
	$i = 0;
	$query = "SELECT image FROM picture_maps, parts_index, keywords ";
	$query .= "WHERE keyword = '".$basePart."' AND keywords.id = parts_index.keywordid AND picture_maps.partid = parts_index.partid ";
	$query .= "GROUP BY image ORDER BY picture_maps.id ASC; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$n = mysqli_num_rows($result);
	while ($r = mysqli_fetch_assoc($result)) {
		$img = $dir.preg_replace('/([.](jpg|jpeg))/i','-vttn$1',$r['image']);
		if (! img_exists($img)) { $img = str_ireplace('-vttn','',$img); }
		$images[] = $img;

		// indicate a primary if none are selected manually
		if ($n>2) {
			if ($i<=1) { $i++; continue; }
			else if ($i<>$sequence) { $i++; continue; }
			$i++;
		} else if ($n==2 AND $i==0) {//if 2 pics, skip the first pic (we're assuming it's a label pic)
			$i++;
			continue;
		}
	}

	if (count($images)==0) { $images[] = '/img/noimage.png'; }

	echo json_encode(array('images'=>$images,'primary'=>$i));
	exit;
?>
