<?php
	include_once 'dbconnect.php';
	include_once 'keywords.php';
	include_once 'getPartId.php';
	include_once 'setPart.php';
	include_once 'insertMarket.php';
	include_once 'getCompany.php';
	include_once 'logRemotes.php';
	include_once 'logSearchMeta.php';

	$res_cid = getCompany('Resion','name','id');

	function parse_res($res,$return_type='') {
		$cid = $GLOBALS['res_cid'];

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
		$resultsRows = $xpath->query("//table[contains(@class,'partsearch_table')]/tbody/tr");

		// number of trs in this table
		$n = $resultsRows->length;

		echo "LIMIT: ".$n;

		for ($i=0; $i<$n; $i++) {
			// get all the columns inside each tr (sadly no specific naming convention on this site)
			$columns = $resultsRows->item($i)->getElementsByTagName('td');

			// Based on site per column
			// - Part Number
			// - Manf
			// - QTY
			// - Date Code (Not needed)
			// - Condition (Not needed only shows in-stock or out)
			// - Request (Not needed)

			$part = '';
			$manf = '';
			$qty = '';

			for($j=0; $j < $columns-> length; $j++) {
				$text = $columns->item($j)->nodeValue;
				// get the qty available
				if($j == 0) {
					$part = $text;
				} else if($j == 1) {
					$manf = (strtolower($text) == 'n/a' ? '' : $text);
				} else if($j == 2) {
					$qty = $text;
				} else {
					continue;
				}
			}
			// echo '<BR><BR>PART NUMBER: ' . $part . "<BR>";
			// echo 'MANF: ' . $manf . "<BR>";
			// echo 'COMPANY SELLING: resion<BR>';
			// echo 'TRANSLATE COMPANY ID: ' . $cid . "<BR>";
			// echo 'STOCK (QTY): ' . $qty . "<BR>";

			// No descr, HECI
			$descr = '';
			$heci = '';

			// attempt to find the partid based on the part
			$partid = getPartId($part,$heci);

			// echo 'PARTID (IF FOUND, IF NOT THEN CONTINUE): ' . $partid . "<BR>";

			if (! $partid) {
				// $partid = setPart(array('part'=>$part,'heci'=>$heci,'manf'=>$manf,'sys'=>'','descr'=>$descr));
				continue;
			}

		    $resArray[] = array('manf'=>$manf,'part'=>$part,'descr'=>$descr,'qty'=>$qty,'heci'=>$heci,'company'=>'Resion');

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
//				$added = insertMarket2($partid,$qty,$cid,$GLOBALS['now'],'ET');
				$inserts[] = array('partid'=>$partid,'qty'=>$qty,'searchid'=>$searchid);
			}
		}

		if ($return_type=='db' AND count($inserts)>0) {
			$metaid = logSearchMeta($cid,false,'','res');
			foreach ($inserts as $r) {
				$added = insertMarket($r['partid'],$r['qty'],false,false,false,$metaid,'availability',$r['searchid']);
			}
		}

		if ($return_type=='db') { return true; }
		else { return ($resArray); }
	}
?>
