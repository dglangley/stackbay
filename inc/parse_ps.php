<?php
	include_once 'dbconnect.php';
	include_once 'getPartId.php';
	include_once 'setPart.php';
	include_once 'insertMarket.php';
	include_once 'getCompany.php';
	include_once 'logRemotes.php';
	include_once 'logSearchMeta.php';

	function parse_ps($res,$return_type='db') {
		if (! $res) { return false; }

		$inserts = array();//gather all records to be inserted into db
		$resArray = array();

		// eliminate html formatting warnings per http://stackoverflow.com/a/10482622/1356496, set error level
		$internalErrors = libxml_use_internal_errors(true);

		$newDom = new domDocument;
		$newDom->loadHTML($res);
		$newDom->preserveWhiteSpace = false;

		// Restore error level, see $internalErrors explanation above
		libxml_use_internal_errors($internalErrors);

		$formContents = $newDom->getElementById('SubmitRfq');
		$rows = (object)array();
		try {
			if (! $formContents OR ! $formContents->getElementsByTagName('tr')) { return false; }

			$rows = $formContents->getElementsByTagName('tr');
		} catch(Exception $e) {
//			echo $e;
			return false;
		}
		for ($a=0; $a<$rows->length; $a++) {
//			echo $rows->item($a)->getAttribute('class').':<BR>';
//			echo htmlentities($rows->item($a)->textContent).'<BR><BR>';
			//if ($rows->item($a)->getAttribute('class')<>'VSCRow resultRow') { continue; }
			if (! strstr($rows->item($a)->getAttribute('class'),'resultRow')) { continue; }

			$cols = $rows->item($a)->getElementsByTagName('td');
//			print "<pre>".print_r($cols,true)."</pre>";
			$part_col = trim(str_replace('&nbsp;',' ',htmlentities($cols->item(1)->getElementsByTagName('span')->item(0)->nodeValue)));
			$clei_pos = false;
			$pos_flag = 5;//number of digits of the 'CLEI:' flag string
			if (strstr($part_col,'CLEI:')) {
				$clei_pos = strpos($part_col,'CLEI:');
			} else if (strstr($part_col,'Model:')) {
				$clei_pos = strpos($part_col,'Model:');
				$pos_flag = 6;//number of digits of the 'Model:' flag string
			}
			$part = '';
			$heci = '';
			// if clei is found in part field, which can happen from various vendors (CSG, I'm lookin at you!)
			if ($clei_pos!==false) {
				if ($clei_pos>0) {
					$part = trim(substr($part_col,0,$clei_pos));
				}
				// clei is at the position of the flag ('clei:' or 'model:') plus the number of digits of the flag
				$heci = trim(substr($part_col,$clei_pos+$pos_flag,strlen($part_col)));
				if (is_numeric($heci) OR (strlen($heci)<>7 AND strlen($heci)<>10)) { $heci = ''; }
			} else {
				$part = trim($part_col);
			}
			$descr = '';
			$descr = trim(str_replace('&nbsp;',' ',htmlentities($cols->item(2)->nodeValue)));
			$manf = trim(str_replace('&nbsp;',' ',htmlentities($cols->item(3)->nodeValue)));
			$qty = trim(str_replace('&nbsp;',' ',htmlentities($cols->item(4)->nodeValue)));
			if (! $qty) { $qty = 0; }
			$price = trim(str_replace('&nbsp;',' ',htmlentities($cols->item(6)->nodeValue)));
			if (strtoupper($price)=='CALL') { $price = ''; }
			$company = trim(str_replace('&amp;','&',str_replace('&nbsp;',' ',htmlentities($cols->item(7)->getElementsByTagName('a')->item(0)->nodeValue))));
			$companyid = getCompany($company,'name','id');
			if (! $companyid) { $companyid = addCompany($company); }

			$resArray[] = array('manf'=>$manf,'part'=>$part,'descr'=>$descr,'qty'=>$qty,'heci'=>$heci,'company'=>$company);

			$partid = getPartId($part,$heci);
			//echo 'ps:'.$part.'<BR>';
			//continue;
			if (! $partid) {
continue;//8-8-16
				$partid = setPart(array('part'=>$part,'heci'=>$heci,'manf'=>$manf,'sys'=>'','descr'=>$descr));
			}
//			echo 'Identifying '.$part.' '.$heci.' = '.$partid.' to be added...'.chr(10);

			//dgl 11-18-16 added so that we can store *how* the supplier is posting their data, so when rfqing them
			//we can refer to their original posted search string instead of an alias they can't match
			if ($heci) {
				$heci7 = preg_replace('/[^[:alnum:]]+/','',substr($heci,0,7));
				// if not stored in our db, create the entry so we have record of their exact match
				if (! isset($GLOBALS['SEARCH_IDS'][$heci7]) OR ! $GLOBALS['SEARCH_IDS'][$heci7]) {
					logRemotes($heci7,$GLOBALS['REMDEF']);
				}
				$searchid = $GLOBALS['SEARCH_IDS'][$heci7];
			} else {
				$fpart = preg_replace('/[^[:alnum:]]+/','',$part);
				// if not stored in our db, create the entry so we have record of their exact match
				if (! isset($GLOBALS['SEARCH_IDS'][$fpart]) OR ! $GLOBALS['SEARCH_IDS'][$fpart]) {
					logRemotes($fpart,$GLOBALS['REMDEF']);
				}
				$searchid = $GLOBALS['SEARCH_IDS'][$fpart];
			}

			//must return a variable so this function doesn't happen asynchronously
			if ($return_type=='db') {
//				$added = insertMarket2($partid,$qty,$companyid,$GLOBALS['now'],'PS');
				$inserts[] = array('partid'=>$partid,'qty'=>$qty,'companyid'=>$companyid,'price'=>$price,'searchid'=>$searchid);
			}
		}

		if ($return_type=='db') {
			foreach ($inserts as $r) {
				$metaid = logSearchMeta($r['companyid'],false,'','ps');
				$added = insertMarket($r['partid'],$r['qty'],$r['price'],false,false,$metaid,'availability',$r['searchid']);
			}
		}

		if ($return_type=='db') { return true; }
		else { return ($resArray); }
	}
?>
