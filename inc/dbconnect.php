<?php
	function qdb($query,$db_connection='WLI') { return (mysqli_query($GLOBALS[$db_connection],$query)); }
	function qid($db_connection='WLI') { return (mysqli_insert_id($GLOBALS[$db_connection])); }
	function qe($db_connection='WLI') { return (mysqli_error($GLOBALS[$db_connection])); }
	function res($str,$db_connection='WLI') { return (mysqli_real_escape_string($GLOBALS[$db_connection],$str)); }
	$WLI_GLOBALS = array();
	if (isset($_SERVER["ROOT_DIR"]) AND (! isset($root_dir) OR ! $root_dir)) { $root_dir = $_SERVER["ROOT_DIR"]; }
	else if (! $root_dir) { $root_dir = '/var/www/html'; }
	if (! isset($_SERVER["DEFAULT_DB"]) OR ! $_SERVER["DEFAULT_DB"]) { $_SERVER["DEFAULT_DB"] = 'vmmdb'; }

	if (! isset($_SERVER['RDS_HOSTNAME'])) { die('could not connect to host'.chr(10)); }

	$WLI_GLOBALS = array(
		'RDS_HOSTNAME' => $_SERVER['RDS_HOSTNAME'],
		'RDS_USERNAME' => $_SERVER['RDS_USERNAME'],
		'RDS_PASSWORD' => $_SERVER['RDS_PASSWORD'],
		'db' => $_SERVER['DEFAULT_DB'],
		'RDS_PORT' => $_SERVER['RDS_PORT']
	);
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

	set_include_path(get_include_path() . PATH_SEPARATOR . $_SERVER["DOCUMENT_ROOT"].'/inc/google-api-php-client/src');
	date_default_timezone_set('America/Los_Angeles');

	$DEV_ENV = false;
	if ($_SERVER["SERVER_NAME"]=='marketmanager.local') { $DEV_ENV = true; }
$DEV_ENV = true;

	$today = date("Y-m-d");
	$now = $today.' '.date("H:i:s");
	$timestamp = mktime();

	$U = array('name'=>'','email'=>'','phone'=>'','id'=>0);
//	$U = array('name'=>'David Langley','email'=>'david@ven-tel.com','phone'=>'(805) 824-0136','id'=>1);

	function is_loggedin($force_userid=0,$force_usertoken='') {
		global $U;

		$userid = 0;
		$user_token = '';
		if (! $force_userid AND isset($_COOKIE["userid"])) { $userid = $_COOKIE["userid"]; }

		$to_sec = time()-(60*60);
/*
		if (! $force_usertoken AND isset($_COOKIE["user_token"])) { $user_token = $_COOKIE["user_token"]; }

		// already logged in, already verified
		if ($U['id']>0 AND $U['id']==$userid) { return true; }

		if ($force_userid) { $userid = $force_userid; }
		if ($force_usertoken) { $user_token = $force_usertoken; }

		// if cookies are not set, set them to a guaranteed time an hour ago
		if (! $userid OR ! $user_token) {
			setcookie('userid','',$to_sec);
			setcookie('user_token','',$to_sec);
			return false;
		}
*/

		$query = "SELECT users.id, users.contactid, contacts.name, user_tokens.user_token, user_tokens.userid ";
		if ($force_userid AND $force_usertoken) { $query .= "FROM contacts, users LEFT JOIN user_tokens ON users.id = user_tokens.userid WHERE "; }
		else { $query .= "FROM contacts, users, user_tokens WHERE users.id = user_tokens.userid AND contacts.id = users.contactid AND "; }
		$query .= "users.id = '".res($userid)."' AND user_token = '".res($user_token)."' ";
		$query .= "AND (user_tokens.expiry IS NULL OR user_tokens.expiry >= '".$GLOBALS['now']."') ";
$query = "SELECT users.id, users.contactid, contacts.name FROM users, contacts WHERE users.id = '".res($userid)."' AND users.contactid = contacts.id; ";
		$result = qdb($query);
		// if user is not registered in db, reset cookies
		if (mysqli_num_rows($result)==0) {
			setcookie('userid','',$to_sec);
			setcookie('user_token','',$to_sec);
			return false;
		} else {
/*
			// after validating admin user, re-query with the super_id
			if ($SUPER_ADMIN AND isset($_REQUEST['super_id']) AND $_REQUEST['super_id']>0) {
				if (isset($_REQUEST['super_id'])) { $super_id = $_REQUEST['super_id']; }

				$r = mysqli_fetch_assoc($result);
				$query = "SELECT *, '".res($user_token)."' user_token ";
				$query .= "FROM users WHERE id = '".res($super_id)."' ";
				$result = qdb($query);
			}
*/

			$U = mysqli_fetch_assoc($result);

			$U['email'] = '';
			$U['phone'] = '';
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
//			$U['name'] = $U['first_name'].' '.$U['last_name'];
//			$U['email'] = $U['login_email'];

			return true;
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

	$ACCESS_TOKEN = false;
	$REFRESH_TOKEN = false;
	if ($U['id']>0) {
		$query = "SELECT access_token, token_type, expires_in, created, refresh_token FROM google_tokens ";
		$query .= "WHERE userid = '".$U['id']."' ORDER BY id DESC; ";
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

	// version control for css and js includes
	$V = '20160612';
?>
