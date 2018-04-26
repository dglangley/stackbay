<?php
	include_once 'dbconnect.php';
	include_once 'keywords.php';
	include_once 'getPartId.php';
	include_once 'setPart.php';
	include_once 'insertMarket.php';
	include_once 'getCompany.php';
	include_once 'logSearchMeta.php';
	include_once 'logRemotes.php';

	$ar_cid = getCompany('IT Recovery Specialists','name','id');

	function parse_ar($res,$return_type='db') {
		global $ar_cid, $DEBUG;

		$cid = $GLOBALS['ar_cid'];

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
		$resultsRows = $xpath->query("//body");

		$n = $resultsRows->length;
		for ($i=0; $i<$n; $i++) {
			$rows = $resultsRows->item($i)->nodeValue;

			//generates the mess into a php clean array, adding true makes it an array vs object
			$decoded = json_decode($rows, true);

			$part = '';
			$manf = '';
			$qty = '';

			// Non existent on this site
			$heci = '';
			$descr = '';

			$partid = 0;


			foreach(reset($decoded) as $item) {

				$part = $item['model'];
				$manf = $item['mfg'];
				$qty = $item['qty'];

				$partid = getPartId($part,$heci);

/*
				echo '<BR><BR>PART NUMBER: ' . $part . "<BR>";
				echo 'MANF: ' . $manf . "<BR>";
				echo 'COMPANY SELLING: IT Recovery Specialists<BR>';
				echo 'TRANSLATE COMPANY ID: ' . $cid . "<BR>";
				echo 'STOCK (QTY): ' . $qty . "<BR>";

				echo 'PARTID (IF FOUND, IF NOT THEN CONTINUE): ' . $partid . "<BR>";
*/

				if (! $partid) {
					// $partid = setPart(array('part'=>$part,'heci'=>$heci,'manf'=>$manf,'sys'=>'','descr'=>$descr));
					continue;
				}

			    $resArray[] = array('manf'=>$manf,'part'=>$part,'descr'=>$descr,'qty'=>$qty,'heci'=>$heci,'company'=> 'IT Recovery Specialists');

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
					$inserts[] = array('partid'=>$partid,'qty'=>$qty,'searchid'=>$searchid);
				}
			}
		}

		if ($return_type=='db' AND count($inserts)>0) {
			$metaid = logSearchMeta($cid,false,'','ar');
			foreach ($inserts as $r) {
				$added = insertMarket($r['partid'],$r['qty'],false,false,false,$metaid,'availability',$r['searchid']);
			}
		}

		if ($return_type=='db') { return true; }
		else { return ($resArray); }
	}
?>
