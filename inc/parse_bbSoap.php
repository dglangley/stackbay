<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearchMeta.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/insertMarket.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logRemotes.php';

	// indeces of columns found in results
	// Currently used as a legend
	// $F = array(
	// 	2 => 'Company',
	// 	5 => 'Part/Model',
	// 	7 => 'HECI/CLEI',
	// 	8 => 'Mfg',
	// 	10 => 'Price',
	// 	11 => 'Qty',
	// 	13 => 'Product Description',
	// );

	function parse_bbSoap($res,$return_type='db') {
		if (! $res) { return false; }

		$searchid;

		//print '<pre>' . print_r($res, true) . '</pre>';

		$resArray = array();
		$inserts = array();//all the records to be added

		// Extract the exact data needed from the array give from the Soap Parse function
		$res = $res['resultset'][0]['result'];
		if (! $res) { return false; }

		foreach($res as $key => $data) {
			$partid = getPartId($data['part'],$data['clei']);
			$part = $data['part'];
			$manf = $data['mfg'];
			$heci = $data['clei'];
			$price = trim(str_replace('CALL', '', $data['price']));

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

			$resArray[] = $data;

			if ($return_type=='db') {
//				$added = insertMarket2($partid,$qty,$companyid,$GLOBALS['now'],'BB');
				$inserts[] = array('partid'=>$partid,'qty'=>$data['qty'],'companyid'=>getCompany($data['company'], 'name', 'id'),'price'=>$price,'searchid'=>$searchid);
			}

			unset($res[$key]);
		}

		if ($return_type=='db') {
			foreach ($inserts as $r) {
				$metaid = logSearchMeta($r['companyid'],false,'','bb');
				$added = insertMarket($r['partid'],$r['qty'],$r['price'],false,false,$metaid,'availability',$r['searchid']);
			}
		}

		//print '<pre>' . print_r($inserts, true) . '</pre>';

		if ($return_type=='db') { return true; }
		else { return ($resArray); }
	}
?>
