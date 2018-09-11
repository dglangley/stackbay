<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$userid = $GLOBALS['U']['id'];

	function clockout($userid){
		$query = "UPDATE timesheets SET clockout = ".fres($GLOBALS['now'])." WHERE userid = ".res($userid)." AND clockout IS NULL;";
		qdb($query) OR die(qe() . ' ' . $query);
	}

	clockout($userid);

	session_start();
	
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

	$_COOKIE['sb_username'] = '';
	$_COOKIE['sb_password'] = '';
    
	header('Location: /?logged_out=true');
	exit;
?>
