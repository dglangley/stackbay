<?php
	include_once 'getCompany.php';
	include_once 'getPartId.php';
	include_once 'setPart.php';
	include_once 'insertMarket.php';

	// indeces of columns found in results
	$F = array(
		2 => 'Company',
		5 => 'Part/Model',
		7 => 'HECI/CLEI',
		8 => 'Mfg',
		10 => 'Price',
		11 => 'Qty',
		13 => 'Product Description',
	);

	function parse_bb($res,$return_type='db') {
		if (! $res) { return false; }

		$F = $GLOBALS['F'];

		$resArray = array();

		$newDom = new domDocument;
		$newDom->loadHTML($res);
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
				$cols = $row->getElementsByTagName('td');
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
				$company = $data[array_search('Company',$F)];
				$part = $data[array_search('Part/Model',$F)];
				$heci = $data[array_search('HECI/CLEI',$F)];
				if (strlen($heci)<>7 AND strlen($heci)<>10) { $heci = ''; }
				$manf = $data[array_search('Mfg',$F)];
				if ($manf=='N/A') { $manf = ''; }
				$price = $data[array_search('Price',$F)];
				if ($price=='CALL') { $price = ''; }
				$qty = $data[array_search('Qty',$F)];
				$descr = $data[array_search('Product Description',$F)];

				$companyid = getCompany($company,'name','id');
				if (! $companyid) { $companyid = addCompany($company); }

				$partid = getPartId($part,$heci,goManf($manf));
				echo 'bb:'.$part.':'.$heci.':'.$partid.' '.$company.'<BR>';
				continue;
				if (! $partid) {
					$partid = setPart(array('part'=>$part,'heci'=>$heci,'manf'=>$manf,'sys'=>'','descr'=>$descr));
				}
//				echo 'Identifying '.$part.' '.$heci.' = '.$partid.' to be added...'.chr(10);

				//must return a variable so this function doesn't happen asynchronously
				if ($return_type=='db') {
					$added = insertMarket2($partid,$qty,$companyid,$GLOBALS['now'],'BB');
				}
			}

//commented this 2-25-16 because in multiple-search submissions, we DO want more than just the first table
//			break;//there are multiple tables of same class, just break after the first one, which is the real one
		}

		if ($return_type=='db') { return true; }
		else { return ($resArray); }
	}
?>
