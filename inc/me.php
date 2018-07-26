<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logRemotes.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearchMeta.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/insertMarket.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';

	$DEBUG = 0;
	$M_ID = getCompany('Mouser Electronics','name','id');

	function me($str) {
		global $M_ID;

		$inserts = array();
		$str = strtoupper($str);

		try {
			$client = new SoapClient('https://api.mouser.com/service/searchapi.asmx?WSDL', array('soap_version' => SOAP_1_2, 'trace' => true)); 

			$headerbody = array('AccountInfo'=>array('PartnerID'=> 'fc17fa7b-b81f-4a79-9be7-22787263285a' ));

			$header = new SoapHeader('http://api.mouser.com/service', 'MouserHeader', $headerbody);
			$client->__setSoapHeaders($header);

			//$paramsQuery = array('mouserPartNumber' => $str);
			$paramsQuery = array(
				'keyword' => $str,
				'records' => 50,
				'startingRecord' => 0,
				'searchOptions' => 1,
			);

			//$response = $client->SearchByPartNumber($parametersQuery);
			$response = $client->SearchByKeyword($paramsQuery);
//			print "<pre>".print_r($response,true)."</pre>";

			$result = $response->SearchByKeywordResult;
			// Part not found
			if(!isset($result->Parts->MouserPart)) {
				return false;
			}

			$num_results = $result->NumberOfResult;

			// No results
			if (! $num_results) {
				return false;
			}

			$parts = $result->Parts;

			// Because Mouser is dumb and returns a different data set for one result vs multi
			if ($num_results==1) {
				$mouserParts = array($parts->MouserPart);
			} else {
				$mouserParts = $parts->MouserPart;
			}

			foreach ($mouserParts as $k => $p) {
//				print "<pre>".print_r($p,true)."</pre>";

				// Manufacturer
				// ManufacturerPartNumber
				// Description
				// DataSheetUrl
				// MouserPartNumber

				// ImagePath
				// Availability
				// PriceBreaks
				// LeadTime

				$avail = $p->Availability;
				$a_words = explode(' ',$avail);
				$qty = $a_words[0];
				if (! is_numeric($qty)) { $qty = 0; }

				$prices = $p->PriceBreaks->Pricebreaks;

				$price = format_price($prices[0]->Price,true,'',true);
				$partid = getPartId($p->ManufacturerPartNumber);
				if (! $partid) {
					$partid = setPart(array('part'=>$p->ManufacturerPartNumber,'heci'=>'','manf'=>$p->Manufacturer,'sys'=>'','descr'=>$p->Description));
				}

                // if not stored in our db, create the entry so we have record of their exact match
				// log the search of the string so we know how it was sourced, and when
                $fstr = preg_replace('/[^[:alnum:]]+/','',$str);
				if (! isset($GLOBALS['SEARCH_IDS'][$fstr]) OR !  $GLOBALS['SEARCH_IDS'][$fstr]) {
                    logRemotes($fstr,$GLOBALS['REMDEF']);
				}
                // if not stored in our db, create the entry so we have record of their exact match
                if (! isset($GLOBALS['SEARCH_IDS'][$fpart]) OR ! $GLOBALS['SEARCH_IDS'][$fpart]) {
                    logRemotes($fpart,$GLOBALS['REMDEF']);
                }
                $searchid = $GLOBALS['SEARCH_IDS'][$fstr];

				$inserts[] = array('partid'=>$partid,'qty'=>$qty,'price'=>$price,'searchid'=>$searchid);
            }

			$metaid = logSearchMeta($M_ID,false,'','me');
			if (! $metaid) { return; }

			foreach ($inserts as $r) {
				$added = insertMarket($r['partid'],$r['qty'],$r['price'],false,false,$metaid,'availability',$r['searchid']);
			}
		} catch (SoapFault $e) {
			print_r($e);
		}
	}
?>
