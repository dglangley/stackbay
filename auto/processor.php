<?php
	set_time_limit(0);
	ini_set('memory_limit', '2000M');

    include_once '../inc/dbconnect.php';
    include_once '../inc/getPartId.php';
    include_once '../inc/insertMarket.php';
    include_once '../inc/getUser.php';
    include_once '../inc/format_date.php';
    include_once '../inc/simplexlsx.class.php';//awesome class used for xlsx-only
    include_once '../inc/php-excel-reader/excel_reader2.php';//specifically for parsing xls files
    include_once '../inc/mailer.php';
    require('../vendor/autoload.php');

	function array_find($needle, $haystack) {
		$f = false;
		foreach ($haystack as $k => $item) {
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

	$test = 0;

	$my_inv = array();
	echo 'Processing...<BR>'.chr(10);

	$query = "SELECT *, id uploadid FROM uploads ";
//	if ($test) { $query .= "WHERE filename = 'nave_131114.csv' "; }
	$query .= "WHERE processed IS NULL ";
	$query .= "ORDER BY datetime ASC; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$filename = $r['filename'];

		$lines = array();
		if (strstr($filename,'.xls')) {
			$file = file_get_contents($r['link']);

			// create temp file name in temp directory
			$tempfile = tempnam(false,'inv'.date("ymdHis"));
//			echo $tempfile.'<BR>';
			// add contents from file
			file_put_contents($tempfile,$file);

			if (strstr($filename,'.xlsx')) {
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
			unlink($tempfile);
		} else if (strstr($filename,'csv')) {
			$handle = fopen($r['link'],"r");

			while (($data = fgetcsv($handle)) !== false) {
//				print "<pre>".print_r($data,true)."</pre>";
				$lines[] = $data;
			}
		} else {//txt
			$file = file_get_contents($r['link']);

			$lines = explode(chr(10),$file);
		}

		// reset every file
		$part_col = false;
		$heci_col = false;
		$qty_col = false;

		$dt = $r['datetime'];

//		$companyid = getUser($r['userid'],'id','companyid');
		$companyid = $r['companyid'];
		if ($test) { echo $filename.', cid '.$companyid.', (date '.$dt.')<BR>'.chr(10); }

		// condense the table of results by stripping white space
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

		$num_lines = count($condensed);
//		print "<pre>".print_r($condensed,true)."</pre>";

		// identify the columns and curate the results by removing punctuations
		$curated = array();
		$report = '';
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
				$heci_col = array_find('heci',$line_lower);
				if ($heci_col===false) { $heci_col = array_find('clei',$line_lower); }
				$qty_col = array_find('qty',$line_lower);
				if ($qty_col===false) { $qty_col = array_find('quantity',$line_lower); }

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

			if ($part_col===false OR $qty_col===false) {
				$email = getUser($r['userid'],'id','email');
				$mail_msg = 'Your inventory file named "'.$filename.'" was rejected because it was '.chr(10).
					'unable to determine the Part# and/or Qty field(s).'.chr(10).chr(10).
					'Oftentimes, this can be due to a missing header row, or if your file has only one column of data.'.chr(10);
				$headers = 'From: "LunaCera support" <no-reply@lunacera.com>' . "\r\n" .
					'Bcc: "LunaCera" <info@lunacera.com>'. "\r\n" .
					'X-Mailer: PHP/' . phpversion();
					//'Reply-To: "'.$U['first_name'].' '.$U['last_name'].'" <'.$U['email'].'>'. "\r\n" .
				if (! $test) {
$email = 'davidglangley@gmail.com';
					mail($email,'Inventory file rejected! '.date('D n/j/y'),$mail_msg,$headers);
				}
				break;
			}

//			$part = strtoupper(preg_replace('/[^[:alnum:]]*/','',trim($L[$part_col])));
			$part = strtoupper(trim($L[$part_col]));

//			if (preg_match('/(REV|REL|ISS|SER)/',$part)) {
//				$part = preg_replace('/(REV|REL|ISS|SER).+$/','',$part);
//			}

			$qty = preg_replace('/^([0-9]+)(x)$/i','$1',trim($L[$qty_col]));
			if (! $qty OR ! is_numeric($qty) OR $qty<0) { $qty = 0; }
			$heci = strtoupper(trim($L[$heci_col]));
			if (! $part AND ! $heci) { continue; }

//			if ($test) { echo 'part: '.$part.' '.$heci.', qty '.$qty.'<BR>'; }
			$partKey = preg_replace('/[^[:alnum:]]+/','',$part);
			if ($heci) { $partKey .= '.'.$heci; }
//			if ($test) { echo $partKey.' '.$qty.'<BR>'; }

			if (! isset($curated[$partKey])) { $curated[$partKey] = 0; }
			$curated[$partKey] += $qty;
//			if ($n>10000) { break; }
		}
		unset($condensed);

		// further consolidate results by tapping the db for partid's, and keying on that id to get summed qtys
//		echo count($curated).' curated<br>';
		$consolidated = array();
		foreach ($curated as $partKey => $qty) {
			$keys = explode('.',$partKey);
			$part = '';
			$heci = '';
			if (isset($keys[0])) { $part = $keys[0]; }
			if (isset($keys[1])) { $heci = $keys[1]; }

			$partid = getPartId($part,$heci);

			if (! isset($consolidated[$partid])) { $consolidated[$partid] = 0; }
			$consolidated[$partid] += $qty;
		}
		unset($curated);
//		echo count($consolidated).' consolidated<br>';

		foreach ($consolidated as $partid => $qty) {
//			if ($test) { echo $part.'/'.$heci.': '.$partid.'<BR>'; }
			$status = 'Added';
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

			insertMarket($partid,$qty,$companyid,$dt,$r['uploadid']);
		}

		if ($report) {
			$report = '"Part","HECI","Qty","Status"'.chr(10).$report;

			// once complete with items added, and if uploaded to replace all previous inventory,
			// set all current inventory as 'expired'
/*
			if ($r['replace_inventory']=='T') {
				$query2 = "UPDATE market SET expired = 'T' WHERE companyid = '".res($companyid)."' AND source <> '".$r['uploadid']."'; ";
				$result2 = qdb($query2);
			}
*/

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

			$query2 = "UPDATE uploads SET processed = '".res($now)."' WHERE id = '".res($r['uploadid'])."' LIMIT 1; ";
			if (! $test) { $result2 = qdb($query2); }
		}
	}
?>
