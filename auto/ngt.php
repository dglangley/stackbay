<?php
	$root_dir = '';
	if (isset($_SERVER["ROOT_DIR"])) { $root_dir = $_SERVER["ROOT_DIR"]; }
	include_once $root_dir.'/inc/dbconnect.php';
	include_once $root_dir.'/inc/format_date.php';
	include_once $root_dir.'/inc/getPartId.php';
	include_once $root_dir.'/inc/insertMarket.php';
	include_once $root_dir.'/inc/getCompany.php';
	include_once $root_dir.'/inc/getContact.php';
	include_once $root_dir.'/inc/getFavorites.php';
	include_once $root_dir.'/inc/logSearchMeta.php';
	include_once $root_dir.'/inc/send_gmail.php';
	include_once $root_dir.'/inc/logRemotes.php';
	include_once $root_dir.'/inc/download_ngt.php';

	// use today's date as the initial search date for most recent inventory additions
	$start_time = time();
	$search_date = '';

	// initialize session to keep connection open
	$NGT_CH = curl_init($ngt_url);

	libxml_use_internal_errors(true); //prevent errors from displaying when loading html dom
	$results = '';
	// loop for up to 7 days worth of results to check against their site, but one day at a time
	for ($i=0; $i<7; $i++) {
		$search_time = $start_time-($i*(60*60*24));//subtract $i days number of seconds
		$days = floor(($search_time - strtotime("2000-01-01")) / (60*60*24));
		$search_date = format_date('2000-01-01','M j, Y',array('d'=>floor($days)));
		echo 'Capturing NGT '.$search_date.' ('.$days.' of '.$search_time.')...<BR>'.chr(10);

		$res = download_ngt($search_time,$NGT_CH);
		// get DOM to load and evaluate potential results
		if ($res) {
			$dom = new domDocument;
			$dom->loadHTML($res);
			$dom->preserveWhiteSpace = false;

			// if there's content on the page in this grid, we have all we need
			if ($dom->getElementById('GridViewInventorySearch')) {
				$results = $dom->getElementById('GridViewInventorySearch');
				break;//stop looping now that we have results
			}
		}
	}

	curl_close($NGT_CH);

	if (! $results) {
		die("No results found.");
	}

	setGoogleAccessToken(5);

	echo 'Initializing at '.$now.', searching date '.$search_date.'<BR>'.chr(10);

	//Log the search into the search meta table, and do so only from here to be sure we didn't die above
	$companyid = getCompany('North Georgia Telecom','name','id');
	echo 'Found companyid '.$companyid.' in db for NGT<BR>';
	$metaid = logSearchMeta($companyid,false,'','ngt');

//	echo $results;
	$favs_report = '';
	$inv_report = '';
	$num_favs = 0;
	$rows = $results->getElementsByTagName('tr');
	$n = $rows->length;
	$ln = 0;
	// start at 1 so that we start after <th> row
	for ($i=1; $i<($n-1); $i++) {
		$cols = $rows->item($i)->getElementsByTagName('td');
		$part = $cols->item(0)->nodeValue;
		$part = trim(str_replace(chr(194).chr(160),'',str_replace("'","",str_replace('"','',$part))));

		$heci = trim(str_replace(chr(194).chr(160),'',$cols->item(2)->nodeValue));

		$qty = trim(str_replace(chr(194).chr(160),'',$cols->item(3)->nodeValue));
		// currently not used
		$manf = trim($cols->item(1)->nodeValue);
		$manf = str_replace("'","",str_replace('"','',$manf));

		// get all related partids for favorites but use only the first result for capturing consolidated results
		$partids = getPartId($part,$heci,0,true);
		$partid = 0;
		if (isset($partids[0])) { $partid = $partids[0]; }

		// assess favorites position by gathering all related partids
		$favs = getFavorites($partids,$GLOBALS['U']['id']);

		if (count($favs)>0) {
			$favs_report .= 'qty '.$qty.'- '.$part.' '.$heci.'<BR>';
			$num_favs++;// += count($favs);
		}
		$inv_report .= 'qty '.$qty.'- '.$part.' '.$heci.'<BR>';

//dgl 10-20-16
//		$partid = getPartId($part,$heci);
		// for now, we don't need to be adding parts into our db that's in NGT's system, just skip 'em
		if (! $partid) { continue; }

		//dgl 11-18-16 added so that we can store *how* the supplier is posting their data, so when rfqing them
		//we can refer to their original posted search string instead of an alias they can't match
		if ($heci) {
			$heci7 = preg_replace('/[^[:alnum:]]+/','',substr($heci,0,7));
			// if not stored in our db, create the entry so we have record of their exact match
			if (! isset($SEARCH_IDS[$heci7]) OR ! $SEARCH_IDS[$heci7]) {
				logRemotes($heci7,$GLOBALS['REMDEF']);
			}
			$searchid = $SEARCH_IDS[$heci7];
		} else {
			$fpart = preg_replace('/[^[:alnum:]]+/','',$part);
			// if not stored in our db, create the entry so we have record of their exact match
			if (! isset($SEARCH_IDS[$fpart]) OR ! $SEARCH_IDS[$fpart]) {
				logRemotes($fpart,$GLOBALS['REMDEF']);
			}
			$searchid = $SEARCH_IDS[$fpart];
		}

		insertMarket($partid,$qty,false,false,false,$metaid,'availability',$searchid,$ln);
		$ln++;
	}
	if ($favs_report) {
		$mail_msg = 'NGT inventory appears to match '.$num_favs.' of our favorites:<BR><BR>'.$favs_report;

		$send_success = send_gmail($mail_msg,'Favorites found in file upload! '.date("D n/j/y"),'sales@ven-tel.com');
		if ($send_success) {
			echo json_encode(array('message'=>'Success'));
		} else {
			echo json_encode(array('message'=>$SEND_ERR));
		}
	}
/*
	if ($inv_report) {
		$mail_msg = 'ngtinc.com inventory report:<BR><BR>'.$inv_report;

		$send_success = send_gmail($mail_msg,'ngtinc.com inventory report '.date("D n/j/y"),'wtb@ven-tel.com');
		if ($send_success) {
			echo json_encode(array('message'=>'Success'));
		} else {
			echo json_encode(array('message'=>$SEND_ERR));
		} 
	}
*/
	echo ('success!')
?>
