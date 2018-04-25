<?php
	include_once 'dbconnect.php';
	include_once 'call_remote.php';

	$OP_CH = false;
	$OP_ERROR = "";

	function download_op($search='',$logout=false,$base='',$remote='', $start = 0, $categoryid = 0) {
		global $OP_CH,$OP_ERROR,$OP_ID;


		$OP_ID = false;
		if($remote) {
			$query = "SELECT id FROM remotes WHERE remote = ".fres($remote)."; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$OP_ID = $r['id'];
			}
		}

		$search = trim($search);

		// reset every use
		$OP_ERROR = "";

		// cannot copy and paste cookies contents into db for use below! for some reason it loses proper encoding;
		// copy a legitimate cookies file as the filename below, then let the script update the database

		// get cookies from database
		$contents = '';
		$query = "SELECT contents FROM remote_sessions WHERE remoteid = '".$OP_ID."'; ";
		$dbres = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($dbres)>0) {
			$r = mysqli_fetch_assoc($dbres);
			$contents = $r['contents'];
		}

		$OP_base = $base;

		$temp_dir = sys_get_temp_dir();
		// if last character of temp dir is not a slash, add it so we can append file after that
		if (substr($temp_dir,strlen($temp_dir)-1)<>'/') { $temp_dir .= '/'; }

		$cookiefile = $temp_dir.$remote.'-remote-session-1.txt';
		$cookiejarfile = $cookiefile;

		// even if empty, write to file; session will be checked below
		file_put_contents($cookiefile,$contents);

		if (! $OP_CH) { $OP_CH = curl_init($OP_base); }

		/***** PART SEARCH *****/
		$res = call_remote($OP_base,($search ? 'search?q=' . urlencode($search) . '&avg_avail=(1__*)&start=' . $start : ''),$cookiefile,$cookiejarfile,'GET',$OP_CH);

		// update cookies data in db
		$newcookies = file_get_contents($cookiefile);
		$query = "REPLACE remote_sessions (contents,remoteid) VALUES ('".$newcookies."','".$OP_ID."'); ";
		$dbres = qedb($query);

		return ($res);
	}
?>
