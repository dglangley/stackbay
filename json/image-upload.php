<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/stampImage.php';
	include_once $_SERVER["ROOT_DIR"].'/vendor/autoload.php';

	$search = '';
	if (isset($_REQUEST['search'])) { $search = trim($_REQUEST['search']); }
	$watermark = 0;
	if (isset($_REQUEST['watermark']) AND $_REQUEST['watermark']==1) { $watermark = 1; }

	$temp_dir = sys_get_temp_dir();
	if (substr($temp_dir,strlen($temp_dir)-1,1)<>'/') { $temp_dir .= '/'; }
//	echo $temp_dir;

    // this will simply read AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY from env vars
	$s3 = false;
	$bucket = '';
	if (! $DEV_ENV) {
		$s3 = Aws\S3\S3Client::factory(array('region'=>'us-west-2'));

		$bucket = getenv('S3_BUCKET')?: die('No "S3_BUCKET" config var in found in env!');
	}

	// get array of uploaded images from user input
	$uploads = array();
	if (isset($_FILES['file']) AND is_array($_FILES['file'])) {
		$uploads = $_FILES['file'];
	}

	// use the 'name' array of uploaded files, and then reference each of the image attributes by that key
	$names = $uploads['name'];
	foreach ($names as $k => $filename) {
		$type = $uploads['type'][$k];//ie, "image/jpeg"
		$tmp_file = $uploads['tmp_name'][$k];//ie, "/var/tmp/blahblahblah"
		$err = $uploads['error'][$k];
		$size = $uploads['size'][$k];

        try {
            // check for file existing already
			$keyExists = false;
			if ($DEV_ENV) {
				// at least for debugging purposes, save to temp dir because otherwise the file is immediately
				// lost after this script is complete
				if (move_uploaded_file($tmp_file, $temp_dir.$filename)) {
//					echo $tmp_file." is valid, and was successfully uploaded as ".$temp_dir.$filename."\n";

					// create stamped image, and upload as well
					if ($watermark) {
						$stamped_image = stampImage($temp_dir.$filename);
						$new_filename = preg_replace('/^(.*)([.](png|jpg|jpeg))$/i','$1-vttn$2',$filename);
						copy($stamped_image, $temp_dir.$new_filename);
					}
				} else {
					die($temp_dir.$filename.' file did not save from '.$tmp_file);
				}
			} else {
	            $s3->registerStreamWrapper();
				$keyExists = file_exists("s3://".$bucket."/".$filename);
			}

            if ($keyExists) {//file has already been uploaded
				die($filename.' image name is already uploaded');
            } else {
				if (! $DEV_ENV) {
	                $upload = $s3->upload($bucket, $filename, fopen($tmp_file, 'rb'), 'public-read');

					// create stamped image, and upload as well
					if ($watermark) {
						$stamped_image = stampImage($tmp_file);
//						$new_filename = preg_replace('/([.](png|jpg|jpeg))?$/i','-vttn$1',$filename);
						$new_filename = preg_replace('/^(.*)([.](png|jpg|jpeg))$/i','$1-vttn$2',$filename);
						$upload = $s3->upload($bucket, $new_filename, fopen($stamped_image, 'rb'), 'public-read');
					}
				}

				// get every partid associated with the search string and match in db with each partid
				$results = hecidb($search);
				foreach ($results as $partid => $P) {
	                $query = "INSERT INTO picture_maps (partid, image) VALUES ('".$partid."','".$filename."'); ";
					//htmlspecialchars($upload->get('ObjectURL'));
					$result = qdb($query) OR die(qe().' '.$query);
				}
			}
        } catch(Exception $e) {
			die('Unable to upload image:\n '.$e);
        }
	}
	exit;//success is without a message
?>
