<?php
	include_once 'dbconnect.php';
	include_once 'call_remote.php';

	$MDG_CH = false;
	$MDG_ERROR = "";

	function download_mdg($search='',$logout=false,$base='',$remote='', $page = 0) {
		global $MDG_CH,$MDG_ERROR,$MDG_ID;


		$MDG_ID = false;
		if($remote) {
			$query = "SELECT id FROM remotes WHERE remote = ".fres($remote)."; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$MDG_ID = $r['id'];
			}
		}

		$search = trim($search);

		// reset every use
		$MDG_ERROR = "";

		// cannot copy and paste cookies contents into db for use below! for some reason it loses proper encoding;
		// copy a legitimate cookies file as the filename below, then let the script update the database

		// get cookies from database
		$contents = '';
		$query = "SELECT contents FROM remote_sessions WHERE remoteid = '".$MDG_ID."'; ";
		$dbres = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($dbres)>0) {
			$r = mysqli_fetch_assoc($dbres);
			$contents = $r['contents'];
		}

		$MDG_base = $base;

		$temp_dir = sys_get_temp_dir();
		// if last character of temp dir is not a slash, add it so we can append file after that
		if (substr($temp_dir,strlen($temp_dir)-1)<>'/') { $temp_dir .= '/'; }

		$cookiefile = $temp_dir.$remote.'-remote-session-1.txt';
		$cookiejarfile = $cookiefile;

		// even if empty, write to file; session will be checked below
		file_put_contents($cookiefile,$contents);

		if (! $MDG_CH) { $MDG_CH = curl_init($MDG_base); }

		/***** PART SEARCH *****/
		$res = call_remote($MDG_base . (!$search ? '?page=' . $page : ''),($search ? 'search?q=' . $search . '&page=' . $page : ''),$cookiefile,$cookiejarfile,'GET',$MDG_CH);

		// update cookies data in db
		$newcookies = file_get_contents($cookiefile);
		$query = "REPLACE remote_sessions (contents,remoteid) VALUES ('".$newcookies."','".$API_ID."'); ";
		$dbres = qedb($query);

		return ($res);
	}
?>
