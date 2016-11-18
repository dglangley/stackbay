<?php
	include_once 'dbconnect.php';
	include_once 'keywords.php';
	include_once 'getPartId.php';
	include_once 'setPart.php';
	include_once 'insertMarket.php';
	include_once 'getCompany.php';
	include_once 'logRemotes.php';

	$et_cols = array(
		0 => 'HECI',
		1 => 'PART NO.',
		2 => 'DESCRIPTION',
		3 => 'VENDOR',
		4 => 'TYPE',
		5 => 'QTY.',
	);

	$et_cid = getCompany('Excel Computers','name','id');

	function parse_excel($res,$return_type='db') {
		$F = $GLOBALS['et_cols'];
		$cid = $GLOBALS['et_cid'];
		$SIDS = $GLOBALS['SEARCH_IDS'];

		$inserts = array();//gather records to be inserted into db
		$resArray = array();

		// eliminate html formatting warnings per http://stackoverflow.com/a/10482622/1356496, set error level
		$internalErrors = libxml_use_internal_errors(true);

		$newDom = new domDocument;
		$newDom->loadHTML($res);

		// Restore error level, see $internalErrors explanation above
		libxml_use_internal_errors($internalErrors);

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
			$manf = trim($cols->item(array_search('VENDOR',$F))->nodeValue);
			$descr = trim(strtoupper($cols->item(array_search('DESCRIPTION',$F))->nodeValue));
			if ($manf AND $descr) { $descr = $manf.' '.$descr; }
			$qty = trim($cols->item(array_search('QTY.',$F))->nodeValue);
			$heci = '';
			if ($cols->item(array_search('HECI',$F))->getElementsByTagName('a')->length>0) {
				if ($cols->item(array_search('HECI',$F))->getElementsByTagName('a')->item(0)->getElementsByTagName('span')->length>0) {
					$heci = trim(str_replace('N/A','',$cols->item(array_search('HECI',$F))->getElementsByTagName('a')->item(0)->getElementsByTagName('span')->item(0)->nodeValue));
				}
			}
//			print "<pre>".print_r($heci,true)."</pre>";
			$part = trim(strtoupper($cols->item(array_search('PART NO.',$F))->nodeValue));
			$partid = getPartId($part,$heci);

			$resArray[] = array('manf'=>$manf,'part'=>$part,'descr'=>$descr,'qty'=>$qty,'heci'=>$heci,'company'=>'Excel Computers');

			//echo 'et:'.$part.'<BR>';
			//continue;
			if (! $partid) {
				$partid = setPart(array('part'=>$part,'heci'=>$heci,'manf'=>$manf,'sys'=>'','descr'=>$descr));
			}
//			echo 'Identifying '.$part.'/'.$heci.' = '.$partid.' to be added...'.chr(10);

			//dgl 11-18-16 added so that we can store *how* the supplier is posting their data, so when rfqing them
			//we can refer to their original posted search string instead of an alias they can't match
			if ($heci) {
				$heci7 = preg_replace('/[^[:alnum:]]+/','',substr($heci,0,7));
				// if not stored in our db, create the entry so we have record of their exact match
				if (! $SIDS[$heci7]) {
					logRemotes($heci7,'000000');
				}
				$searchid = $SIDS[$heci7];
			} else {
				$fpart = preg_replace('/[^[:alnum:]]+/','',$part);
				// if not stored in our db, create the entry so we have record of their exact match
				if (! $SIDS[$fpart]) {
					logRemotes($fpart,'000000');
				}
				$searchid = $SIDS[$fpart];
			}

			//must return a variable so this function doesn't happen asynchronously
			if ($return_type=='db') {
//				$added = insertMarket2($partid,$qty,$cid,$GLOBALS['now'],'ET');
				$inserts[] = array('partid'=>$partid,'qty'=>$qty,'searchid'=>$searchid);
			}
		}

		if ($return_type=='db' AND count($inserts)>0) {
			$metaid = logSearchMeta($cid,false,'','et');
			foreach ($inserts as $r) {
				$added = insertMarket($r['partid'],$r['qty'],false,false,false,$metaid,'availability',$r['searchid']);
			}
		}

		if ($return_type=='db') { return true; }
		else { return ($resArray); }
	}
?>
