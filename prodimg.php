<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$uri = strtolower(trim(preg_replace('/([\/]img[\/]parts[\/])([^.]+)([.])(png|jpg|gif)/i','$2',$_SERVER["REQUEST_URI"])));
//	if (substr($uri,0,8)=='/prodimg') { exit; }
	$uriSplit = explode('_',$uri);
	$basePart = preg_replace('/[^[:alnum:]]+/','',$uriSplit[0]);
	$sequence = 1;
	if (count($uriSplit)>1) { $sequence = $uriSplit[1]; }
	$sequence += 1;//first two pics in iterations below are label (part#/heci) pics, skip those

//	header('Content-Type:image/jpeg');
//	readfile('img/data-center.jpg');

	$img = 'img/spacer.jpg';
	$dir = '/var/prodimg/';
//	$dir = 'sqlite:/Users/Shared/WebServer/Sites/';

	$query = "SELECT image FROM picture_maps, parts_index, keywords ";
	$query .= "WHERE keyword = '".$basePart."' AND keywords.id = parts_index.keywordid AND picture_maps.partid = parts_index.partid ";
	$query .= "ORDER BY picture_maps.id ASC; ";
	$result = qdb($query);
	$i = 0;//iteration counter
	while ($r = mysqli_fetch_assoc($result)) {
		// skip the first two pics, assuming they're label pictures
		if ($i<=1) { $i++; continue; }
		else if ($i<>$sequence) { $i++; continue; }
		$i++;

		$img = $dir.str_replace('.jpg','-vttn.jpg',str_replace('.JPG','-vttn.JPG',$r['image']));
		break;
	}
//if ($uri=='1180212l2') { echo $query.'<BR>'; exit; }

//	if ($i==0) { $img = 'img/noimage.png'; }

	header('Content-Type:image/jpeg');
	readfile($img);
?>
