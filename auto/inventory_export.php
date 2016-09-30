<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getManf.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSys.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/array_stristr.php';

	// build BB, PS, TE and other inventories; see /var/www/venteldb/inventory/util.py and search for "BrokerBin", etc

	// BrokerBin
	$csvBB = '"part number","heci","manufacturer","condition","price","quantity","description"'.chr(10);

	// PowerSource doesn't like headers
	//$csvPS = '"Posting Id *","Part No","Manufacturer","HECI/CLEI","Condition","Quantity *","Price","Description","Category *","Category Id *"'.chr(10);
	$csvPS = '';

	// Tel-Explorer
	$csvTE = '"part number","clei","manufacturer","condition","quantity","price","description"'.chr(10);

	$results = array();//contains all parts data that will be consolidated between db's for export

	$query = "SELECT category_id_id, part_number part, clean_part_number, heci, clei, short_description description, ";
	$query .= "inventory_manufacturer.name manf, system_name system, inventory_id, ";
	$query .= "COUNT(inventory_itemlocation.id) AS qty, previous_export_qty visible_qty ";
	$query .= "FROM inventory_itemlocation, inventory_location, inventory_inventory, inventory_manufacturer ";
	$query .= "WHERE no_sales = '0' AND inventory_inventory.id = inventory_itemlocation.inventory_id ";
	$query .= "AND inventory_itemlocation.location_id = inventory_location.id AND category_id_id = '2' ";
	$query .= "AND inventory_inventory.manufacturer_id_id = inventory_manufacturer.id ";
//	$query .= "AND part_number LIKE '437420600%' ";
	$query .= "GROUP BY inventory_id ORDER BY part_number ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$part_numbers = explode(' ',$r['part']);
		$part = strtoupper(str_replace('"','',trim(str_replace('MERGEME','',$part_numbers[0]))));
		$fpart = preg_replace('/[^[:alnum:]]+/','',$part);

		$heci = $r['clei'];
		$heci7 = $r['heci'];
		if (is_numeric($heci) OR preg_match('/[^[:alnum:]]+/',$heci)) {
			$heci = '';
		} else if (strlen($heci7)==7 AND ! $heci) {
			$heci = $heci7;
		}
		if (strlen($heci)==7) { $heci = $heci7.'VTL'; }
		$heci = str_replace('"','',$heci);

		$manf = strtoupper(trim($r['manf']));
		$manfid = getManf($manf);
		if (! $manfid) {
			$query2 = "SELECT id FROM manfs WHERE name LIKE '".res($manf)."%'; ";
			$result2 = qdb($query2) OR die(qe().' '.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$manfid = $r2['id'];
			}
		}

		$partid = getPartId($part,$heci,$manfid);

		// don't add parts that are already exported earlier in loop
		if (isset($results[$partid])) { continue; }

		// build manf, sys and descr in order to insert part into db from old db, if applicable
		$descr = strtoupper(trim($r['description']));
		$sysid = 0;
		$system = strtoupper(trim($r['system']));
		if ($system) {
			$sysid = getSys($system);

			$query2 = "SELECT id FROM systems WHERE system LIKE '".res($system)."%'; ";
			$result2 = qdb($query2) OR die(qe().' '.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$sysid = $r2['id'];
			} else {
				$descr = trim($system.' '.$descr);
			}
		}
		$descr = trim(str_replace('BOT GENERATED','',str_replace('*',' ',$descr)));
		if (! $partid) {
			$partid = setPart(array('part'=>$part,'heci'=>$heci,'manfid'=>$manfid,'sysid'=>$sysid,'descr'=>$descr));
		}

		// add aliases without heci code
		$aliases = array();
		for ($i=1; $i<count($part_numbers); $i++) {
			// index it by part number to consolidate, and save on lookups later
			$part_numbers[$i] = trim(str_replace('MERGEME','',$part_numbers[$i]));
			$fstr = preg_replace('/[^[:alnum:]]+/','',$part_numbers[$i]);

			// must be at least 2 chars, must not be the same as the part#, and must not be a series ("S-08")
			if (strlen($fstr)<2 OR $fstr==$part OR preg_match('/^S-[0-9]{1,2}$/',$part_numbers[$i]) OR isset($aliases[$fstr])) { continue; }

			$aliases[$fstr] = $part_numbers[$i];
		}

		// get aliases from old inventory aliases db table
		$query2 = "SELECT part_number FROM inventory_inventoryalias ";
		$query2 .= "WHERE inventory_id = '".$r['inventory_id']."' AND hide_from_export = '0' ";
		$query2 .= "AND clean_part_number <> '".$r['clean_part_number']."'; ";
//		echo $query2.'<BR>';
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$splits = explode(' ',$r2['part_number']);
			foreach ($splits as $s) {
				// lots of part/alias strings have "MERGEME" (annoying!) which we don't want
				$s = trim(str_replace('MERGEME','',$s));

				$falias = trim(preg_replace('/[^[:alnum:]]+/','',$s));
				$alias = trim($s);

				// must be at least 2 chars, must not be the same as the part#, and must not be a series ("S-08")
				if (strlen($falias)<2 OR $falias==$fpart OR preg_match('/^S-[0-9]{1,2}$/',$alias) OR isset($aliases[$falias])) { continue; }

				$aliases[$falias] = strtoupper($alias);
			}
		}

		$results[$partid] = array('partid'=>$partid,'visible_qty'=>$r['visible_qty'],'part'=>$part,'heci'=>$heci,'manf'=>$manf,'descr'=>$descr,'aliases'=>$aliases,'system'=>$system);
	}

	// get ghost inventory items
	$query = "SELECT partid, SUM(vqty) visible_qty, parts.* FROM staged_qtys, parts ";
	$query .= "WHERE staged_qtys.partid = parts.id ";
	$query .= "AND partid <> '292429' AND partid <> '29784' ";
	$query .= "GROUP BY partid ORDER BY part ASC; ";
	$result = qdb($query) OR die(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		// don't add parts that are already being exported from previous db above
		if (isset($results[$r['partid']])) { continue; }

		$part_numbers = explode(' ',$r['part']);
		$part = $part_numbers[0];
		$fpart = preg_replace('/[^[:alnum:]]+/','',$part);
		$heci = $r['heci'];
		$manf = getManf($r['manfid']);
		$system = getSys($r['systemid']);
		$descr = trim($system.' '.$r['description']);
		$aliases = array();
		for ($i=1; $i<count($part_numbers); $i++) {
			$falias = preg_replace('/[^[:alnum:]]+/','',$part_numbers[$i]);
			if (strlen($falias)<2 OR $falias==$fpart OR isset($aliases[$falias])) { continue; }
			$aliases[$falias] = $part_numbers[$i];
		}

		$results[$r['partid']] = array('partid'=>$r['partid'],'visible_qty'=>$r['visible_qty'],'part'=>$part,'heci'=>$heci,'manf'=>$manf,'descr'=>$descr,'aliases'=>$aliases,'system'=>$system);
	}

	$k = 0;
	foreach ($results as $partid => $r) {
		$qty = $r['visible_qty'];
		$part = $r['part'];
		$fpart = preg_replace('/[^[:alnum:]]+/','',$part);
		$heci = $r['heci'];
		$manf = $r['manf'];
		$descr = $r['descr'];
		$aliases = $r['aliases'];

		$manf = str_replace(",","",str_replace("\t","",str_replace('"','',$manf)));
		$descr = str_replace(",","",str_replace("\t","",str_replace('"','',$descr)));

		// while our qty may be above 0 to get us within this loop in the first place, $qty (visible qty) may be 0 or even
		// errantly negative, so don't include these lines...
		if ($qty<=0) { continue; }

		$url = '//ven-tel.com';
		$img = '<img src=//ven-tel.com/img/parts/'.strtoupper($part).' width=34>';
		$exts = array('/products/'.strtolower(preg_replace('/[^[:alnum:]]+/','-',$manf)));
		if ($r['system']) {
			$exts[] = '/'.strtolower(preg_replace('/[^[:alnum:]]+/','-',$r['system']));
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
			$url = '//ven-tel.com';
			$img = '<img src=//ven-tel.com/img/parts/'.strtoupper($alias).' width=34>';
			// replace part# with this alias
			$exts[2] = '/'.strtolower($alias);
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

	echo 'Exporting '.count($results).' part(s) totaling '.$k.' item(s)<BR>'.chr(10);
//	echo str_replace(chr(10),'<BR>',$csvTE);

	setGoogleAccessToken(5);

	// create temp file name in temp directory for each file
	$attachment = sys_get_temp_dir()."/inventory_export_bb-".date("ymdHis").".csv";
	$handle = fopen($attachment, "w");
	// add contents from file
	fwrite($handle, $csvBB);
	fclose($handle);

//sendMailMIME("upload@brokerbin.com", "BrokerBin Export", "Report Attached", '/tmp/csvBB.csv')
	$send_success = send_gmail('Report Attached','BrokerBin Export','upload@brokerbin.com','david@ven-tel.com','',$attachment);
	if ($send_success) {
		echo json_encode(array('message'=>'BrokerBin Email Export Successful'));
	} else {
		echo json_encode(array('message'=>$SEND_ERR));
	}

	// create temp file name in temp directory for each file
	$attachment = sys_get_temp_dir()."/inventory_export_ps-".date("ymdHis").".csv";
	$handle = fopen($attachment, "w");
	// add contents from file
	fwrite($handle, $csvPS);
	fclose($handle);

//sendMailMIME("inv@powersourceonline.com", "PowerSource Online Export", "Report Attached", '/tmp/psexport.csv')
	$send_success = send_gmail('Report Attached','PowerSource Online Export','inv@powersourceonline.com','david@ven-tel.com','',$attachment);
	if ($send_success) {
		echo json_encode(array('message'=>'PowerSource Online Email Export Successful'));
	} else {
		echo json_encode(array('message'=>$SEND_ERR));
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
		echo "successfully uploaded $attachment<BR>".chr(10);
//		ftp.storlines('STOR ventura.csv', exportFile) # Disable this for debugging
	} else {
		echo "problem uploading $attachment<BR>".chr(10);
	}
	ftp_close($ftpid);

	$send_success = send_gmail('Report Attached','Tel-Explorer Export','david@ven-tel.com','','',$attachment);
	if ($send_success) {
		echo json_encode(array('message'=>'Tel-Explorer Email Export Successful'));
	} else {
		echo json_encode(array('message'=>$SEND_ERR));
	}
?>
