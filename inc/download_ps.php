<?php
	include_once 'dbconnect.php';
	include_once 'call_remote.php';

	$PS_CH = false;
	$PS_ERROR = "";
	$PS_CREDS = '';
	if (isset($_REQUEST['remote_login']) AND isset($_REQUEST['remote_password'])) {
		$PS_CREDS = 'user='.urlencode(trim($_REQUEST['remote_login'])).'&pass='.urlencode(trim($_REQUEST['remote_password'])).'&rlogin=1&submit=Log%20in';
	}

	$PS_ID = false;
	$query = "SELECT id FROM remotes WHERE remote = 'ps'; ";
	$result = qdb($query);
	if (mysqli_num_rows($result)>0) {
		$r = mysqli_fetch_assoc($result);
		$PS_ID = $r['id'];
	}

	function download_ps($search='',$logout=false) {
		global $PS_CH,$PS_ERROR,$PS_CREDS,$PS_ID;

		$search = trim($search);

		// reset every use
		$PS_ERROR = "";

		// cannot copy and paste cookies contents into db for use below! for some reason it loses proper encoding;
		// copy a legitimate cookies file as the filename below, then let the script update the database

		// get cookies from database
		$contents = '';
		$query = "SELECT contents FROM remote_sessions WHERE remoteid = '".$PS_ID."'; ";
		$dbres = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($dbres)>0) {
			$r = mysqli_fetch_assoc($dbres);
			$contents = $r['contents'];
		}

		$ps_base = 'http://www.powersourceonline.com';

		$temp_dir = sys_get_temp_dir();
		// if last character of temp dir is not a slash, add it so we can append file after that
		if (substr($temp_dir,strlen($temp_dir)-1)<>'/') { $temp_dir .= '/'; }
		$cookiefile = $temp_dir.'ps-remote-session-1.txt';
		$cookiejarfile = $cookiefile;

		// even if empty, write to file; session will be checked below
		file_put_contents($cookiefile,$contents);

		if (! $PS_CH) { $PS_CH = curl_init($ps_base); }

		/***** LOGIN ATTEMPT *****/
		if (! $contents OR (! $logout AND ! $search)) {
			if (! $PS_CREDS) {//user hasn't been prompted to login yet
				$PS_ERROR = "Please login to initialize a PowerSource session";
				curl_close($PS_CH);

				$query = "DELETE FROM remote_sessions WHERE remoteid = '".$PS_ID."'; ";
				$dbres = qdb($query);
				return false;
			}

			// initiating a session with PS requires a two-step process to first get a unique token from their site
			$loginUrl = str_replace('http:','https:',$ps_base).'/cgi/en/sign-in.prep';
			$res = call_remote($loginUrl,'',$cookiefile,$cookiejarfile,'GET',$PS_CH);

            // get session token
            $dom = new domDocument;
            $dom->loadHTML($res);
//          $form = $dom->getElementById('signInForm');
            $loginid = $dom->getElementById('Session_LoginId')->getAttribute('value');

            // re-query site now with loginid
			$params = 'Session_Username='.urlencode(trim($_REQUEST['remote_login'])).
				'&Session_Password='.urlencode(trim($_REQUEST['remote_password'])).
				'&Session_Remember_Password=true&Session_LoginId='.$loginid.'&Session_PageName=Index'.
				'&Target_Page='.$target_page.'&JsaTargetPage=true&Session_Remember_Password_Control=true';

            $loginUrl = str_replace('http:','https:',$ps_base).'/cgi/en/session.access.login';
			$res = call_remote($loginUrl,$params,$cookiefile,$cookiejarfile,'POST',$PS_CH);
		} else if ($logout) {/***** LOGOUT *****/
			$res = call_remote($ps_base.'/cgi/en/session.access.logout','',$cookiefile,$cookiejarfile,'GET',$PS_CH);

			$query = "DELETE FROM remote_sessions WHERE remoteid = '".$PS_ID."'; ";
			$dbres = qdb($query);

			curl_close($PS_CH);
		} else if ($search) {/***** PART SEARCH *****/
			$res = call_remote($ps_base.'/iris-multi.search-process-en.jsa','?Q='.urlencode($search),$cookiefile,$cookiejarfile,'POST',$PS_CH);
		}

		/***** FAILED LOGIN, DELETE CREDENTIALS FILE AND RETRY *****/
		if (! $res OR strstr($res,'Login Failed') OR strstr($res,'Access Denied')) {
			if (! $logout AND ! $search) {// user was already prompted and this is a login attempt, and results were invalid
				$PS_ERROR = "Your credentials appear to be invalid, please try logging in again";
				$query = "DELETE FROM remote_sessions WHERE remoteid = '".$PS_ID."'; ";
				$dbres = qdb($query);
				return false;
			}
			$res = call_remote($ps_base,$PS_CREDS,$cookiefile,$cookiejarfile,'POST',$PS_CH);

			if ($search) {
				$res = call_remote($ps_base.'/iris-multi.search-process-en.jsa','?Q='.urlencode($search),$cookiefile,$cookiejarfile,'POST',$PS_CH);

				if (! $res OR $logout OR strstr($res,'Login Failed') OR strstr($res,'Access Denied')) {
					$PS_ERROR = "There was a problem validating your PowerSource session, please check your credentials or contact Technical Support";
					$query = "DELETE FROM remote_sessions WHERE remoteid = '".$PS_ID."'; ";
					$dbres = qdb($query);
					return false;
				}
			}
		}

		// update cookies data in db
		$newcookies = file_get_contents($cookiefile);
		$query = "REPLACE remote_sessions (contents,remoteid) VALUES ('".$newcookies."','".$PS_ID."'); ";
		$dbres = qdb($query) OR die(qe().' '.$query);

		return ($res);
	}
?>
