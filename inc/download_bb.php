<?php
	include_once 'dbconnect.php';
	include_once 'call_remote.php';

	$BB_CH = false;
	$BB_ERROR = "";
	$BB_CREDS = '';
	if (isset($_REQUEST['remote_login']) AND isset($_REQUEST['remote_password'])) {
		$BB_CREDS = 'login='.urlencode(trim($_REQUEST['remote_login'])).'&password='.urlencode(trim($_REQUEST['remote_password'])).'&remember=1';
	}

	$BB_ID = false;
	$query = "SELECT id FROM remotes WHERE remote = 'bb'; ";
	$result = qdb($query);
	if (mysqli_num_rows($result)>0) {
		$r = mysqli_fetch_assoc($result);
		$BB_ID = $r['id'];
	}

	function download_bb($search='',$logout=false) {
		global $BB_CH,$BB_ERROR,$BB_CREDS,$BB_ID;

		$search = trim($search);

		// reset every use
		$BB_ERROR = "";

		// cannot copy and paste cookies contents into db for use below! for some reason it loses proper encoding;
		// copy a legitimate cookies file as the filename below, then let the script update the database

		// get cookies from database
		$contents = '';
		$query = "SELECT contents FROM remote_sessions WHERE remoteid = '".$BB_ID."'; ";
		$dbres = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($dbres)>0) {
			$r = mysqli_fetch_assoc($dbres);
			$contents = $r['contents'];
		}

		$bb_base = 'http://members.brokerbin.com';//main.php';//?loc=partkey&parts=nt6x52aa&clm=partclei

		$temp_dir = sys_get_temp_dir();
		// if last character of temp dir is not a slash, add it so we can append file after that
		if (substr($temp_dir,strlen($temp_dir)-1)<>'/') { $temp_dir .= '/'; }
		$cookiefile = $temp_dir.'bb-remote-session-1.txt';
		$cookiejarfile = $cookiefile;

		// even if empty, write to file; session will be checked below
		file_put_contents($cookiefile,$contents);

		if (! $BB_CH) { $BB_CH = curl_init($bb_base); }

		// login attempt
		if (! $contents OR (! $logout AND ! $search)) {
			if (! $BB_CREDS) {//user hasn't been prompted to login yet
				$BB_ERROR = "Please login to initialize a BrokerBin session";
				curl_close($BB_CH);

				$query = "DELETE FROM remote_sessions WHERE remoteid = '".$BB_ID."'; ";
				$dbres = qdb($query);
				return false;
			}
			$res = call_remote($bb_base,$BB_CREDS,$cookiefile,$cookiejarfile,'POST',$BB_CH);
		} else if ($logout) {
			$res = call_remote($bb_base,'?LogOut=1',$cookiefile,$cookiejarfile,'GET',$BB_CH);

			$query = "DELETE FROM remote_sessions WHERE remoteid = '".$BB_ID."'; ";
			$dbres = qdb($query);

			curl_close($BB_CH);
		} else if ($search) {
			$res = call_remote($bb_base,'/main.php?loc=partkey&clm=partclei&parts='.urlencode($search),$cookiefile,$cookiejarfile,'GET',$BB_CH);
		}

		// failed login, delete credentials file and retry
		if (strstr($res,'<h1>Members Access - Sign In</h1>')) {
			if (! $logout AND ! $search) {// user was already prompted and this is a login attempt, and results were invalid
				$BB_ERROR = "Your credentials appear to be invalid, please try logging in again";
				$query = "DELETE FROM remote_sessions WHERE remoteid = '".$BB_ID."'; ";
				$dbres = qdb($query);
				return false;
			}
			$res = call_remote($bb_base,$BB_CREDS,$cookiefile,$cookiejarfile,'POST',$BB_CH);

			if ($search) {
				$res = call_remote($bb_base,'/main.php?loc=partkey&clm=partclei&parts='.urlencode($search),$cookiefile,$cookiejarfile,'GET',$BB_CH);

				if (strstr($res,'<h1>Members Access - Sign In</h1>')) {
					$BB_ERROR = "There was a problem validating your BrokerBin session, please check your credentials or contact Technical Support";
					$query = "DELETE FROM remote_sessions WHERE remoteid = '".$BB_ID."'; ";
					$dbres = qdb($query);
					return false;
				}
			}
		}

		// update cookies data in db
		$newcookies = file_get_contents($cookiefile);
		$query = "REPLACE remote_sessions (contents,remoteid) VALUES ('".$newcookies."','".$BB_ID."'); ";
		$dbres = qdb($query) OR die(qe().' '.$query);

		return ($res);
	}
?>
