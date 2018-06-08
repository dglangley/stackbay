<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/call_remote.php';

	$TE_CH = false;
	$TE_ERROR = "";
	$TE_CREDS = '';
	if (isset($_REQUEST['remote_login']) AND isset($_REQUEST['remote_password'])) {
		// 'remote' may be a new problem, if it's not present than TE complains about it missing, but I don't know what value should be here
		$TE_CREDS = 'user='.urlencode(trim($_REQUEST['remote_login'])).'&pass='.urlencode(trim($_REQUEST['remote_password'])).'&rlogin=1&remote=1';//&submit=Log%20in';
	}

	$TE_ID = false;
	$query = "SELECT id FROM remotes WHERE remote = 'te'; ";
	$result = qdb($query);
	if (mysqli_num_rows($result)>0) {
		$r = mysqli_fetch_assoc($result);
		$TE_ID = $r['id'];
	}

	function download_te($search='',$logout=false) {
		global $TE_CH,$TE_ERROR,$TE_CREDS,$TE_ID;

		$search = trim($search);

		// reset every use
		$TE_ERROR = "";

		// cannot copy and paste cookies contents into db for use below! for some reason it loses proper encoding;
		// copy a legitimate cookies file as the filename below, then let the script update the database

		// get cookies from database
		$contents = '';
		$query = "SELECT contents FROM remote_sessions WHERE remoteid = '".$TE_ID."'; ";
		$dbres = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($dbres)>0) {
			$r = mysqli_fetch_assoc($dbres);
			$contents = $r['contents'];
		}

		$te_base = 'http://www.tel-explorer.com';

		$temp_dir = $GLOBALS['TEMP_DIR'];
		$cookiefile = $temp_dir.'te-remote-session-1.txt';
		$cookiejarfile = $cookiefile;

		// even if empty, write to file; session will be checked below
		file_put_contents($cookiefile,$contents);

		if (! $TE_CH) { $TE_CH = curl_init($te_base); }

		/***** LOGIN ATTEMPT *****/
		if (! $contents OR (! $logout AND ! $search)) {
			if (! $TE_CREDS) {//user hasn't been prompted to login yet
				$TE_ERROR = "Please login to initialize a Tel-Explorer session";
				curl_close($TE_CH);

				$query = "DELETE FROM remote_sessions WHERE remoteid = '".$TE_ID."'; ";
				$dbres = qdb($query);
				return false;
			}
			$res = call_remote($te_base.'/Log_on.php',$TE_CREDS,$cookiefile,$cookiejarfile,'POST',$TE_CH);
		} else if ($logout) {/***** LOGOUT *****/
			$res = call_remote($te_base.'/Log_out.php','',$cookiefile,$cookiejarfile,'GET',$TE_CH);

			$query = "DELETE FROM remote_sessions WHERE remoteid = '".$TE_ID."'; ";
			$dbres = qdb($query);

			curl_close($TE_CH);
		} else if ($search) {/***** PART SEARCH *****/
			// gotta hit tel-explorer individually because there's no work-around for their multi-search (when not logged in)
//			$te_base = 'http://tel-explorer.com/Main_Page/Search/Multi_srch_go.php';
			$res = call_remote($te_base.'/Main_Page/Search/Part_srch_go.php','part='.urlencode($search).'&submit=submit',$cookiefile,$cookiejarfile,'POST',$TE_CH);
		}

		// tel-explorer has a workaround where the user can, without being logged in, still extract the required data;
		// however, some pages don't have that workaround, so in the following conditions, use the results where we can get them
		$failed_attempt = extract_results($res);

		/***** FAILED LOGIN, DELETE CREDENTIALS FILE AND RETRY *****/
		if ($failed_attempt) {
			if (! $logout AND ! $search) {// user was already prompted and this is a login attempt, and results were invalid
				$TE_ERROR = "Your credentials appear to be invalid, please try logging in again";
				$query = "DELETE FROM remote_sessions WHERE remoteid = '".$TE_ID."'; ";
				$dbres = qdb($query);
				return false;
			}
			$res = call_remote($te_base,$TE_CREDS,$cookiefile,$cookiejarfile,'POST',$TE_CH);

			if ($search) {
				$res = call_remote($te_base.'/Main_Page/Search/Part_srch_go.php','part='.urlencode($search).'&submit=submit',$cookiefile,$cookiejarfile,'POST',$TE_CH);

				$failed_attempt = extract_results($res);
				if ($failed_attempt) {
					$TE_ERROR = "There was a problem validating your Tel-Explorer session, please check your credentials or contact Technical Support";
					$query = "DELETE FROM remote_sessions WHERE remoteid = '".$TE_ID."'; ";
					$dbres = qdb($query);
					return false;
				}
			}
		}

		// update cookies data in db
		$newcookies = file_get_contents($cookiefile);
		$query = "REPLACE remote_sessions (contents,remoteid) VALUES ('".$newcookies."','".$TE_ID."'); ";
		$dbres = qdb($query) OR die(qe().' '.$query);

		return ($res);
	}

	function extract_results(&$res) {
		if (strstr($res,'window.top.location=("../../Log_on.html")') OR strstr($res,'window.top.location="../../Log_on.html"')) {
			if (! strstr($res,'Result Returned:')) { return true; }

			$res = preg_replace('/window[^L]*Log_on[.]html\"[)]?/','',$res);
		}
		return false;
	}
?>
