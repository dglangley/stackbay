<?php
	// includes S3 stream wrapper functions
	include_once $_SERVER["ROOT_DIR"].'/vendor/autoload.php';

	// this will simply read AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY from env vars
	if (! $DEV_ENV) {
		$S3 = Aws\S3\S3Client::factory(array('region'=>'us-west-2'));
		$BUCKET = getenv('S3_ORDER_UPLOADS')?: die('No "S3_ORDER_UPLOADS" config var in found in env!');
	}

	function saveFile($file) {
		global $FILE_ERR,$BUCKET,$TEMP_DIR,$DEV_ENV,$S3;

		// $file should be an array from $_FILES

		$file_upload = '';
		try {
			$filename = date("Ymd").'_'.preg_replace('/[^[:alnum:].]+/','-',$file['name']);

			// check for file existing already
			$keyExists = false;
			if (! $DEV_ENV) {
				$S3->registerStreamWrapper();
				$keyExists = file_exists("s3://".$BUCKET."/".$filename);
			}
			if ($keyExists) {//file has already been uploaded
				$FILE_ERR = 'File has already been uploaded!';

				$file_upload = "https://s3-us-west-2.amazonaws.com/".$BUCKET."/".$filename;
				return ($file_upload);
			}
			if ($DEV_ENV) {
				$temp_file = $TEMP_DIR.$filename;
				if ($GLOBALS['debug']) {
					return ($temp_file);
					//$file_upload = "https://s3-us-west-2.amazonaws.com/".$BUCKET."/".$filename;
					//return ($file_upload);
				}

				// store uploaded file in temp dir so we can use it later
				if (move_uploaded_file($file['tmp_name'], $temp_file)) {
					// File is valid, and successfully uploaded/moved
					$file_upload = $temp_file;

				} else {
					$FILE_ERR = 'File "'.$file['tmp_name'].'" did not save to "'.$temp_file.'"!';
					return false;
				}
			} else {
				if (! $GLOBALS['debug']) {
	                $upload = $S3->upload($BUCKET, $filename, fopen($file['tmp_name'], 'rb'), 'public-read');
				}
				$file_upload = "https://s3-us-west-2.amazonaws.com/".$BUCKET."/".$filename;
			}
       	} catch(Exception $e) {
			$FILE_ERR = 'Error! '.$e;
			return false;
		}

		return ($file_upload);
	}

	$FILE_ERR;
	function saveFiles($files) {
		global $FILE_ERR;

		if (! is_array($files)) { $files = array(); }

		$file_uploads = array();
		foreach ($files as $file) {
			//4=no file uploaded
			if ($file['error']==4) { return false; }

			$file_uploads[] = saveFile($file);
			if ($FILE_ERR) {
				return false;
			}
		}

		if (count($file_uploads)==1) {
			return ($file_uploads[0]);
		} else if (count($file_uploads)>0) {
			return ($file_uploads);
		} else {
			return false;
		}
	}
?>
