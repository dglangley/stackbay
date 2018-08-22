<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/call_remote.php';

	$target_page = '';
	$PS_CH = false;
	$PS_ERROR = "";
	$PS_CREDS = '';
	if (isset($_REQUEST['remote_login']) AND isset($_REQUEST['remote_password'])) {
		$PS_CREDS = 'user='.urlencode(trim($_REQUEST['remote_login'])).'&pass='.urlencode(trim($_REQUEST['remote_password'])).'&rlogin=1&submit=Log%20in';
	}

	$PS_ID = false;
	$query = "SELECT id FROM remotes WHERE remote = 'ps'; ";
	$result = qedb($query);
	if (mysqli_num_rows($result)>0) {
		$r = mysqli_fetch_assoc($result);
		$PS_ID = $r['id'];
	}

	function download_ps($search='',$logout=false) {
		global $PS_CH,$PS_ERROR,$PS_CREDS,$PS_ID,$target_page;

		$search = trim($search);

		// reset every use
		$PS_ERROR = "";

		/**************** INSTRUCTIONS ******************/
		// Do NOT copy and paste cookies contents into db for use below! For some reason it loses proper encoding.
		// 1) Logout all PS sessions for user
		// 2) Initialize a session using test.php or other similar method, with remote_login and remote_password being passed in
		// 3) Save tmp cookies file (created below) to local computer
		// 4) Open file directly into contents field (double click field entry) in remote_sessions table using Sequel Pro
		// 5) uncomment this in call_remote.php:              curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		// get cookies from database
		$contents = '';
		$query = "SELECT contents FROM remote_sessions WHERE remoteid = '".$PS_ID."'; ";
		$dbres = qedb($query);
		if (mysqli_num_rows($dbres)>0) {
			$r = mysqli_fetch_assoc($dbres);
			$contents = $r['contents'];
		}

		$ps_base = 'http://www.powersourceonline.com';

		$tdir = $GLOBALS['TEMP_DIR'];
		$cookiefile = $tdir.'ps-remote-session-1.txt';
		$cookiejarfile = $cookiefile;

		/***** INITIALIZE CURL SESSION *****/
		if (! $PS_CH) {
			// if there's no current connection, get cookies (otherwise, we're assuming cookies are already active
			// and we can't do anything with the cookies file anyway, with curl still open).
			// note that if we don't have $contents, try to get it from an existing cookies file
			if (! $contents) {
				$contents = file_get_contents($cookiefile);
			} else {// write to cookies file
				file_put_contents($cookiefile,$contents);
			}
			$PS_CH = curl_init($ps_base);
		}


		$res = '';
		/***** LOGIN ATTEMPT *****/
		if (! $contents OR (! $logout AND ! $search)) {
			if (! $PS_CREDS) {//user hasn't been prompted to login yet
				$PS_ERROR = "Please login to initialize a PowerSource session";
				curl_close($PS_CH);
				$PS_CH = false;//reset connection variable

				$query = "DELETE FROM remote_sessions WHERE remoteid = '".$PS_ID."'; ";
				$dbres = qedb($query);
				return false;
			}

			// initiating a session with PS requires a two-step process to first get a unique token from their site
			$loginUrl = str_replace('http:','https:',$ps_base).'/cgi/en/sign-in.prep';
			$res = call_remote($loginUrl,'',$cookiefile,$cookiejarfile,'GET',$PS_CH);
//			file_put_contents($tdir.date("YmdHis").'-'.preg_replace('/[^[:alnum:]]/','',$loginUrl),$res);

            // get session token
			$dom = new domDocument;
			$dom->loadHTML($res);
//			$form = $dom->getElementById('signInForm');
			if ($dom->getElementById('Session_LoginId')) {
				$loginid = $dom->getElementById('Session_LoginId')->getAttribute('value');

				// re-query site now with loginid
				$params = 'Session_Username='.urlencode(trim($_REQUEST['remote_login'])).
					'&Session_Password='.urlencode(trim($_REQUEST['remote_password'])).
					'&Session_Remember_Password=true&Session_LoginId='.$loginid.'&Session_PageName=Index'.
					'&Target_Page='.$target_page.'&JsaTargetPage=true&Session_Remember_Password_Control=true';

	            $loginUrl = str_replace('http:','https:',$ps_base).'/cgi/en/session.access.login';
				$res = call_remote($loginUrl,$params,$cookiefile,$cookiejarfile,'POST',$PS_CH);
//				file_put_contents($tdir.date("YmdHis").'-'.preg_replace('/[^[:alnum:]]/','',$loginUrl.$params),$res);
			}

			// we have to close the curl session in order for curl to write the cookies file, prior to getting
			// the contents at the end of this script and writing to the db, otherwise it's an empty file
			curl_close($PS_CH);
			$PS_CH = false;//reset so if the login retries below, it will re-initialize a curl session automatically
		} else if ($logout) {/***** LOGOUT *****/
			$res = call_remote($ps_base.'/cgi/en/session.access.logout','',$cookiefile,$cookiejarfile,'GET',$PS_CH);
//			file_put_contents($tdir.date("YmdHis").'-'.preg_replace('/[^[:alnum:]]/','',$ps_base.'/cgi/en/session.access.logout'),$res);

			$query = "DELETE FROM remote_sessions WHERE remoteid = '".$PS_ID."'; ";
			$dbres = qedb($query);

			curl_close($PS_CH);
			$PS_CH = false;//reset connection variable
		} else if ($search) {/***** PART SEARCH *****/
			$res = call_remote($ps_base.'/iris-multi.search-process-en.jsa','?Q='.urlencode($search),$cookiefile,$cookiejarfile,'GET',$PS_CH);
//			file_put_contents($tdir.date("YmdHis").'-'.preg_replace('/[^[:alnum:]]/','',$ps_base.'/iris-multi.search-process-en.jsa?Q='.urlencode($search)),$res);
		}

		/***** FAILED LOGIN, DELETE CREDENTIALS FILE AND RETRY *****/
		if (! $res OR (strstr($res,'Login Failed') AND ! strstr($res,'A session is already in progress'))) {// OR strstr($res,'Access Denied')) {
			if (! $logout AND ! $search) {// user was already prompted and this is a login attempt, and results were invalid
				$PS_ERROR = "Your credentials appear to be invalid, please try logging in again";
				$query = "DELETE FROM remote_sessions WHERE remoteid = '".$PS_ID."'; ";
				$dbres = qedb($query);
				return false;
			}
			$res = call_remote($ps_base,$PS_CREDS,$cookiefile,$cookiejarfile,'POST',$PS_CH);
//			file_put_contents($tdir.date("YmdHis").'-'.preg_replace('/[^[:alnum:]]/','',$ps_base.$PS_CREDS),$res);

			if ($search) {
				$res = call_remote($ps_base.'/iris-multi.search-process-en.jsa','?Q='.urlencode($search),$cookiefile,$cookiejarfile,'GET',$PS_CH);
//				file_put_contents($tdir.date("YmdHis").'-'.preg_replace('/[^[:alnum:]]/','',$ps_base.'/iris-multi.search-process-en.jsa?Q='.urlencode($search)),$res);

				if (! $res OR $logout OR (strstr($res,'Login Failed') AND ! strstr($res,'A session is already in progress'))) {// OR strstr($res,'Access Denied')) {
					$PS_ERROR = "There was a problem validating your PowerSource session, please check your credentials or contact Technical Support";
					$query = "DELETE FROM remote_sessions WHERE remoteid = '".$PS_ID."'; ";
					$dbres = qedb($query);
					return false;
				}
			}
		} else if (strstr($res,'A session is already in progress')) {
			// powersource is weird, for some reason even though it warns of an existing session elsewhere for this user,
			// the message can basically be bypassed by re-trying the home page, which calls up the existing session into
			// the current request, and logs the user in; the second call_remote() below is commented out for now (10/6/16)
			// because I couldn't seem to get it working on a repeat call with the $search, but at least the session is
			// activated using the first call...
			if ($PS_CH) { curl_close($PS_CH); }
			$PS_CH = curl_init($ps_base);
			$res = call_remote($ps_base.'/','',$cookiefile,$cookiejarfile,'GET',$PS_CH,true);
//			file_put_contents($tdir.date("YmdHis").'-'.preg_replace('/[^[:alnum:]]/','',$ps_base).'/',$res);
//			$res = call_remote($ps_base.'/iris-multi.search-process-en.jsa','?Q='.urlencode($search),$cookiefile,$cookiejarfile,'GET',$PS_CH);
		} else if (strstr($res,'Access Denied')) {
			if (strstr($res,'Your profile does not allow you to access the site')) {
				$PS_ERROR = 'Your profile does not allow you to access the site.';
			} else {
				$PS_ERROR = 'Access Denied, please retry on powersource.com for additional details';
			}
			$query = "DELETE FROM remote_sessions WHERE remoteid = '".$PS_ID."'; ";
			$dbres = qedb($query);
			return false;
		}

		// update cookies data in db
		$newcookies = file_get_contents($cookiefile);
		$query = "REPLACE remote_sessions (contents,remoteid) VALUES ('".$newcookies."','".$PS_ID."'); ";
		$dbres = qedb($query);

		return ($res);
	}
?>
