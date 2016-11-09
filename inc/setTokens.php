<?php
//	include_once $_SERVER["ROOT_DIR"].'/inc/setDevice.php';

	if (! isset($EDITOR_ROLE)) { $EDITOR_ROLE = ''; }
	function setTokens($userid=0,$user_token='',$token_expiry=0,$token_type=false,$deviceid=false) {
		global $SUPER_ADMIN,$EDITOR_ROLE;
		if (! $userid OR ! $user_token) { return false; }

		$test = 0;
		if (isset($GLOBALS['test'])) { $test = $GLOBALS['test']; }

/*
		$deviceid = '';
		if (! $deviceid) { $deviceid = setDevice(); }

		if ($deviceid) {
            $query = "SELECT * FROM user_tokens WHERE deviceid = '".res($deviceid)."' ";
            $result = qdb($query);
            if (mysqli_num_rows($result)>0) {
                $query = "UPDATE user_tokens SET deviceid = NULL WHERE deviceid = '".res($deviceid)."' ";
                $result = qdb($query);
            }
		}
*/

        $query = "REPLACE user_tokens (user_token, expiry, userid) ";
		$query .= "VALUES ('".res($user_token)."',";
		if ($token_expiry>0) { $query .= "'".res($token_expiry)."',"; } else { $query .= "NULL,"; }
		$query .= "'".res($userid)."'); ";
		if (! $test) {
			$query_failed = false;
			$result = qdb($query) OR $query_failed = true;
			if ($query_failed===true) { return false; }
			if (! qid()) { return false; }

			setcookie('userid',$userid,$token_expiry,'/');
			setcookie('user_token',$user_token,$token_expiry,'/');
/*
			if ($userid == 1) {
				setcookie('SUPER_ADMIN',1,$token_expiry,'/');
			} else {
				setcookie('SUPER_ADMIN','',time()-3600,'/');
			}
*/
		}

		return true;
	}
?>
