<?php
	include_once 'dbconnect.php';
	include_once 'call_remote.php';

	$EXCEL_CH = false;
	$EXCEL_ERROR = "";

	$EXCEL_ID = false;
	$query = "SELECT id FROM remotes WHERE remote = 'excel'; ";
	$result = qdb($query);
	if (mysqli_num_rows($result)>0) {
		$r = mysqli_fetch_assoc($result);
		$EXCEL_ID = $r['id'];
	}

	function download_excel($search='',$logout=false) {
		global $EXCEL_CH,$EXCEL_ERROR,$EXCEL_ID;

		$search = trim($search);

		// reset every use
		$EXCEL_ERROR = "";

		// cannot copy and paste cookies contents into db for use below! for some reason it loses proper encoding;
		// copy a legitimate cookies file as the filename below, then let the script update the database

		// get cookies from database
		$contents = '';
		$query = "SELECT contents FROM remote_sessions WHERE remoteid = '".$EXCEL_ID."'; ";
		$dbres = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($dbres)>0) {
			$r = mysqli_fetch_assoc($dbres);
			$contents = $r['contents'];
		}

		$excel_base = 'http://www.excel-telco.com';

		$temp_dir = sys_get_temp_dir();
		// if last character of temp dir is not a slash, add it so we can append file after that
		if (substr($temp_dir,strlen($temp_dir)-1)<>'/') { $temp_dir .= '/'; }
		$cookiefile = $temp_dir.'excel-remote-session-1.txt';
		$cookiejarfile = $cookiefile;

		// even if empty, write to file; session will be checked below
		file_put_contents($cookiefile,$contents);

		if (! $EXCEL_CH) { $EXCEL_CH = curl_init($excel_base); }

		/***** PART SEARCH *****/
		$res = call_remote($excel_base.'/inventory/','searchcriteria='.urlencode($search).'&ispost=1&vendor=&submit=&citycode=murcielagoW:]BeeG',$cookiefile,$cookiejarfile,'POST',$EXCEL_CH);

		// update cookies data in db
		$newcookies = file_get_contents($cookiefile);
		$query = "REPLACE remote_sessions (contents,remoteid) VALUES ('".$newcookies."','".$EXCEL_ID."'); ";
		$dbres = qdb($query) OR die(qe().' '.$query);

		return ($res);
	}
?>
