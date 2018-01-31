<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/img_exists.php';
	include_once $_SERVER["ROOT_DIR"].'/vendor/autoload.php';

	function reportError($err) {
		echo json_encode(array('message'=>$err));
		exit;
	}

	$img = '';
	$imgAction = '';
	if (isset($_REQUEST['img']) AND $_REQUEST['img']) { $img = $_REQUEST['img']; }
	if (isset($_REQUEST['imgAction']) AND $_REQUEST['imgAction']) { $imgAction = $_REQUEST['imgAction']; }

	if ($DEV_ENV) {
//		$dir = '/devimgs/';

		$prefix = sys_get_temp_dir();
		if (substr($prefix,strlen($prefix)-1,1)<>'/') { $prefix .= '/'; }
		$dir = $prefix;

//2-23-17 for display purposes only
		$dir = 'https://s3-us-west-2.amazonaws.com/ven-tel.com-product-images/';
	} else {
//		$dir = '/img/parts/';
		$dir = 'https://s3-us-west-2.amazonaws.com/ven-tel.com-product-images/';

		// when handling images, needs to be on S3
		if ($imgAction=='delete') {
			$s3 = Aws\S3\S3Client::factory(array('region'=>'us-west-2'));
			$bucket = getenv('S3_BUCKET')?: die('No "S3_BUCKET" config var in found in env!');
			$prefix = 's3://'.$bucket.'/';

	        $s3->registerStreamWrapper();
		}
	}
	$basePart = preg_replace('/[^[:alnum:]]+/','',format_part($_REQUEST['search']));

	$images = array();
	$prev_images = array();
	$prime = false;
	$i = 0;
	// count grouped results first, then process individually below so that we can mark individual images with deletion or prime
	$query = "SELECT image, prime, picture_maps.id FROM picture_maps, parts_index, keywords ";
	$query .= "WHERE keyword = '".$basePart."' AND keywords.id = parts_index.keywordid AND picture_maps.partid = parts_index.partid ";
	$query .= "GROUP BY image ORDER BY picture_maps.id ASC; ";
	$result = qdb($query) OR reportError(qe().' '.$query);
	$n = mysqli_num_rows($result);

	$query = "SELECT image, prime, picture_maps.id FROM picture_maps, parts_index, keywords ";
	$query .= "WHERE keyword = '".$basePart."' AND keywords.id = parts_index.keywordid AND picture_maps.partid = parts_index.partid ";
	//$query .= "GROUP BY image ORDER BY picture_maps.id ASC; ";
	$query .= "ORDER BY image ASC, picture_maps.id ASC; ";
	$result = qdb($query) OR reportError(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		// if the user has selected this image for deletion or setting as prime, do it now
		if ($r['image']==$img) {
			if ($imgAction=='delete') {
				unlink($prefix.$img);
				// check for watermarked version as well
				$stampedImage = preg_replace('/^(.*)([.](png|jpg|jpeg))$/i','$1-vttn$2',$img);
				unlink($prefix.$stampedImage);

				$query2 = "DELETE FROM picture_maps WHERE image = '".res($img)."'; ";
				$result2 = qdb($query2) OR reportError(qe().' '.$query2);

				continue;
			} else if ($imgAction=='prime') {
				$query2 = "UPDATE picture_maps SET prime = '1' WHERE id = '".res($r['id'])."'; ";
				$result2 = qdb($query2) OR reportError(qe().' '.$query2);

				if ($prime===false) { $prime = $i; }
			}
		} else if ($img AND $imgAction=='prime' AND $r['prime']==1) {
			//if the user has selected an image to be its prime, and this, if previously-selected as prime, is not that image, unset it
			$query2 = "UPDATE picture_maps SET prime = '0' WHERE id = '".res($r['id'])."'; ";
			$result2 = qdb($query2) OR reportError(qe().' '.$query2);
		} else if (! $img AND $r['prime'] AND $prime===false) {
			// if no image is being manipulated and we're just pulling images, set prime to the db-selected prime
			$prime = $i;
		}
		// our method of grouping without it being in the query so we can do image marking above
		if (isset($prev_images[$r['image']])) { continue; }
		$prev_images[$r['image']] = true;

//		$imgPath = $dir.preg_replace('/([.](jpg|jpeg))/i','-vttn$1',$r['image']);
		$imgPath = $dir.preg_replace('/^(.*)([.](png|jpg|jpeg))$/i','$1-vttn$2',$r['image']);
		if (! img_exists($imgPath)) { $imgPath = str_ireplace('-vttn','',$imgPath); }
$imgPath = $dir.$r['image'];
		$images[] = array('path'=>$imgPath,'filename'=>$r['image']);

		// indicate a prime if none are selected manually
		if ($n>2) {
			if ($i<=1) { $i++; continue; }
			$i++;
		} else if ($n==2 AND $i==0) {//if 2 pics, skip the first pic (we're assuming it's a label pic)
			$i++;
			continue;
		}
	}
	if ($prime!==false) { $i = $prime; }

	if (count($images)==0) { $images[] = array('path'=>'/img/noimage.png','filename'=>'noimage.png'); }

	echo json_encode(array('images'=>$images,'prime'=>$i,'message'=>''));
	exit;
?>
