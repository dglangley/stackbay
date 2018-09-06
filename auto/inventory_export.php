<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getManf.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSys.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/array_stristr.php';

	if (! isset($debug)) { $debug = 1; }
	// build BB, PS, TE and other inventories; see /var/www/venteldb/inventory/util.py and search for "BrokerBin", etc

	// BrokerBin
	$csvBB = '"part number","heci","manufacturer","condition","price","quantity","description"'.chr(10);

	// PowerSource doesn't like headers
	//$csvPS = '"Posting Id *","Part No","Manufacturer","HECI/CLEI","Condition","Quantity *","Price","Description","Category *","Category Id *"'.chr(10);
	$csvPS = '';

	// Tel-Explorer
	$csvTE = '"part number","clei","manufacturer","condition","quantity","price","description"'.chr(10);

	$results = array();//contains all parts data that will be consolidated between db's for export

	$query = "SELECT * FROM parts p, qtys q WHERE q.partid = p.id AND p.classification = 'equipment' ORDER BY part ASC, heci ASC; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$results[$r['partid']] = $r;
	}

	// get ghost inventory items
	$query = "SELECT partid, SUM(vqty) visible_qty, parts.* FROM staged_qtys, parts ";
	$query .= "WHERE staged_qtys.partid = parts.id AND parts.classification = 'equipment' ";
	$query .= "AND partid <> '292429' AND partid <> '29784' ";
	$query .= "GROUP BY partid ORDER BY part ASC, heci ASC; ";
	$result = qdb($query) OR die(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		// don't add parts that are already being exported from previous db above
		if (isset($results[$r['partid']])) { continue; }

		$results[$r['partid']] = $r;
	}

	$k = 0;
	// build data for population into lists
	foreach ($results as $partid => $r) {
		$qty = $r['visible_qty'];
		$hidden_qty = false;
		if (isset($r['hidden_qty']) AND ($r['hidden_qty']==='0' OR $r['hidden_qty']>0)) {
			$hidden_qty = $r['hidden_qty'];
		}
		// 0 = do not show
		if ($hidden_qty!==false) {
			if ($hidden_qty==='0') { continue; }
			$qty -= $hidden_qty;
			if ($qty<0) { continue; }
		}

		$part_numbers = explode(' ',$r['part']);
		$part = strtoupper(str_replace('"','',trim(str_replace('MERGEME','',$part_numbers[0]))));
		$fpart = preg_replace('/[^[:alnum:]]+/','',$part);

		$heci = $r['heci'];
		if (strlen($heci)==7) { $heci .= 'VTL'; }

		$manf = getManf($r['manfid']);
		$system = getSys($r['systemid']);
//		$descr = strtoupper(trim($system.' '.$r['description']));
		$descr = strtoupper(trim($system.' '.preg_replace('/, REPLACE.*/','',$r['description'])));

		$aliases = array();
		for ($i=1; $i<count($part_numbers); $i++) {
			$falias = preg_replace('/[^[:alnum:]]+/','',$part_numbers[$i]);
			if (strlen($falias)<2 OR $falias==$fpart OR isset($aliases[$falias])) { continue; }
			$aliases[$falias] = $part_numbers[$i];
		}

		// strip out commas to prevent our csv from breaking
		$manf = str_replace(",","",str_replace("\t","",str_replace('"','',$manf)));
		$descr = str_replace(",","",str_replace("\t","",str_replace('"','',$descr)));

		// while our qty may be above 0 to get us within this loop in the first place, $qty (visible qty) may be 0 or even
		// errantly negative, so don't include these lines...
		if ($qty<=0) { continue; }

		$url = '//ven-tel.com';
		$img = '<img src='.$url.'/img/parts/'.strtoupper($part).'.jpg width=34>';
		$exts = array('/products/'.strtolower(preg_replace('/[^[:alnum:]]+/','-',$manf)));
		if ($system) {
			$exts[] = '/'.strtolower(preg_replace('/[^[:alnum:]]+/','-',$system));
			$exts[] = '/'.strtolower($part);
			if ($heci) { $exts[] = '/'.strtolower($heci); }
		}
		foreach ($exts as $ext) {
			$a_prep = '<a href='.$url.$ext.'>'.$img.'</a>';
			// Condition max length: 130 chars
			if (strlen($a_prep)<=130) { $url .= $ext; } else { break; }
		}
		$cond = '<a href='.$url.'>'.$img.'</a>';

		$csvBB .= '"'.$part.'","'.$heci.'","'.$manf.'","CALL","CALL","'.$qty.'","'.$descr.'"'.chr(10);
		$csvPS .= '"'.($k++).'","'.substr($part,0,25).'","'.$manf.'","'.$heci.'","CALL","'.$qty.'","CALL","'.$descr.'","Central Office","1"'.chr(10);
		$csvTE .= '"'.$part.'","'.$heci.'","'.$manf.'","'.$cond.'","'.$qty.'","CALL","'.$descr.'"'.chr(10);

		// lastly get all aliases from keywords table, as we want all mutations of heci codes to be included
		$query3 = "SELECT keyword FROM keywords, parts_index ";
		$query3 .= "WHERE partid = '".$partid."' AND parts_index.keywordid = keywords.id ";
		$query3 .= "AND rank = 'primary' ";
		$query3 .= "ORDER BY length(keyword) DESC; ";
//		echo $query3.'<BR>';
		$result3 = qdb($query3) OR die(qe().' '.$query3);
		while ($r3 = mysqli_fetch_assoc($result3)) {
			$fkeyword = $r3['keyword'];
			if (strlen($fkeyword)<2 OR $fkeyword==$fpart OR isset($aliases[$fkeyword]) OR (strlen($fkeyword)==7 AND array_stristr($aliases,$fkeyword)!==false)) { continue; }

			$aliases[$fkeyword] = $r3['keyword'];
		}

		foreach ($aliases as $alias) {
			$img = '<img src='.$url.'/img/parts/'.strtoupper($alias).'.jpg width=34>';
			// replace part# with this alias
			if ($system AND isset($exts[2])) { $exts[2] = '/'.strtolower($alias); }
			foreach ($exts as $ext) {
				$a_prep = '<a href='.$url.$ext.'>'.$img.'</a>';
				// Condition max length: 130 chars
				if (strlen($a_prep)<=130) { $url .= $ext; } else { break; }
			}
			$cond = '<a href='.$url.'>'.$img.'</a>';

			$csvBB .= '"'.$alias.'","","'.$manf.'","CALL","CALL","'.$qty.'","'.$descr.'"'.chr(10);
			$csvPS .= '"'.($k++).'","'.substr($alias,0,25).'","'.$manf.'","","CALL","'.$qty.'","CALL","'.$descr.'","Central Office","1"'.chr(10);
			$csvTE .= '"'.$alias.'","","'.$manf.'","'.$cond.'","'.$qty.'","CALL","'.$descr.'"'.chr(10);
		}
	}

	if ($debug) {
		echo 'Exporting '.count($results).' part(s) totaling '.$k.' item(s)<BR>'.chr(10);
		echo str_replace(chr(10),'<BR>',$csvTE);
	}

	setGoogleAccessToken(5);

	// create temp file name in temp directory for each file
	$attachment = sys_get_temp_dir()."/inventory_export_bb-".date("ymdHis").".csv";
	$handle = fopen($attachment, "w");
	// add contents from file
	fwrite($handle, $csvBB);
	fclose($handle);

//sendMailMIME("upload@brokerbin.com", "BrokerBin Export", "Report Attached", '/tmp/csvBB.csv')
	$send_success = send_gmail('Report Attached','BrokerBin Export','upload@brokerbin.com','david@ven-tel.com','',$attachment);
	if ($debug) {
		if ($send_success) {
			echo json_encode(array('message'=>'BrokerBin Email Export Successful'));
		} else {
			echo json_encode(array('message'=>$SEND_ERR));
		}
	}

	// create temp file name in temp directory for each file
	$attachment = sys_get_temp_dir()."/inventory_export_ps-".date("ymdHis").".csv";
	$handle = fopen($attachment, "w");
	// add contents from file
	fwrite($handle, $csvPS);
	fclose($handle);

//sendMailMIME("inv@powersourceonline.com", "PowerSource Online Export", "Report Attached", '/tmp/psexport.csv')
	$send_success = send_gmail('Report Attached','PowerSource Online Export','inv@powersourceonline.com','david@ven-tel.com','',$attachment);
	if ($debug) {
		if ($send_success) {
			echo json_encode(array('message'=>'PowerSource Online Email Export Successful'));
		} else {
			echo json_encode(array('message'=>$SEND_ERR));
		}
	}

	// create temp file name in temp directory for each file
	$attachment = sys_get_temp_dir()."/inventory_export_te-".date("ymdHis").".csv";
	$handle = fopen($attachment, "w");
	// add contents from file
	fwrite($handle, $csvTE);
	fclose($handle);

	$ftpid = ftp_connect('tel-explorer.com');
	$ftp_login = ftp_login($ftpid,'Inventory', 'u5678d') OR die("Cannot login");
	//switch to passive mode
	ftp_pasv($ftpid, true) OR die("Cannot switch to passive");
	if (ftp_put($ftpid,'ventura.csv',$attachment,FTP_ASCII)) {
		if ($debug) { echo "successfully uploaded $attachment<BR>".chr(10); }
//		ftp.storlines('STOR ventura.csv', exportFile) # Disable this for debugging
	} else {
		if ($debug) { echo "problem uploading $attachment<BR>".chr(10); }
	}
	ftp_close($ftpid);

	$send_success = send_gmail('Report Attached','Tel-Explorer Export','david@ven-tel.com','','',$attachment);
	if ($debug) {
		if ($send_success) {
			echo json_encode(array('message'=>'Tel-Explorer Email Export Successful'));
		} else {
			echo json_encode(array('message'=>$SEND_ERR));
		}
	}
?>
