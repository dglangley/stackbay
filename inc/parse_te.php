<?php
	include_once 'dbconnect.php';
	include_once 'keywords.php';
	include_once 'getPartId.php';
	include_once 'setPart.php';
	include_once 'insertMarket.php';
	include_once 'getCompany.php';
	include_once 'logRemotes.php';
	include_once 'logSearchMeta.php';

	$te_cols = array(
		1 => 'Company',
		2 => 'Part',
		3 => 'Description',
		4 => 'HECI/CLEI',
		5 => 'Manf',
		7 => 'Qty'
	);

	function parse_te($res,$return_type='db') {
		$F = $GLOBALS['te_cols'];

		$inserts = array();//gather all records to be inserted into db
		$resArray = array();

		$sorter = 'company';

		// eliminate html formatting warnings per http://stackoverflow.com/a/10482622/1356496, set error level
		$internalErrors = libxml_use_internal_errors(true);

		$newDom = new domDocument;
		$newDom->loadHTML($res);

		// Restore error level, see $internalErrors explanation above
		libxml_use_internal_errors($internalErrors);

		$newDom->preserveWhiteSpace = false;
		$tables = $newDom->getElementsByTagName('form')->item(1)->getElementsByTagName('table');
		$n = $tables->length;
		for ($i=0; $i<$n; $i++) {
			$rows = $tables->item($i)->getElementsByTagName('tr');
			$nrows = $rows->length;
			for ($j=1; $j<($nrows-1); $j++) {
				$cols = $rows->item($j)->getElementsByTagName('td');

				$eci = 0;
				$manf = trim($cols->item(array_search('Manf',$F))->nodeValue);
				$descr = trim(strtoupper($cols->item(array_search('Description',$F))->nodeValue));
				$descr = str_ireplace('call','',$descr);
//				if ($manf AND $descr) { $descr = $manf.' '.$descr; }
				$qty = trim($cols->item(array_search('Qty',$F))->nodeValue);
				$heci = trim(str_replace('CALL','',strtoupper($cols->item(array_search('HECI/CLEI',$F))->nodeValue)));
				// eliminate invalid heci data from suppliers
				if ((strlen($heci)<>7 AND strlen($heci)<>10) OR strstr($heci,' ')) { $heci = ''; }

				$manf = trim($cols->item(array_search('Manf',$F))->nodeValue);
				$part = trim(strtoupper($cols->item(array_search('Part',$F))->nodeValue));
				$part = trim(substr($part,0,strpos($part,'(')-1));
				if ($qty>=9999 OR strstr($part,$qty)) { $qty = 1; }//fixes results such as in tel-explorer that gets Telmar's qty wrong
				$company = trim($cols->item(array_search('Company',$F))->nodeValue);
				$company = ucfirst(trim(substr($company,0,strpos($company,'(')-1)));
				$companyid = getCompany($company,'name','id');

				$resArray[] = array('manf'=>$manf,'part'=>$part,'descr'=>$descr,'qty'=>$qty,'heci'=>$heci,'company'=>$company);

				$partid = getPartId($part,$heci);

				//echo 'te:'.$part.'<BR>';
				//continue;
				if (! $partid) {
continue;//8-8-16
					$partid = setPart(array('part'=>$part,'heci'=>$heci,'manf'=>$manf,'sys'=>'','descr'=>$descr));
				}
//				echo 'Identifying '.$part.' '.$heci.' = '.$partid.' to be added...'.chr(10);

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
//					$added = insertMarket2($partid,$qty,$companyid,$GLOBALS['now'],'TE',false,$part);
					$inserts[] = array('partid'=>$partid,'qty'=>$qty,'companyid'=>$companyid,'searchid'=>$searchid);
				}
			}
		}

		if ($return_type=='db') {
			foreach ($inserts as $r) {
				$metaid = logSearchMeta($r['companyid'],false,'','te');
				$added = insertMarket($r['partid'],$r['qty'],false,false,false,$metaid,'availability',$r['searchid']);
			}
		}

		if ($return_type=='db') { return true; }
		else { return ($resArray); }
	}
?>
