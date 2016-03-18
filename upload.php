<?php
	include_once 'inc/dbconnect.php';
	include_once 'inc/format_date.php';
    include_once 'inc/logSearchMeta.php';
    include_once 'inc/processUpload.php';
    include_once 'inc/getCompany.php';
	require('vendor/autoload.php');

	// set cookies for upload selections, if present
//	setcookie('upload_type.870','Req');//VZ
//	setcookie('upload_type.361','Avail');//Rogers

    // this will simply read AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY from env vars
	if (! $DEV_ENV) {
		$s3 = Aws\S3\S3Client::factory();
		$bucket = getenv('S3_BUCKET')?: die('No "S3_BUCKET" config var in found in env!');
	}

	$upload_listid = 0;
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['upload_file']['tmp_name'])) {
        try {
			$cid = setCompany('upload_companyid');//uses $_REQUEST data with this field name, passed in
			if ($cid) {
				if (! isset($_REQUEST['upload_type']) OR $_REQUEST['upload_type']=='Avail') {
					setcookie('upload_type.'.$cid,'Avail');
				} else {
					setcookie('upload_type.'.$cid,'Req');
				}
			}

            // key the filename on aws using today's date, companyid and the filename
            $filename = 'inv'.date("Ymd").'_'.$cid.'_'.$_FILES['upload_file']['name'];

            // check for file existing already
			$keyExists = false;
			if (! $DEV_ENV) {
	            $s3->registerStreamWrapper();
				$keyExists = file_exists("s3://".$bucket."/".$filename);
			}

            if ($keyExists) {//file has already been uploaded
                $ALERT = array('code'=>14,'message'=>$E[14]['message']);
die('file already is uploaded');
            } else {
				// default
				$expDate = format_date(date("m-d-Y 17:00"),"n/j/Y g:i A",array('d'=>7));
				if (isset($_REQUEST['expDate']) AND format_date($_REQUEST['expDate'])) {
					$expDate = date("Y-m-d H:i:s",strtotime($_REQUEST['expDate']));
				}
				$upload_type = 'availability';
				if (isset($_REQUEST['upload_type']) AND $_REQUEST['upload_type']=='Req') { $upload_type = 'demand'; }

				// check for re-submission duplicate
				$query = "SELECT uploads.id FROM uploads, search_meta ";
				$query .= "WHERE filename = '".res($_FILES['upload_file']['name'])."' AND companyid = '".$cid."' ";
				$query .= "AND datetime LIKE '".$today."%' AND uploads.metaid = search_meta.id; ";
				$result = qdb($query);
				if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$upload_listid = $r['id'];
				} else {
					$metaid = logSearchMeta($cid);

					if ($DEV_ENV) {
						$temp_dir = sys_get_temp_dir();
						if (substr($temp_dir,strlen($temp_dir)-1,1)<>'/') { $temp_dir .= '/'; }
						$temp_file = $temp_dir.preg_replace('/[^[:alnum:].]+/','-',$_FILES['upload_file']['name']);

						// store uploaded file in temp dir so we can use it later
						if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $temp_file)) {
//							echo "File is valid, and was successfully uploaded.\n";
						} else {
							die('file did not save');
						}

   		             	$query = "INSERT INTO uploads (filename, userid, metaid, exp_datetime, type, processed, link) ";
   		             	$query .= "VALUES ('".res($_FILES['upload_file']['name'])."','1','".$metaid."',";
   		             	$query .= "'".res($expDate)."','".$upload_type."',NULL,'".htmlspecialchars($temp_file)."'); ";
					} else {
		                $upload = $s3->upload($bucket, $filename, fopen($_FILES['upload_file']['tmp_name'], 'rb'), 'public-read');

		                $query = "INSERT INTO uploads (filename, userid, companyid, datetime, exp_datetime, type, processed, link) ";
		                $query .= "VALUES ('".res($_FILES['upload_file']['name'])."','".res($U['id'])."','".$metaid."',";
		                $query .= "'".res($expDate)."','".$upload_type."',NULL,'".htmlspecialchars($upload->get('ObjectURL'))."'); ";
					}
					$result = qdb($query) OR die(qe().' '.$query);
					$upload_listid = qid();
				}

				// process file now, if less than 500kb; otherwise, leave it to be scheduled
				if ($_FILES['upload_file']['size']<500000) {
					processUpload($upload_listid);
				}

/*
                $ALERT = array('code'=>0,'message'=>'Success! Processing can take up to 20 mins...');
*/
            }
        } catch(Exception $e) {
//            $ALERT = array('code'=>18,'message'=>$E[18]['message']);
die('died');
        }
    } else if (isset($_REQUEST['upload_listid']) AND is_numeric($_REQUEST['upload_listid']) AND $_REQUEST['upload_listid']>0) {
		$upload_listid = $_REQUEST['upload_listid'];
	}

	$urlstr = '';
	if ($upload_listid) { $urlstr = '?listid='.$upload_listid; }
	if (isset($_REQUEST['favorites']) AND $_REQUEST['favorites']==1) {
		if ($urlstr) { $urlstr .= '&'; } else { $urlstr .= '?'; }
		$urlstr .= 'favorites=1';
	}

	header('Location: /'.$urlstr);
	exit;
?>
