<?php
	include_once 'dbconnect.php';
	include_once 'call_remote.php';

	$RES_CH = false;
	$RES_ERROR = "";

	function download_res($search='',$logout=false,$base='',$remote='', $letter = '') {
		global $RES_CH,$RES_ERROR,$RES_ID;

		// Set the limit of results we want to scrub
		$limit = 50000;

		$RES_ID = false;
		if($remote) {
			$query = "SELECT id FROM remotes WHERE remote = ".fres($remote)."; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$RES_ID = $r['id'];
			}
		}

		$search = trim($search);

		// reset every use
		$RES_ERROR = "";

		// cannot copy and paste cookies contents into db for use below! for some reason it loses proper encoding;
		// copy a legitimate cookies file as the filename below, then let the script update the database

		// get cookies from database
		$contents = '';
		$query = "SELECT contents FROM remote_sessions WHERE remoteid = '".$RES_ID."'; ";
		$dbres = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($dbres)>0) {
			$r = mysqli_fetch_assoc($dbres);
			$contents = $r['contents'];
		}

		$RES_base = $base;

		$temp_dir = sys_get_temp_dir();
		// if last character of temp dir is not a slash, add it so we can append file after that
		if (substr($temp_dir,strlen($temp_dir)-1)<>'/') { $temp_dir .= '/'; }

		$cookiefile = $temp_dir.$remote.'-remote-session-1.txt';
		$cookiejarfile = $cookiefile;

		// even if empty, write to file; session will be checked below
		file_put_contents($cookiefile,$contents);

		if (! $RES_CH) { $RES_CH = curl_init($RES_base); }

		/***** PART SEARCH *****/
		$res = call_remote($RES_base . 'part-search?limit='.$limit.((! $search) ? '&partnum=' . ($letter ? : '0') : ''),($search ? '&search=' . urlencode($search) . '&view=search' : ''),$cookiefile,$cookiejarfile,'GET',$RES_CH);

		// update cookies data in db
		$newcookies = file_get_contents($cookiefile);
		$query = "REPLACE remote_sessions (contents,remoteid) VALUES ('".$newcookies."','".$RES_ID."'); ";
		$dbres = qedb($query);

		return ($res);
	}
?>
