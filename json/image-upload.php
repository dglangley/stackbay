<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/logSearchMeta.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/processUpload.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/vendor/autoload.php';

    // this will simply read AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY from env vars
/*
	if (! $DEV_ENV) {
		$s3 = Aws\S3\S3Client::factory();
		$bucket = getenv('S3_BUCKET')?: die('No "S3_BUCKET" config var in found in env!');
	}
*/

	$upload_listid = 0;
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['upload_file']['tmp_name'])) {
        try {
            // key the filename on aws using today's date, companyid and the filename
            $filename = 'inv'.date("Ymd").'_'.$cid.'_'.$_FILES['upload_file']['name'];
die('test mode');

            // check for file existing already
			$keyExists = false;
			if (! $DEV_ENV) {
	            $s3->registerStreamWrapper();
				$keyExists = file_exists("s3://".$bucket."/".$filename);
			}

            if ($keyExists) {//file has already been uploaded
                $ALERT = array('code'=>14,'message'=>$E[14]['message']);
            } else {
				// default
				$expDate = format_date(date("m-d-Y 17:00"),"n/j/Y g:i A",array('d'=>7));
				if (isset($_REQUEST['expDate']) AND format_date($_REQUEST['expDate'])) {
					$expDate = date("Y-m-d H:i:s",strtotime($_REQUEST['expDate']));
				}
				$upload_type = 'availability';
				if (isset($_REQUEST['upload_type']) AND $_REQUEST['upload_type']=='Req') { $upload_type = 'demand'; }
			}
        } catch(Exception $e) {
//            $ALERT = array('code'=>18,'message'=>$E[18]['message']);
die('died');
        }
	}
?>
