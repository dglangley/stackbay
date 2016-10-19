<?php
	function qdb($query,$db_connection='WLI') { return (mysqli_query($GLOBALS[$db_connection],$query)); }
	function qid($db_connection='WLI') { return (mysqli_insert_id($GLOBALS[$db_connection])); }
	function qe($db_connection='WLI') { return (mysqli_error($GLOBALS[$db_connection])); }
	function res($str,$db_connection='WLI') { return (mysqli_real_escape_string($GLOBALS[$db_connection],$str)); }
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

	//This is Andrews DB connection stuff
	// $WLI_GLOBALS = array(
	// 	'RDS_HOSTNAME' => 'localhost',
	// 	'RDS_USERNAME' =>'ven_admin',
	// 	'RDS_PASSWORD' => 'Hob@rt777',
	// 	'db' => 'vmmdb',
	// 	'RDS_PORT' => '3306'
	// );
	if (! $WLI_GLOBALS['db']) { $WLI_GLOBALS['db'] = 'vmmdb'; }
//	if ($_SERVER["RDS_HOSTNAME"]=='localhost') { $root_dir = '/Users/Shared/WebServer/Sites/marketmanager'; }

	$WLI = mysqli_connect($WLI_GLOBALS['RDS_HOSTNAME'], $WLI_GLOBALS['RDS_USERNAME'], $WLI_GLOBALS['RDS_PASSWORD'], $WLI_GLOBALS['db'], $WLI_GLOBALS['RDS_PORT']);
	if (mysqli_connect_errno($WLI)) {
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}

	if (isset($NO_CACHE) AND $NO_CACHE===true) {
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate( 'D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache'); 
	}
	// requires login, see below
	if (! isset($LOCKED)) { $LOCKED = false; }

	set_include_path(get_include_path() . PATH_SEPARATOR . $_SERVER["ROOT_DIR"].'/inc/google-api-php-client/src');
	date_default_timezone_set('America/Los_Angeles');

	$DEV_ENV = false;
	if (isset($_SERVER["SERVER_NAME"]) AND $_SERVER["SERVER_NAME"]=='marketmanager.local') { $DEV_ENV = true; }
$DEV_ENV = true;

	$today = date("Y-m-d");
	$now = $today.' '.date("H:i:s");
	$timestamp = mktime();

	//Declaring all Globally used elements
	$U = array('name'=>'','email'=>'','phone'=>'','id'=>0, 'username' => '');
	$USER_ROLES;
	$PAGE_ROLES;
	$ROLES;


	//This gets the current page that the user is on including the extension
	$pageName = basename($_SERVER['PHP_SELF']);

	function is_loggedin($force_userid=0,$force_usertoken='') {
		global $U;
		global $ROLES;
		global $PAGE_ROLES;
		global $USER_ROLES;
		global $pageName;

		//Start the Session or call existing ones
		session_start();

		//get the current time
		$now = time();

		$userid = 0;
		$user_token = $_SESSION["user_token"];
		//Get the users id from the user token
		if (! $force_userid AND isset($_SESSION["user_token"])) { 
			$query = "SELECT userid FROM user_tokens WHERE user_token = '".res($user_token)."' LIMIT 0,1; ";
			$result2 = qdb($query);
				if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$userid = $r2["userid"]; 
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

		    header('Location: index.php?timeout=true');
			exit;
		} else {
			//If session is not expired then increase the session time by a week for this computer
			//$_SESSION['expiry'] = $now + (7 * 24 * 60 * 60);
		}

		$to_sec = time()-(60*60);

		$query = "SELECT users.id, users.contactid, contacts.name, user_tokens.user_token, user_tokens.userid ";
		if ($force_userid AND $force_usertoken) { $query .= "FROM contacts, users LEFT JOIN user_tokens ON users.id = user_tokens.userid WHERE "; }
		else { $query .= "FROM contacts, users, user_tokens WHERE users.id = user_tokens.userid AND contacts.id = users.contactid AND "; }
		$query .= "users.id = '".res($userid)."' AND user_token = '".res($user_token)."' ";
		$query .= "AND (user_tokens.expiry IS NULL OR user_tokens.expiry >= '".$GLOBALS['now']."') ";
		$query = "SELECT users.id, users.contactid, contacts.name FROM users, contacts WHERE users.id = '".res($userid)."' AND users.contactid = contacts.id; ";
		$result = qdb($query);

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

			$U['email'] = '';
			$U['phone'] = '';
			$U['username'] = '';
			$U['email'] = $userid;
			$query2 = "SELECT email FROM emails WHERE contactid = '".$U['contactid']."' ORDER BY IF(type='Work',0,1) LIMIT 0,1; ";
			$result2 = qdb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$U['email'] = $r2['email'];
			}
			$query2 = "SELECT phone FROM phones WHERE contactid = '".$U['contactid']."' ORDER BY IF(type='Office',0,1) LIMIT 0,1; ";
			$result2 = qdb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$U['phone'] = $r2['phone'];
			}
			$query2 = "SELECT username FROM usernames WHERE userid = '".res($userid)."' LIMIT 0,1; ";
			$result2 = qdb($query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$U['username'] = $r2['username'];
			}

			//Create a global array of all the current logged in users privileges
			$query2 = "SELECT * FROM user_roles WHERE userid = '" . res($userid) . "'";
			$result2 = qdb($query2);

			if (mysqli_num_rows($result2)>0) {
				while ($row = $result2->fetch_assoc()) {
				  $USER_ROLES[] = $row['privilegeid'];
				}
			}

			//Create a global array of all privileges and the id linked to the privilege
			$query2 = "SELECT * FROM user_privileges";
			$result2 = qdb($query2);

			if (mysqli_num_rows($result2)>0) {
				while ($row = $result2->fetch_assoc()) {
				  $ROLES[$row['id']] = $row['privilege'];
				}
			}

			//Create a global array of the privileges for the current page the user is on
			$query2 = "SELECT * FROM page_roles WHERE page = '" . res($pageName) . "'";
			$result2 = qdb($query2);

			if (mysqli_num_rows($result2)>0) {
				while ($row = $result2->fetch_assoc()) {
				  $PAGE_ROLES[] = $row['privilegeid'];
				}
			}


			return true;
		}

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
		$result = qdb($query);
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

/*
	$E = array(
		0 => array('type'=>'unknown','message'=>'SUCCESS'),
		1 => array('type'=>'email','message'=>'Email is invalid or does not exist'),
		2 => array('type'=>'email','message'=>'Email addresses do not match'),
		3 => array('type'=>'password','message'=>'Password is invalid or does not match'),
		4 => array('type'=>'email','message'=>'User is already registered'),
		5 => array('type'=>'password','message'=>'Passwords do not match'),
		6 => array('type'=>'password','message'=>'Password is too short (min 6 chars)'),
		7 => array('type'=>'host','message'=>'Entry is missing or invalid'),
		8 => array('type'=>'phone','message'=>'Phone number is invalid'),
		9 => array('type'=>'unknown','message'=>'An unknown error occurred, please contact the administrator'),
		10 => array('type'=>'date','message'=>'Date/time occurs in the past'),
		11 => array('type'=>'','message'=>'Company name is missing or invalid'),
		12 => array('type'=>'','message'=>'Postal Code is required to process your application'),
		13 => array('type'=>'','message'=>'Host Name is required to process your application'),
		14 => array('type'=>'','message'=>'File has already been uploaded!'),
		15 => array('type'=>'unknown','message'=>'Missing data!'),
		16 => array('type'=>'unknown','message'=>'Your calendar service is not properly setup, please check your profile settings'),
		17 => array('type'=>'unknown','message'=>'This is not a registered walo host'),
		18 => array('type'=>'','message'=>'Upload failed; please try again later'),
		19 => array('type'=>'user','message'=>'You must be signed in'),
		20 => array('type'=>'user','message'=>'First or last name is missing or invalid'),
		21 => array('type'=>'unknown','message'=>'The data passed in is invalid or corrupt, please try again'),
		22 => array('type'=>'','message'=>'You must be in closer proximity to submit a wait time'),
		23 => array('type'=>'','message'=>'Did you mean to say that the wait is MORE than 0:00?'),
		24 => array('type'=>'','message'=>'Wait awhile to suggest another wait time for the same place, or delete your past suggestion under Rewards.'),
		25 => array('type'=>'','message'=>'You do not have access to this resource!'),
		26 => array('type'=>'','message'=>'We cannot accept this Party Size, please try again'),
		27 => array('type'=>'','message'=>'You have exceeded the allowed usage for this service!'),
	);

	if ($LOCKED AND ! is_loggedin()) {
		if (isset($_REQUEST['json'])) {
			echo json_encode(array('code'=>19,'message'=>$E[19]['message']));
		} else {
			header('Location: /');
		}
		exit;
	}
*/
	$is_loggedin = is_loggedin();

	if(!$is_loggedin) {
		require_once 'signin.php';
		exit;
	} else if(isset($_SESSION['init'])) {
		//Check to see if the user is logging in for the first time
		require_once 'reset.php';
		exit;
	//Check if any of the permissions intersect and make sure page roles are not empty, if user has no permission then redirect to the no access page
	} else if(!array_intersect($USER_ROLES, $PAGE_ROLES) && !empty($PAGE_ROLES)) {
		header('Location: permission.php');
		exit;
	}

	$ACCESS_TOKEN = false;
	$REFRESH_TOKEN = false;
	if ($U['id']>0) {
		setGoogleAccessToken($U['id']);
	}

	// version control for css and js includes
	$V = '20161005';
?>
