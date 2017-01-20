<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/img_exists.php';

	if (isset($_SERVER["SERVER_NAME"]) AND $_SERVER["SERVER_NAME"]=='marketmanager.local') {
		$dir = '/devimgs/';
	} else {
		$dir = 'https://s3-us-west-2.amazonaws.com/ven-tel.com-product-images/';
	}
	$basePart = preg_replace('/[^[:alnum:]]+/','',$_REQUEST['search']);

	$images = array();
	$query = "SELECT image FROM picture_maps, parts_index, keywords ";
	$query .= "WHERE keyword = '".$basePart."' AND keywords.id = parts_index.keywordid AND picture_maps.partid = parts_index.partid ";
	$query .= "GROUP BY image ORDER BY picture_maps.id ASC; ";
	$result = qdb($query) OR die(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$img = $dir.str_replace('.jpg','-vttn.jpg',str_replace('.JPG','-vttn.JPG',$r['image']));
		if (! img_exists($img)) { $img = str_ireplace('-vttn','',$img); }
		$images[] = $img;
	}

	if (count($images)==0) { $images[] = '/img/noimage.png'; }

	echo json_encode(array('images'=>$images));
	exit;
?>
