<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

	$saltLog = "10";
	function salt($userid=0,$returnSaltWithLog=true) {
		global $saltLog;

		$salt = "";
		$saltLog = "10";//gets reset to 10 every instance

		if ($userid AND is_numeric($userid) AND $userid>0) {
			$query = "SELECT * FROM salts WHERE userid = '".res($userid)."'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$salt = $r['salt'];
				$saltLog = $r['log'];//leave globally as this for setSalt()
			}
		}

		if (! $salt) { $salt = substr(str_replace('+', '.', uniqid(mt_rand(), true)), 0, 22); }

		$saltWithLog = "$2a$".$saltLog."$".$salt."$";

		if ($userid AND is_numeric($userid) AND $userid>0) {
			setSalt($userid,$salt);
		}

		if ($returnSaltWithLog) {
			return ($saltWithLog);
		} else {
			return ($salt);
		}
	}

	function setSalt($userid,$salt) {
		global $saltLog;

		$query = "REPLACE salts (salt, userid, log) ";
		$query .= "VALUES ('".res($salt)."','".res($userid)."','".res($saltLog)."'); ";
		$result = qdb($query);
	}
?>
