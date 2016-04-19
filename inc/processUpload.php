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

	function array_find($needle, $haystack) {
		$f = false;
		foreach ($haystack as $k => $item) {
			//strpos() is haystack, needle
			if (strpos($item, $needle) !== FALSE) {
				// cannot have dups of same column
				if ($f!==false) { return false; }
				$f = $k;//identify the key but keep searching for dups
			}
		}
		return ($f);
	}
	function find_fields($arr) {
		if ($GLOBALS['test']) { print "<pre>".print_r($arr,true)."</pre>"; }

		$qty = false;
		$heci = false;
		$part = false;
		foreach ($arr as $k => $col) {
			$col = trim($col);
			if (! $col) { continue; }
			// easiest field to identify is qty
			if (is_numeric($col) AND $col<10000) {
				if ($qty!==false) { $qty = true; }//if qty has already been found, discredit it for finding a 2nd match
				else { $qty = $k; }
				continue;
			}
			if ($heci===false AND preg_match('/^[[:alnum:]]{7,10}$/',$col)) {
				$query = "SELECT * FROM parts WHERE heci LIKE '".res($col)."%'; ";
				$result = qdb($query);
				if (mysqli_num_rows($result)>0) {
					$heci = $k;
					continue;
				}
			}
			if ($part===false AND strlen($col)>1) {
				$fields = explode(' ',$col);
				if (strlen($fields[0])>1) { $part = $k; }
			}
		}

		if ($qty===true) {
			// should I try to search the entire list array to get a better grasp of qty column?
			$qty = false;
		}

		return (array($part,$heci,$qty));
	}
	function condenseLines($lines) {
		$condensed = array();
		// use this loop to eliminate empty rows
		foreach ($lines as $n => $L) {
			// verify that there's data within the array
			$verified = false;
			if (! is_array($L)) {
				$verified = true;
			} else {
				foreach ($L as $d) {
					if (trim($d)) { $verified = true; }
				}
			}
			if ($verified) { $condensed[] = $L; }
		}

		return ($condensed);
	}
	function curateResults($condensed,$filename) {
		$num_lines = count($condensed);

		// columns detected below for each field
		$part_col = false;
		$heci_col = false;
		$qty_col = false;

		// identify the columns and curate the results by removing punctuations
		$curated = array();
		foreach ($condensed as $n => $L) {
			// make array out of fields if not already
			if (! is_array($L)) {
				$data = explode(' ',$L);// ?????
				$num_fields = count($data);
				//reassign $L as the array now
				$L = $data;
			}

//			print "<pre>".print_r($L,true)."</pre>";

			// is this a header row? try to find out
			if ($n==0) {
				$line_lower = array_map('strtolower',$L);
				$part_col = array_find('part',$line_lower);
				if ($part_col===false) { $part_col = array_find('model',$line_lower); }
				if ($part_col===false) { $part_col = array_find('mpn',$line_lower); }
				if ($part_col===false) { $part_col = array_find('item',$line_lower); }
				$heci_col = array_find('heci',$line_lower);
				if ($heci_col===false) { $heci_col = array_find('clei',$line_lower); }
				$qty_col = array_find('qty',$line_lower);
				if ($qty_col===false) { $qty_col = array_find('quantity',$line_lower); }
				if ($qty_col===false) { $qty_col = array_find('qnty',$line_lower); }

				// at least one of these two fields must be present, otherwise it's prob not a header row.
				if ($part_col!==false AND $qty_col!==false) {
					continue;
				}

				// we have to auto-determine the rows, so use samplings to find types of columns
				$r1 = round($num_lines/3);
				$r2 = $r1*2;
				$s1 = $lines[rand(1,$r1)];//sample array one
				$s2 = $lines[rand(($r1+1),$r2)];//sample array two
				$s3 = $lines[rand(($r2+1),($num_lines-1))];//sample array three

				$ff1 = find_fields($s1);
				$ff2 = find_fields($s2);
				$ff3 = find_fields($s3);
				if ($ff1[0]!==false AND $ff1[2]!==false) {
					if (($ff1[0]==$ff2[0] AND $ff1[2]==$ff2[2]) OR ($ff1[0]==$ff3[0] AND $ff1[2]==$ff3[2])) {
						$part_col = $ff1[0];
						$qty_col = $ff1[2];
						if ($ff1[1]!==false) { $heci_col = $ff1[1]; }
						else if ($ff2[1]!==false) { $heci_col = $ff2[1]; }
						else if ($ff3[0]==$ff1[0] AND $ff3[2]==$ff1[2] AND $ff3[1]!==false) { $heci_col = $ff3[1]; }
					}
				} else if ($ff2[0]!==false AND $ff2[2]!==false AND $ff2[0]==$ff3[0] AND $ff2[2]==$ff3[2]) {
					$part_col = $ff2[0];
					$qty_col = $ff2[2];
					if ($ff2[1]!==false) { $heci_col = $ff2[1]; }
					else if ($ff3[1]!==false) { $heci_col = $ff3[1]; }
				}
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

//			$part = strtoupper(preg_replace('/[^[:alnum:]]*/','',trim($L[$part_col])));
			$part = '';
			if ($part_col!==false) {
				$part = strtoupper(trim($L[$part_col]));
			}

//			if (preg_match('/(REV|REL|ISS|SER)/',$part)) {
//				$part = preg_replace('/(REV|REL|ISS|SER).+$/','',$part);
//			}

			// replacing chr(0) is removing null characters, specifically for verizon bid list
			$qty = preg_replace('/^([0-9]+)(x)$/i','$1',str_replace(chr(0),'',trim($L[$qty_col])));
			if (! $qty OR ! is_numeric($qty) OR $qty<0) { $qty = 0; }
			$heci = '';
			if ($heci_col!==false) {
				$heci = strtoupper(trim($L[$heci_col]));
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
