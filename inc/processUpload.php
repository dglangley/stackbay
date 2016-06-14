<?php
	set_time_limit(0);
	ini_set('memory_limit', '2000M');
	ini_set('mbstring.func_overload', '2');
	ini_set('mbstring.internal_encoding', 'UTF-8');

    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/dbconnect.php';
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/getPartId.php';
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/insertMarket.php';
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/format_date.php';
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/simplexlsx.class.php';//awesome class used for xlsx-only
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/php-excel-reader/excel_reader2.php';//specifically for parsing xls files
//	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/mailer.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/PHPExcel.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/array_find.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/find_fields.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/set_columns.php';
//	require($_SERVER["DOCUMENT_ROOT"].'/vendor/autoload.php');

	$test = 0;
	if (! isset($userid)) { $userid = 1; }

	// used for logging searches below, check once to get number of 0's to log
	$num_remotes = 0;
	$query = "SELECT id FROM remotes; ";
	$result = qdb($query);
	$num_remotes = mysqli_num_rows($result);
	$remotes_log = '';
	for ($i=0; $i<$num_remotes; $i++) {
		$remotes_log .= '0';
	}

	function condenseLines($lines) {
		$condensed = array();
		// use this loop to eliminate empty rows
		foreach ($lines as $n => $row_arr) {
			// verify that there's data within the array
			$verified = false;
			if (! is_array($row_arr)) {
				$verified = true;
			} else {
				foreach ($row_arr as $d) {
					if (trim($d)) { $verified = true; }
				}
			}
			if ($verified) { $condensed[] = $row_arr; }
		}

		return ($condensed);
	}
	function curateResults($condensed,$filename) {
		// columns detected below for each field
		$part_col = false;
		$heci_col = false;
		$qty_col = false;

		// identify the columns and curate the results by removing punctuations
		$curated = array();
		foreach ($condensed as $n => $row_arr) {
			// make array out of fields if not already
			if (! is_array($row_arr)) {
				$data = explode(' ',$row_arr);// ?????
				$num_fields = count($data);
				//reassign $row_arr as the array now
				$row_arr = $data;
			}

//			print "<pre>".print_r($row_arr,true)."</pre>";

			// is this a header row? try to find out
			if ($n==0) {
				list($part_col,$qty_col,$heci_col) = set_columns($row_arr,$condensed);
			}
			if ($n<=1 AND $test) { echo "partcol:$part_col, hecicol:$heci_col, qtycol:$qty_col<BR>"; }

			if (($part_col===false AND $heci_col===false) OR $qty_col===false) {
//				$email = getUser($r['userid'],'id','email');
$email = 'davidglangley@gmail.com';
				$mail_msg = 'Your inventory file named "'.$filename.'" was rejected because it was '.chr(10).
					'unable to determine the Part# and/or Qty field(s).'.chr(10).chr(10).
					'Oftentimes, this can be due to a missing header row, or if your file has only one column of data.'.chr(10);
				$headers = 'From: "LunaCera support" <no-reply@lunacera.com>' . "\r\n" .
					'Bcc: "LunaCera" <info@lunacera.com>'. "\r\n" .
					'X-Mailer: PHP/' . phpversion();
					//'Reply-To: "'.$U['first_name'].' '.$U['last_name'].'" <'.$U['email'].'>'. "\r\n" .
				if (! $test) {
					mail($email,'Inventory file rejected! '.date('D n/j/y'),$mail_msg,$headers);
				}
				break;
			}

//			$part = strtoupper(preg_replace('/[^[:alnum:]]*/','',trim($row_arr[$part_col])));
			$part = '';
			if ($part_col!==false) {
				$part = strtoupper(trim($row_arr[$part_col]));
			}

//			if (preg_match('/(REV|REL|ISS|SER)/',$part)) {
//				$part = preg_replace('/(REV|REL|ISS|SER).+$/','',$part);
//			}

			// replacing chr(0) is removing null characters, specifically for verizon bid list
			$qty = preg_replace('/^([0-9]+)(x|ea)$/i','$1',str_replace(chr(0),'',trim($row_arr[$qty_col])));
			if (! $qty OR ! is_numeric($qty) OR $qty<0) { $qty = 0; }
			$heci = '';
			if ($heci_col!==false) {
				$heci = strtoupper(trim($row_arr[$heci_col]));
				// there's no such thing as a legitimate all-numeric heci, but verizon sometimes uses their internal codes here
				if (is_numeric($heci)) { $heci = ''; }
			}
			if (! $part AND ! $heci) { continue; }

			if ($test) { echo 'part: '.$part.' '.$heci.', qty '.$qty.'<BR>'; }
			$partKey = '';
			if ($part) { $partKey = preg_replace('/[^[:alnum:]]+/','',$part); }
			$partKey .= '.';
			if ($heci) { $partKey .= $heci; }
//			if ($test) { echo $partKey.' '.$qty.'<BR>'; }

			if (! isset($curated[$partKey])) { $curated[$partKey] = 0; }
			$curated[$partKey] += $qty;
//			if ($n>10000) { break; }
		}

		return ($curated);
	}
	function consolidatePartids($curated) {
		global $userid,$now,$today,$remotes_log;

		// further consolidate results by tapping the db for partid's, and keying on that id to get summed qtys
		$consolidated = array();
		foreach ($curated as $partKey => $qty) {
			$keys = explode('.',$partKey);
			$part = '';
			$heci = '';
			if (isset($keys[0])) { $part = $keys[0]; }
			if (isset($keys[1])) { $heci = $keys[1]; }

			$partid = getPartId($part,$heci);
			if (! $partid) { continue; }

			$searchkey = '';
			if ($heci) { $searchkey = $heci; } else { $searchkey = $part; }
			$searchkey = preg_replace('/[^[:alnum:]]*/','',$searchkey);

			// create search string log but without hitting the remotes
			$query2 = "SELECT id FROM searches WHERE search = '".$searchkey."' AND userid = '".$userid."' ";
			$query2 .= "AND datetime LIKE '".$today."%' AND scan = '".$remotes_log."'; ";
			$result2 = qdb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$searchid = $r2['id'];
			} else {
				$query2 = "INSERT INTO searches (search, userid, datetime, scan) ";
				$query2 .= "VALUES ('".$searchkey."','".$userid."','".$now."','".$remotes_log."'); ";
				$result2 = qdb($query2) OR die(qe().' '.$query2);
				$searchid = qid();
			}

			if (! isset($consolidated[$partid])) { $consolidated[$partid] = array('qty'=>0,'searchid'=>$searchid); }
			$consolidated[$partid]['qty'] += $qty;
		}

		return ($consolidated);
	}

	$temp_dir = sys_get_temp_dir();
	if (substr($temp_dir,strlen($temp_dir)-1,1)<>'/') { $temp_dir .= '/'; }
	function processUpload($uploadid) {
		global $temp_dir,$now,$test,$userid,$today,$remotes_log;
		if (! $uploadid OR ! is_numeric($uploadid)) { return false; }

		$query = "SELECT uploads.*, search_meta.companyid, uploads.id uploadid FROM uploads, search_meta ";
		$query .= "WHERE uploads.id = '".res($uploadid)."' AND uploads.metaid = search_meta.id; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) { return false; }

		$r = mysqli_fetch_assoc($result);
		$filename = $r['filename'];
		$filelink = $r['link'];
		$companyid = $r['companyid'];
		$upload_type = $r['type'];
		$metaid = $r['metaid'];

		$lines = array();
		if (strstr($filename,'.xls')) {
			$file = file_get_contents($filelink);

			// create temp file name in temp directory
			$tempfile = tempnam($temp_dir,'inv'.date("ymdHis"));
			// add contents from file
			file_put_contents($tempfile,$file);
/*
			$tempfile = $filelink;
$tempfile = '/var/tmp/400004291.xls';
*/

			if ($companyid==870) {//Verizon SRM file
				$xls = PHPExcel($tempfile);
				foreach ($xls as $bid_num => $rows) {
					$lines = $rows;
				}
			} else if (strstr($filename,'.xlsx')) {
				$xlsx = new SimpleXLSX($tempfile);
				$lines = $xlsx->rows();
			} else {
				$xls = new Spreadsheet_Excel_Reader($tempfile,false);
				$sheets = $xls->sheets;
				foreach ($sheets as $sheet) {
					$lines = $sheet["cells"];
					break;//end after first worksheet found
				}
			}

			// delete the temp file
//			unlink($tempfile);
		} else if (strstr($filename,'csv')) {
			$handle = fopen($filelink,"r");

			while (($data = fgetcsv($handle)) !== false) {
//				print "<pre>".print_r($data,true)."</pre>";
				$lines[] = $data;
			}
		} else {//txt
			$file = file_get_contents($filelink);

			$lines = explode(chr(10),$file);
		}

//		print "<pre>".print_r($lines,true)."</pre>";

		// condense the table of results by stripping white space
		$condensed = condenseLines($lines);
//		print "<pre>".print_r($condensed,true)."</pre>";
		unset($lines);

		// curate results by summing qtys of matching parts and eliminating bogus rows
		$curated = curateResults($condensed,$filename);
//		print "<pre>".print_r($curated,true)."</pre>";
		unset($condensed);

		$consolidated = consolidatePartids($curated);
//		print "<pre>".print_r($consolidated,true)."</pre>";
		unset($curated);

		// now take the finished results and add to the db
		$ln = 0;//line number, even tho it's not necessarily accurate to original list, it's still a good way to keep track
		$report = '';
		foreach ($consolidated as $partid => $row) {
//			if ($test) { echo $part.'/'.$heci.': '.$partid.'<BR>'; }
			$status = 'Added';
			$qty = $row['qty'];
			$searchid = $row['searchid'];

			if (! $qty OR ! $partid) {
				$status = '';
				if (! $qty) { $status = 'Missing Qty'; }
				if (! $partid) {
					if ($status) { $status .= ', '; }
					$status .= 'Unidentified Item';
				}
			}
			$report .= '"'.$part.'","'.$heci.'","'.$qty.'","'.$status.'"'.chr(10);
			if (! $partid OR ! $qty) { continue; }

			insertMarket($partid,$qty,false,false,false,$metaid,$upload_type,$searchid,$ln);
			$ln++;
		}

		if ($report) {
			$report = '"Part","HECI","Qty","Status"'.chr(10).$report;

			// create temp file name in temp directory
			$attachment = sys_get_temp_dir()."/inv-report-".date("ymdHis").".csv";
			$handle = fopen($attachment, "w");
			// add contents from file
			fwrite($handle, $report);
			fclose($handle);

/*
			$email = getUser($r['userid'],'id','email');
			$mail_msg = 'Please see attached'.chr(10).chr(10).chr(10).chr(10);
			if (! $test) {
$email = 'davidglangley@gmail.com';
//				mailer($email,'Inventory Upload Report '.date("D n/j/y"),$mail_msg,'info@lunacera.com',$replyTo='no-reply@lunacera.com','',array('info@lunacera.com','LunaCera'),$attachment);
			}
*/

			$query2 = "UPDATE uploads SET processed = '".res($now)."' WHERE id = '".res($uploadid)."' LIMIT 1; ";
			if (! $test) { $result2 = qdb($query2) OR die(qe().' '.$query2); }
		}

		return true;
	}
?>
