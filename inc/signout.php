<?php
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/mconnect.php';

	function signout($user_token=false) {
		$userid = $_COOKIE["userid"];
		if (! $user_token) { $user_token = $_COOKIE["user_token"]; }

		$test = 0;
		if (isset($GLOBALS['test'])) { $test = $GLOBALS['test']; }

		if (! $test) { session_start(); }

		// delete fb access token, if any
		if ($userid) {
			// get ready for deletion below...
			$del_query = "DELETE FROM user_tokens WHERE userid = '".res($userid)."' ";
			if ($user_token) { $del_query .= "AND user_token = '".res($user_token)."' "; }

			if (! $test) { $del_result = qdb($del_query) OR die(qe($del_query)); }
		}

		// a time in the past
		$to_sec = time()-(60*60);
		if (! $test) {
			setcookie('userid','',$to_sec,'/');
			unset($_COOKIE['userid']);
			setcookie('user_token','',$to_sec,'/');
			unset($_COOKIE['user_token']);
			setcookie('user_remember','',$to_sec,'/');
			unset($_COOKIE['user_remember']);
			setcookie('SUPER_ADMIN','',$to_sec,'/');
			unset($_COOKIE['SUPER_ADMIN']);
			session_unset();
			session_destroy();
		}

		return (false);
	}
?>
