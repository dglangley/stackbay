<?php
	$WLI_GLOBALS = array();
	if (! isset($root_dir)) { $root_dir = ''; }
	if (isset($_SERVER["ROOT_DIR"]) AND ! $root_dir) { $root_dir = $_SERVER["ROOT_DIR"]; }
	else if (! $root_dir) { $root_dir = '/var/www/html'; }
	if (! isset($_SERVER["DEFAULT_DB"]) OR ! $_SERVER["DEFAULT_DB"]) { $_SERVER["DEFAULT_DB"] = 'vmmdb'; }

	if (! isset($_SERVER['RDS_HOSTNAME'])) { die('Host is not set in env globals, could not connect'.chr(10)); }

	$WLI_GLOBALS = array(
		'RDS_HOSTNAME' => $_SERVER['RDS_HOSTNAME'],
		'RDS_USERNAME' => $_SERVER['RDS_USERNAME'],
		'RDS_PASSWORD' => $_SERVER['RDS_PASSWORD'],
		'db' => $_SERVER['DEFAULT_DB'],
		'RDS_PORT' => $_SERVER['RDS_PORT']
	);
	if ($WLI_GLOBALS['RDS_HOSTNAME']<>'localhost') { $WLI_GLOBALS['RDS_PASSWORD'] = 'avenpass02!'; }

	if (! $WLI_GLOBALS['db']) { $WLI_GLOBALS['db'] = 'vmmdb'; }
//	if ($_SERVER["RDS_HOSTNAME"]=='localhost') { $root_dir = '/Users/Shared/WebServer/Sites/marketmanager'; }

	// debugging:
	// 0 = all queries executed
	// 1 = echo INSERT/REPLACE/UPDATE/DELETE, but NO EXECUTION
	// 2 = echo INSERT/REPLACE/UPDATE/DELETE, AND execute
	// 3 = echo ALL queries, but NO EXECUTION
	if (! isset($DEBUG)) { $DEBUG = 0; }

	$WLI = mysqli_connect($WLI_GLOBALS['RDS_HOSTNAME'], $WLI_GLOBALS['RDS_USERNAME'], $WLI_GLOBALS['RDS_PASSWORD'], $WLI_GLOBALS['db'], $WLI_GLOBALS['RDS_PORT']);
	if (mysqli_connect_errno($WLI)) {
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}
	function qdb($query,$db_connection='WLI') { return (mysqli_query($GLOBALS[$db_connection],$query)); }
	function qid($db_connection='WLI') { return (mysqli_insert_id($GLOBALS[$db_connection])); }
	function qar($db_connection='WLI') { return (mysqli_affected_rows($GLOBALS[$db_connection])); }
	function qe($db_connection='WLI') { return (mysqli_error($GLOBALS[$db_connection])); }
	function qfetch($result) { return (mysqli_fetch_assoc($result)); }
	function qnum($result) { return (mysqli_num_rows($result)); }
	function qrow($result,$err='') {
		if (mysqli_num_rows($result)==0) {
			if ($err) {
				die($err);
			} else {
				return array();
			}
		}
		return (mysqli_fetch_assoc($result));
	}
	function qedb($query,$db_connection='WLI') {
		$DEBUG = $GLOBALS['DEBUG'];

		$executor = preg_match('/INSERT|REPLACE|UPDATE|DELETE/',$query);

		if (($executor AND $DEBUG) OR $DEBUG==3) { echo $query.'<BR>'; }

		if ($DEBUG==0 OR $DEBUG==2 OR ! $executor) {
			$result = qdb($query,$db_connection) OR die(qe($db_connection)."<BR>".$query);
			return ($result);
		} else {
			return false;
		}
	}
	function res($str,$db_connection='WLI') { return (mysqli_real_escape_string($GLOBALS[$db_connection],$str)); }
	function fres($str,$repl_str = "NULL",$db_connection='WLI') {
		$str = trim($str);
		$output = (! empty($str)) ? "'".res($str,$db_connection)."'" : $repl_str;

		return ($output);
	}

	if (isset($NO_CACHE) AND $NO_CACHE===true) {
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate( 'D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache'); 
	} else {
		ini_set('session.cache_limiter','public');
		session_cache_limiter(false);
	}
	// requires login, see below
	if (! isset($LOCKED)) { $LOCKED = false; }

	set_include_path(get_include_path() . PATH_SEPARATOR . $_SERVER["ROOT_DIR"].'/inc/google-api-php-client/src');
	date_default_timezone_set('America/Los_Angeles');

	$DEV_ENV = false;
	if (isset($_SERVER["SERVER_NAME"]) AND $_SERVER["SERVER_NAME"]<>'www.stackbay.com') { $DEV_ENV = true; }	

	$today = date("Y-m-d");
	$now = $today.' '.date("H:i:s");
	//mktime deprecated
	//$timestamp = mktime();
	$timestamp = time();

	//Declaring all Globally used elements
	$U = array('name'=>'','email'=>'','phone'=>'','id'=>0, 'username' => '', 'status'=>'', 'hourly_rate'=>'', 'companyid'=>0);
	$USER_ROLES = array();
		$PAGE_ROLES = array();
	$ROLES = array();
	$ALERTS = array();//global errors array for output to alert modal (see inc/footer.php)
	
	//Important pages that always must have minimum admin privileges
	$ADMIN_PAGE = array('edit_user.php', 'page_permissions.php', 'password.php');
	
	$session_ttl = (7 * 24 * 60 * 60);
	session_set_cookie_params(time() + $session_ttl);
	ini_set('session.gc_maxlifetime',$session_ttl);
	//Start the Session or call existing ones
	session_start();


	//This gets the current page that the user is on including the extension
	$pageName = basename($_SERVER['PHP_SELF']);

	function is_loggedin($force_userid=0,$force_usertoken='') {
			global $U, $ROLES, $PAGE_ROLES, $USER_ROLES, $pageName;

		$now = time();
		$userid = 0;
		$user_token = '';
		//Get the users id from the user token
		if (! $force_userid AND isset($_SESSION["user_token"])) { 
			$user_token = $_SESSION["user_token"];
			$query = "SELECT userid FROM user_tokens WHERE user_token = '".res($user_token)."' LIMIT 0,1; ";
			$result = qedb($query);
				if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$userid = $r["userid"]; 
			}
		} else { 
			//If the user session doesn't exists then they haven't logged in so return instant false
			return false; 
		}

		if (isset($_SESSION['expiry']) && $now > $_SESSION['expiry']) {
		    // this session has worn out its welcome; kill it and start a brand new one
		    session_unset();
		    session_destroy();
		    session_start();

		    header('Location: /?timeout=true');
			exit;
		} else {
			//If session is not expired then increase the session time by a week for this computer
			$_SESSION['expiry'] = time() + (7 * 24 * 60 * 60);
		}

/*
		$query = "SELECT users.id, users.contactid, contacts.name, user_tokens.user_token, user_tokens.userid ";
		if ($force_userid AND $force_usertoken) { $query .= "FROM contacts, users LEFT JOIN user_tokens ON users.id = user_tokens.userid WHERE "; }
		else { $query .= "FROM contacts, users, user_tokens WHERE users.id = user_tokens.userid AND contacts.id = users.contactid AND "; }
		$query .= "users.id = '".res($userid)."' AND user_token = '".res($user_token)."' ";
		$query .= "AND (user_tokens.expiry IS NULL OR user_tokens.expiry >= '".$GLOBALS['now']."') ";
*/
		$query = "SELECT users.id, users.contactid, contacts.name, contacts.status, users.hourly_rate, contacts.companyid ";
		$query .= "FROM users, contacts ";
		$query .= "WHERE users.id = '".res($userid)."' AND users.contactid = contacts.id; ";
		$result = qedb($query);

		// if user is not registered in db, reset cookies/session
		// Make sure that the session exists in the first place
		if (mysqli_num_rows($result)==0) {

			// Unset all of the session variables.
			$_SESSION = array();

			// If it's desired to kill the session, also delete the session cookie.
			// Note: This will destroy the session, and not just the session data!
			if (ini_get("session.use_cookies")) {
			    $params = session_get_cookie_params();
			    setcookie(session_name(), '', time() - 42000,
			        $params["path"], $params["domain"],
			        $params["secure"], $params["httponly"]
			    );
			}
			session_destroy();

			return false;

		//If the user exists there should only be 1 record for the user not multiple
		} else {

			$U = mysqli_fetch_assoc($result);

			//$U['email'] = '';
			$U['phone'] = '';
			$U['username'] = '';
			$U['email'] = $userid;
			
			$query2 = "SELECT email FROM emails WHERE contactid = '".$U['contactid']."' ORDER BY IF(type='Work',0,1) LIMIT 0,1; ";
			$result2 = qedb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$U['email'] = $r2['email'];
			}
			
			$query2 = "SELECT phone FROM phones WHERE contactid = '".$U['contactid']."' ORDER BY IF(type='Office',0,1) LIMIT 0,1; ";
			$result2 = qedb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$U['phone'] = $r2['phone'];
			}

			$query2 = "SELECT username FROM usernames WHERE userid = '".res($userid)."' LIMIT 0,1; ";
			$result2 = qedb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$U['username'] = $r2['username'];
			}
			
/*
			$query2 = "SELECT status FROM contacts WHERE id = '".$U['contactid']."' LIMIT 0,1; ";
			$result2 = qedb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$U['status'] = $r2['status'];
			}
*/

			//Create a global array of all the current logged in user privileges
			$query2 = "SELECT * FROM user_roles WHERE userid = '" . res($userid) . "'";
			$result2 = qedb($query2);

			if (mysqli_num_rows($result2)>0) {
				while ($row = $result2->fetch_assoc()) {
				  $USER_ROLES[] = $row['privilegeid'];
				}
			}

			//Create a global array of all privileges and the id linked to the privilege
			$query2 = "SELECT * FROM user_privileges";
			$result2 = qedb($query2);

			if (mysqli_num_rows($result2)>0) {
				while ($row = $result2->fetch_assoc()) {
				  $ROLES[$row['id']] = $row['privilege'];
				}
			}

			//Create a global array of the privileges for the current page the user is on
			$query2 = "SELECT * FROM page_roles WHERE page = '" . res($pageName) . "'";
			$result2 = qedb($query2);

			if (mysqli_num_rows($result2)>0) {
				while ($row = $result2->fetch_assoc()) {
					  $PAGE_ROLES[] = $row['privilegeid'];
				}
			}

			// see http://stackoverflow.com/questions/15686572/two-simultaneous-ajax-requests-wont-run-in-parallel
			session_write_close();

			return true;
		}

		// see http://stackoverflow.com/questions/15686572/two-simultaneous-ajax-requests-wont-run-in-parallel
		session_write_close();

		//If all else fails then we will assume the login failed
		return false;
	}

	$GMAIL_USERID = 0;
	function setGoogleAccessToken($userid) {
		global $ACCESS_TOKEN,$REFRESH_TOKEN,$GMAIL_USERID,$timestamp;

		// we use this globally in format_email(), for example, to set signature variables like name, phone, etc
		$GMAIL_USERID = $userid;

		$query = "SELECT access_token, token_type, expires_in, created, refresh_token FROM google_tokens ";
		$query .= "WHERE userid = '".$userid."' ORDER BY id DESC; ";
		$result = qedb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$exp_time = $r['created']+$r['expires_in'];
			if ($timestamp<$exp_time) {
				$ACCESS_TOKEN = json_encode($r);
				break;
			} else if ($r['refresh_token']) {
				$REFRESH_TOKEN = $r['refresh_token'];
				break;
			}
		}
	}
	
	//This function is here to check and make sure upon initial login of any user that the page permissions are set correctly and not missing.
	//Putting it on login so it is only invoked once instead of multiple times
	function checkPagePermissions() {
		global $WLI, $ADMIN_PAGE;
		//Find the admin ID from user_privileges table
		$query = "SELECT id FROM user_privileges WHERE privilege='Administration'";
		$result = qedb($query);
		
		//Set the variable admin ID
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$adminid = $r['id'];
		}
		
		//print_r($ADMIN_PAGE);
		
		foreach($ADMIN_PAGE as $pageName) {
			// Prepare and Bind for Privileges
			$stmt = $WLI->prepare('
				REPLACE page_roles (page, privilegeid) 
					VALUES (?, ?) 
			');
			//s = string, i - integer, d = double, b = blob for params of mysqli
			$stmt->bind_param("si", $pageName, $privid);
			//Package it all and execute the query
			$privid = $adminid;
			$stmt->execute();
			$stmt->close();
		}
	}

	//Check if logged in
	$is_loggedin = is_loggedin();

	//Check if signin is required or check if the user status is alive
	if((!$is_loggedin AND ! strstr($_SERVER["PHP_SELF"],'auto/')) || (isset($U['status']) && $U['status'] == 'Inactive')) {
		checkPagePermissions();
		require_once $_SERVER["ROOT_DIR"].'/signin.php';
	}
	
	//print_r($_SESSION);
	
	//Check if user needs to reset password
	if(isset($_SESSION['init'])) {
		//Check to see if the user is logging in for the first time
		require_once 'reset.php';
		exit;
	}

	//Check if any of the permissions intersect and make sure page roles are not empty, if user has no permission then redirect to the no access page
	//Array 1 stand for admin in which they have permission to everything no matter what
	if(!empty($PAGE_ROLES) && !array_intersect($USER_ROLES, $PAGE_ROLES) && !in_array("1", $USER_ROLES)) {
		header('Location: /permission.php');
		exit;
	}

	$TEMP_DIR = sys_get_temp_dir();
	if (substr($TEMP_DIR,strlen($TEMP_DIR)-1,1)<>'/') { $TEMP_DIR .= '/'; }

	$ACCESS_TOKEN = false;
	$REFRESH_TOKEN = false;
	if ($U['id']>0) {
		setGoogleAccessToken($U['id']);
	}

	function get_browser_name() {
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'Opera';
		elseif (strpos($user_agent, 'Edge')) return 'Edge';
		elseif (strpos($user_agent, 'Chrome')) return 'Chrome';
		elseif (strpos($user_agent, 'Safari')) return 'Safari';
		elseif (strpos($user_agent, 'Firefox')) return 'Firefox';
		elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Internet Explorer';
	
		return 'Other';
	}
	if (isset($_REQUEST['SEARCH_MODE']) AND $_REQUEST['SEARCH_MODE']) {
		$SEARCH_MODE = preg_replace('/^(https?:\/\/[[:alnum:]_.-]*)(\/[[:alnum:]_.-]*)(\?.*)?$/','$2',$_REQUEST['SEARCH_MODE']);
	} else if (isset($_COOKIE['SEARCH_MODE'])) {
		$SEARCH_MODE = preg_replace('/^(https?:\/\/[[:alnum:]_.-]*)(\/[[:alnum:]_.-]*)(\?.*)?$/','$2',$_COOKIE['SEARCH_MODE']);
	} else {
		$SEARCH_MODE = '/';//default
	}

	function logUser() {
		global $U;

		if (! $U['id']) { return; }

		$url = $_SERVER["REQUEST_URI"];
		if (stristr($url,'/json/') OR stristr($url,'/inc/') OR stristr($url,'/auto/') OR stristr($url,'/img/')) { return; }

		$method = 'GET';
		if ($_SERVER["REQUEST_METHOD"]=='POST') {
			$method = 'POST';

			$kv = array();
			foreach ($_POST as $k => $v) {
				if (is_array($v)):
					foreach ($v as $v2) {
						if (is_array($v2)):
							$temp = array();
							foreach ($v2 as $v3) {
								$temp[] = trim($v3);
							}
							$kv[] = "$k=" . join("|", $temp);
						else:
							$kv[] = "$k=" . trim($v2);
						endif;
					}
				else:
					$kv[] = "$k=".trim($v);
				endif;
			}
			$url .= '?'.join("&", $kv);
		}

		$query = "REPLACE userlog (userid, datetime, url, method) ";
		$query .= "VALUES ('".$U['id']."', '".$GLOBALS['now']."', '".res(trim($url))."', '".$method."'); ";
		$result = qedb($query);
	}
	logUser();

	// version control for css and js includes
	$V = '20180209';
?>
