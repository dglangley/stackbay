<?php
	set_time_limit(0);
	ini_set('memory_limit', '2000M');
	ini_set('mbstring.func_overload', '2');
	ini_set('mbstring.internal_encoding', 'UTF-8');

    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFavorites.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/insertMarket.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/simplexlsx.class.php';//awesome class used for xlsx-only
    include_once $_SERVER["ROOT_DIR"].'/inc/php-excel-reader/excel_reader2.php';//specifically for parsing xls files
//	include_once $_SERVER["ROOT_DIR"].'/inc/mailer.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/PHPExcel.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/array_find.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/find_fields.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setColumns.php';
//	require($_SERVER["ROOT_DIR"].'/vendor/autoload.php');

	$test = 0;
//	if (! isset($userid)) { $userid = $U['id']; }
	$userid = $U['id'];

	setGoogleAccessToken(5);

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
	function curateResults($condensed,$filename,$uploadid=0) {
		global $now,$test,$userid;

		// columns detected below for each field
		$part_col = false;
		$heci_col = false;
		$qty_col = false;
		$price_col = false;

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
				list($part_col,$qty_col,$heci_col,$price_col,$header_row) = setColumns($row_arr,$condensed);
				// if this first row is found to be a header row (based on columns content), we don't keep processing the row
				if ($header_row===true) { continue; }
			}
			if ($n<=1 AND $test) { echo "partcol:$part_col, hecicol:$heci_col, qtycol:$qty_col<BR>"; }

			if (($part_col===false AND $heci_col===false) OR $qty_col===false) {
				$mail_msg = 'I could not process your file upload named "'.$filename.'" because '.
					'I was unable to determine the Part#/HECI and/or Qty field(s). Please note the following conditions:<BR><BR>'.
					'* Lists need EITHER a Part# or HECI column<BR>'.
					'* Qualifying Part# column names are: "part", "model", "mpn", and "item" (case insensitive)<BR>'.
					'* You cannot have more than one column with the same type (i.e., only one Part# field, not two)<BR>'.
					'* Qualifying HECI column names are: "heci" and "clei" (case insensitive)<BR>'.
					'* Qualifying Qty column names are: "qty", "quantity", and "qnty" (case insensitive)<BR>'.
					'Please edit the file to add these column names, and then re-upload the file. Thanks!';
				if (! $test) {
					// only add bcc to david if we're not already sending to david as the user/recipient (gmail won't allow it for some reason)
					$bcc = '';
					if ($userid<>1) { $bcc = 'david@ven-tel.com'; }

					$send_success = send_gmail($mail_msg,'Inventory upload rejected! '.date("D n/j/y"),getContact($userid,'userid','email'),'david@ven-tel.com');
					if ($send_success) {
						echo json_encode(array('message'=>'Success'));
					} else {
						echo json_encode(array('message'=>$SEND_ERR));
					}

					$query2 = "UPDATE uploads SET processed = '".res($now)."' WHERE id = '".res($uploadid)."' LIMIT 1; ";
					if (! $test) { $result2 = qdb($query2) OR die(qe().' '.$query2); }
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

			// replacing chr(0) is removing null characters, specifically for verizon bid list;
			// see http://php.net/manual/en/function.trim.php#98812 for trim() technique on non-breaking spaces
			$qty = preg_replace('/^([[:space:]]*)?([0-9]+)(x|ea)?([[:space:]]*)?$/i','$2',str_replace(chr(0),'',trim($row_arr[$qty_col],chr(0xC2).chr(0xA0))));
			if (! $qty OR ! is_numeric($qty) OR $qty<0) { $qty = 0; }
			$heci = '';
			if ($heci_col!==false) {
				$heci = strtoupper(trim($row_arr[$heci_col]));
				// there's no such thing as a legitimate all-numeric heci, but verizon sometimes uses their internal codes here
				if (is_numeric($heci)) { $heci = ''; }
			}
			if (! $part AND ! $heci) { continue; }

			$price = false;
			if ($price_col!==false) {
				$price = strtoupper(trim($row_arr[$price_col]));
				// we don't want to store a float in the db if there's no price
				if (! $price) { $price = false; }
			}

			if ($test) { echo 'part: '.$part.' '.$heci.', qty '.$qty.'<BR>'; }
			$partKey = '';
			if ($part) { $partKey = preg_replace('/[^[:alnum:]]+/','',$part); }
			$partKey .= '.';
			if ($heci) { $partKey .= $heci; }
//			if ($test) { echo $partKey.' '.$qty.'<BR>'; }

			if (! isset($curated[$partKey])) { $curated[$partKey] = array('qty'=>0,'price'=>0); }
			$curated[$partKey]['qty'] += $qty;
			if ($price!==false AND $price>0) { $curated[$partKey]['price'] = $price; }
//			if ($n>10000) { break; }
		}

		return ($curated);
	}
	function consolidatePartids($curated) {
		global $userid,$now,$today,$remotes_log;

		// further consolidate results by tapping the db for partid's, and keying on that id to get summed qtys
		$consolidated = array();
		foreach ($curated as $partKey => $r) {
			$qty = $r['qty'];
			$price = $r['price'];

			$keys = explode('.',$partKey);
			$part = '';
			$heci = '';
			if (isset($keys[0])) { $part = $keys[0]; }
			if (isset($keys[1])) { $heci = $keys[1]; }

			// concatenate the part string and heci string as our combined search string that produced this result
			$searchkey = '';
			if ($heci) { $searchkey = $heci; } else { $searchkey = $part; }
			$searchkey = preg_replace('/[^[:alnum:]]+/','',$searchkey);

			// get all related partids for favorites but use only the first result for capturing consolidated results
			$partids = getPartId($part,$heci,0,true);
			$partid = 0;//$searchkey;
			if (isset($partids[0])) { $partid = $partids[0]; }
//			if (! $partid) { continue; }

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

			if (! isset($consolidated[$searchkey])) {
				$consolidated[$searchkey] = array('qty'=>0,'searchid'=>$searchid,'part'=>$part,'heci'=>$heci,'partids'=>$partids,'partid'=>$partid,'price'=>$price);
			}
			$consolidated[$searchkey]['qty'] += $qty;
			// update price every time it's found, we really don't know how to distinguish a righter price over another
			if ($price!==false AND $price>0) { $consolidated[$searchkey]['price'] = $price; }
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
		$userid = $r['userid'];

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
		$curated = curateResults($condensed,$filename,$uploadid);
//		print "<pre>".print_r($curated,true)."</pre>";
		unset($condensed);

		$consolidated = consolidatePartids($curated);
//		print "<pre>".print_r($consolidated,true)."</pre>";
		unset($curated);

		// now take the finished results and add to the db
		$ln = 0;//line number, even tho it's not necessarily accurate to original list, it's still a good way to keep track
		$csv_report = '';
		$report = '';
		$num_favs = 0;
		$favs_report = '';
		foreach ($consolidated as $searchkey => $row) {
			$partid = $row['partid'];
//			if ($test) { echo $part.'/'.$heci.': '.$partid.'<BR>'; }
			$status = 'Added';
			$qty = $row['qty'];
			$price = $row['price'];
			if ($price!==false) { $price = format_price($price,false,'',true); }
			$searchid = $row['searchid'];

			// on invalid qty or invalid partid (missing or non-numeric), notate it as an unidentified item
			if (! $qty OR ! $partid OR ! is_numeric($partid)) {
				$status = '';
				if (! $qty) { $status = 'Missing Qty'; }
				if (! $partid) {
					if ($status) { $status .= ', '; }
					$status .= 'Unidentified Item';
				}
			}

			// assess favorites position by gathering all related partids
			$favs = getFavorites($row['partids']);

			$csv_report .= '"'.$row['part'].'","'.$row['heci'].'","'.$row['qty'].'","'.$status.'"'.chr(10);

			$report .= $row['qty'].'- ';
			if ($row['heci']) { $report .= $row['heci'].' '; }
			$report .= $row['part'].'<BR>'.chr(10);

			if (count($favs)>0) {
				$favs_report .= 'qty '.$qty.'- '.$row['part'].' '.$row['heci'].'<BR>';// ("'.$status.'")<BR>';
				$num_favs++;// += count($favs);
			}
			if (! $partid OR ! is_numeric($partid) OR ! $qty) { continue; }

			insertMarket($partid,$qty,$price,false,false,$metaid,$upload_type,$searchid,$ln);
			$ln++;
		}

		if ($csv_report) {
			$csv_report = '"Part","HECI","Qty","Status"'.chr(10).$csv_report;

			// create temp file name in temp directory
			$attachment = sys_get_temp_dir()."/inv-report-".date("ymdHis").".csv";
			$handle = fopen($attachment, "w");
			// add contents from file
			fwrite($handle, $csv_report);
			fclose($handle);

			// only add bcc to david if we're not already sending to david as the user/recipient (gmail won't allow it for some reason)
			$bcc = '';
			if ($userid<>1) { $bcc = 'david@ven-tel.com'; }

			// if fewer than 30 lines, send plain text email; otherwise, send csv attached report
			if ($ln<=30) {
				$mail_msg = 'I successfully imported your file upload, please see the result(s) below...<BR><BR>'.$report;

				$mail_sbj = 'File Upload Report '.date("D n/j/y");
				// if verizon telecom, always send $bcc to sales@, which includes david@ from above if $userid<>1
				if ($companyid==870) {
					$bcc = 'sales@ven-tel.com';
					$mail_sbj = getCompany($companyid).' Upload Report '.date("D n/j/y");
				}

				$send_success = send_gmail($mail_msg,$mail_sbj,getContact($userid,'userid','email'),$bcc);
			} else {
				$mail_msg = 'I successfully imported your file upload, please see the attached report for details<BR><BR>';
				$send_success = send_gmail($mail_msg,'File Upload Report '.date("D n/j/y"),getContact($userid,'userid','email'),$bcc,'',$attachment);
			}
			if ($send_success) {
				echo json_encode(array('message'=>'Success'));
			} else {
				echo json_encode(array('message'=>$SEND_ERR));
			}

			if ($favs_report) {
				$mail_msg = 'Your file upload ("'.$filename.'") appears to match '.$num_favs.' of our favorites:<BR><BR>'.$favs_report;

				$send_success = send_gmail($mail_msg,'Favorites found in file upload! '.date("D n/j/y"),getContact($userid,'userid','email'),$bcc);
				if ($send_success) {
					echo json_encode(array('message'=>'Success'));
				} else {
					echo json_encode(array('message'=>$SEND_ERR));
				}
			}

			$query2 = "UPDATE uploads SET processed = '".res($now)."' WHERE id = '".res($uploadid)."' LIMIT 1; ";
			if (! $test) { $result2 = qdb($query2) OR die(qe().' '.$query2); }
		}

		return true;
	}
?>
