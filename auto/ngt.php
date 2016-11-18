<?php
	$root_dir = '';
	if (isset($_SERVER["ROOT_DIR"])) { $root_dir = $_SERVER["ROOT_DIR"]; }
	include_once $root_dir.'/inc/dbconnect.php';
	include_once $root_dir.'/inc/format_date.php';
	include_once $root_dir.'/inc/call_remote.php';
	include_once $root_dir.'/inc/getPartId.php';
	include_once $root_dir.'/inc/insertMarket.php';
	include_once $root_dir.'/inc/getCompany.php';
	include_once $root_dir.'/inc/getContact.php';
	include_once $root_dir.'/inc/logSearchMeta.php';
	include_once $root_dir.'/inc/send_gmail.php';
	include_once $root_dir.'/inc/logRemotes.php';

	setGoogleAccessToken(5);

	// use today's date as the initial search date for most recent inventory additions
	$search_date = date("m-d-Y");
	$search_time = date("H:i:s");
	$url = "http://www.ngtinc.com/pub_find_item.cgi";
	
	//If there no results, look back through the older dates
	for ($i=0; $i<7; $i++) {
		// set search date back, if after the initial date
		if ($i>0) {
			$search_date = format_date($today,'m-d-Y',array('d'=>-$i));
		}
		echo 'Capturing NGT '.$search_date.' at '.$url.'...<BR>'.chr(10);
		
		//Set the html to the result of the htmlDOM document.
		//If we want to pull the full day's database, we can pass in junk data
		$ngt_html = call_remote($url,"?startDate=$search_date",$cookiejar,$cookiejarfile,'GET');

		// if there's content on the page and it's content that doesn't say "No results found", we have all we need
		if ($ngt_html and ! strstr($ngt_html,'No results found.')) { break; }
	}
	if ($i>0) { $search_time = '08:00:00'; }//default to 8am the date of search if prior to today

	if ($i>=7) { die("No results found."); }

	echo 'Initializing at '.$now.', searching date '.$search_date.'<BR>'.chr(10);

	//Log the search into the search meta table, and do so only from here to be sure we didn't die above
	$companyid = getCompany('North Georgia Telecom','name','id');
	echo 'Found companyid '.$companyid.' in db for NGT<BR>';
	$metaid = logSearchMeta($companyid,false,'','ngt');

//	echo $ngt_html;
	$favs_report = '';
	$num_favs = 0;
	libxml_use_internal_errors(true); //prevent errors from displaying
	$newDom = new domDocument;
	$newDom->loadHTML($ngt_html);
	$newDom->preserveWhiteSpace = false;
	$inv = $newDom->getElementsByTagName('form')->item(0)->getElementsByTagName('table')->item(0);
	$rows = $inv->getElementsByTagName('tr');
	$n = $rows->length;
	// start after <th> row
	for ($i=1; $i<($n-1); $i++) {
		$cols = $rows->item($i)->getElementsByTagName('td');
		$part = $cols->item(2)->nodeValue;
		$heci = trim($cols->item(5)->nodeValue);
		if (! $part) { $part = $heci; }
		else { $part = trim($part.' '.$cols->item(3)->nodeValue); }
		if (! $part) { continue; }

		$part = trim(str_replace("'","",str_replace('"','',$part)));
		$qty = trim($cols->item(6)->nodeValue);
		$descr = trim($cols->item(1)->nodeValue.' '.$cols->item(4)->nodeValue);
		$descr = str_replace("'","",str_replace('"','',$descr));

		// get all related partids for favorites but use only the first result for capturing consolidated results
		$partids = getPartId($part,$heci,0,true);
		$partid = 0;
		if (isset($partids[0])) { $partid = $partids[0]; }

		// assess favorites position by gathering all related partids
		$favs = getFavorites($partids);

		if (count($favs)>0) {
			$favs_report .= 'qty '.$qty.'- '.$part.' '.$heci.'<BR>';
			$num_favs++;// += count($favs);
		}

//dgl 10-20-16
//		$partid = getPartId($part,$heci);
		// for now, we don't need to be adding parts into our db that's in NGT's system, just skip 'em
		if (! $partid) { continue; }

		//dgl 11-18-16 added so that we can store *how* the supplier is posting their data, so when rfqing them
		//we can refer to their original posted search string instead of an alias they can't match
		if ($heci) {
			$heci7 = preg_replace('/[^[:alnum:]]+/','',substr($heci,0,7));
			// if not stored in our db, create the entry so we have record of their exact match
			if (! $SIDS[$heci7]) {
				logRemotes($heci7,'000000');
			}
			$searchid = $SIDS[$heci7];
		} else {
			$fpart = preg_replace('/[^[:alnum:]]+/','',$part);
			// if not stored in our db, create the entry so we have record of their exact match
			if (! $SIDS[$fpart]) {
				logRemotes($fpart,'000000');
			}
			$searchid = $SIDS[$fpart];
		}

		insertMarket($partid,$qty,false,false,false,$metaid,'availability',$searchid);
	}
	if ($favs_report) {
		$mail_msg = 'Your file upload ("'.$filename.'") appears to match '.$num_favs.' of our favorites:<BR><BR>'.$favs_report;

		$send_success = send_gmail($mail_msg,'Favorites found in file upload! '.date("D n/j/y"),getContact($userid,'userid','email'),$bcc);
		if ($send_success) {
			echo json_encode(array('message'=>'Success'));
		} else {
			echo json_encode(array('message'=>$SEND_ERR));
		}
	}
	echo ('success!')
?>
