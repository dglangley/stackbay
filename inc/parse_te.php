<?php
	include_once 'dbconnect.php';
	include_once 'keywords.php';
	include_once 'getPartId.php';
	include_once 'setPart.php';
	include_once 'insertMarket.php';
	include_once 'getCompany.php';

	$te_cols = array(
		1 => 'Company',
		2 => 'Part',
		3 => 'Description',
		4 => 'HECI/CLEI',
		5 => 'Manf',
		8 => 'Qty'
	);

	function parse_te($res,$return_type='db') {
		$F = $GLOBALS['te_cols'];

		$resArray = array();

		$sorter = 'company';

		$newDom = new domDocument;
		$newDom->loadHTML($res);
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
				if ($manf AND $descr) { $descr = $manf.' '.$descr; }
				$qty = trim($cols->item(array_search('Qty',$F))->nodeValue);
				$heci = trim(str_replace('CALL','',strtoupper($cols->item(array_search('HECI/CLEI',$F))->nodeValue)));
				// eliminate invalid heci data from suppliers
				if ((strlen($heci)<>7 AND strlen($heci)<>10) OR strstr($heci,' ')) { $heci = ''; }

				$manf = trim($cols->item(array_search('Manf',$F))->nodeValue);
				$part = trim(strtoupper($cols->item(array_search('Part',$F))->nodeValue));
				$part = trim(substr($part,0,strpos($part,'(')-1));
				$company = trim($cols->item(array_search('Company',$F))->nodeValue);
				$company = ucfirst(trim(substr($company,0,strpos($company,'(')-1)));
				$companyid = getCompany($company,'name','id');

				$resArray[] = array('manf'=>$manf,'part'=>$part,'descr'=>$descr,'qty'=>$qty,'heci'=>$heci,'company'=>$company);

				$partid = getPartId($part,$heci);
				//echo 'te:'.$part.'<BR>';
				//continue;
				if (! $partid) {
					$partid = setPart(array('part'=>$part,'heci'=>$heci,'manf'=>$manf,'sys'=>'','descr'=>$descr));
				}
//				echo 'Identifying '.$part.' '.$heci.' = '.$partid.' to be added...'.chr(10);
				//must return a variable so this function doesn't happen asynchronously
				if ($return_type=='db') {
					$added = insertMarket2($partid,$qty,$companyid,$GLOBALS['now'],'TE',$part);
				}
			}
		}

		if ($return_type=='db') { return true; }
		else { return ($resArray); }
	}
?>
