<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/img_exists.php';

	$uri = strtolower(trim(preg_replace('/([\/]img[\/]parts[\/])([^.]+)([.])(png|jpg|gif)/i','$2',$_SERVER["REQUEST_URI"])));
//	if (substr($uri,0,8)=='/prodimg') { exit; }
	$uriSplit = explode('_',$uri);
	$basePart = preg_replace('/[^[:alnum:]]+/','',$uriSplit[0]);
	$sequence = 1;
	if (count($uriSplit)>1) { $sequence = $uriSplit[1]; }
	$sequence += 1;//first two pics in iterations below are label (part#/heci) pics, skip those

//	header('Content-Type:image/jpeg');
//	readfile('img/data-center.jpg');

	if ($DEV_ENV) {
		if (strstr($_SERVER["REQUEST_URI"],'devimgs')) {
			$dir = sys_get_temp_dir();
			if (substr($dir,strlen($dir)-1,1)<>'/') { $dir .= '/'; }

			$img = $dir.str_replace('/devimgs/','',$uri);
			if (! strstr($img,'vttn')) {
				$img = preg_replace('/^(.*)([.](png|jpg|jpeg))$/i','$1-vttn$2',$img);
			}

			// now img will have 'vttn' in the filename, but if doesn't exist, remove it
			if (! file_exists($img)) {
				$img = str_ireplace('-vttn','',$img);
			}

			header('Content-Type:image/jpeg');
			echo readfile($img);
			exit;
		} else {
			$dir = sys_get_temp_dir();
			if (substr($dir,strlen($dir)-1,1)<>'/') { $dir .= '/'; }
		}
		$dir = 'https://s3-us-west-2.amazonaws.com/ven-tel.com-product-images/';
	} else {
		$dir = 'https://s3-us-west-2.amazonaws.com/ven-tel.com-product-images/';
	}
	$img = $dir.'spacer.jpg';

	$query = "SELECT image, prime FROM picture_maps, parts_index, keywords ";
	$query .= "WHERE keyword = '".$basePart."' AND keywords.id = parts_index.keywordid AND picture_maps.partid = parts_index.partid ";
	$query .= "GROUP BY image ORDER BY IF(prime='1',0,1), picture_maps.id ASC; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$n = mysqli_num_rows($result);
	$i = 0;//iteration counter
	while ($r = mysqli_fetch_assoc($result)) {
		if (! $r['prime']) {
			// skip the first two pics, assuming they're label pictures and as long as there are at least 3 pics
			if ($n>2) {
				if ($i<=1) { $i++; continue; }
				else if ($i<>$sequence) { $i++; continue; }
				$i++;
			} else if ($n==2 AND $i==0) {//if 2 pics, skip the first pic (we're assuming it's a label pic)
				$i++;
				continue;
			}
			$i++;
		}

//		$img = $dir.str_replace('.jpg','-vttn.jpg',str_replace('.JPG','-vttn.JPG',$r['image']));
		$img = $dir.preg_replace('/^(.*)([.](png|jpg|jpeg))$/i','$1-vttn$2',urlencode($r['image']));

		// now img will have 'vttn' in the filename, but if doesn't exist, remove it
//		if (! file_exists($img)) {
		if (! img_exists($img)) {
			$img = str_ireplace('-vttn','',$img);
		}
		break;
	}
//echo $img;exit;
//if ($uri=='1180212l2') { echo $query.'<BR>'; exit; }

//	if ($i==0) { $img = 'img/noimage.png'; }

	// check that the file actually exists, and if it doesn't with the appended "-vttn",
	// try removing it because some of brian's pictures don't have it for some reason;
	// distinction for local vs remote because img_exists() is for remote, file_exists() for local
	if ($DEV_ENV) {
		$img_exists = file_exists($img);
	} else {
		$img_exists = img_exists($img);
	}
	if (! $img_exists) { $img = str_ireplace('-vttn','',$img); }
	$img = str_replace(' ','+',$img);

	header('Content-Type:image/jpeg');
	echo readfile($img);
?>
