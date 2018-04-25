<?php
	include_once 'dbconnect.php';
	include_once 'keywords.php';
	include_once 'getPartId.php';
	include_once 'setPart.php';
	include_once 'insertMarket.php';
	include_once 'getCompany.php';
	include_once 'logRemotes.php';
	include_once 'logSearchMeta.php';

	$op_cid = getCompany('Octopart','name','id');

	// Allows the searching of a class after the xpath has been generated
	function getElementsByClass(&$parentNode, $tagName, $className) {
	    $nodes=array();

	    $childNodeList = $parentNode->getElementsByTagName($tagName);
	    for ($i = 0; $i < $childNodeList->length; $i++) {
	        $temp = $childNodeList->item($i);
	        if (stripos($temp->getAttribute('class'), $className) !== false) {
	            $nodes[]=$temp;
	        }
	    }

	    return $nodes;
	}

	function parse_op($res,$return_type='db') {
		$cid = $GLOBALS['op_cid'];

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
		$resultsRows = $xpath->query("//tbody[contains(@class,'serp-part-card')]");

		// $resultsTable = $newDom->getElementById('searchResults')->getElementsByTagName('div');
		// print "<pre>".print_r($resultsRows,true)."</pre>";

		$n = $resultsRows->length;

		for ($i=0; $i<$n; $i++) {

			$mpn = getElementsByClass($resultsRows->item($i), 'span', 'part-card-mpn');

			// get the part numnber
			// reset as there is only 1 mpn class
			$mpn_text = trim(reset($mpn)->nodeValue);

			// get all the companies that offer the part
			$table_rows = getElementsByClass($resultsRows->item($i), 'tr', 'offerRow');

			foreach($table_rows as $row) {
				// get the qty available
				$qtyRow = getElementsByClass($row, 'td', 'col-avail');
				$qty_text = trim(reset($qtyRow)->nodeValue);

				// If nothing is available then continue and don't record this
				if($qty_text < 1) {
					continue;
				}

				$companyRow = getElementsByClass($row, 'div', 'col-seller-inner');
				$company_text = trim(reset($companyRow)->nodeValue);

				// get the manf
				$manfRow = getElementsByClass($resultsRows->item($i), 'div', 'serp-card-header');

				// print_r($manfRow);
				$manf_text = trim(reset($manfRow)->getAttribute("data-brand"));
				// $descr_text = reset($manfRow)->nodeValue;

				$companyid = getCompany($company_text, 'name', 'id');

				// Ask if valid
				// Try a like search for company if not found
				// AKA Mouser but should be Mouser Electronics
				if(! $companyid) {
					$query = "SELECT id, name FROM companies WHERE name LIKE '".$company_text."%';";
					$result = qedb($query);

					if(mysqli_num_rows($result) > 0) {
						$r = mysqli_fetch_assoc($result);

						$companyid = $r['id'];

						// if found replace the company name with the one in the db
						$company_text = $r['name'];
					}
				}

				// echo '<BR><BR>PART NUMBER: ' . $mpn_text . "<BR>";
				// echo 'MANF: ' . $manf_text . "<BR>";
				// echo 'COMPANY SELLING: ' . $company_text . "<BR>";
				// echo 'TRANSLATE COMPANY ID: ' . $companyid . "<BR>";
				// echo 'STOCK (QTY): ' . $qty_text . "<BR>";
				// echo 'POSSIBLE DESCR: ' . $descr_text . "<BR>";

				// HECI doesn't seem to exist to Octoparts
				// Will ask David if this is true or not

				// DESCR also embedded in an auto populating modal
				$descr = '';
				$heci = '';

				$partid = getPartId($part,$heci);

				// echo 'PARTID (IF FOUND, IF NOT THEN CONTINUE): ' . $partid . "<BR>";

				if (! $partid) {
					// $partid = setPart(array('part'=>$part,'heci'=>$heci,'manf'=>$manf,'sys'=>'','descr'=>$descr));
					continue;
				}

			    $resArray[] = array('manf'=>$manf_text,'part'=>$mpn_text,'descr'=>$descr,'qty'=>$qty_text,'heci'=>$heci,'company'=>$company_text);

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
		}

		if ($return_type=='db' AND count($inserts)>0) {
			$metaid = logSearchMeta($cid,false,'','op');
			foreach ($inserts as $r) {
				$added = insertMarket($r['partid'],$r['qty'],false,false,false,$metaid,'availability',$r['searchid']);
			}
		}

		if ($return_type=='db') { return true; }
		else { return ($resArray); }
	}
?>
