<?php


/*
	$list_name = '*';
	if (isset($argv[1])) { $list_name = $argv[1]; }
*/

	include_once $root_dir.'/inc/dbconnect.php';
	include_once $root_dir.'/inc/format_date.php';
	include_once $root_dir.'/inc/format_date.php';
	include_once $root_dir."/inc/call_remote.php";
	include_once $root_dir.'/inc/getPartId.php';
	include_once $root_dir.'/inc/insertMarket.php';
	include_once $root_dir.'/inc/getCompany.php';
	include_once $root_dir.'/inc/logSearchMeta.php';

//Log the search into the search meta table	
	$companyid = getCompany('North Georgia Telecom','name','id');
	$metaid = logSearchMeta($companyID,false,'','ngt');
	
	


	// use today's date as the initial search date for most recent inventory additions
	$search_date = date("m-d-Y");
	$search_time = date("H:i:s");
	$url = "http://www.ngtinc.com/pub_find_item.cgi";
	
	//If there no results, look back through the older dates
	for ($i=0; $i<7; $i++) {
		echo 'Capturing NGT '.$search_date.'...<BR>'.chr(10);

		// set search date back, if after the initial date
		if ($i>0) {
			$search_date = format_date($today,'m-d-Y',array('d'=>-$i));
		}
		echo "$url<br>$date<br>";
		//Set the html to the result of the htmlDOM document.
		
		//If we want to pull the full day's database, we can pass in junk data
		$ngt_html = call_remote($url,"?startDate=$search_date",$cookiejar,$cookiejarfile,'GET');

		
	// if there's content on the page and it's content that doesn't say "No results found", we have all we need
	if ($ngt_html and ! strstr($ngt_html,'No results found.')) { break; }
		
	}
	if ($i>0) { $search_time = '08:00:00'; }//default to 8am the date of search if prior to today

	if ($i>=7) { die("No results found."); }

	echo 'Initializing at '.$now.', searching date '.$search_date.'<BR>'.chr(10);

//	echo $ngt_html;
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
		


		$partid = getPartId($part,$heci);
		insertMarket($partid,$qty,'',false,false,$metaid,'availability');
	}
	echo ('success!')
?>
