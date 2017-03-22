<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/call_remote.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	//Store the cookies into the ALU text temporary file
	$temp_dir = sys_get_temp_dir();

	//If last character of temp dir is not a slash, add it so we can append file after that
	if (substr($temp_dir,strlen($temp_dir)-1)<>'/') { $temp_dir .= '/'; }

	//Store the cookie file
	$ngt_cookiefile = $temp_dir.'ngt-curl.txt';
	$ngt_cookiejarfile = $ngt_cookiefile;

	$ngt_url = 'http://www.ngtinc.com/InventorySearch';

	function download_ngt($search_date='',$NGT_CH=false) {
		global $ngt_url,$ngt_cookiefile,$ngt_cookiejarfile;

		// default to searching today's date
		if (! $search_date) { $search_date = time(); }

		if ($NGT_CH===false) { $ch = curl_init($ngt_url); }
		else { $ch = $NGT_CH; }

		$params = array();
		$res = call_remote($ngt_url,$params,$ngt_cookiefile,$ngt_cookiejarfile,'POST',$ch);


		// get DOM to load session vars into POST params
		$dom = new domDocument;
		$dom->loadHTML($res);

		// load session-initialized variables from page
		$eventtar = '';
		if ($dom->getElementById('__EVENTTARGET')) {
			$eventtar = $dom->getElementById('__EVENTTARGET')->getAttribute('value');
		}
		$eventarg = '';
		if ($dom->getElementById('__EVENTARGUMENT')) {
			$eventarg = $dom->getElementById('__EVENTARGUMENT')->getAttribute('value');
		}
		$viewstategen = '';
		if ($dom->getElementById('__VIEWSTATEGENERATOR')) {
			$viewstategen = $dom->getElementById('__VIEWSTATEGENERATOR')->getAttribute('value');
		}
		$eventval = '';
		if ($dom->getElementById('__EVENTVALIDATION')) {
			$eventval = $dom->getElementById('__EVENTVALIDATION')->getAttribute('value');
		}
		$viewstate = '';
		if ($dom->getElementById('__VIEWSTATE')) {
			$viewstate = $dom->getElementById('__VIEWSTATE')->getAttribute('value');
		}

		// set POST variables
		$params = array(
			'ButtonDateSearch' => trim('Date Search'),
			'__VIEWSTATEGENERATOR' => trim($viewstategen),
			'__EVENTVALIDATION' => trim($eventval),
			'__EVENTTARGET' => trim($eventtar),
			'__EVENTARGUMENT' => trim($eventarg),
			'__VIEWSTATE' => trim($viewstate),
		);

		// re-query site now with session vars
		$res = call_remote($ngt_url,$params,$ngt_cookiefile,$ngt_cookiejarfile,'POST',$ch);


		// get DOM to load session vars into POST params
		$dom = new domDocument;
		$dom->loadHTML($res);

		if ($dom->getElementById('__VIEWSTATEGENERATOR')) {
			$params['__VIEWSTATEGENERATOR'] = $dom->getElementById('__VIEWSTATEGENERATOR')->getAttribute('value');
		}
		if ($dom->getElementById('__EVENTVALIDATION')) {
			$params['__EVENTVALIDATION'] = $dom->getElementById('__EVENTVALIDATION')->getAttribute('value');
		}
		if ($dom->getElementById('__VIEWSTATE')) {
			$params['__VIEWSTATE'] = $dom->getElementById('__VIEWSTATE')->getAttribute('value');
		}

		// this button just opens the calendar boxes, we're not going to click this button again
		unset($params['ButtonDateSearch']);

		// set calendar start date parameter, then re-query
		$params['__EVENTTARGET'] = 'CalendarStartDate';
		// ngt's calendar numbering starts at Jan 1, 2000; see http://stackoverflow.com/a/2040589/1356496 for days calc method
		$params['__EVENTARGUMENT'] = floor(($search_date - strtotime("2000-01-01")) / (60*60*24));//divide seconds since unix epoch by a day's number of secs

		// re-query with calendar start date set
		$res = call_remote($ngt_url,$params,$ngt_cookiefile,$ngt_cookiejarfile,'POST',$ch);


		// get DOM to load session vars into POST params
		$dom = new domDocument;
		$dom->loadHTML($res);

		if ($dom->getElementById('__VIEWSTATEGENERATOR')) {
			$params['__VIEWSTATEGENERATOR'] = $dom->getElementById('__VIEWSTATEGENERATOR')->getAttribute('value');
		}
		if ($dom->getElementById('__EVENTVALIDATION')) {
			$params['__EVENTVALIDATION'] = $dom->getElementById('__EVENTVALIDATION')->getAttribute('value');
		}
		if ($dom->getElementById('__VIEWSTATE')) {
			$params['__VIEWSTATE'] = $dom->getElementById('__VIEWSTATE')->getAttribute('value');
		}

		// set calendar end date parameter, then re-query
		$params['__EVENTTARGET'] = 'CalendarEndDate';

		// re-query with calendar end date set
		$res = call_remote($ngt_url,$params,$ngt_cookiefile,$ngt_cookiejarfile,'POST',$ch);


		// get DOM to load session vars into POST params
		$dom = new domDocument;
		$dom->loadHTML($res);

		if ($dom->getElementById('__VIEWSTATEGENERATOR')) {
			$params['__VIEWSTATEGENERATOR'] = $dom->getElementById('__VIEWSTATEGENERATOR')->getAttribute('value');
		}
		if ($dom->getElementById('__EVENTVALIDATION')) {
			$params['__EVENTVALIDATION'] = $dom->getElementById('__EVENTVALIDATION')->getAttribute('value');
		}
		if ($dom->getElementById('__VIEWSTATE')) {
			$params['__VIEWSTATE'] = $dom->getElementById('__VIEWSTATE')->getAttribute('value');
		}

		// clear the two variables that we were using to set new options
		$params['__EVENTTARGET'] = '';
		$params['__EVENTARGUMENT'] = '';

		// now search results using the search button
		$params['ButtonFindDate'] = 'Search';
//		print "<pre>".print_r($params,true)."</pre>";

		$res = call_remote($ngt_url,$params,$ngt_cookiefile,$ngt_cookiejarfile,'POST',$ch);
//		echo $res;

		// close locally-born session if not passed in
		if ($NGT_CH===false) { curl_close($ch); }

		return ($res);
	}
?>
