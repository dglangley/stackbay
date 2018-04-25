<?php
	include_once 'dbconnect.php';
	include_once 'keywords.php';
	include_once 'getPartId.php';
	include_once 'setPart.php';
	include_once 'insertMarket.php';
	include_once 'getCompany.php';
	include_once 'logRemotes.php';

	$et_cid = getCompany('MDG Sales','name','id');

	function parse_mdg($res,$return_type='') {
		$cid = $GLOBALS['et_cid'];

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
		$resultsRows = $xpath->query("//td");

		// $resultsTable = $newDom->getElementById('searchResults')->getElementsByTagName('div');
		// print "<pre>".print_r($resultsRows,true)."</pre>";

		$n = $resultsRows->length;

		for($i=0; $i < $n; $i++) {
			$hasContent = false;
			$metas = $resultsRows->item($i)->getElementsByTagName('meta');
			for($j=0; $j < $metas-> length; $j++) {
			    $itemprop = $metas->item($j)->getAttribute("itemprop");
			    $content = $metas->item($j)->getAttribute("content");

			    $manf = '';
			    $part = '';
			    $partid = 0;
			    $descr = '';
			    $qty = 0;
			    $heci = '';
			    $company = 'MDG Sales';

			    if($itemprop == 'mpn') {
			    	echo "Part Number: " . $content . "<BR>";
			    	$hasContent = true;
			    	$part = $content;
			    } 

			    if($itemprop == 'price') {
			    	echo "Price: " . $content . "<BR>";
			    } 

			    if($itemprop == 'model') {
			    	echo "Model/HECI: " . $content . "<BR>";
			    	$heci = $content;
			    } 

			    if($itemprop == 'inventoryLevel') {
			    	echo "Stock QTY: " . $content . "<BR>";
			    	$qty = $content;

			    	// If no qty present then skip this part
			    	if($content == 0) {
			    		continue;
			    	}
			    } 

			    if($itemprop == 'name') {
			    	echo "Possible Description: " . $content . "<BR>";
			    	$descr = $content;
			    } 

			    if($itemprop == 'brand') {
			    	echo "Vendor / Manf: " . $content . "<BR>";
			    	$manf = $content;
			    } 

			    // trim the part and try to find the part in our system
			    $part = trim($part);

			    // Ask David if this is valid, but a lot of part number matches the heci, should the heci then be nulled?
			    if($part == $heci) {
			    	$heci = '';
			    }

				$partid = getPartId($part,$heci);

				if (! $partid) {
					// $partid = setPart(array('part'=>$part,'heci'=>$heci,'manf'=>$manf,'sys'=>'','descr'=>$descr));
					continue;
				}

			    $resArray[] = array('manf'=>$manf,'part'=>$part,'descr'=>$descr,'qty'=>$qty,'heci'=>$heci,'company'=>$company);

				if ($heci) {
					$heci7 = preg_replace('/[^[:alnum:]]+/','',substr($heci,0,7));
					// if not stored in our db, create the entry so we have record of their exact match
					if (! isset($GLOBALS['SEARCH_IDS'][$heci7]) OR ! $GLOBALS['SEARCH_IDS'][$heci7]) {
						logRemotes($heci7,'000000');
					}
					$searchid = $GLOBALS['SEARCH_IDS'][$heci7];
				} else {
					$fpart = preg_replace('/[^[:alnum:]]+/','',$part);
					// if not stored in our db, create the entry so we have record of their exact match
					if (! isset($GLOBALS['SEARCH_IDS'][$fpart]) OR ! $GLOBALS['SEARCH_IDS'][$fpart]) {
						logRemotes($fpart,'000000');
					}
					$searchid = $GLOBALS['SEARCH_IDS'][$fpart];
				}

				//must return a variable so this function doesn't happen asynchronously
				if ($return_type=='db') {
	//				$added = insertMarket2($partid,$qty,$cid,$GLOBALS['now'],'ET');
					$inserts[] = array('partid'=>$partid,'qty'=>$qty,'searchid'=>$searchid);
				}
			}

			// For clean echo purposes only
			if($hasContent) {
				echo '<BR><BR>';
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