<?php
	$LOCKED = true;
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/insertMarket.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	require($_SERVER["ROOT_DIR"].'/vendor/autoload.php');

	$et_cols = array(
		0 => 'HECI',
		1 => 'PART NO.',
		2 => 'DESCRIPTION',
		3 => 'VENDOR',
		4 => 'TYPE',
		5 => 'QTY.',
	);

	if (! isset($REMOTES)) {
		$REMOTES = array();
		$query = "SELECT * FROM remotes WHERE userid = '".res($U['id'])."'; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$REMOTES[$r['remote']] = $r;
		}
	}

	if (! isset($filterOn)) { $filterOn = false; }
	$et_cid = getCompany('Excel Computers','name','id');

	function download_et($search='',$logout=false) {
		$search = trim($search);
		if (! $search AND ! $logout) { return false; }

		// for now:
		if ($logout) { return false; }

		$tries = 0;
		$res = false;
		while ($res===false AND $tries<3) {
			$res = et($search,$logout);//returning false indicates a new session just created or an old one found invalid
			$tries++;
		}
		// couldn't activate a good session for whatever reason
		if ($res===false OR $logout) {
			return false;
		}

		$col_cats = $GLOBALS['et_cols'];
		$cid = $GLOBALS['et_cid'];

		$newDom = new domDocument;
		$newDom->loadHTML($res);
//		$newDom->preserveWhiteSpace = false;
		$xpath = new DomXpath($newDom);
//		$entries = $xpath->query("//*[@id='searchResults']/div[contains(concat(' ', normalize-space(@class), ' '), ' inner ')]");
		$resultsRows = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' inner ')]");

//		$resultsTable = $newDom->getElementById('searchResults')->getElementsByTagName('div');
//		print "<pre>".print_r($resultsTable,true)."</pre>";
		$n = $resultsRows->length;
		for ($i=1; $i<$n; $i++) {
			$cols = $resultsRows->item($i)->getElementsByTagName('div');

			$eci = 0;
			$manf = trim($cols->item(array_search('VENDOR',$col_cats))->nodeValue);
			$descr = trim(strtoupper($cols->item(array_search('DESCRIPTION',$col_cats))->nodeValue));
			if ($manf AND $descr) { $descr = $manf.' '.$descr; }
			$qty = trim($cols->item(array_search('QTY.',$col_cats))->nodeValue);
			$heci = '';
			if ($cols->item(array_search('HECI',$col_cats))->getElementsByTagName('a')->length>0) {
				if ($cols->item(array_search('HECI',$col_cats))->getElementsByTagName('a')->item(0)->getElementsByTagName('span')->length>0) {
					$heci = trim(str_replace('N/A','',$cols->item(array_search('HECI',$col_cats))->getElementsByTagName('a')->item(0)->getElementsByTagName('span')->item(0)->nodeValue));
				}
			}
//			print "<pre>".print_r($heci,true)."</pre>";
			$part = trim(strtoupper($cols->item(array_search('PART NO.',$col_cats))->nodeValue));
			$partid = getPartId($part,$heci);

			//echo 'et:'.$part.'<BR>';
			//continue;
			if (! $partid) {
				$partid = setPart(array('part'=>$part,'heci'=>$heci,'manf'=>$manf,'sys'=>'','descr'=>$descr));
			}
//			echo 'Identifying '.$part.'/'.$heci.' = '.$partid.' to be added...'.chr(10);
			//must return a variable so this function doesn't happen asynchronously
			$added = insertMarket($partid,$qty,$cid,$GLOBALS['now'],'ET');
		}

		return true;
	}

	$et_base = 'http://www.excel-telco.com/inventory/index.php';
	function et($search,$logout=false) {
		$userid = $GLOBALS['U']['id'];
		if (! $userid) { $userid = 1; }

		// create temp file name in temp directory for cookies
		$filename = "et-cookies-".$userid.".txt";
		$temp_dir = sys_get_temp_dir();
		// if last character of temp dir is not a slash, add it so we can append file after that
		if (substr($temp_dir,strlen($temp_dir)-1)<>'/') { $temp_dir .= '/'; }
		$cookies_file = $temp_dir.$filename;

		$s3fileExists = false;
		// this will simply read AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY from env vars
		$s3 = Aws\S3\S3Client::factory();
		// register the stream wrapper to use the native php functions on s3
		$s3->registerStreamWrapper();
		$bucket = getenv('S3_COOKIES_BUCKET')?: die('No "bucket" config var found!');

		$globalSessionExists = true;
		// if not there, create it by getting existing file from s3, and if not there, create it altogether
		$cookiesTempFileExists = file_exists($cookies_file);
		if (! $cookiesTempFileExists) {
			// check for file existing already on s3
			$s3fileExists = file_exists("s3://".$bucket."/".$filename);

			if ($s3fileExists) {//get it from s3 and place it in temp dir
				$fileurl = $s3->getObjectUrl($bucket, $filename);
				$file = file_get_contents($fileurl);

				file_put_contents($cookies_file,$file);
			} else {//indicates a session doesn't exist, so we'll initialize it below
				$globalSessionExists = false;
			}
		}

/*
		// if no session and no login request, we can't continue forward
		if (! $globalSessionExists AND ! $logout AND (! isset($_REQUEST['remote_login']) OR ! isset($_REQUEST['remote_password']))) {
			return false;
		}
*/

		$ch = curl_init('http://www.excel-telco.com');
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 4);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies_file);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies_file);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if (isset($_SERVER['HTTP_USER_AGENT']) AND $_SERVER['HTTP_USER_AGENT']) {
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		} else {
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/535.6 (KHTML, like Gecko) Chrome/16.0.897.0 Safari/535.6');
		}

		curl_setopt($ch, CURLOPT_HTTPGET, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_REFERER, 'http://www.excel-telco.com/inventory');
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'searchcriteria='.urlencode($search).'&ispost=1&vendor=&submit=&citycode=murcielagoW:]BeeG');
		curl_setopt($ch, CURLOPT_URL, $GLOBALS['et_base']);

		$result = curl_exec($ch);

		curl_close($ch);
//		print "<pre>".print_r(htmlentities($result),true)."</pre>";

		return ($result);
	}
?>
