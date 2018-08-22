<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearchMeta.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/insertMarket.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logRemotes.php';

	// indeces of columns found in results
	$F = array(
		2 => 'Company',
		5 => 'Part/Model',
		8 => 'HECI/CLEI',
		9 => 'Mfg',
		11 => 'Price',
		14 => 'Qty',
		14 => 'Product Description',
	);

	function parse_bb($res,$return_type='db') {
		if (! $res) { return false; }

		$F = $GLOBALS['F'];

		$company_col = array_search('Company',$F);
		$part_col = array_search('Part/Model',$F);
		$heci_col = array_search('HECI/CLEI',$F);
		$manf_col = array_search('Mfg',$F);
		$price_col = array_search('Price',$F);
		$qty_col = array_search('Qty',$F);
		$descr_col = array_search('Product Description',$F);

		$resArray = array();
		$inserts = array();//all the records to be added

		// eliminate html formatting warnings per http://stackoverflow.com/a/10482622/1356496, set error level
		$internalErrors = libxml_use_internal_errors(true);

		$newDom = new domDocument;
		$newDom->loadHTML($res);

		// Restore error level, see $internalErrors explanation above
		libxml_use_internal_errors($internalErrors);

		$newDom->preserveWhiteSpace = false;
		$divs = $newDom->getElementsByTagName('div');
		foreach ($divs as $i => $div) {
			if ($div->getAttribute('class')<>'main') { continue; }
			$table = $div->getElementsByTagName('table')->item(0);
			if (! $table) { continue; }
//print "<pre>".print_r($div->getElementsByTagName('table')->item(0),true)."</pre>";

			if ($table->getAttribute('class')<>'rowHighlight') { continue; }
//			print "<pre>".print_r($table,true)."</pre>";

			$rows = $table->getElementsByTagName('tr');
			foreach ($rows as $j => $row) {
//				print "<pre>".print_r($row,true)."</pre>";
				//skip thead and tfoot rows
				if ($j<=1) { continue; }

				$data = array();
				// get all columns in this row
				$cols = $row->getElementsByTagName('td');
				// add relevant text within each column to new array $data for handling below
				foreach ($cols as $k => $col) {
					if (! isset($F[$k])) { continue; }

					$text = '';
					if ($col->getElementsByTagName('a')->length > 0) {
						// value exists in the last anchor tag of the column
						$l = $col->getElementsByTagName('a')->length;
						$text = $col->getElementsByTagName('a')->item(($l-1))->nodeValue;
					} else {
						$text = $col->nodeValue;
					}
					$data[$k] = trim($text);
				}
				//skip thead and tfoot rows but check first to be sure this is not a contacts table
				if ($j<=1) {
					if ($data[2]=='St' AND $data[5]=='Phone') { break; }//we're in the wrong table
					continue;//just a normal header row, skip it
				}

				$resArray[] = $data;
//				print "<pre>".print_r($data,true)."</pre>";

				$eci = 0;
				$company = $data[$company_col];
				$part = $data[$part_col];
				$heci = preg_replace('/^[^[:alnum:]]*([[:alnum:]]+)[^[:alnum:]]*$/','$1',$data[$heci_col]);
				if (strlen($heci)<>7 AND strlen($heci)<>10) { $heci = ''; }
				$manf = $data[$manf_col];
				if ($manf=='N/A') { $manf = ''; }
				$price = $data[$price_col];
				if ($price=='CALL') { $price = ''; }
				$qty = $data[$qty_col];
				$descr = $data[$descr_col];

				$companyid = getCompany($company,'name','id');
				if (! $companyid) { $companyid = addCompany($company); }

				$partid = getPartId($part,$heci);//,goManf($manf));
//				echo 'bb:'.$part.':'.$heci.':'.$partid.' '.$company.'<BR>';
//				continue;
				if (! $partid) {
					// try once more for part if there are multiple words (ie, "1425105L2 TOTAL REAC" by CO Systems)
					$words = explode(' ',$part);
					if (count($words)>1) {
						$partid = getPartId($words[0],$heci,goManf($manf));
					}
					if (! $partid) {
continue;
						$partid = setPart(array('part'=>$part,'heci'=>$heci,'manf'=>$manf,'sys'=>'','descr'=>$descr));
					}
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
//					$added = insertMarket2($partid,$qty,$companyid,$GLOBALS['now'],'BB');
					$inserts[] = array('partid'=>$partid,'qty'=>$qty,'companyid'=>$companyid,'price'=>$price,'searchid'=>$searchid);
				}
			}

//commented this 2-25-16 because in multiple-search submissions, we DO want more than just the first table
//			break;//there are multiple tables of same class, just break after the first one, which is the real one
		}

		if ($return_type=='db') {
			foreach ($inserts as $r) {
				$metaid = logSearchMeta($r['companyid'],false,'','bb');
				$added = insertMarket($r['partid'],$r['qty'],$r['price'],false,false,$metaid,'availability',$r['searchid']);
			}
		}

		if ($return_type=='db') { return true; }
		else { return ($resArray); }
	}
?>
